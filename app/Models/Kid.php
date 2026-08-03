<?php

namespace App\Models;

use App\Enums\QrCodeStatus;
use App\Support\PersonName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kid extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'medical_notes',
        'is_active',
    ];

    protected $default = [
        'gender' => 'male',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'gender' => 'string',
    ];

    protected $appends = [
        'full_name',
        'age',
    ];

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

    /**
     * Get the kid's age in years.
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }

    /**
     * The contacts that belong to the kid.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)
            ->withPivot('relationship_type')
            ->withTimestamps();
    }

    public function allergies()
    {
        return $this->belongsToMany(Allergy::class)
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the currently assigned QR code for this kid.
     */
    public function qrCode(): HasOne
    {
        return $this->hasOne(QrCode::class)->where('status', QrCodeStatus::Assigned);
    }

    /**
     * Get all attendances for this kid.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
