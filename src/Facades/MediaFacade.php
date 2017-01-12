<?php namespace Devfactory\Media\Facades;

use Illuminate\Support\Facades\Facade;

class MediaFacade extends Facade {

  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor() { return 'media'; }

}