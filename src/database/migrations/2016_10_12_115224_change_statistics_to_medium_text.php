<?php

use Illuminate\Database\Schema\Blueprint;
use Code16\Metrics\Tools\Migration;

class ChangeStatisticsToMediumText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->getConnection())->table('metric_metrics', function(Blueprint $table) {
//            $table->string('statistics', 16777215)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('metric_metrics', function(Blueprint $table) {
//            $table->text('statistics')->change();
        });
    }
}
