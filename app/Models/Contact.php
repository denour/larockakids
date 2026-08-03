<?php

namespace App\Models;

use App\Support\PersonName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    /**
     * Normaliza el nombre al guardar, venga de donde venga (panel, import, seeder).
     * Sin esto los nombres se vuelven a ensuciar con cada captura.
     */
    protected function firstName(): Attribute
    {
        return Attribute::set(fn (?string $value) => PersonName::firstName($value));
    }

    protected function lastName(): Attribute
    {
        return Attribute::set(fn (?string $value) => PersonName::lastName($value));
    }

    protected static function booted(): void
    {
        static::saving(function (Contact $contact) {
            // Clean phone: remove +, spaces, dashes
            $phone = preg_replace('/[\+\s\-]/', '', $contact->phone);

            // Remove leading country code if duplicated
            $code = preg_replace('/[\+\s\-]/', '', $contact->international_code);
            if ($code && str_starts_with($phone, $code)) {
                $phone = substr($phone, strlen($code));
            }

            $contact->phone = $phone;
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'international_code',
        'email',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'full_name',
        'full_phone',
    ];

    /**
     * The validation rules for the model.
     *
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'international_code' => 'required|string|max:5',
            'email' => 'nullable|email|max:255',
        ];
    }

    /**
     * Get the full name of the contact.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Clean phone number: remove +, spaces, dashes, and any leading country code
     * that matches the international_code to avoid duplication.
     */
    public function getCleanPhoneAttribute(): string
    {
        // Remove +, spaces, dashes
        $phone = preg_replace('/[\+\s\-]/', '', $this->phone);

        // Remove leading country code if it's duplicated
        $code = preg_replace('/[\+\s\-]/', '', $this->international_code);
        if ($code && str_starts_with($phone, $code)) {
            $phone = substr($phone, strlen($code));
        }

        return $phone;
    }

    /**
     * Get the full phone number with international code.
     */
    public function getFullPhoneAttribute(): string
    {
        return "+{$this->international_code}{$this->clean_phone}";
    }

    /**
     * The kids that belong to the contact.
     */
    public function kids(): BelongsToMany
    {
        return $this->belongsToMany(Kid::class)
            ->withPivot('relationship_type')
            ->withTimestamps();
    }
}
