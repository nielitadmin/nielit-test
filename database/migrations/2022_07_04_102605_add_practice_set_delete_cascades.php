<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPracticeSetDeleteCascades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('practice_sessions', function (Blueprint $table) {
            $table->foreign('practice_set_id')->references('id')->on('practice_sets')->onDelete('cascade');
        });

        Schema::table('practice_session_questions', function (Blueprint $table) {
            $table->foreign('practice_session_id')->references('id')->on('practice_sessions')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
