<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterAttendeesTableAddWebsiteNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('attendees', function (Blueprint $table) {
            $table->string('website')->nullable()->change();
            $table->string('linkedin_page_link')->nullable()->change();
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
            $table->string('website')->change();
            $table->string('linkedin_page_link')->change();
        });
    }
}
