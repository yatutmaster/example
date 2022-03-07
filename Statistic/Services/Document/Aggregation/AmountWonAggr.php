<?php

namespace Modules\Statistic\Services\Document\Aggregation;

use Illuminate\Support\Facades\DB;
use Modules\Activity\Enums\ActivityLogActions;
use Modules\Activity\Enums\ActivityLogNames;
use Modules\Statistic\Services\Document\Aggregation\Helpers\DocSum;
use Modules\Statistic\Services\Document\Interfaces\AggregatorInterface;

class AmountWonAggr extends AbstractAggr
{
    protected function __construct()
    {
        $query = DB::query()
                ->select('documents.type as doc_type', 'activity_log.*')
                ->from('activity_log')
                ->join('documents', 'documents.id', '=', 'activity_log.subject_id')
                ->where('activity_log.description', '=', ActivityLogActions::SET_MARK_WON)
                ->where('activity_log.log_name', '=', ActivityLogNames::DOCUMENT)
                ->orderByRaw('activity_log.created_at, activity_log.id ASC');

        $this->setQuery($query);
    }

    protected function parseItem($item): array
    {
        $collect = DB::query()
                    ->select('al.*')
                    ->from(DB::raw(
                        "activity_log as al, 
                        (select * from activity_log where id = {$item->id}) as al2"))
                    ->where('al.subject_type', DB::raw('al2.subject_type'))
                    ->where('al.subject_id', DB::raw('al2.subject_id'))
                    ->where('al.created_at', '<', DB::raw('al2.created_at'))
                    ->where(function ($query) {
                        $query->where('al.description', ActivityLogActions::UPDATED)
                            ->orwhere('al.description', ActivityLogActions::CREATED);
                    })
                    ->orderBy('al.id', 'desc')
                    ->get();

        $sum = 0;
        $manager_id = 0;
        $currency = 0;

        foreach ($collect as $val) {
            if ($sum and $manager_id and $currency) {
                break;
            }

            $json_data = data_get(json_decode($val->properties), 'attributes.json_data');

            if (!$json_data) {
                continue;
            }

            if (!$sum) {
                $sum = DocSum::getSum($json_data);
            }

            if (!$manager_id) {
                $manager_id = (int) ($json_data->manager_id ?? 0);
            }

            if (!$currency) {
                $currency = (int) ($json_data->billing->currency ?? 0);
            }
        }

        return [
            'doc_id' => $item->subject_id,
            'type' => $item->doc_type,
            'manager_id' => $manager_id,
            'sum_rub' => DocSum::convertSumToRUB($currency, $sum),
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    public function afterDate(string $date): AggregatorInterface
    {
        $this->getQuery()->where('activity_log.created_at', '>', $date);

        return $this;
    }
}
