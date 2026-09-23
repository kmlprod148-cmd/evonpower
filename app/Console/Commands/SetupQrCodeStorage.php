<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SetupQrCodeStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:setup-qrcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up QR code storage directory structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up QR code storage...');

        try {
            // 1. Ensure storage link exists
            $this->info('1. Checking storage link...');
            $storageLink = public_path('storage');
            $storageTarget = storage_path('app/public');

            if (!is_link($storageLink) && !is_dir($storageLink)) {
                $this->info('   Creating storage link...');
                try {
                    symlink($storageTarget, $storageLink);
                    $this->info('   ✅ Storage link created');
                } catch (\Exception $e) {
                    $this->warn('   ⚠️  Could not create symlink (Windows permission issue)');
                    $this->info('   📝 Run: php artisan storage:link');
                }
            } else {
                $this->info('   ✅ Storage link exists');
            }

            // 2. Ensure qrcodes directory exists
            $this->info('2. Checking qrcodes directory...');
            $qrcodesPath = storage_path('app/public/qrcodes');
            
            if (!is_dir($qrcodesPath)) {
                $this->info('   Creating qrcodes directory...');
                mkdir($qrcodesPath, 0755, true);
                $this->info('   ✅ QR codes directory created');
            } else {
                $this->info('   ✅ QR codes directory exists');
            }

            // 3. Check public qrcodes directory
            $this->info('3. Checking public qrcodes directory...');
            $publicQrcodesPath = public_path('storage/qrcodes');
            
            if (!is_dir($publicQrcodesPath)) {
                $this->info('   Creating public qrcodes directory...');
                mkdir($publicQrcodesPath, 0755, true);
                $this->info('   ✅ Public QR codes directory created');
            } else {
                $this->info('   ✅ Public QR codes directory exists');
            }

            // 4. Test storage access
            $this->info('4. Testing storage access...');
            $publicDisk = Storage::disk('public');
            
            if ($publicDisk->exists('qrcodes')) {
                $this->info('   ✅ Storage disk can access qrcodes directory');
                
                $files = $publicDisk->files('qrcodes');
                $this->info("   📄 Found " . count($files) . " QR code files");
                
                foreach ($files as $file) {
                    $this->info("     - " . basename($file));
                }
            } else {
                $this->error('   ❌ Storage disk cannot access qrcodes directory');
            }

            // 5. Set proper permissions
            $this->info('5. Setting permissions...');
            chmod($qrcodesPath, 0755);
            chmod($publicQrcodesPath, 0755);
            $this->info('   ✅ Permissions set');

            $this->info('QR code storage setup completed successfully!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error setting up QR code storage: ' . $e->getMessage());
            Log::error('QR code storage setup failed: ' . $e->getMessage());
            
            return Command::FAILURE;
        }
    }
}
