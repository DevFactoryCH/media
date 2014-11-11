<?php namespace Devfactory\Media;

use Illuminate\Support\Facades\Config;

trait MediaTrait {

  protected $file;

  /**
	 * Returns a collection of Media related to the model
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function media() {
		return $this->morphMany('Devfactory\Media\Models\Media', 'mediable');
	}

  public function saveMedia($file) {
    $this->file = $file;

    $extension = $file->getClientOriginalExtension();

    $media = [
      'filename' => $this->getFilename(),
    ];

    echo "<pre>";
    print_r($media);
    echo "</pre>";

    echo "<pre>";
    dd($file);

  }

  private function getFilename() {
    echo "<pre>";
    print_r(Config::get('media'));
    echo "</pre>";
    switch (Config::get('media::rename')) {
      case 'transliterate':
        return Transliteration::clean_filename($files->getClientOriginalName());
        break;
    }
  }

}