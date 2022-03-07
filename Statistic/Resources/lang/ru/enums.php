<?php

use Modules\Statistic\Enums\DocumentChartType;
use Modules\Statistic\Enums\TimeIntervalType;

return [
    DocumentChartType::class => [
        DocumentChartType::AMOUNT_OF_CREATED => 'Динамика количества созданных документов',
        DocumentChartType::AMOUNT_OF_WON => 'Динамика количества выигранных документов',
        DocumentChartType::SUM_OF_CREATED => 'Динамика суммы созданных документов',
        DocumentChartType::SUM_OF_WON => 'Динамика суммы выигранных документов',
        DocumentChartType::AVERAGE_APPROVAL_TIME => 'Среднее время согласования документов',
    ],
    TimeIntervalType::class => [
        TimeIntervalType::DAY => 'День',
        TimeIntervalType::WEEK => 'Неделя',
        TimeIntervalType::MONTH => 'Месяц',
        TimeIntervalType::QUARTER => 'Квартал',
        TimeIntervalType::HALF_A_YEAR => 'Полугодие',
    ],
];
