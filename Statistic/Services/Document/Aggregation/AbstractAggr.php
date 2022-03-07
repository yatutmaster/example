<?php

namespace Modules\Statistic\Services\Document\Aggregation;

use Illuminate\Database\Eloquent\Builder as EBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QBuilder;
use Modules\Statistic\Services\Document\Interfaces\AggregatorInterface;

abstract class AbstractAggr implements AggregatorInterface
{
    private EBuilder|QBuilder $_query;
    private int $_lazyCount = 500;

    public static function init(): self
    {
        return new static();
    }

    public function iterate(): iterable
    {
        foreach ($this->getQuery()->lazy($this->_lazyCount) as $item) {
            yield $this->parseItem($item);
        }
    }

    public function setLazyCount(int $count): self
    {
        $this->_lazyCount = $count;

        return $this;
    }

    protected function getQuery(): EBuilder|QBuilder
    {
        return $this->_query;
    }

    protected function setQuery(EBuilder|QBuilder $query): void
    {
        $this->_query = $query;
    }

    abstract protected function parseItem(Model $item): array;
}
