<?php

namespace Qs\La\Filepond;

use Illuminate\Support\ServiceProvider;

class FilepondServiceProvider extends ServiceProvider
{
    const MIDDLEWARE_UPLOADFILE_FILTER = 'Filepond.uploadfile-filter';

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-admin-filepond');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/laravel-admin-ext/la-filepond'),
            __DIR__.'/../resources/lang'   => resource_path('lang'),
        ]);

        Filepond::boot();
    }
}
