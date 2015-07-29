<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateNameAltTitleInMediaTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('media', function(Blueprint $table)
		{
      DB::statement('ALTER TABLE `media` MODIFY `name` VARCHAR(255) NULL;');
      DB::statement('ALTER TABLE `media` MODIFY `alt` VARCHAR(255) NULL;');
      DB::statement('ALTER TABLE `media` MODIFY `title` VARCHAR(255) NULL;');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('media', function(Blueprint $table)
		{
      DB::statement('ALTER TABLE `media` MODIFY `name` VARCHAR(255);');
      DB::statement('ALTER TABLE `media` MODIFY `alt` VARCHAR(255);');
      DB::statement('ALTER TABLE `media` MODIFY `title` VARCHAR(255);');
		});
	}

}
