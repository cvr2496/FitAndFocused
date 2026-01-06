<?php

namespace App\Console\Commands;

use Database\Seeders\DemoUserSeeder;
use Illuminate\Console\Command;

class SeedDemoUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:seed {--fresh : Delete existing demo data before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with demo user and workout data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Seeding demo user data...');

        if ($this->option('fresh')) {
            $this->warn('⚠️  Fresh flag enabled - will delete existing demo data');
        }

        // Run the DemoUserSeeder
        $seeder = new DemoUserSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('✨ Demo user seeding complete!');
        
        return Command::SUCCESS;
    }
}

