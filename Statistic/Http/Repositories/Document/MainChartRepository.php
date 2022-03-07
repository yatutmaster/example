<?php

namespace Modules\Statistic\Http\Repositories\Document;

use App\Helpers\Format;
use Illuminate\Support\Collection;
use Modules\Statistic\Enums\DocumentChartType;
use Modules\Statistic\Enums\TimeIntervalType;
use Modules\Statistic\Services\Helpers\QuarterDates;

abstract class MainChartRepository
{
    protected function __construct(protected array $validated)
    {
    }

    public static function find(string $chart_type, array $validated)
    {
        $repository = match ($chart_type) {
            DocumentChartType::AMOUNT_OF_CREATED,
            DocumentChartType::SUM_OF_CREATED,
            DocumentChartType::SUM_OF_WON,
            DocumentChartType::AMOUNT_OF_WON => (new CountSumChartRepository($chart_type, $validated)),
            DocumentChartType::AVERAGE_APPROVAL_TIME => (new AvgApprovalTimeChartRepository($validated)),
            default => abort("Doc chart $chart_type is not ready"),
        };

        return $repository->getResult();
    }

    abstract protected function getStatData(string $startDate, string $endDate, ?array $employees, ?array $documentTypes): Collection;

    abstract protected function getFromDay(string $category, string $dateKey, Collection $statData): array;

    abstract protected function getBetweenDays(string $category, string $fromKey, string $toKey, Collection $statData): array;

    protected function getResult(): array
    {
        $startDate = Format::strToDate('Y-m-d', $this->validated['start_date']);
        $endDate = Format::strToDate('Y-m-d', $this->validated['end_date']);
        $timeInterval = (int) $this->validated['time_interval'];
        $employees = $this->validated['employees'] ?? null;
        $documentTypes = $this->validated['document_types'] ?? null;

        $bdData = $this->getStatData($startDate, $endDate, $employees, $documentTypes);

        return match ($timeInterval) {
            TimeIntervalType::DAY => $this->parseDays($startDate, $endDate, $bdData),
            TimeIntervalType::WEEK => $this->parseWeeks($startDate, $endDate, $bdData),
            TimeIntervalType::MONTH => $this->parseMonths($startDate, $endDate, $bdData),
            TimeIntervalType::QUARTER => $this->parseQuarters($startDate, $endDate, $bdData),
            TimeIntervalType::HALF_A_YEAR => $this->parseHalfYears($startDate, $endDate, $bdData),
        };
    }

    //на дни
    protected function parseDays(string $startDate, string $endDate, Collection $statData): array
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        $buff = [];

        while ($start <= $end) {
            $dateKey = date('Y-m-d', $start);
            $dateCat = date('d.m.Y l', $start);

            $buff[] = $this->getFromDay($dateCat, $dateKey, $statData);

            $start = strtotime('+1 day', $start);
        }

        return $buff;
    }

    //на недели
    protected function parseWeeks(string $startDate, string $endDate, Collection $statData): array
    {
        $startMonday = strtotime('monday this week', strtotime($startDate));
        $startSunday = strtotime('sunday this week', $startMonday);
        $end = strtotime('sunday this week', strtotime($endDate));

        $buff = [];

        while ($startSunday <= $end) {
            $fromKey = date('Y-m-d', $startMonday);
            $toKey = date('Y-m-d', $startSunday);
            $fromDate = date('W - (d.m.Y', $startMonday);
            $toDate = date('d.m.Y)', $startSunday);

            $buff[] = $this->getBetweenDays("$fromDate - $toDate", $fromKey, $toKey, $statData);

            $startMonday = strtotime('+1 week', $startMonday);
            $startSunday = strtotime('+1 week', $startSunday);
        }

        return $buff;
    }

    //на месяцы
    protected function parseMonths(string $startDate, string $endDate, Collection $statData): array
    {
        $startFirstDayMonth = strtotime(substr_replace($startDate, '01', -2));
        $startLastDayMonth = strtotime('last day of this month', $startFirstDayMonth);
        $endLastDayMonth = strtotime('last day of this month', strtotime($endDate));

        $buff = [];

        while ($startLastDayMonth <= $endLastDayMonth) {
            $fromKey = date('Y-m-d', $startFirstDayMonth);
            $toKey = date('Y-m-d', $startLastDayMonth);
            $fromDate = date('F - (d.m.Y', $startFirstDayMonth);
            $toDate = date('d.m.Y)', $startLastDayMonth);

            $buff[] = $this->getBetweenDays("$fromDate - $toDate", $fromKey, $toKey, $statData);

            $startFirstDayMonth = strtotime('+1 month', $startFirstDayMonth);
            $startLastDayMonth = strtotime('last day of this month', $startFirstDayMonth);
        }

        return $buff;
    }

    //на кварталы
    protected function parseQuarters(string $startDate, string $endDate, Collection $statData): array
    {
        $quarters = new QuarterDates($startDate, $endDate);

        $buff = [];

        foreach ($quarters->getQuarters(true) as $quarter) {
            $category = $quarter->quarter.' ('.date('F d.m.Y', strtotime($quarter->period_start)).' - '.date('F d.m.Y', strtotime($quarter->period_end)).')';

            $buff[] = $this->getBetweenDays($category, $quarter->period_start, $quarter->period_end, $statData);
        }

        return $buff;
    }

    //на полугодии
    protected function parseHalfYears(string $startDate, string $endDate, Collection $statData): array
    {
        $startYear = substr($startDate, 0, 4);
        $endYear = substr($endDate, 0, 4);

        $loopHalf = ceil(substr($startDate, 5, 2) / 6);

        if ($loopHalf == 1) {
            $startYearHalf = "$startYear-01-01";
            $endYearHalf = "$startYear-06-30";
        } else {
            $startYearHalf = "$startYear-07-01";
            $endYearHalf = "$startYear-12-31";
        }

        if (ceil(substr($endDate, 5, 2) / 6) == 1) {
            $endHalf = "$endYear-06-30";
        } else {
            $endHalf = "$endYear-12-31";
        }

        $buff = [];

        while ($endYearHalf <= $endHalf) {
            $fromDate = date('d.m.Y', strtotime($startYearHalf));
            $toDate = date('d.m.Y', strtotime($endYearHalf));

            $buff[] = $this->getBetweenDays("$loopHalf ($fromDate - $toDate)", $startYearHalf, $endYearHalf, $statData);

            if ($loopHalf == 1) {
                $startYearHalf = "$startYear-07-01";
                $endYearHalf = "$startYear-12-31";
                $loopHalf = 2;
            } else {
                ++$startYear;
                $startYearHalf = "$startYear-01-01";
                $endYearHalf = "$startYear-06-30";
                $loopHalf = 1;
            }
        }

        return $buff;
    }
}
