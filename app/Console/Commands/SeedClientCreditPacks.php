<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\ClientCreditPackSeeder;

class SeedClientCreditPacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credit-packs:seed-client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed 10 client credit packs between 50 and 5000 EUR';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Création de 10 packs de crédit clients (50-5000 EUR)...');
        
        $seeder = new ClientCreditPackSeeder();
        // Laravel définit automatiquement $this->command dans le seeder quand appelé via call()
        $this->call(ClientCreditPackSeeder::class);
        
        $this->info('✅ 10 packs de crédit clients créés avec succès !');
        
        return Command::SUCCESS;
    }
}

