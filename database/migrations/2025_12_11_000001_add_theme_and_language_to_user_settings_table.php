<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::table('user_settings', function (Blueprint $table) {
			$table->string('theme')->default('light')->after('remember_me');
			$table->string('language', 10)->default('en')->after('theme');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('user_settings', function (Blueprint $table) {
			$table->dropColumn(['theme', 'language']);
		});
	}
};
