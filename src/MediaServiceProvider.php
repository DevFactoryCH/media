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
    $this->publishConfig();
    $this->publishMigration();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register() {
    $this->mergeConfig();
	}

  /**
   * Get the services provided by the provider.
   *
   * @return array
   */
  public function provides() {
    return ['media'];
  }


  /**
   * Publish the package configuration
   */
  protected function publishConfig() {
    $this->publishes([
      __DIR__ . '/config/config.php' => config_path('media.config.php'),
    ]);
  }

  /**
   * Publish the migration stub
   */
  protected function publishMigration() {
    $this->publishes([
      __DIR__ . '/migrations' => base_path('database/migrations')
    ]);
  }

  /**
   * Merge media config with users.
   */
  private function mergeConfig() {
    $this->mergeConfigFrom(
      __DIR__ . '/config/config.php', 'media.config'
    );
  }

}
