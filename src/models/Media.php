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
    'type',
    'status',
    'weight',
  ];

  public function mediable() {
    return $this->morphTo();
  }

}