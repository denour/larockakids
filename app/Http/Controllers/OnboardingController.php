<?php

namespace App\Http\Controllers;

use App\Http\Requests\Onboarding\SearchKidRequest;
use App\Http\Requests\Onboarding\StoreKidRequest;
use App\Http\Requests\Onboarding\UpdateKidRequest;
use App\Models\Contact;
use App\Models\Kid;
use App\Models\OnboardingSession;
use App\Services\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(private readonly OnboardingService $onboarding) {}

    /**
     * Screen 1 — WhatsApp code entry. Generates a session and shows the code to relay.
     */
    /**
     * Idle screen the kiosk rests on between families.
     */
    public function splash(): View
    {
        return view('onboarding.splash');
    }

    public function entry(): View
    {
        $session = $this->onboarding->startSession();

        return view('onboarding.entry', [
            'session' => $session,
            'whatsappNumber' => config('onboarding.whatsapp_display_number'),
        ]);
    }

    /**
     * Polling endpoint used by the kiosk to advance once WhatsApp matches a kid.
     */
    public function status(string $code): JsonResponse
    {
        $session = OnboardingSession::query()->where('code', $code)->latest('id')->first();

        if ($session === null) {
            return response()->json(['status' => 'unknown'], 404);
        }

        return response()->json([
            'status' => $session->status,
            'redirect' => $session->isMatched() && $session->kid_id !== null
                ? route('onboarding.confirm', $session->kid_id)
                : null,
        ]);
    }

    /**
     * Search screen — look up a kid by full name.
     */
    public function search(): View
    {
        return view('onboarding.search');
    }

    public function find(SearchKidRequest $request): RedirectResponse
    {
        $matches = $this->onboarding->searchByName($request->validated('name'));

        if ($matches->count() === 1) {
            return redirect()->route('onboarding.confirm', $matches->first());
        }

        if ($matches->isEmpty()) {
            return redirect()->route('onboarding.register', ['name' => $request->validated('name')]);
        }

        return redirect()->route('onboarding.search')
            ->with('matches', $matches->pluck('full_name', 'id')->all());
    }

    /**
     * Screen 2 — confirm the registered information.
     */
    public function confirm(Kid $kid): View
    {
        $kid->load(['contacts', 'allergies']);

        return view('onboarding.confirm', ['kid' => $kid]);
    }

    /**
     * Registration form for a kid that was not found.
     */
    public function register(Request $request): View
    {
        return view('onboarding.register', [
            'name' => $request->query('name', ''),
            'allergyOptions' => \App\Models\Allergy::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreKidRequest $request): RedirectResponse
    {
        $data = $request->validated();
        [$firstName, $lastName] = $this->splitName($data['name']);

        $kid = Kid::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'grade_level' => $data['grade_level'],
            'classroom' => $data['classroom'] ?? null,
            'school_cycle' => $this->currentCycle(),
            'medical_conditions' => $data['medical_conditions'] ?? null,
            'medications' => $data['medications'] ?? null,
            'sphincter_control' => $data['sphincter_control'] ?? null,
            'nap' => $data['nap'] ?? null,
            'routine_notes' => $data['routine_notes'] ?? null,
            'wants_parents_group' => $request->boolean('wants_parents_group'),
            'notification_channel' => $data['notification_channel'],
        ]);

        $this->syncGuardian($kid, $data);
        $this->syncAllergy($kid, $data['allergy_id'] ?? null);

        return redirect()->route('onboarding.done', $kid);
    }

    public function edit(Kid $kid): View
    {
        $kid->load(['contacts', 'allergies']);

        return view('onboarding.edit', [
            'kid' => $kid,
            'allergyOptions' => \App\Models\Allergy::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateKidRequest $request, Kid $kid): RedirectResponse
    {
        $data = $request->validated();
        [$firstName, $lastName] = $this->splitName($data['name']);

        $kid->update([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $data['birth_date'],
            'gender' => $data['gender'],
            'grade_level' => $data['grade_level'],
            'classroom' => $data['classroom'] ?? null,
            'medical_conditions' => $data['medical_conditions'] ?? null,
            'medications' => $data['medications'] ?? null,
            'sphincter_control' => $data['sphincter_control'] ?? null,
            'nap' => $data['nap'] ?? null,
            'routine_notes' => $data['routine_notes'] ?? null,
            'wants_parents_group' => $request->boolean('wants_parents_group'),
            'notification_channel' => $data['notification_channel'],
        ]);

        $this->syncGuardian($kid, $data);
        $this->syncAllergy($kid, $data['allergy_id'] ?? null);

        return redirect()->route('onboarding.confirm', $kid);
    }

    /**
     * Final screen — success, or graduation notice for the final grade.
     */
    public function done(Kid $kid): View
    {
        if ($kid->isGraduating()) {
            return view('onboarding.graduation', ['kid' => $kid]);
        }

        return view('onboarding.done', ['kid' => $kid]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = array_shift($parts) ?? '';
        $last = implode(' ', $parts);

        return [$first, $last];
    }

    private function currentCycle(): string
    {
        return now()->year.' – '.(now()->year + 1);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncGuardian(Kid $kid, array $data): void
    {
        [$firstName, $lastName] = $this->splitName($data['guardian_name']);

        $contact = $kid->contacts()->first();

        $attributes = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $data['phone'],
            'international_code' => $data['international_code'] ?? '52',
        ];

        if ($contact === null) {
            $contact = Contact::create($attributes);
            $kid->contacts()->attach($contact->id, ['relationship_type' => 'parent']);

            return;
        }

        $contact->update($attributes);
    }

    private function syncAllergy(Kid $kid, ?int $allergyId): void
    {
        if ($allergyId === null) {
            return;
        }

        $kid->allergies()->syncWithoutDetaching([$allergyId]);
    }
}
