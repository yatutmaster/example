<?php

namespace Modules\Statistic\Services\Document\Aggregation\Helpers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Closure;

class TimePeriod
{
    private ?Closure $_excClosure = null;
    private ?Closure $_workTimeClosure = null;
    private int $_workingHourFrom = 9; //начало рабочего времени
    private int $_workingHourTo = 18; //конец рабочего времени
    private int $_hours = 0;
    private int $_minutes = 0;
    private int $_seconds = 0;

    public function __construct()
    {
    }

    public function getHours(): int
    {
        return $this->_hours;
    }

    public function getMinutes(): int
    {
        return $this->_minutes;
    }

    public function getSeconds(): int
    {
        return $this->_seconds;
    }

    public function getAllSeconds(): int
    {
        $seconds = $this->_seconds;
        $minutes = $this->_minutes;
        $hours = $this->_hours;

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function getHumanTime(): string
    {
        $addZero = function (int $val) {
            return $val < 10 ? "0$val" : $val;
        };

        $seconds = $addZero($this->_seconds);
        $minutes = $addZero($this->_minutes);
        $hours = $addZero($this->_hours);

        return "{$hours}:{$minutes}:{$seconds}";
    }

    public function setWorkingHours(int $from, int $to): void
    {
        $this->_workingHourFrom = $from;
        $this->_workingHourTo = $to;
    }

    public function setExceptClosure(Closure $callback): void
    {
        $this->_excClosure = $callback;
    }

    public function setWorkTimeClosure(Closure $callback): void
    {
        $this->_workTimeClosure = $callback;
    }

    public function calculate(Carbon $from_date, Carbon $to_date): self
    {
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        $first_minut = '00:00:00';
        $last_minut = '00:00:00';

        $period = CarbonPeriod::create($from_date->format('Y-m-d H:00:00'), '1 hour', $to_date->format('Y-m-d H:00:00'));

        $from_day = $period->getStartDate()->format('d');

        $from_tail = $from_date->format('00:i:s');
        $to_tail = $to_date->format('00:i:s');

        $loop = 0;

        foreach ($period as $key => $date) {
            if ($this->_runExceptClosure($date)) {
                continue;
            }

            $this->_runWorkTimeClosure($date);

            if ($date->format('G') < $this->_workingHourFrom or $date->format('G') > $this->_workingHourTo) {
                continue; //не раб время пропускаем
            }

            if ($date->format('d') != $from_day) {//если переходим на след день, обновляем счетчик
                $from_day = $date->format('d');
                $loop = 0;
            }

            if ($loop > 0) {
                ++$hours;
            }

            if (!$key and $date->format('G') < $this->_workingHourTo) {//берем первый остаток если они входят в раб время
                $first_minut = $from_tail;
            }

            if ($date->format('Y-m-d H') === $period->getEndDate()->format('Y-m-d H')) {//берем последний остаток если они входят в раб время
                $last_minut = $to_tail;
            }

            ++$loop;
        }

        $date = new Carbon($first_minut);

        $diff = $date->diffInSeconds($last_minut, false);

        if ($diff < 0) {
            $seconds = $diff + 3600;
            --$hours;
        } else {
            $seconds = $diff;
        }

        $minutes = floor($seconds / 60);

        $seconds = floor($seconds % 60);

        $this->_seconds = $seconds;
        $this->_minutes = $minutes;
        $this->_hours = $hours;

        return $this;
    }

    private function _runExceptClosure(Carbon $time): bool
    {
        if ($this->_excClosure) {
            $callback = $this->_excClosure;

            return $callback($time);
        }

        return false;
    }

    private function _runWorkTimeClosure(Carbon $time): void
    {
        if ($this->_workTimeClosure) {
            $callback = $this->_workTimeClosure;

            $callback($time, $this->_workingHourFrom, $this->_workingHourTo);
        }
    }
}
