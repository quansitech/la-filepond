<?php
namespace Qs\La\Filepond;

use Illuminate\Support\ServiceProvider;

class FilepondServiceProvider extends ServiceProvider{

    const MIDDLEWARE_UPLOADFILE_FILTER = 'Filepond.uploadfile-filter';

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravel-admin-filepond');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/laravel-admin-ext/la-filepond'),
            __DIR__.'/../resources/lang' => resource_path('lang')
        ]);

        Filepond::boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMiddleware();
    }

    protected function registerMiddleware(){
        app('router')->aliasMiddleware(self::MIDDLEWARE_UPLOADFILE_FILTER, RegisterFilter::class);

        if(app('router')->hasMiddlewareGroup('admin')){
            $adminMiddleGroup = app('router')->getMiddlewareGroups()['admin'];
            array_push($adminMiddleGroup, self::MIDDLEWARE_EXTEND);
            app('router')->middlewareGroup('admin', $adminMiddleGroup);
        }

    }
}