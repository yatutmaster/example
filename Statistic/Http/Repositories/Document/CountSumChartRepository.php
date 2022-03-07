<?php

namespace Modules\Statistic\Http\Repositories\Document;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Statistic\Entities\DocAmountCreated;
use Modules\Statistic\Entities\DocAmountWon;
use Modules\Statistic\Enums\DocumentChartType;

class CountSumChartRepository extends MainChartRepository
{
    protected function __construct(protected string $chart_type, protected array $validated)
    {
    }

    protected function getStatData(string $startDate, string $endDate, ?array $employees, ?array $documentTypes): Collection
    {
        $entity = $this->getEntityClass();

        $query = $entity::select(DB::raw('count(doc_id) as count_docs, sum(sum_rub) as sum_rub , Date(created_at) date_created'))
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->groupByRaw('Date(created_at)');

        if (!empty($employees)) {
            $query->where(function ($query) use ($employees) {
                foreach ($employees as $item) {
                    $query->orWhere('manager_id', '=', $item);
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

        return $query->get()->mapWithKeys(fn ($item, $key) => [$item['date_created'] => $item]);
    }

    protected function getFromDay(string $category, string $dateKey, Collection $statData): array
    {
        $sumRub = 0;
        $countDocs = 0;

        if ($dateKey = $statData->get($dateKey)) {
            $sumRub = $dateKey->sum_rub;
            $countDocs = $dateKey->count_docs;
        }

        return $this->wrapResult($category, $sumRub, $countDocs);
    }

    protected function getBetweenDays(string $category, string $fromKey, string $toKey, Collection $statData): array
    {
        $sumRub = 0;
        $countDocs = 0;

        $statData->whereBetween('date_created', [$fromKey, $toKey])->each(function ($item) use (&$sumRub, &$countDocs) {
            $sumRub += $item->sum_rub;
            $countDocs += $item->count_docs;
        });

        return $this->wrapResult($category, $sumRub, $countDocs);
    }

    protected function wrapResult(string $category, int $sumRub, int $countDocs): array
    {
        return [
            'category' => $category,
            'sum_rub' => $sumRub,
            'count_docs' => $countDocs,
        ];
    }

    protected function getEntityClass(): string
    {
        $chart_type = $this->chart_type;

        return match ($chart_type) {
            DocumentChartType::AMOUNT_OF_CREATED,
            DocumentChartType::SUM_OF_CREATED => DocAmountCreated::class,
            DocumentChartType::SUM_OF_WON,
            DocumentChartType::AMOUNT_OF_WON => DocAmountWon::class,
            default => abort("$chart_type is incompatible in this class ".__CLASS__),
        };
    }
}
