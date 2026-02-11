<?php

namespace App\Console\Commands;

use App\Services\GeoNodeProxyService;
use Illuminate\Console\Command;

class UpdateProxiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cyper:proxies:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force update and validation of proxies from GeoNode';

    /**
     * Execute the console command.
     */
    public function handle(GeoNodeProxyService $service): int
    {
        $this->info('Starting proxy update via GeoNode...');
        $this->newLine();

        try {
            $proxies = $service->updateProxies();
            
            $count = count($proxies);
            
            if ($count > 0) {
                $this->info("✓ Successfully updated cache with {$count} validated proxies.");
                return self::SUCCESS;
            } else {
                $this->warn('✗ No valid proxies found or update failed.');
                return self::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
