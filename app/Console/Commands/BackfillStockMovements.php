<?php

namespace App\Console\Commands;

use App\Models\CustomerSupplyReceipt;
use App\Models\ItemPurchaseSetting;
use App\Models\StockMovement;
use App\Models\UsageLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStockMovements extends Command
{
    /**
     * Command name.
     */
    protected $signature = 'stock:backfill-movements';

    /**
     * Command description.
     */
    protected $description =
    'Backfill opening stock, customer supply receipts, and usage logs into stock movements';


    public function handle()
    {
        $this->info('Starting stock movement backfill...');

        $openingCreated = 0;
        $customerSupplyCreated = 0;
        $usageCreated = 0;

        $openingSkipped = 0;
        $customerSupplySkipped = 0;
        $usageSkipped = 0;


        DB::transaction(function () use (
            &$openingCreated,
            &$customerSupplyCreated,
            &$usageCreated,
            &$openingSkipped,
            &$customerSupplySkipped,
            &$usageSkipped
        ) {

            /*
            |--------------------------------------------------------------------------
            | 1. Opening Stock
            |--------------------------------------------------------------------------
            */

            $settingsList = ItemPurchaseSetting::with('item')->get();

            foreach ($settingsList as $settings) {

                $openingStock = (float) $settings->opening_stock;

                /*
                |--------------------------------------------------------------------------
                | Skip zero opening stock
                |--------------------------------------------------------------------------
                */

                if ($openingStock <= 0) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Opening Stock Date
                |--------------------------------------------------------------------------
                |
                | Prefer Planning Start Date.
                |
                | If it does not exist, use settings created date.
                |
                */

                if ($settings->planning_start_date) {

                    $movementDate = $settings->planning_start_date;
                } elseif ($settings->created_at) {

                    $movementDate = $settings->created_at->toDateString();
                } else {

                    $movementDate = now()->toDateString();
                }


                /*
                |--------------------------------------------------------------------------
                | Prevent Duplicate Opening Stock
                |--------------------------------------------------------------------------
                */

                $exists = StockMovement::where(
                    'source_type',
                    'item_purchase_setting'
                )
                    ->where(
                        'source_id',
                        $settings->id
                    )
                    ->exists();


                if ($exists) {

                    $openingSkipped++;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Create Opening Stock Movement
                |--------------------------------------------------------------------------
                */

                StockMovement::create([

                    'item_id' => $settings->item_id,

                    'movement_date' => $movementDate,

                    'movement_type' => 'opening_stock',

                    'direction' => 'in',

                    'quantity' => $openingStock,

                    'source_type' => 'item_purchase_setting',

                    'source_id' => $settings->id,

                    'reference' =>
                    'OPENING-'
                        . ($settings->item->item_code ?? $settings->item_id),

                    'remarks' =>
                    'Opening stock imported from Item Purchase Settings.',

                ]);

                $openingCreated++;
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Customer Supply Receipts
            |--------------------------------------------------------------------------
            */

            CustomerSupplyReceipt::orderBy('id')
                ->chunkById(500, function ($receipts) use (
                    &$customerSupplyCreated,
                    &$customerSupplySkipped
                ) {

                    foreach ($receipts as $receipt) {

                        /*
                        |--------------------------------------------------------------------------
                        | Prevent Duplicate
                        |--------------------------------------------------------------------------
                        */

                        $exists = StockMovement::where(
                            'source_type',
                            'customer_supply_receipt'
                        )
                            ->where(
                                'source_id',
                                $receipt->id
                            )
                            ->exists();


                        if ($exists) {

                            $customerSupplySkipped++;

                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Create Stock IN
                        |--------------------------------------------------------------------------
                        */

                        StockMovement::create([

                            'item_id' => $receipt->item_id,

                            'movement_date' => $receipt->receipt_date,

                            'movement_type' => 'customer_supply',

                            'direction' => 'in',

                            'quantity' => $receipt->quantity,

                            'source_type' => 'customer_supply_receipt',

                            'source_id' => $receipt->id,

                            'reference' => $receipt->reference,

                            'remarks' => $receipt->remarks,

                        ]);

                        $customerSupplyCreated++;
                    }
                });


            /*
            |--------------------------------------------------------------------------
            | 3. Usage Logs
            |--------------------------------------------------------------------------
            */

            UsageLog::orderBy('id')
                ->chunkById(500, function ($usageLogs) use (
                    &$usageCreated,
                    &$usageSkipped
                ) {

                    foreach ($usageLogs as $usageLog) {

                        /*
                        |--------------------------------------------------------------------------
                        | Prevent Duplicate
                        |--------------------------------------------------------------------------
                        */

                        $exists = StockMovement::where(
                            'source_type',
                            'usage_log'
                        )
                            ->where(
                                'source_id',
                                $usageLog->id
                            )
                            ->exists();


                        if ($exists) {

                            $usageSkipped++;

                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Create Stock OUT
                        |--------------------------------------------------------------------------
                        */

                        StockMovement::create([

                            'item_id' => $usageLog->item_id,

                            'movement_date' => $usageLog->usage_date,

                            'movement_type' => 'usage',

                            'direction' => 'out',

                            'quantity' => $usageLog->quantity,

                            'source_type' => 'usage_log',

                            'source_id' => $usageLog->id,

                            'reference' => $usageLog->reference,

                            'remarks' => $usageLog->remarks,

                        ]);

                        $usageCreated++;
                    }
                });
        });


        /*
        |--------------------------------------------------------------------------
        | Result
        |--------------------------------------------------------------------------
        */

        $this->newLine();

        $this->info('Stock movement backfill completed successfully.');

        $this->newLine();

        $this->table(
            [
                'Source',
                'Created',
                'Skipped Existing',
            ],
            [
                [
                    'Opening Stock',
                    $openingCreated,
                    $openingSkipped,
                ],

                [
                    'Customer Supply',
                    $customerSupplyCreated,
                    $customerSupplySkipped,
                ],

                [
                    'Usage Logs',
                    $usageCreated,
                    $usageSkipped,
                ],
            ]
        );

        return Command::SUCCESS;
    }
}
