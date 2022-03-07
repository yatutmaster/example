<?php

namespace Modules\Statistic\Enums;

use App\Enums\Enum;

final class DocumentChartType extends Enum
{
    public const AMOUNT_OF_CREATED = 'amount-of-created'; //Динамика количества созданных документов
    public const AMOUNT_OF_WON = 'amount-of-won'; //Динамика количества выигранных документов
    public const SUM_OF_CREATED = 'sum-of-created'; //Динамика суммы созданных документов
    public const SUM_OF_WON = 'sum-of-won'; //Динамика суммы выигранных документов
    public const AVERAGE_APPROVAL_TIME = 'average-approval-time'; //Среднее время согласования документов
}
