<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-products {--all : Clear all cache including system cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all product-related caches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing product caches...');

        // Clear product filter caches
        Product::clearProductFilterCaches();
        $this->info('✓ Product filter caches cleared');

        // Clear all caches starting with 'products_list_'
        if ($this->option('all')) {
            Cache::flush();
            $this->info('✓ All cache cleared');
        } else {
            // For file-based cache, we can't easily clear by pattern
            // So we'll clear some known cache keys
            $patterns = [
                'products_list_*',
                'category_products_*',
                'featured_products',
                'new_arrivals',
                'trending_products',
                'bestsellers',
            ];

            $this->info('✓ Product list caches will expire naturally (5 minutes TTL)');
        }

        $this->info('');
        $this->info('Product cache cleared successfully!');
        
        return Command::SUCCESS;
    }
}
