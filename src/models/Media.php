<?php namespace Devfactory\Media\Models;

class Media extends \Eloquent {

  protected $table = 'media';

  protected $fillable = [
    'filename',
    'mime',
    'size',
    'name',
    'alt',
    'title',
    'group',
    'status',
    'weight',
  ];

  public function mediable() {
    return $this->morphTo();
  }

  public function getUrlAttribute() {
    return \Url::asset(\Config::get('media::files_directory') . $this->filename);
  }

  public function getTitleAttribute($value) {
    if (empty($value)) {
      return basename($this->attributes['filename']);
    }

    return $value;
  }

}