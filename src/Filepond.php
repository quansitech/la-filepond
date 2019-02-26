<?php
namespace Qs\La\Filepond;

use Encore\Admin\Extension;
use Encore\Admin\Form;
use Illuminate\Support\Facades\Artisan;

class Filepond extends Extension {

    /**
     * @var string
     */
    protected $name = "filepond";

    /**
     * Bootstrap this package.
     *
     * @return void
     */
    public static function boot()
    {
        if(parent::boot()){

            Form::extend('filepondFile', File::class);
        }
    }

    public static function import(){
        Artisan::call("vendor:publish", [
            '--provider' => FilepondServiceProvider::class
        ]);
    }
}