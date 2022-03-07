<?php

namespace Modules\Statistic\Services\Document\Interfaces;

use Illuminate\Console\Command;

interface SyncInterface
{
    public static function init(?Command $cmd = null): SyncInterface;

    public function refresh(): void;

    public function continue(): void;
}
