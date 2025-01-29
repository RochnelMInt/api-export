<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_uid')->nullable();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('avatar')->default("user.png");
            $table->string('question')->nullable();
            $table->string('address')->nullable();
            $table->string('answer')->nullable();
            $table->integer('is_admin')->default(0);
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('about_me')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('gender')->nullable();
            $table->integer('is_activated')->default(0);
            $table->integer('is_super_admin')->default(0);
            $table->enum('status', ['PENDING', 'ACCEPTED', 'REFUSED', 'BANNED'])->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('password')->nullable();
            $table->string('temp_password')->nullable();
            $table->timestamp('banned_until')->nullable();
            $table->integer('count_bad_request')->default(0);
            $table->boolean('is_first_connection')->default(true);
            $table->timestamp('login_well_on')->nullable();
            $table->enum('comment_privacy', ['EVERYBODY', 'MEMBERS', 'ONLYME'])->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('users');
        Schema::enableForeignKeyConstraints();
    }
}
