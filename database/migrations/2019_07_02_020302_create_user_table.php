<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username');
            //default to user, please.
            $table->bigInteger('role_id')->unsigned()->default(2);
            $table->boolean('active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->string('password');
            $table->unique("username");
            $table->unique("email");
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('role_id')->references('id')->on('user_role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
}
