<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatDocAmountWonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_doc_amount_won', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('doc_id')->nullable(false)->comment('ID документа');
            $table->unsignedTinyInteger('type')->nullable(false)->comment('Тип документа');
            $table->unsignedInteger('manager_id')->nullable(false)->comment('ID менеджера документа');
            $table->unsignedInteger('sum_rub')->nullable(false)->comment('Сумма в рублях документа');
            $table->timestamp('created_at')->nullable(false)->comment('Время создания');
            $table->timestamp('updated_at')->nullable(false)->comment('Время обновления');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_doc_amount_won');
    }
}
