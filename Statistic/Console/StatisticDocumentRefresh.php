<?php

namespace Modules\Statistic\Console;

use Illuminate\Console\Command;
use Modules\Statistic\Enums\DocumentChartType;
use Modules\Statistic\Services\Document\Sync\AmountCreatedSync;
use Modules\Statistic\Services\Document\Sync\AmountWonSync;
use Modules\Statistic\Services\Document\Sync\AverageApprovalTimeSync;

class StatisticDocumentRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistic:document-refresh {type=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh document statistics, clear and sync';

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
            DocumentChartType::SUM_OF_CREATED => $this->amountCreatedRefresh(),
            DocumentChartType::AMOUNT_OF_WON,
            DocumentChartType::SUM_OF_WON => $this->amountWonRefresh(),
            DocumentChartType::AVERAGE_APPROVAL_TIME => $this->AvgApprovalTimeRefresh(),
            'all' => $this->runAll(),
            default => $this->error("Argument = $arg not found. allowed = ".(implode(', ', DocumentChartType::getValues())))
        };

        $this->info('Done refresh');
    }

    protected function runAll(): void
    {
        $this->amountCreatedRefresh();
        $this->amountWonRefresh();
        $this->AvgApprovalTimeRefresh();
    }

    protected function amountCreatedRefresh(): void
    {
        $this->info('Start amountCreatedRefresh');
        AmountCreatedSync::init($this)->refresh();
    }

    protected function amountWonRefresh(): void
    {
        $this->info('Start amountWonRefresh');
        AmountWonSync::init($this)->refresh();
    }

    protected function AvgApprovalTimeRefresh(): void
    {
        $this->info('Start AvgApprovalTimeRefresh');
        AverageApprovalTimeSync::init($this)->refresh();
    }
}
