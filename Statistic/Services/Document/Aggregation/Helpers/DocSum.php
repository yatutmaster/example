<?php

namespace Modules\Statistic\Services\Document\Aggregation\Helpers;

use App\Helpers\Currency;
use Modules\Document\Entities\Document;
use Modules\Document\Enums\Currency as EnumsCurrency;

class DocSum
{
    public static function getSumToRubFromDoc(Document $doc): int
    {
        $doc_sum = self::getSum($doc->json_data);

        if (!$doc_sum) {
            return 0;
        }

        $currency = $doc->json_data->billing->currency ?? 0;

        return self::convertSumToRUB($currency, $doc_sum);
    }

    public static function convertSumToRUB(int|string $doc_curr, int $doc_sum): int
    {
        if (EnumsCurrency::hasValue($doc_curr, false) and $doc_curr != EnumsCurrency::RUB) {
            $doc_sum = Currency::init()->convertToRUB(EnumsCurrency::getKey((int) $doc_curr), $doc_sum);
        }

        return $doc_sum;
    }

    public static function getSum(\stdClass $json_data): int
    {
        $doc_sum = 0;

        if (is_array(($json_data->products ?? 0))) {
            $doc_sum = collect($json_data->products)->sum(function ($item) {
                return $item->cost * $item->amount;
            });
        } elseif (($json_data->billing->price ?? 0) > 0) {
            $doc_sum = (int) $json_data->billing->price;
        }

        return $doc_sum;
    }
}
