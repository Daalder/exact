<?php namespace Daalder\Exact\Commands;

use Illuminate\Console\Command;
use Picqer\Financials\Exact\Account;
use Picqer\Financials\Exact\Connection;
use Pionect\Daalder\Models\Customer\Customer;

class PullCustomerReferences extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'exact:pull-customer-references';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Matches Daalder customers to Exact Accounts using the Daalder customer email addresses and saves the matched Exact Account IDs to the corresponding Daalder customers';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connection = app(Connection::class);
        $totalMatchedCount = 0;

        Customer::query()
            ->chunk(100, function($customers) use ($connection, &$totalMatchedCount) {
                $matchPairs = [];

                foreach($customers as $customer) {
                    try {
                        $account = new Account($connection);
                    } catch (\Exception $e) {
                        $this->error('Failed to connect to Exact API. See error below.');
                        $this->error($e->getMessage());
                    }

                    try {
                        $account = $account->filter("Email eq '$customer->email'");
                    } catch (\Exception $e) {
                        $this->error('Failed to filter for Exact Account with email: '. $customer->email . '. See error below.');
                        $this->error($e->getMessage());
                        continue;
                    }

                    if(count($account) === 0) {
                        $this->error('Failed to find Exact Account with email: '. $customer->email);
                        continue;
                    }

                    if(count($account) > 1) {
                        $this->warning('Found '.count($account).' Exact Accounts with email: '. $customer->email .'. Skipping this one...');
                        continue;
                    }
                    $account = $account[0];

                    $matchPairs[] = [
                        'id' => $customer->id,
                        'exact_id' => $account->ID,
                    ];
                }

                Customer::upsert($matchPairs, ['id'], ['exact_id']);
                $totalMatchedCount += count($matchPairs);
            });

        $this->info('Matched ' . $totalMatchedCount . ' Daalder Customers/Exact Accounts.');
    }
}
