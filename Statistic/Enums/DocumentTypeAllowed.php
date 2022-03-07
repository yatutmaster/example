<?php

namespace Modules\Statistic\Enums;

use App\Enums\Enum;
use Modules\Document\Enums\DocumentType;
use Modules\Document\Enums\Traits\AllowedDocumentTypeTrait;

//допустимые типы документа
final class DocumentTypeAllowed extends Enum
{
    use AllowedDocumentTypeTrait;

    public const ALLOWED = [
        DocumentType::Contract,
        DocumentType::Bill,
        DocumentType::Bill_contract,
        DocumentType::Contract_leasing,
        DocumentType::Contract_gos,
        DocumentType::Contract_clinic,
        DocumentType::Old_contract,
        DocumentType::Old_bill,
        DocumentType::Old_bill_contract,
        DocumentType::Old_contract_leasing,
        DocumentType::Old_contract_gos,
        DocumentType::Old_contract_clinic,
    ];

    public static function getAllowed(): array
    {
        return self::ALLOWED;
    }
}
