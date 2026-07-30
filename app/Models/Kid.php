<?php

namespace App\Models;

use App\Enums\GradeLevel;
use App\Enums\NapPreference;
use App\Enums\NotificationChannel;
use App\Enums\QrCodeStatus;
use App\Enums\SphincterControl;
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
        'grade_level',
        'classroom',
        'school_cycle',
        'medical_notes',
        'medical_conditions',
        'medications',
        'sphincter_control',
        'nap',
        'routine_notes',
        'wants_parents_group',
        'notification_channel',
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
        'grade_level' => GradeLevel::class,
        'sphincter_control' => SphincterControl::class,
        'nap' => NapPreference::class,
        'notification_channel' => NotificationChannel::class,
        'wants_parents_group' => 'boolean',
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
     * Whether the kid is in the final grade and about to graduate from Piedritas.
     */
    public function isGraduating(): bool
    {
        return $this->grade_level instanceof GradeLevel && $this->grade_level->isFinal();
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
