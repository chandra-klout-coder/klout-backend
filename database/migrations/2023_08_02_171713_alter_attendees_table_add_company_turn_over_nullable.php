<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAttendeesTableAddCompanyTurnOverNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('company_turn_over')->nullable()->change();
            $table->string('employee_size')->nullable()->change();

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
        Schema::table('attendees', function (Blueprint $table) {

            $table->string('company_turn_over')->change();
            $table->string('employee_size')->change();

        });
    }
}
