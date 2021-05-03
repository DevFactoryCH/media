<?php namespace Devfactory\Media;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use File;

trait MediaTrait
{

    protected $file;

    protected $media;

    protected $group;
    protected $type;

    protected $options;

    protected $filename_original;
    protected $filename_new;

    protected $public_path;
    protected $files_directory;
    protected $directory;
    protected $directory_uri;

    protected $create_sub_directories;

    protected $mimetype;
    protected $filesize;

    /**
     * Setup variables and file systems (basically constructor,
     * but actual trait __contruct()'s are bad apparently)
     */
    private function setup()
    {
        $this->public_path = rtrim(config('media.config.public_path'), '/\\') . '/';
        $this->files_directory = rtrim(ltrim(config('media.config.files_directory'), '/\\'), '/\\') . '/';

        $this->create_sub_directories = config('media.config.sub_directories');

        $this->directory = $this->public_path . $this->files_directory;
        if ($this->create_sub_directories) {
            $this->directory_uri = Str::lower(class_basename($this)) . '/';
        }

        $this->directory .= $this->directory_uri;

        $this->storage_path = '';
        if ($this->create_sub_directories) {
            $this->storage_path = Str::lower(class_basename($this)) . '/';
        }

        if (!empty($this->file)) {
            $this->filename_original = $this->file->getClientOriginalName();
            $this->filename_new = $this->getFilename();
        }
    }

    /**
     * Returns a collection of Media related to the model
     *
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function media()
    {
        return $this->morphMany(config('media.config.model'), 'mediable');
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
     * @param $options array
     *  An array of extra options for the file
     *
     * @return object
     *  The Devfactory\Media\Models\Media Object
     */
    public function saveMedia($file, $group = 'default', $type = 'single', $options = [])
    {
        $this->file = $file;
        $this->group = $group;
        $this->type   = $type;
        $this->options = $options;

        $this->setup();

        $this->parseOptions();

        if ($this->type == 'single') {
            $this->removeExistingMedia();
        }

        $result = $this->databasePut();

        $this->storagePut();

        return $result;
    }

    /**
     * Move existing media from bucket, and save the media to required place
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
     * @param $options array
     *  An array of extra options for the file
     *
     * @return object
     *  The Devfactory\Media\Models\Media Object
     */
    public function s3MoveAndSaveMedia($file, $group = 'default', $type = 'single', $options = [])
    {
        $this->filename_original = $file['name'];
        $this->filename_new = $this->getFilename();
        $this->mimetype = $file['content_type'];
        $this->filesize = Storage::size($file['key']);

        $this->group = $group;
        $this->type   = $type;
        $this->options = $options;

        $this->setup();

        $this->parseOptions();

        if ($this->type == 'single') {
            $this->removeExistingMedia();
        }

        $result = $this->databasePut();

        $this->storageS3Move($file);

        return $result;
    }

    /**
     * Update the meta data of a certain media file.
     *
     * @param $id int
     *  The ID of the media item to update
     *
     * @param $options array
     *  An key => value array of elements in the DB to change, key must be one of:
     *    'alt', 'title', 'name', 'weight'
     *
     * @return void
     */
    public function updateMediaById($id, $options)
    {
        $this->options = $options;

        $this->parseOptions();

        $model = config('media.config.model');
        $this->media = $model::find($id);

        $this->media->alt = $this->getAlt();
        $this->media->title = $this->getTitle();
        $this->media->name = $this->getName();
        $this->media->weight = $this->getWeight();

        $this->media->save();
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
    public function getMedia($group = null)
    {
        if (is_null($group)) {
            return $this->media()->orderBy('weight', 'ASC')->get();
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
    private function getMediaByGroup($group)
    {
        return $this->media()
                    ->where('group', $group)
                    ->orderBy('weight', 'ASC')
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
    private function getMediaById($id)
    {
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
    public function deleteMedia($group = null)
    {
        $media = $this->getMedia($group);

        $count = 0;
        foreach ($media as $item) {
            $count += (int) $this->removeMedia($item);
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
    public function deleteMediaById($id)
    {
        $media = $this->getMediaById($id);

        if (!is_null($media)) {
            return $this->removeMedia($media);
        }

        return false;
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
    private function deleteMediaByGroup($group)
    {
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
     * @return bool
     */
    private function removeMedia($media)
    {
        $this->setup();

        if (Storage::delete($media->filename)) {
            $media->delete();
            return true;
        }

        return false;
    }

    /**
     * Parse the optional arguments and merge them with the defaults
     */
    private function parseOptions()
    {
        $default_options = [
            'alt' => null,
            'title' => null,
            'name' => null,
            'weight' => null,
        ];

        $this->options += $default_options;

        $this->options = (object) $this->options;
    }

    /**
     * Helper function to process the media filename according to the settings
     *
     * @return string
     *  The parsed filename
     */
    private function getFilename()
    {
        switch (config('media.config.rename')) {
            case 'transliterate':
                $this->filename_new = $this->cleanFilename($this->filename_original);
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
    private function fileExistsRename()
    {
        if (Storage::missing($this->storage_path . $this->filename_new)) {
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
    private function fileRename()
    {
        $filename = $this->filename_new;
        $extension = '.' . File::extension($this->filename_new);
        $basename = rtrim($filename, $extension);

        $increment = 0;

        while (Storage::exists($this->storage_path . $filename)) {
            $filename = $basename . '_' . ++$increment . $extension;
        }

        return $this->filename_new = $filename;
    }

    /**
     * Get the alt text for the file, from options, then from db, otherwise ''
     *
     * @return string
     *  The Alt text
     */
    private function getAlt()
    {
        if (!is_null($this->options->alt)) {
            return $this->options->alt;
        }

        if (!is_null($this->media)) {
            return $this->media->alt;
        }

        return '';
    }

    /**
     * Get the Title text for the file, from options, then from db, otherwise ''
     *
     * @return string
     *  The Title text
     */
    private function getTitle()
    {
        if (!is_null($this->options->title)) {
            return $this->options->title;
        }

        if (!is_null($this->media)) {
            return $this->media->title;
        }

        return '';
    }

    /**
     * Get the Name text for the file, from options, then from db, otherwise ''
     *
     * @return string
     *  The Name text
     */
    private function getName()
    {
        if (!is_null($this->options->name)) {
            return $this->options->name;
        }

        if (!is_null($this->media)) {
            return $this->media->name;
        }

        return '';
    }

    /**
     * Calculate the weight of the image from options, then from existing media,
     * then compared to others in the group
     *
     * @return int
     *  The weight of the image
     */
    private function getWeight()
    {
        if (!is_null($this->options->weight)) {
            return $this->options->weight;
        }

        if (!is_null($this->media)) {
            return $this->media->weight;
        }

        return $this->media()->where('group', $this->group)->count();
    }

    /**
     * Insert the media into the database
     *
     * @return object
     *  Devfactory\Media\Models\Media Object
     */
    private function databasePut()
    {
        $media = [
            'filename' => $this->directory_uri . $this->filename_new,
            'mime' => $this->getMimeType(),
            'size' => $this->getFileSize(),
            'title' => $this->getTitle(),
            'alt' => $this->getAlt(),
            'name' => $this->getName(),
            'group' => $this->group,
            'status' => true,
            'weight' => $this->getWeight(),
        ];

        $model = config('media.config.model');
        return $this->media()->save(new $model($media));
    }

    /**
     * Retrieve file mime-type
     *
     * @return object
     *  Devfactory\Media\Models\Media Object
     */
    private function getMimeType()
    {
        if ($this->file) {
            return $this->file->getMimeType();
        }

        if (!empty($this->mimetype)) {
            return $this->mimetype;
        }

        return '';
    }

    /**
     * Retrieve file size
     *
     * @return object
     *  Devfactory\Media\Models\Media Object
     */
    private function getFileSize()
    {
        if ($this->file) {
            return $this->file->getSize();
        }

        if (!empty($this->filesize)) {
            return $this->filesize;
        }

        return null;
    }

    /**
     * Remove any media that already exists for the current group type
     *
     * @return int
     *  The number of elements removed
     */
    private function removeExistingMedia()
    {
        $existing_media = $this->getMedia($this->group);

        if (!$existing_media->isEmpty()) {
            return $this->deleteMedia($this->group);
        }

        return 0;
    }

    /**
     * Write the media to the file system
     */
    private function storagePut()
    {
        Storage::putFileAs($this->storage_path, $this->file, $this->filename_new, 'public');
    }

    /**
     * Copy an existing media file from a bucket
     */
    private function storageS3Move($file)
    {
        $new_file = $this->storage_path . $file['name'];
        Storage::move($file['key'], $new_file);
        Storage::setVisibility($new_file, 'public');
    }

    /**
     * Clone a file from passed Media Object to a new Media location on disk
     *
     * @return bool
     *  TRUE if the file copied, FALSE if it could not be copied
     */
    private function storageClone()
    {
        return Storage::copy($this->media->filename, $this->storage_path . $this->filename_new);
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
    private function makeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            return true;
        }

        return File::makeDirectory($directory, 0755, true);
    }

    /**
     * Clone an existing media item, onto a new model instance.
     *
     * @param object $media
     *  The old Media that should be cloned to the current instance.
     * @param bool $storage_clone
     *  Optionally, also copy the file in storage to a new, unique file
     * @param $clone_attributes
     *  Optionally, override certain attributes on the clone
     *
     * @return \Devfactory\Media\Models\Media
     */
    public function cloneMedia($media, $clone_storage = false, $clone_attributes = [])
    {
        $this->media = $media->replicate();
        $this->setup();
        $this->filename_new = basename($media->filename);

        if ($clone_storage) {
            $this->fileExistsRename();
            $this->storageClone();
        }

        $this->media->fill($clone_attributes);
        $this->media->filename = $this->directory_uri . $this->filename_new;

        return $this->media()->save($this->media);
    }

    /**
     * Transliterates and sanitizes a file name.
     *
     * The resulting file name has white space replaced with underscores, consists
     * of only US-ASCII characters, and is converted to lowercase (if configured).
     * If multiple files have been submitted as an array, the names will be
     * processed recursively.
     *
     * @param $filename
     *   A file name, or an array of file names.
     * @param $source_langcode
     *   Optional ISO 639 language code that denotes the language of the input and
     *   is used to apply language-specific variations. If the source language is
     *   not known at the time of transliteration, it is recommended to set this
     *   argument to the site default language to produce consistent results.
     *   Otherwise the current display language will be used.
     * @return
     *   Sanitized file name, or array of sanitized file names.
     *
     * @see language_default()
     */
    public function cleanFilename($filename, $source_langcode = null)
    {
        if (is_array($filename)) {
            foreach ($filename as $key => $value) {
                $filename[$key] = $this->cleanFilename($value, $source_langcode);
            }
            return $filename;
        }
        $filename = $this->transliterationProcess($filename, '', $source_langcode);
        // Replace whitespace.
        $filename = str_replace(' ', '_', $filename);
        // Remove remaining unsafe characters.
        $filename = preg_replace('![^0-9A-Za-z_.-]!', '', $filename);
        // Remove multiple consecutive non-alphabetical characters.
        $filename = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $filename);

        $filename = strtolower($filename);

        return $filename;
    }

    /**
     * Transliterates UTF-8 encoded text to US-ASCII.
     *
     * Based on Mediawiki's UtfNormal::quickIsNFCVerify().
     *      Swiped from drupal's transliteration module: https://drupal.org/project/transliteration
     *
     * @param $string
     *   UTF-8 encoded text input.
     * @param $unknown
     *   Replacement string for characters that do not have a suitable ASCII
     *   equivalent.
     * @param $source_langcode
     *   Optional ISO 639 language code that denotes the language of the input and
     *   is used to apply language-specific variations. If the source language is
     *   not known at the time of transliteration, it is recommended to set this
     *   argument to the site default language to produce consistent results.
     *   Otherwise the current display language will be used.
     * @return
     *   Transliterated text.
     */
    public function transliterationProcess($string, $unknown = '?', $source_langcode = null)
    {
        // ASCII is always valid NFC! If we're only ever given plain ASCII, we can
        // avoid the overhead of initializing the decomposition tables by skipping
        // out early.
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }
        static $tail_bytes;
        if (!isset($tail_bytes)) {
            // Each UTF-8 head byte is followed by a certain number of tail bytes.
            $tail_bytes = array();
            for ($n = 0; $n < 256; $n++) {
                if ($n < 0xc0) {
                    $remaining = 0;
                } elseif ($n < 0xe0) {
                    $remaining = 1;
                } elseif ($n < 0xf0) {
                    $remaining = 2;
                } elseif ($n < 0xf8) {
                    $remaining = 3;
                } elseif ($n < 0xfc) {
                    $remaining = 4;
                } elseif ($n < 0xfe) {
                    $remaining = 5;
                } else {
                    $remaining = 0;
                }
                $tail_bytes[chr($n)] = $remaining;
            }
        }
    }
}
