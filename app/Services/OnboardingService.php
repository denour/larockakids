<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Kid;
use App\Models\OnboardingSession;

class OnboardingService
{
    /**
     * Number of minutes a generated code stays valid.
     */
    public const CODE_TTL_MINUTES = 15;

    /**
     * Create a fresh onboarding session with a unique 6-digit code.
     */
    public function startSession(): OnboardingSession
    {
        return OnboardingSession::create([
            'code' => $this->generateCode(),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);
    }

    /**
     * Try to match an inbound WhatsApp message (code + sender phone) to a pending
     * session and the kid linked to that phone. Returns the matched session, or null.
     */
    public function matchInboundMessage(string $code, string $phone): ?OnboardingSession
    {
        $code = $this->extractCode($code);

        if ($code === null) {
            return null;
        }

        $session = OnboardingSession::query()
            ->where('code', $code)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($session === null || $session->isExpired()) {
            return null;
        }

        $kid = $this->findKidByPhone($phone);

        if ($kid === null) {
            return null;
        }

        $session->update([
            'status' => 'matched',
            'kid_id' => $kid->id,
            'phone' => $this->normalizePhone($phone),
        ]);

        return $session;
    }

    /**
     * Find the first kid linked to a contact matching the given phone number.
     */
    public function findKidByPhone(string $phone): ?Kid
    {
        $needle = $this->localDigits($phone);

        if ($needle === '') {
            return null;
        }

        $contact = Contact::query()
            ->with('kids')
            ->get()
            ->first(fn (Contact $contact): bool => $this->localDigits($contact->full_phone) === $needle);

        return $contact?->kids->first();
    }

    /**
     * Search kids by full name (case/accent tolerant on the trimmed input).
     *
     * @return \Illuminate\Support\Collection<int, Kid>
     */
    public function searchByName(string $name): \Illuminate\Support\Collection
    {
        $terms = collect(preg_split('/\s+/', trim($name)))->filter();

        if ($terms->isEmpty()) {
            return collect();
        }

        return Kid::query()
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->where(function ($q) use ($term) {
                        $q->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    });
                }
            })
            ->orderBy('first_name')
            ->get();
    }

    private function generateCode(): string
    {
        do {
            $code = (string) random_int(100000, 999999);
        } while (OnboardingSession::query()->where('code', $code)->where('status', 'pending')->exists());

        return $code;
    }

    /**
     * Pull a 6-digit code out of a free-form WhatsApp message body.
     */
    private function extractCode(string $body): ?string
    {
        if (preg_match('/\b(\d{6})\b/', $body, $matches) === 1) {
            return $matches[1];
        }

        $digits = preg_replace('/\D/', '', $body);

        return strlen((string) $digits) === 6 ? $digits : null;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }

    /**
     * Reduce a phone number to its trailing 10 local digits for comparison,
     * absorbing country codes and the Mexican mobile "1" prefix differences.
     */
    private function localDigits(string $phone): string
    {
        $digits = $this->normalizePhone($phone);

        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }
}
