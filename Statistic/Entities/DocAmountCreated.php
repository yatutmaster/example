<?php

namespace Modules\Statistic\Entities;

use Illuminate\Database\Eloquent\Model;

class DocAmountCreated extends Model
{
    protected $guarded = ['id'];

    protected $table = 'stat_doc_amount_created';
}
