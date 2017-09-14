# Laravel image thumbs


Trait for create image thumbs for models via InterventionImage.


## Install on Laravel 5.5

1) Install using composer (run in your terminal):

```bash
composer require johan-code/image-thumbs
```

2) Publish (run in your terminal):

```bash
php artisan vendor:publish --provider="JohanCode\ImageThumbs\ServiceProvider"
```

3) Set name of disk for uploading in `/config/image-thumbs.php`:
 ```php
 return [
     'disk_name' => 'public'
 ];
 ```
 
4) Make sure the disk for uploading exist and available in public.

Example config `/config/filesystems.php`
```php
'disks' => [
    ...
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => '/storage',
    ],
    ...
],
```

Use laravel command for create symlink (run in your terminal):
```bash
php artisan storage:link
```


## Install on Laravel 5.4

Add service provider in `config/app.php`:

```php
'providers' => [
    ...
    JohanCode\ImageThumbs\ServiceProvider::class,
    ...
]
```
... and follow main instruction.
