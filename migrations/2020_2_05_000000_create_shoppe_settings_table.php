<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShoppeSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shoppe_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group', 100);
            $table->string('name', 100);
            $table->string('type', 20);
            $table->boolean('bool_value')->nullable();
            $table->string('string_value', 300)->nullable();
            $table->text('text_value')->nullable();
            $table->integer('integer_value')->nullable();
            $table->decimal('decimal_value', 9, 2)->nullable();
            $table->float('float_value', 8, 2)->nullable();
            $table->dateTime('date_value', 0)->nullable();
            $table->json('options')->nullable();
            $table->text('notes')->nullable();
            $table->integer('ordinal')->default(0);
            $table->integer('group_ordinal')->default(0);
			$table->bigInteger('created_by')->nullable();
			$table->bigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
			$table->index('name');
            $table->index('group');
            $table->index('ordinal');
            $table->index('group_ordinal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('shoppe_settings');
    }
}
