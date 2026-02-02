<?php

namespace App\Models;

use App\Enums\QrCodeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kid extends Model
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
        'birth_date',
        'gender',
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
