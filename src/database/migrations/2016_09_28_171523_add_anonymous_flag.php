<?php

use Illuminate\Database\Schema\Blueprint;
use Code16\Metrics\Tools\Migration;

class AddAnonymousFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->getConnection())->table('metric_visits', function(Blueprint $table) {
            $table->boolean('anonymous')->default(false);
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
            $table->dropColumn('anonymous');
        });
    }
}
