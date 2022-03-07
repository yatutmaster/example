<?php

namespace Modules\Statistic\Services\Helpers;

//Класс помогает разбить интервал дат на кварталы
class QuarterDates
{
    public function __construct(protected string $startDate, protected string $endDate)
    {
    }

    //если $round = true округляет кварталы, иначе отсекает (начало и конец) дат, которые не охватываю польностью кварталы
    public function getQuarters(bool $round = false): array
    {
        $quarters = [];

        $startTmp = strtotime($this->startDate);
        $endTmp = strtotime($this->endDate);

        $startMonth = date('m', $startTmp);
        $startYear = date('Y', $startTmp);

        $endMonth = date('m', $endTmp);
        $endYear = date('Y', $endTmp);

        if ($round) {
            $startQuarter = ceil($startMonth / 3);
            $endQuarter = ceil($endMonth / 3);
        } else {
            $startQuarter = $startMonth / 3;
            $endQuarter = $endMonth / 3;

            if (is_float($startQuarter)) {
                $startQuarter = ceil($startQuarter) + 1;
            }

            if (is_float($endQuarter)) {
                $endQuarter = floor($endQuarter);
            }
        }

        for ($y = $startYear; $y <= $endYear; ++$y) {
            $maxQtr = ($y == $endYear) ? $endQuarter : 4;

            for ($q = $startQuarter; $q <= $maxQtr; ++$q) {
                $quarters[] = $this->wrapQuarter($q, $y);
            }

            $startQuarter = 1;
        }

        return $quarters;
    }

    protected function wrapQuarter($quarter, $year): \stdClass
    {
        $currentQuarter = new \stdClass();

        $endMonthNum = $this->zeroPad($quarter * 3);
        $startMonthNum = $this->zeroPad($endMonthNum - 2);

        $currentQuarter->quarter = $quarter;
        $currentQuarter->period_start = "$year-$startMonthNum-01"; // yyyy-mm-dd
        $currentQuarter->period_end = "$year-$endMonthNum-".$this->monthEndDate($year, $endMonthNum);

        return $currentQuarter;
    }

    protected function monthEndDate($year, $monthNumber): string
    {
        return date('t', strtotime("$year-$monthNumber"));
    }

    protected function zeroPad(int $number): string
    {
        return ($number < 10) ? "0$number" : "$number";
    }
}
