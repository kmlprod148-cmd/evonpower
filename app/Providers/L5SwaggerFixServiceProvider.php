<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\FilesystemManager;

class L5SwaggerFixServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('filesystem', function () {
            return $this->app->make(FilesystemManager::class);
        });
    }
}