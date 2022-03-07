<?php

namespace Modules\Statistic\Services\Document\Sync;

use Modules\Statistic\Entities\DocAmountWon;
use Modules\Statistic\Services\Document\Aggregation\AmountWonAggr;

class AmountWonSync extends AbstractSync
{
    protected static function initAggregator(): AmountWonAggr
    {
        return AmountWonAggr::init();
    }

    public function refresh(): void
    {
        DocAmountWon::truncate();

        $this->continue();
    }

    public function continue(): void
    {
        $last_create = DocAmountWon::select('created_at')
                        ->orderBy('created_at', 'DESC')
                        ->limit(1)
                        ->first();

        if ($last_create) {
            $this->getAggregator()->afterDate($last_create->created_at);
        }

        $items = [];

        foreach ($this->getAggregator()->iterate() as $item) {
            $items[] = $item;

            $this->outputCmdInfo("Record processed - doc_id={$item['doc_id']} created={$item['created_at']}");

            if (count($items) > 500) {
                DocAmountWon::insert($items);
                $items = [];
            }
        }

        if (!empty($items)) {
            DocAmountWon::insert($items);
        }
    }
}
