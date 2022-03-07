<?php

namespace Modules\Statistic\Entities;

use Illuminate\Database\Eloquent\Model;

class DocAverageApprovalTime extends Model
{
    protected $guarded = ['id'];

    protected $table = 'stat_doc_approval_time';
}
