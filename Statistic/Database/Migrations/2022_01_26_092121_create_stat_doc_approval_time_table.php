<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatDocApprovalTimeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stat_doc_approval_time', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('doc_id')->nullable(false)->comment('ID документа');
            $table->unsignedTinyInteger('type')->nullable(false)->comment('Тип документа');
            $table->unsignedInteger('user_id')->nullable(false)->comment('ID согласующего');
            $table->string('department', 100)->nullable(false)->comment('Отдел согласующего');
            $table->string('time_human', 10)->nullable(false)->comment('Отрезок времени в понятном виде 00:00:00');
            $table->unsignedInteger('seconds')->nullable(false)->comment('Отрезок времени в секундах');
            $table->unsignedInteger('sent_activity_id')->nullable(false)->comment('activity_log ID отправки');
            $table->unsignedInteger('response_activity_id')->nullable(false)->comment('activity_log ID ответа');
            $table->timestamp('sent_time')->nullable(false)->comment('Время отправки на согласование');
            $table->timestamp('response_time')->nullable(false)->comment('Время принятия решения');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stat_doc_approval_time');
    }
}
