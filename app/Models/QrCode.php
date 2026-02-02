<?php

namespace App\Models;

use App\Enums\QrCodeStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class QrCode extends Model
{
    /** @use HasFactory<\Database\Factories\QrCodeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'kid_id',
        'qr_image_path',
        'status',
        'assigned_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QrCodeStatus::class,
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * The kid that this QR code is assigned to.
     */
    public function kid(): BelongsTo
    {
        return $this->belongsTo(Kid::class);
    }

    /**
     * Check if the QR code is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this->status === QrCodeStatus::Available;
    }

    /**
     * Check if the QR code is assigned to a kid.
     */
    public function isAssigned(): bool
    {
        return $this->status === QrCodeStatus::Assigned;
    }

    /**
     * Check if the QR code is marked as lost.
     */
    public function isLost(): bool
    {
        return $this->status === QrCodeStatus::Lost;
    }

    /**
     * Assign this QR code to a kid.
     */
    public function assignToKid(Kid $kid): void
    {
        $this->update([
            'kid_id' => $kid->id,
            'status' => QrCodeStatus::Assigned,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Mark this QR code as lost and unassign from kid.
     */
    public function markAsLost(): void
    {
        $this->update([
            'kid_id' => null,
            'status' => QrCodeStatus::Lost,
            'assigned_at' => null,
        ]);
    }

    /**
     * Unassign this QR code from its current kid.
     */
    public function unassign(): void
    {
        $this->update([
            'kid_id' => null,
            'status' => QrCodeStatus::Available,
            'assigned_at' => null,
        ]);
    }

    /**
     * Get the QR image URL (with temporary URL for S3/R2).
     */
    protected function qrImageUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (! $this->qr_image_path) {
                    return null;
                }

                $disk = Storage::disk(config('filesystems.default'));

                if (method_exists($disk, 'temporaryUrl')) {
                    try {
                        return $disk->temporaryUrl($this->qr_image_path, now()->addHour());
                    } catch (\Exception) {
                        // Fall back to regular URL if temporaryUrl fails
                    }
                }

                return $disk->url($this->qr_image_path);
            }
        );
    }
}
