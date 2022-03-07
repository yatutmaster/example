<?php

namespace Modules\Statistic\Console;

use Illuminate\Console\Command;
use Modules\Statistic\Enums\DocumentChartType;
use Modules\Statistic\Services\Document\Sync\AmountCreatedSync;
use Modules\Statistic\Services\Document\Sync\AmountWonSync;
use Modules\Statistic\Services\Document\Sync\AverageApprovalTimeSync;

class StatisticDocumentSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistic:document-sync {type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize document statistics';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $arg = $this->argument('type');

        match ($arg) {
            DocumentChartType::AMOUNT_OF_CREATED,
            DocumentChartType::SUM_OF_CREATED => $this->amountCreatedSync(),
            DocumentChartType::AMOUNT_OF_WON,
            DocumentChartType::SUM_OF_WON => $this->amountWonSync(),
            DocumentChartType::AVERAGE_APPROVAL_TIME => $this->averageApprovalTimeSync(),
            'all' => $this->runAll(),
            default => $this->error("Argument = $arg not found. allowed = ".(implode(', ', DocumentChartType::getValues())))
        };

        $this->info('Done sync');
    }

    protected function runAll(): void
    {
        $this->amountCreatedSync();
        $this->amountWonSync();
        $this->averageApprovalTimeSync();
        //else sync
    }

    protected function amountCreatedSync(): void
    {
        $this->info('Start AmountCreatedSync');
        AmountCreatedSync::init($this)->continue();
    }

    protected function amountWonSync(): void
    {
        $this->info('Start AmountWonSync');
        AmountWonSync::init($this)->continue();
    }

    protected function averageApprovalTimeSync(): void
    {
        $this->info('Start averageApprovalTimeSync');
        AverageApprovalTimeSync::init($this)->continue();
    }
}
