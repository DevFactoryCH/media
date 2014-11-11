<?php namespace Devfactory\Media;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

  /**
   * Boot
   *
   * @return void
   */
	public function boot() {
		$this->package('devfactory/media', 'media', __DIR__);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
		$this->app['media'] = $this->app->share(function($app) {
      return new Media;
    });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return array('media');
	}

}
