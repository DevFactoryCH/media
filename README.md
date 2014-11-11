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

    'Devfactory\media\MediaServiceProvider'

And the Facade:

    'Media'      => 'Devfactory\Media\Facades\MediaFacade',

Publish the config:

    php artisan config:publish devfactory/media

## Usage

Define some presets in `app/config/packages/devfactory/media/config.php`

```php
<?php
return array(

);
```
