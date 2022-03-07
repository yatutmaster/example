<?php

namespace Modules\Statistic\Services\Document\Sync;

use Illuminate\Console\Command;
use Modules\Statistic\Services\Document\Interfaces\AggregatorInterface;
use Modules\Statistic\Services\Document\Interfaces\SyncInterface;

abstract class AbstractSync implements SyncInterface
{
    private int $_cmd_index = 1;

    protected function __construct(private AggregatorInterface $_aggregator, private ?Command $_cmd)
    {
    }

    public static function init(?Command $cmd = null): SyncInterface
    {
        $aggregator = static::initAggregator();

        return new static($aggregator, $cmd);
    }

    protected function getAggregator(): AggregatorInterface
    {
        return $this->_aggregator;
    }

    protected function getCommand(): ?Command
    {
        return $this->_cmd;
    }

    protected function outputCmdInfo(string $row_info): void
    {
        if ($cmd = $this->getCommand()) {
            $cmd->info("№ {$this->_cmd_index} - $row_info");

            ++$this->_cmd_index;
        }
    }

    abstract protected static function initAggregator(): AggregatorInterface;
}
