<?php

namespace App\Console\Commands;

use App\Services\Recurring\RecurringTransactionScheduler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kasku:proses-transaksi-rutin')]
#[Description('Catat transaksi rutin yang jatuh tempo sampai hari ini (Asia/Jakarta), termasuk yang terlewat')]
class ProsesTransaksiRutin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RecurringTransactionScheduler $scheduler): int
    {
        $today = RecurringTransactionScheduler::today();
        $result = $scheduler->processDue($today);

        $this->info("{$result['transactions']} transaksi rutin dicatat dari {$result['rules']} aturan (sampai {$today->toDateString()}).");

        return self::SUCCESS;
    }
}
