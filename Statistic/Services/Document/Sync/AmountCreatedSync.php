<?php

namespace Modules\Statistic\Services\Document\Sync;

use Modules\Statistic\Entities\DocAmountCreated;
use Modules\Statistic\Services\Document\Aggregation\AmountCreatedAggr;

class AmountCreatedSync extends AbstractSync
{
    protected static function initAggregator(): AmountCreatedAggr
    {
        return AmountCreatedAggr::init();
    }

    public function refresh(): void
    {
        DocAmountCreated::truncate();

        $this->continue();
    }

    public function continue(): void
    {
        $last_update = DocAmountCreated::select('updated_at')
                        ->orderBy('updated_at', 'DESC')
                        ->limit(1)
                        ->first();

        if ($last_update) {
            $this->getAggregator()->afterDate($last_update->updated_at);
        }

        $buff = [];

        foreach ($this->getAggregator()->iterate() as $item) {
            $buff[] = $item;

            $this->outputCmdInfo("Record processed - doc_id={$item['doc_id']}");

            if (count($buff) > 500) {
                $this->upsert($buff);
                $buff = [];
            }
        }

        if (!empty($buff)) {
            $this->upsert($buff);
        }
    }

    protected function upsert(array $items): void
    {
        DocAmountCreated::upsert($items, ['doc_id'], ['type', 'manager_id', 'sum_rub', 'updated_at']);
    }
}
