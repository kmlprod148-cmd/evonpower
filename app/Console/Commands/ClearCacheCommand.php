<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear application cache (alias for cache:clear)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing application cache...');
        
        try {
            // Run the actual cache:clear command
            Artisan::call('cache:clear');
            
            $this->info('✅ Application cache cleared successfully!');
            
            // Show helpful information
            $this->line('');
            $this->comment('💡 Available cache clearing commands:');
            $this->line('   • php artisan cache:clear     - Clear application cache');
            $this->line('   • php artisan config:clear     - Clear configuration cache');
            $this->line('   • php artisan route:clear      - Clear route cache');
            $this->line('   • php artisan view:clear       - Clear view cache');
            $this->line('   • php artisan event:clear      - Clear event cache');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to clear application cache: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
