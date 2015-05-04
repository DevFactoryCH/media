<?php

return array(
  /**
   * The location of the public folder for your laravel installation
   */
  'public_path'  => public_path(),

  /**
   * The location of the directory where you wanted to store  your uploaded
   * files on the site relative to the laravel public directory
   */
  'files_directory' => 'uploads/',

  /**
   * Set to TRUE if want sub directories by Model to be created in the
   * upload folder. FALSE if you want to have them all in the root.
   */
  'sub_directories' => TRUE,

  /**
   * Set to TRUE if you want sub directories by Model and then sub directories
   * by model ID to be created in the upload folder. If TRUE this takes
   * precedence over 'sub_directories'. FALSE if you want to have them all in
   * the root.
   */
  'sub_directories_by_id' => FALSE,

  /**
   * Define how to rename filenames, options are:
   *
   * - 'nothing' [NOT RECOMMENDED¨]
   *   Don't rename the file, leave as is when uploaded.
   *
   * - 'unique'
   *   Generate a unique filename by using a hash of the current datetime
   *
   * - 'transliterate'
   *   Clean up the file name, lowercasing all letters, replacing
   *   spaces with _'s and removing accents and special characters.
   */
  'rename' => 'transliterate',
);
