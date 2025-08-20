<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalculateCommissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:calculate {product_id} {user_type} {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate commission for a product based on user type and amount';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $productId = $this->argument('product_id');
        $userType = $this->argument('user_type');
        $amount = (float) $this->argument('amount');

        try {
            $product = \App\Models\Product::findOrFail($productId);
            $commission = $product->calculateCommissionFor($userType, $amount);
            
            $this->info("Product: {$product->name}");
            $this->info("User Type: {$userType}");
            $this->info("Amount: Rp " . number_format($amount, 0, ',', '.'));
            $this->info("Commission: Rp " . number_format($commission, 0, ',', '.'));
            
            // Show commission details
            if ($product->commission) {
                $commissionInfo = $product->commission->getCommissionInfo($userType);
                $this->line("Commission Rate: {$commissionInfo['rate']} ({$commissionInfo['type']})");
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
