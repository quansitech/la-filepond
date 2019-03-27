# filepond for laravel-admin

![Travis (.com)](https://img.shields.io/travis/com/tiderjian/la-filepond.svg?style=flat-square)
![style ci](https://img.shields.io/travis/com/tiderjian/la-filepond.svg?style=flat-square)
![download](https://img.shields.io/packagist/dt/tiderjian/la-filepond.svg?style=flat-square)
![lincense](https://img.shields.io/github/license/tiderjian/la-filepond.svg?style=flat-square)
![Pull request welcome](https://img.shields.io/badge/pr-welcome-green.svg?style=flat-square)

## [中文文档](https://github.com/tiderjian/la-filepond/blob/master/README.md)

## About
   [Filepond](https://github.com/pqina/filepond)is a flexible and fun JavaScript file upload library, that can upload anything you throw at it, optimizes images for faster uploads, and offers a great, accessible, silky smooth user experience. Filepond for laravel-admin is [laravel-admin](https://github.com/z-song/laravel-admin) extention base on it.
   

## Why
laravel-admin's upload item is hard to use and more complex, and slowly upload while more multiple files, this is a bad user experience. Filepond upload by ajax, suitable for multiple file upload.

## Demo
<img src="https://user-images.githubusercontent.com/1665649/54975771-280ac900-4fd3-11e9-91c6-c26661242fcb.gif" />

## Install
first, install [laravel-admin](https://github.com/z-song/laravel-admin), and run below command.
```
composer require tiderjian/la-filepond
```
run laravel-admin extention import.
```
php artisan admin:import filepond
```

## Config
find out the extensions segment in the config/admin.php，add filepond config
```
'extensions' => [
    'filepond' => [
        // enable or disable the extension
        'enable' => true,
        // atuo delete the uploaded files(default false)
        'autodelete' => true
    ]
]
```

## Usage
```
//image upload
$form->filepondImage(@database column, @label)
//file upload
$form->filepondFile(@database column, @label)

//multiple images upload
$form->filepondImage(@database column, @label)->multiple()
//multiple files upload
$form->filepondFile(@database column, @label)->multiple()

//ps:multiple upload save to database in json，must set the casts to json on the model.
protected $casts = [
    'images' => 'json',
    'files'  => 'json',
];

//set required
$form->filepondImage(@database column, @label)->rules('required')

//set file type that can be uploaded.
$form->filepondFile(@database column, @label)->mineType(['application/msword', 'application/pdf'])
$form->filepondFile(@database column, @label)->mineType('application/msword')

//set max file size, unit: KB
$form->filepondFile(@database column, @label)->size(30)
```

## extension
You can extend it by youself, here is a sample that extend a image size validate plugin.
1. download [filepond-plugin-image-validate-size](https://github.com/pqina/filepond-plugin-image-validate-size),add to public/vendor/laravel-admin-ext/la-filepond/js 

2. add to app/Admin/bootstrap.php
```
\Encore\Admin\Admin::booting(function(){
    \Qs\La\Filepond\File::extendPluginJs(['/vendor/laravel-admin-ext/la-filepond/js/filepond-plugin-image-validate-size.min.js']);
    \Qs\La\Filepond\File::extendPlugin('FilePondPluginImageValidateSize');
    //use the \Qs\La\Filepond\File::extendPluginCss function to add css file
});
```

3. find out the pulgin document, and add config as you like
```
//imageValidateSizeMinWidth、imageValidateSizeMaxWidth is the config keys
$form->filepondImage('images', 'images')->multiple()->options(['imageValidateSizeMinWidth' => 200, 'imageValidateSizeMaxWidth' => 400]);
```

## lincense
[MIT License](https://github.com/tiderjian/la-filepond/blob/master/LICENSE)