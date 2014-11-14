<?php namespace Devfactory\Media;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use File;

use Devfactory\Media\Models\Media;

trait MediaTrait {

  protected $file;

  protected $group;
  protected $type;

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
    $this->public_path = rtrim(Config::get('media::public_path'), '/\\') . '/';
    $this->files_directory = rtrim(ltrim(Config::get('media::files_directory'), '/\\'), '/\\') . '/';

    $this->create_sub_directories = Config::get('media::sub_directories');

    $this->directory = $this->public_path . $this->files_directory;
    if ($this->create_sub_directories) {
      $this->directory_uri .= Str::lower(class_basename($this)) . '/';
    }

    $this->directory .= $this->directory_uri;

    $this->filename_original = $this->file->getClientOriginalName();
    $this->filename_new = $this->getFilename();
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
   * @param $group string
   *  The group of file being uploaded, to allow for say a profile picture and a
   *  background to be uploaded for a user
   *
   * @param $type string
   *  If the field is for a single file that gets deleted set it to 'single',
   *  or if it is a multiple file upload, set it to 'multiple'
   *
   * @param $args array
   *  An array of extra options for the file
   *
   * @return object
   *  The Devfactory\Media\Models\Media Object
   */
  public function saveMedia($file, $group = 'default', $type = 'single', $args = []) {
    $this->file = $file;
    $this->group = $group;
    $this->type   = $type;
    $this->args = $args;

    $this->setup();

    $this->parseArgs();

    if ($this->type == 'single') {
      $this->removeExistingMedia();
    }

    $result = $this->databasePut();

    $this->storagePut();

    return $result;
  }

  /**
   * Get all the media assigned to the current model instance
   *
   * @param $group string
   *  The file group to retrieve
   *
   * @return object
   *  A Illuminate\Database\Eloquent\Collection Object of
   *  Devfactory\Media\Models\Media Objects
   */
  public function getMedia($group = NULL) {
    if (is_null($group)) {
      return $this->media()->get();
    }

    return $this->getMediaByGroup($group);
  }

  /**
   * Get all the media assigned to the current model instance
   * by the given $group
   *
   * @param $group string
   *  The file group to retrieve
   *
   * @return object
   *  A Illuminate\Database\Eloquent\Collection Object of
   *  Devfactory\Media\Models\Media Objects
   */
  private function getMediaByGroup($group) {
    return $this->media()
      ->where('group', $group)
      ->get();
  }

  /**
   * Get all the media assigned to the current model instance
   * by the given $group
   *
   * @param $id int
   *  The ID of the media media to retrieve
   *
   * @return object
   *  A Illuminate\Database\Eloquent\Collection Object of
   *  Devfactory\Media\Models\Media Objects
   */
  private function getMediaById($id) {
    return $this->media()
      ->find($id);
  }

  /**
   * Delete all the media assigned to the current model instance
   *
   * @param $group string
   *  The file group to delete
   *
   * @return int
   *  The number of elements deleted
   */
  public function deleteMedia($group = NULL) {
    $media = $this->getMedia($group);

    $count = 0;
    foreach ($media as $item) {
      $count += $this->removeMedia($item);
    }

    return $count;
  }

  /**
   * Delete all the media assigned to the current model instance
   *
   * @param $id int
   *  The ID of the media to delete
   *
   * @return int
   *  The number of elements deleted
   */
  public function deleteMediaById($id) {
    return $this->removeMedia($this->getMediaById($id));
  }

  /**
   * Delete all the media assigned to the current model instance by
   * the give $group
   *
   * @param $group string
   *  The file group to delete
   *
   * @return int
   *  The number of elements deleted
   */
  private function deleteMediaByGroup($group) {
    return $this->media()
      ->where('group', $group)
      ->delete();
  }

  /**
   * Perform the actual delete of the database row and removal of the
   * file from the filesystem
   *
   * @param $group string
   *  The file group to delete
   *
   * @return void
   */
  private function removeMedia($media) {
    File::delete($this->public_path . $this->files_directory . $media->filename);
    $media->delete();
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
        $this->filename_new = \Transliteration::clean_filename($this->filename_original);
        break;
      case 'unique':
        $this->filename_new = md5(microtime() . str_random(5)) .'.'. $this->filename_original;
        break;
      case 'nothing':
        $this->filename_new = $this->file->getClientOriginalName();
        break;
    }

    return $this->fileExistsRename();
  }

  /**
   * Checks if a file exists and creates a new name if it does
   *
   * @return string
   *  The uniquely named file
   */
  private function fileExistsRename() {
    if (!File::exists($this->directory . $this->filename_new)) {
      return $this->filename_new;
    }

    return $this->fileRename();
  }

  /**
   * Appends _X to the file's basename if it already exists
   *
   * @return string
   *  The newly renamed file
   */
  private function fileRename() {
    $filename = $this->filename_new;
    $extension = '.' . File::extension($this->filename_new);
    $basename = rtrim($filename, $extension);

    $increment = 0;

    while (File::exists($this->directory . $filename)) {
      $filename = $basename . '_' . ++$increment . $extension;
    }

    return $this->filename_new = $filename;
  }

  /**
   * Calculate the weight of the image compared to others in the group
   *
   * @return int
   *  The weight of the image in relation to the others
   */
  private function getWeight() {
    return $this->media()->where('group', $this->group)->count();
  }

  /**
   * Insert the media into the database
   *
   * @return object
   *  Devfactory\Media\Models\Media Object
   */
  private function databasePut() {
    $media = [
      'filename' => $this->directory_uri . $this->filename_new,
      'mime' => $this->file->getMimeGroup(),
      'size' => $this->file->getSize(),
      'group' => $this->group,
      'status' => TRUE,
      'weight' => $this->getWeight(),
    ];

    return $this->media()->save(new Media($media));
  }

  /**
   * Remove any media that already exists for the current group type
   *
   * @return int
   *  The number of elements removed
   */
  private function removeExistingMedia() {
    $existing_media = $this->getMedia($this->group);

    if (!$existing_media->isEmpty()) {
      return $this->deleteMedia();
    }

    return 0;
  }

  /**
   * Write the media to the file system
   */
  private function storagePut() {
    if ($this->makeDirectory($this->directory)) {
      $this->file->move($this->directory, $this->filename_new);
    }
  }

  /**
   * Creates the passed directory if it doesn't exist
   *
   * @param $directory string
   *  The full path of the directory to create
   *
   * @return bool
   *  TRUE if the directory exists, FALSE if it could not be created
   */
  private function makeDirectory($directory) {
    if (File::isDirectory($directory)) {
      return TRUE;
    }

    return File::makeDirectory($directory, 0755, TRUE);
  }

}