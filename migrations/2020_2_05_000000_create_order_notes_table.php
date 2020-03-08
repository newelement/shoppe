<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('order_id');
            $table->boolean('public')->default(0);
            $table->text('notes');
			$table->bigInteger('created_by')->nullable();
			$table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
			$table->index('order_id');
            $table->index('public');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('order_notes');
    }
}
