<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMediaTable extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('media', function(Blueprint $table)
      {
        $table->increments('id');
        $table->string('mediable_type');
        $table->integer('mediable_id')->unsigned();
        $table->string('filename');
        $table->string('mime');
        $table->bigInteger('size')->unsigned()->nullable()->default(NULL);
        $table->string('name')->nullable();
        $table->string('alt')->nullable();
        $table->string('title')->nullable();
        $table->string('group');
        $table->boolean('status');
        $table->integer('weight')->unsigned()->default(0);
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
    Schema::drop('media');
  }

}
