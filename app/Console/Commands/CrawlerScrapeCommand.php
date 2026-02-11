<?php

namespace App\Console\Commands;

use App\Services\CrawlerService;
use Illuminate\Console\Command;

class CrawlerScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cyper:crawler:scrape
                            {--competitor= : Scrape only specific competitor by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape competitor prices from websites';

    /**
     * Execute the console command.
     */
    public function handle(CrawlerService $service): int
    {
        $this->warn('⚠️  Rate limit: 1 request per minute');
        $this->newLine();

        $this->info('Starting web scraping...');
        $this->newLine();

        if ($competitorId = $this->option('competitor')) {
            return $this->scrapeSingleCompetitor($service, $competitorId);
        }

        return $this->scrapeAllCompetitors($service);
    }

    /**
     * Scrape single competitor
     *
     * @param CrawlerService $service
     * @param int $competitorId
     * @return int
     */
    private function scrapeSingleCompetitor(CrawlerService $service, int $competitorId): int
    {
        $competitor = \App\Models\Competitor::find($competitorId);

        if (!$competitor) {
            $this->error("Competitor with ID {$competitorId} not found");
            return self::FAILURE;
        }

        $this->info("Scraping: {$competitor->name}");

        try {
            $count = $service->scrapeCompetitor($competitor, function ($status, $data) {
                switch ($status) {
                    case 'scraping':
                        $this->line("<comment>Scraping: {$data}</comment>");
                        break;
                    case 'generated':
                        $this->info("   ✓ Found: {$data}");
                        break;
                    case 'error':
                        $this->error("   ✗ Error: {$data}");
                        break;
                    case 'wait':
                        $this->line("   ... Sleeping {$data}s");
                        break;
                }
            });
            $this->info("✓ Scraped {$count} products from {$competitor->name}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to scrape {$competitor->name}: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Scrape all active competitors
     *
     * @param CrawlerService $service
     * @return int
     */
    private function scrapeAllCompetitors(CrawlerService $service): int
    {
        $results = $service->scrapeAll();

        $this->displayResults($results);

        return self::SUCCESS;
    }

    /**
     * Display scraping results
     *
     * @param array $results
     * @return void
     */
    private function displayResults(array $results): void
    {
        $tableData = [];

        foreach ($results as $competitorName => $result) {
            $tableData[] = [
                'competitor' => $competitorName,
                'status' => $result['success'] ? '✓ Success' : '✗ Failed',
                'products' => $result['success'] ? $result['count'] : '-',
                'error' => $result['success'] ? '' : $result['error'],
            ];
        }

        $this->table(
            ['Competitor', 'Status', 'Products', 'Error'],
            $tableData
        );
    }
}
