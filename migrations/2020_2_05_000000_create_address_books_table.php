<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
			$table->string('address_type', 100);
			$table->string('name', 300)->nullable();
            $table->string('company_name', 300)->nullable();
            $table->string('address', 400)->nullable();
            $table->string('address2', 400)->nullable();
            $table->string('city', 300)->nullable();
            $table->string('state', 3)->nullable();
            $table->string('zipcode', 30)->nullable();
            $table->string('country', 4)->nullable();
            $table->tinyInteger('default')->default(0);
			$table->bigInteger('created_by')->nullable();
			$table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('address_type');
			$table->index('user_id');
            $table->index('default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('address_books');
    }
}
