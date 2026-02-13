<?php

namespace App\Console\Commands;

use App\Enums\QrCodeStatus;
use App\Models\Kid;
use App\Models\QrCode;
use App\Services\QrCodeService;
use Illuminate\Console\Command;

class GenerateTopAttendersQrCodes extends Command
{
    protected $signature = 'kids:generate-qr 
                            {--count=30 : Number of top attenders to generate QR codes for}
                            {--force : Regenerate QR codes even if they already exist}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Generate QR codes for the top N kids with the most attendances';

    public function __construct(
        protected QrCodeService $qrCodeService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info("🔍 Finding top {$count} kids by attendance...");

        // Get kids ordered by attendance count using subquery for MariaDB compatibility
        $topKids = Kid::query()
            ->withCount('attendances')
            ->orderByDesc('attendances_count')
            ->limit($count)
            ->get();

        if ($topKids->isEmpty()) {
            $this->warn('No kids found in the database.');

            return Command::SUCCESS;
        }

        $this->info("📋 Found {$topKids->count()} kids to process:\n");

        // Display table with kids info
        $tableData = $topKids->map(fn ($kid) => [
            'ID' => $kid->id,
            'Name' => $kid->full_name,
            'Attendances' => $kid->attendances_count,
            'Has QR' => $kid->qrCode ? '✅ '.$kid->qrCode->code : '❌',
        ])->toArray();

        $this->table(['ID', 'Name', 'Attendances', 'Has QR'], $tableData);

        if ($dryRun) {
            $this->info("\n🔍 Dry run mode - no changes made.");

            return Command::SUCCESS;
        }

        $generated = 0;
        $skipped = 0;
        $regenerated = 0;

        $this->newLine();
        $this->info('🏭 Processing QR codes...');
        $progressBar = $this->output->createProgressBar($topKids->count());
        $progressBar->start();

        foreach ($topKids as $kid) {
            $existingQr = $kid->qrCode;

            if ($existingQr && ! $force) {
                $skipped++;
                $progressBar->advance();

                continue;
            }

            if ($existingQr && $force) {
                // Regenerate existing QR image
                $this->qrCodeService->regenerateImage($existingQr);
                $regenerated++;
            } else {
                // Check if there's an available QR code to assign
                $availableQr = QrCode::where('status', QrCodeStatus::Available)->first();

                if ($availableQr) {
                    $availableQr->assignToKid($kid);
                } else {
                    // Generate a new QR code
                    $newQrCodes = $this->qrCodeService->generateBatch(1);
                    $newQr = $newQrCodes->first();
                    $newQr->assignToKid($kid);
                }
                $generated++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✅ Done!');
        $this->table(['Action', 'Count'], [
            ['Generated/Assigned', $generated],
            ['Regenerated (--force)', $regenerated],
            ['Skipped (already had QR)', $skipped],
        ]);

        return Command::SUCCESS;
    }
}
