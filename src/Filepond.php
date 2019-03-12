<?php
namespace Qs\La\Filepond;

use Encore\Admin\Admin;
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
            Admin::extend('filepond', __CLASS__);
        }
    }

    public static function import(){
        Artisan::call("vendor:publish", [
            '--provider' => FilepondServiceProvider::class
        ]);
    }
}