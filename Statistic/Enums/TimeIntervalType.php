<?php

namespace Modules\Statistic\Enums;

use App\Enums\Enum;

final class TimeIntervalType extends Enum
{
    public const DAY = 1; //День
    public const WEEK = 2; //Неделя
    public const MONTH = 3; //Месяц
    public const QUARTER = 4; //Квартал
    public const HALF_A_YEAR = 5; //Полугодие
}
