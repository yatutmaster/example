<?php

namespace Modules\Statistic\Services\Document\Sync;

use Modules\Statistic\Entities\DocAverageApprovalTime;
use Modules\Statistic\Services\Document\Aggregation\AverageApprovalTimeAggr;

class AverageApprovalTimeSync extends AbstractSync
{
    protected static function initAggregator(): AverageApprovalTimeAggr
    {
        return AverageApprovalTimeAggr::init();
    }

    public function refresh(): void
    {
        DocAverageApprovalTime::truncate();

        $this->continue();
    }

    public function continue(): void
    {
        $last_resp = DocAverageApprovalTime::select('response_time')
                        ->orderBy('response_time', 'DESC')
                        ->limit(1)
                        ->first();

        if ($last_resp) {
            $this->getAggregator()->afterDate($last_resp->response_time);
        }

        $items = [];

        foreach ($this->getAggregator()->iterate() as $item) {
            if (empty($item)) {
                continue;
            }

            $items[] = $item;

            $this->outputCmdInfo("Record processed - doc_id={$item['doc_id']} department={$item['department']}");

            if (count($items) > 500) {
                DocAverageApprovalTime::insert($items);
                $items = [];
            }
        }

        if (!empty($items)) {
            DocAverageApprovalTime::insert($items);
        }
    }
}
