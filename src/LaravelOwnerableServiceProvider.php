<?php

namespace PeltonSolutions\LaravelOwnerable;

use Illuminate\Support\ServiceProvider;

class LaravelOwnerableServiceProvider extends ServiceProvider
{
	public function register(): void
	{

	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
				__DIR__.'/../config/config.php' => config_path('ownerable.php'),
		]);
	}
}