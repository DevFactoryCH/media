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

);
