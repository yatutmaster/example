<?php

namespace Modules\Statistic\Entities;

use Illuminate\Database\Eloquent\Model;

class DocAmountWon extends Model
{
    protected $guarded = ['id'];

    protected $table = 'stat_doc_amount_won';
}
