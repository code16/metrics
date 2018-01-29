<?php

use Illuminate\Database\Schema\Blueprint;
use Code16\Metrics\Tools\Migration;

class CreateVisitsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection($this->getConnection())->create('metric_visits', function(Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ip');
            $table->string('url');
            $table->string('referer')->nullable();
            $table->string('cookie')->nullable();
            $table->string('user_agent');
            $table->text('custom')->nullable();
            $table->text('actions')->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->datetime('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('metric_visits');
    }
}
