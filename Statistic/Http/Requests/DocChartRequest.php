<?php

namespace Modules\Statistic\Http\Requests;

use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Document\Enums\DocumentType;
use Modules\Statistic\Enums\TimeIntervalType;

class DocChartRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'start_date' => [ // от даты
                'required', 'date', 'date_format:d.m.Y',
            ],
            'end_date' => [ // до даты
                'required', 'date', 'date_format:d.m.Y', 'after_or_equal:start_date',
            ],
            'time_interval' => [ // Временой интервал
                'required', new EnumValue(TimeIntervalType::class, false),
            ],
            'employees' => [ // Сотрудники
                'sometimes', 'array',
            ],
            'employees.*' => [ // Сотрудники
                'sometimes', 'distinct', 'exists:users,id',
            ],
            'document_types' => [ //Тип документа
                'sometimes', 'array',
            ],
            'document_types.*' => [ //Тип документа
                'sometimes', 'distinct', new EnumValue(DocumentType::class, false),
            ],
        ];

        return $rules;
    }

    public function attributes()
    {
        return [
            'start_date' => 'Старт даты',
            'end_date' => 'Конец даты',
            'time_interval' => 'Период',
            'employees' => 'Сотрудник',
            'document_types' => 'Тип документа',
        ];
    }
}
