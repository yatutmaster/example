<?php

namespace Modules\Statistic\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;
use Modules\Statistic\Enums\DocumentChartType;
use Modules\Statistic\Http\Repositories\Document\MainChartRepository;
use Modules\Statistic\Http\Requests\DocChartRequest;

class StatisticController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function documentChart($doc_chart_type, DocChartRequest $request)
    {
        abort_unless(DocumentChartType::hasValue($doc_chart_type), 404, 'Document chart not found');

        return MainChartRepository::find($doc_chart_type, $request->validated());
    }
}
