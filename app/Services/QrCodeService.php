<?php

namespace App\Services;

use App\Enums\QrCodeStatus;
use App\Models\QrCode;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    protected string $prefix;

    protected string $storagePath;

    protected string $disk;

    public function __construct()
    {
        $this->prefix = config('qrcode.prefix', 'LRK');
        $this->storagePath = 'qr-codes';
        $this->disk = config('filesystems.default', 'local');
    }

    /**
     * Generate a batch of QR codes.
     *
     * @return Collection<int, QrCode>
     */
    public function generateBatch(int $count, ?string $prefix = null): Collection
    {
        $prefix = $prefix ?? $this->prefix;
        $qrCodes = collect();

        $startNumber = $this->getNextSequenceNumber($prefix);

        for ($i = 0; $i < $count; $i++) {
            $code = $this->formatCode($prefix, $startNumber + $i);
            $qrCode = $this->createQrCode($code);
            $qrCodes->push($qrCode);
        }

        return $qrCodes;
    }

    /**
     * Create a single QR code.
     */
    public function createQrCode(string $code): QrCode
    {
        $imagePath = $this->generateQrImage($code);

        return QrCode::create([
            'code' => $code,
            'qr_image_path' => $imagePath,
            'status' => QrCodeStatus::Available,
        ]);
    }

    /**
     * Generate the QR code image and save to storage.
     */
    public function generateQrImage(string $code): string
    {
        $storage = Storage::disk($this->disk);
        $storage->makeDirectory($this->storagePath);

        $renderer = new ImageRenderer(
            new RendererStyle(300, 2),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($code);

        $filename = "{$code}.svg";
        $path = "{$this->storagePath}/{$filename}";

        $storage->put($path, $svg, 'public');

        return $path;
    }

    /**
     * Regenerate the QR image for an existing QR code.
     */
    public function regenerateImage(QrCode $qrCode): QrCode
    {
        $imagePath = $this->generateQrImage($qrCode->code);

        $qrCode->update(['qr_image_path' => $imagePath]);

        return $qrCode->fresh();
    }

    /**
     * Get the next sequence number for a given prefix.
     */
    protected function getNextSequenceNumber(string $prefix): int
    {
        $position = strlen($prefix) + 2;

        $query = QrCode::query()
            ->where('code', 'like', "{$prefix}-%");

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $query->orderByRaw("CAST(SUBSTRING(code, {$position}) AS INTEGER) DESC");
        } else {
            $query->orderByRaw("CAST(SUBSTRING(code, {$position}) AS UNSIGNED) DESC");
        }

        $lastCode = $query->value('code');

        if (! $lastCode) {
            return 1;
        }

        $lastNumber = (int) substr($lastCode, strlen($prefix) + 1);

        return $lastNumber + 1;
    }

    /**
     * Format a code with prefix and padded number.
     */
    protected function formatCode(string $prefix, int $number): string
    {
        return sprintf('%s-%04d', $prefix, $number);
    }

    /**
     * Get the public URL for a QR code image.
     */
    public function getImageUrl(QrCode $qrCode): ?string
    {
        if (! $qrCode->qr_image_path) {
            return null;
        }

        return Storage::disk($this->disk)->url($qrCode->qr_image_path);
    }

    /**
     * Delete the QR image file from storage.
     */
    public function deleteImage(QrCode $qrCode): void
    {
        if ($qrCode->qr_image_path) {
            Storage::disk($this->disk)->delete($qrCode->qr_image_path);
        }
    }
}
