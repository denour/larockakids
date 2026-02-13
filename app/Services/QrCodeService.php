<?php

namespace App\Services;

use App\Enums\QrCodeStatus;
use App\Models\QrCode;
use App\Models\Setting;
use chillerlan\QRCode\QRCode as QRGenerator;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Common\EccLevel;
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

        // QR code options with high error correction for logo
        $options = new QROptions([
            'version'      => 5,
            'eccLevel'     => EccLevel::H,
        ]);

        $qrGenerator = new QRGenerator($options);
        
        // Generate SVG with circular dots and logo
        $svg = $this->generateCircularSvg($qrGenerator, $code);

        $filename = "{$code}.svg";
        $path = "{$this->storagePath}/{$filename}";

        $storage->put($path, $svg, 'public');

        return $path;
    }

    /**
     * Generate SVG with circular modules and embedded logo
     */
    protected function generateCircularSvg(QRGenerator $qrGenerator, string $code): string
    {
        // Add data segment first, then get matrix
        $qrGenerator->addByteSegment($code);
        $matrix = $qrGenerator->getQRMatrix();
        $moduleCount = $matrix->getSize();
        $scale = 10;
        $size = $moduleCount * $scale;
        $logoSize = $size * 0.28; // Logo takes 28% of QR
        
        // Start SVG
        $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
     viewBox="0 0 {$size} {$size}" width="{$size}" height="{$size}">
  <rect width="100%" height="100%" fill="white"/>
SVG;

        // Draw modules as circles
        $darkColor = '#161d6a'; // Piedritas brand color
        $circleRadius = $scale * 0.42;
        
        // Logo exclusion zone (center area)
        $logoZoneStart = ($moduleCount / 2) - ($moduleCount * 0.18);
        $logoZoneEnd = ($moduleCount / 2) + ($moduleCount * 0.18);
        
        for ($y = 0; $y < $moduleCount; $y++) {
            for ($x = 0; $x < $moduleCount; $x++) {
                // Skip logo area
                if ($x > $logoZoneStart && $x < $logoZoneEnd && 
                    $y > $logoZoneStart && $y < $logoZoneEnd) {
                    continue;
                }
                
                // Get module value and check if dark
                $moduleValue = $matrix->get($x, $y);
                if ($matrix->isDark($moduleValue)) {
                    $cx = ($x * $scale) + ($scale / 2);
                    $cy = ($y * $scale) + ($scale / 2);
                    
                    // Finder patterns stay as rounded squares
                    if ($matrix->checkTypeIn($x, $y, [
                        QRMatrix::M_FINDER_DARK,
                        QRMatrix::M_FINDER_DOT,
                    ])) {
                        $rectX = $x * $scale;
                        $rectY = $y * $scale;
                        $svg .= "  <rect x=\"{$rectX}\" y=\"{$rectY}\" width=\"{$scale}\" height=\"{$scale}\" rx=\"2\" fill=\"{$darkColor}\"/>\n";
                    } else {
                        $svg .= "  <circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$circleRadius}\" fill=\"{$darkColor}\"/>\n";
                    }
                }
            }
        }

        // Add logo in center with circular mask
        $logoData = $this->getLogoBase64();
        if ($logoData) {
            $logoCenterX = $size / 2;
            $logoCenterY = $size / 2;
            $logoDisplaySize = $logoSize * 0.85;
            
            // White circle background for logo
            $svg .= "  <circle cx=\"{$logoCenterX}\" cy=\"{$logoCenterY}\" r=\"" . ($logoSize / 2 + 3) . "\" fill=\"white\"/>\n";
            $svg .= "  <circle cx=\"{$logoCenterX}\" cy=\"{$logoCenterY}\" r=\"" . ($logoSize / 2) . "\" fill=\"white\" stroke=\"{$darkColor}\" stroke-width=\"3\"/>\n";
            
            // Logo image (rectangular, centered)
            $logoX = $logoCenterX - ($logoDisplaySize / 2);
            $logoY = $logoCenterY - ($logoDisplaySize / 4);
            $svg .= "  <image x=\"{$logoX}\" y=\"{$logoY}\" width=\"{$logoDisplaySize}\" height=\"" . ($logoDisplaySize / 2) . "\" xlink:href=\"data:image/png;base64,{$logoData}\" preserveAspectRatio=\"xMidYMid meet\"/>\n";
        }

        $svg .= "</svg>";

        return $svg;
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

    /**
     * Get logo as base64 string for embedding in QR SVG.
     * Tries S3 first (from Settings), falls back to local logo.png
     */
    protected function getLogoBase64(): ?string
    {
        // Try to get logo from Settings (S3)
        $logo = Setting::get('site_logo');
        
        if ($logo && Storage::disk('s3')->exists($logo)) {
            try {
                $contents = Storage::disk('s3')->get($logo);
                return base64_encode($contents);
            } catch (\Exception $e) {
                // Fall through to local fallback
            }
        }
        
        // Fallback to local logo.png
        $localPath = public_path('logo.png');
        if (file_exists($localPath)) {
            return base64_encode(file_get_contents($localPath));
        }
        
        return null;
    }
}
