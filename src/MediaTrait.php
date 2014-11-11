<?php namespace Devfactory\Media;

trait MediaTrait {

  /**
	 * Returns a collection of Media related to the model
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function media() {
		return $this->morphMany('Devfactory\Media\Models\Media', 'mediable');
	}

}