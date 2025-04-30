<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

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
     * Get the full phone number with international code.
     */
    public function getFullPhoneAttribute(): string
    {
        return "+{$this->international_code}{$this->phone}";
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
