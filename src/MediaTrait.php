<?php namespace Devfactory\Media;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use File;

use Devfactory\Media\Models\Media;

trait MediaTrait {

  protected $file;

  protected $type;
  protected $op;

  protected $args;

  protected $filename_original;
  protected $filename_new;

  protected $public_path;
  protected $files_directory;
  protected $directory;
  protected $directory_uri = '';

  protected $create_sub_directories;

  /**
   * Setup variables and file systems (basically constructor,
   * but actual trait __contruct()'s are bad apparently)
   */
  private function setup() {
    $this->filename_original = $this->file->getClientOriginalName();
    $this->filename_new = $this->getFilename();

    $this->public_path = rtrim(Config::get('media::public_path'), '/\\') . '/';
    $this->files_directory = rtrim(ltrim(Config::get('media::files_directory'), '/\\'), '/\\') . '/';

    $this->create_sub_directories = Config::get('media::sub_directories');

    $this->directory = $this->public_path . $this->files_directory;
    if ($this->create_sub_directories) {
      $this->directory_uri .= Str::lower(class_basename($this)) . '/';
    }

    $this->directory .= $this->directory_uri;
  }

  /**
	 * Returns a collection of Media related to the model
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function media() {
		return $this->morphMany('Devfactory\Media\Models\Media', 'mediable');
	}

  /**
   * Save the media to disk and DB
   *
   * @param $file object
   *  The Symfony\Component\HttpFoundation\File\UploadedFile Object
   *
   * @param $type string
   *  The type of file being uploaded, to allow for say a profile picture and a
   *  background to be uploaded for a user
   *
   * @param $op string
   *  If the existing media is to be overwritten (1 file) or if we keep the old
   *  media and add this one after (Multiple files)
   *
   * @param $args array
   *  An array of extra options for the file
   *
   * @return object
   *  The Devfactory\Media\Models\Media Object
   */
  public function saveMedia($file, $type = 'default', $op = 'overwrite', $args = []) {
    $this->file = $file;
    $this->type = $type;
    $this->op   = $op;
    $this->args = $args;

    $this->setup();

    $this->parseArgs();

    $result = $this->databasePut();

    $this->storagePut();

    return $result;
  }

  /**
   * Get all the media assigned to the current model instance
   *
   * @param $type string
   *  The file type to retrieve
   *
   * @return object
   *  A Illuminate\Database\Eloquent\Collection Object of
   *  Devfactory\Media\Models\Media Objects
   */
  public function getMedia($type = NULL) {
    if (is_null($type)) {
      return $this->media()->get();
    }

    return $this->getMediaByType($type);
  }

  /**
   * Get all the media assigned to the current model instance
   * by the given $type
   *
   * @param $type string
   *  The file type to retrieve
   *
   * @return object
   *  A Illuminate\Database\Eloquent\Collection Object of
   *  Devfactory\Media\Models\Media Objects
   */
  private function getMediaByType($type) {
    return $this->media()
      ->where('type', $type)
      ->get();
  }

  /**
   * Delete all the media assigned to the current model instance
   *
   * @param $type string
   *  The file type to delete
   *
   * @return int
   *  The number of elements deleted
   */
  public function deleteMedia($type = NULL) {
    if (is_null($type)) {
      return $this->removeMedia();
    }

    return $this->removeMedia($type);
  }

  /**
   * Delete all the media assigned to the current model instance by
   * the give $type
   *
   * @param $type string
   *  The file type to delete
   *
   * @return int
   *  The number of elements deleted
   */
  private function deleteMediaByType($type) {
    return $this->media()
      ->where('type', $type)
      ->delete();
  }

  /**
   * Perform the actual delete of the database row and removal of the
   * file from the filesystem
   *
   * @param $type string
   *  The file type to delete
   *
   * @return void
   */
  private function removeMedia($type = NULL) {
    foreach ($this->getMedia($type) as $media) {
      File::delete($this->public_path . $this->files_directory . $media->filename);
      $media->delete();
    }
  }

  /**
   * Parse the optional arguments and merge them with the defaults
   */
  private function parseArgs() {
    $default_args = [
    ];

    $this->args = $this->args + $default_args;

    $this->args = (object) $this->args;
  }

  /**
   * Helper function to process the media filename according to the settings
   *
   * @return string
   *  The parsed filename
   */
  private function getFilename() {
    switch (Config::get('media::rename')) {
      case 'transliterate':
        return \Transliteration::clean_filename($this->filename_original);
        break;
      case 'unique':
        return md5(microtime() . str_random(5)) .'.'. $this->filename_original;
        break;
      case 'nothing':
        return $this->file->getClientOriginalName();
        break;
    }
  }

  /**
   * Calculate the weight of the image compared to others in the type
   *
   * @return int
   *  The weight of the image in relation to the others
   */
  private function getWeight() {
    return $this->media()->where('type', $this->type)->count();
  }

  /**
   * Insert the media into the database
   *
   * @return object
   *  Devfactory\Media\Models\Media Object
   */
  private function databasePut() {
    if ($this->op == 'overwrite') {
      $existing_media = $this->getMedia($this->type);
      if (!$existing_media->isEmpty()) {
        $this->deleteMedia();
      }
    }

    $media = [
      'filename' => $this->directory_uri . $this->filename_new,
      'mime' => $this->file->getMimeType(),
      'size' => $this->file->getSize(),
      'type' => $this->type,
      'status' => TRUE,
      'weight' => $this->getWeight(),
    ];

    return $this->media()->save(new Media($media));
  }

  /**
   * Write the media to the file system
   */
  private function storagePut() {
    if (!File::isDirectory($this->directory)) {
      File::makeDirectory($this->directory, 0755, TRUE);
    }

    $this->file->move($this->directory, $this->filename_new);
  }

}