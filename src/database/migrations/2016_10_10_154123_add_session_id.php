<?php

use Illuminate\Database\Schema\Blueprint;
use Code16\Metrics\Tools\Migration;

class AddSessionId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->getConnection())->table('metric_visits', function(Blueprint $table) {
            $table->string('session_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('metric_visits', function(Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
}
