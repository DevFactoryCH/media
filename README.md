#Media

This package saves uploaded files and links them with models

## Installation

Using Composer, edit your `composer.json` file to require `devfactory/media`.

    "require": {
      "devfactory/media": "1.0.*"
    }

Then from the terminal run

    composer update

Then in your `app/config/app.php` file register the service provider:

    'Devfactory\Media\MediaServiceProvider'

Run the migration to create the DB table:

    php artisan migrate --package=devfactory/media

Finally, publish the config to make changes to where and how the files are stored:

    php artisan config:publish devfactory/media

## Usage

To use the package, you need to add the following to any of your models which will be receiving media uploads.

```php
<?php

class User extends Eloquent {

  use \Devfactory\Media\MediaTrait;

);
```

Then to save a media, in the method that handles your form submission you just need to pass the File object to `saveMedia()`:

```php
public function upload() {
  $user = User::firstOrCreate(['id' => 1]);

  if (Input::hasFile('image')) {
    $user->saveMedia(Input::file('image'));
  }

  return Redirect::route('route');
}
```

This will create the file on the file system and insert an entry into the DB table media.

If you need to set multiple different types of images on a Model, like the users' profile picture and a background for their page you can use the second parameter:

```php
$user->saveMedia(Input::file('profile_picture'), 'profile_picture');
$user->saveMedia(Input::file('background_image'), 'background_image');
```

To retrieve the images again, you just need to call `getMedia()`:

```php
// Retrieves every Media linked with the user
$all_media = $user->getMedia();

// Retrieve a specific Media
$profile_picture = $user->getMedia('profile_picture');
```

Finally you can delete media with `deleteMedia()`:

```php
// Delete all media for a user
$user->deleteMedia();

// Delete specific media
$user->deleteMedia('profile_picture');
```
