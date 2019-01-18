<?php namespace Devfactory\Media\Models;

use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;

class Media extends Model {

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
    return Url::asset(config('media.config.files_directory') . $this->filename);
  }

  public function getTitleAttribute($value) {
    if (empty($value)) {
      return basename($this->attributes['filename']);
    }

    return $value;
  }

}