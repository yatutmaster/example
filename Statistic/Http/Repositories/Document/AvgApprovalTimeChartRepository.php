<?php

namespace Modules\Statistic\Http\Repositories\Document;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Statistic\Entities\DocAverageApprovalTime;

class AvgApprovalTimeChartRepository extends MainChartRepository
{
    private array $_departments = [];

    protected function getStatData(string $startDate, string $endDate, ?array $employees, ?array $documentTypes): Collection
    {
        $this->_departments = DocAverageApprovalTime::select(DB::raw('distinct(department)'))
                ->get()->mapWithKeys(fn ($item, $key) => [
                    $item['department'] => [
                    'avg_approve_time' => '00:00:00',
                    'avg_seconds' => 0,
                    'count_docs' => 0,
                    'count_approve' => 0,
                    'seconds' => 0,
                    ], ])->toArray();

        $query = DocAverageApprovalTime::select(
            DB::raw('count(id) as count_approve, COUNT(DISTINCT(doc_id)) as count_docs, sum(seconds) as seconds , department, Date(response_time) date_key'))
            ->where('response_time', '>=', $startDate)
            ->where('response_time', '<=', $endDate)
            ->groupByRaw('Date(response_time) , department');

        if (!empty($employees)) {
            $query->where(function ($query) use ($employees) {
                foreach ($employees as $item) {
                    $query->orWhere('user_id', '=', $item);
                }
            });
        }

        if (!empty($documentTypes)) {
            $query->where(function ($query) use ($documentTypes) {
                foreach ($documentTypes as $item) {
                    $query->orWhere('type', '=', $item);
                }
            });
        }

        return $query->get();
    }

    protected function getFromDay(string $category, string $dateKey, Collection $statData): array
    {
        $result = $this->_departments;

        $statData->where('date_key', $dateKey)->each(function ($item) use (&$result) {
            $result[$item['department']]['seconds'] = (int) $item['seconds'];
            $result[$item['department']]['count_docs'] = $item['count_docs'];
            $result[$item['department']]['count_approve'] = $item['count_approve'];
        });

        return $this->wrapResult($result, $category);
    }

    protected function getBetweenDays(string $category, string $fromKey, string $toKey, Collection $statData): array
    {
        $result = $this->_departments;

        $statData->whereBetween('date_key', [$fromKey, $toKey])->each(function ($item) use (&$result) {
            $result[$item['department']]['seconds'] += (int) $item['seconds'];
            $result[$item['department']]['count_docs'] += $item['count_docs'];
            $result[$item['department']]['count_approve'] += $item['count_approve'];
        });

        return $this->wrapResult($result, $category);
    }

    protected function wrapResult(array $result, string $category): array
    {
        foreach ($result as $department => $data) {
            $seconds = $data['seconds'];

            if (!$seconds) {
                continue;
            }

            $count_approve = $data['count_approve'];

            $avg_sec = round($seconds / $count_approve);

            $result[$department]['avg_seconds'] = $avg_sec;
            $result[$department]['avg_approve_time'] = $this->getToHuman($avg_sec);
        }

        $result['category'] = $category;

        return $result;
    }

    protected function getToHuman(int $seconds): string
    {
        $minutes = 0;
        $hours = 0;

        if ($seconds >= 3600) {//get hours
            $hours = floor($seconds / 3600);
            $seconds = $seconds - $hours * 3600;
        }

        if ($seconds >= 60) {//get minutes
            $minutes = floor($seconds / 60);
            $seconds = $seconds - $minutes * 60;
        }

        $addZero = function (int $val) {
            return $val < 10 ? "0$val" : $val;
        };

        $seconds = $addZero($seconds);
        $minutes = $addZero($minutes);
        $hours = $addZero($hours);

        return "{$hours}:{$minutes}:{$seconds}";
    }
}
