<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('email', 150)->unique()->index();
            $table->text('password');
            $table->string('company_name', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->tinyInteger('status')->default('1');
            $table->tinyInteger('on_boarding_status')->default('1');
            $table->string('role')->default('2');
            $table->tinyInteger('first_login')->default('1');
            $table->tinyInteger('deactivation_reason_code')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('users');
    }

}
