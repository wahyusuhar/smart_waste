<?php

namespace App\Providers;

use App\Filesystem\CloudinaryAdapter;
use Cloudinary\Cloudinary;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Replaces the cloudinary driver registered by cloudinary-labs/cloudinary-laravel (package providers
        // boot first) with the same setup, but with an adapter that can also write string contents.
        Storage::extend('cloudinary', function ($app, array $config) {
            $adapter = new CloudinaryAdapter(new Cloudinary($config['url']), null, $config['prefix'] ?? null);

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });
    }
}
