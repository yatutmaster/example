<?php

namespace Modules\Statistic\Services\Document\Interfaces;

interface AggregatorInterface
{
    public static function init(): AggregatorInterface;

    public function afterDate(string $date): AggregatorInterface;

    public function iterate(): iterable;
}
