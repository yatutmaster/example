<?php

namespace Modules\Statistic\Services\Document\Aggregation;

use Modules\Document\Entities\Document;
use Modules\Document\Enums\DocumentType;
use Modules\Statistic\Services\Document\Aggregation\Helpers\DocSum;
use Modules\Statistic\Services\Document\Interfaces\AggregatorInterface;

class AmountCreatedAggr extends AbstractAggr
{
    protected function __construct()
    {
        $query = Document::orderByRaw('created_at, id ASC')
        ->where('type', '<>', DocumentType::Agreement)//исключаем доп соглашения
        ->where('type', '<>', DocumentType::Pre_doc); //исключаем предварительный договор

        $this->setQuery($query);
    }

    protected function parseItem($doc): array
    {
        return [
            'doc_id' => $doc->id,
            'type' => $doc->type,
            'manager_id' => (int) $doc->json_data->manager_id ?? 0,
            'sum_rub' => DocSum::getSumToRubFromDoc($doc),
            'created_at' => $doc->created_at,
            'updated_at' => $doc->updated_at,
        ];
    }

    public function afterDate(string $date): AggregatorInterface
    {
        $this->getQuery()->where('updated_at', '>', $date);

        return $this;
    }
}
