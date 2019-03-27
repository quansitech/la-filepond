# filepond for laravel-admin

![Travis (.com)](https://img.shields.io/travis/com/tiderjian/la-filepond.svg?style=flat-square)
![style ci](https://img.shields.io/travis/com/tiderjian/la-filepond.svg?style=flat-square)
![download](https://img.shields.io/packagist/dt/tiderjian/la-filepond.svg?style=flat-square)
![lincense](https://img.shields.io/github/license/tiderjian/la-filepond.svg?style=flat-square)
![Pull request welcome](https://img.shields.io/badge/pr-welcome-green.svg?style=flat-square)

## [documentation](https://github.com/tiderjian/la-filepond/blob/master/README.md)

## 关于filepond for laravel-admin
   [filepond](https://github.com/pqina/filepond)是github上的一个基于MIT开源协议的前端上传控件。其基于插件式的模式开发，具有很强的扩展能力，同时UI设计也简洁美观。而filepond for laravel-admin就是基于该上传控件开发的la form扩展组件。
   

## 为什么要做它
由于la本身自带的文件上传控件操作复杂，默认配置的上传功能在多图上传时会因图片数量多了导致提交非常缓慢，考虑到用户体验，采用ajax分文件上传会更加合适（当然也可以强行配置成该种模式上传，但依然会有很多问题）。因此作者决定重新开发一个新的上传组件，因此就有了它。

## demo
<img src="https://user-images.githubusercontent.com/1665649/54975771-280ac900-4fd3-11e9-91c6-c26661242fcb.gif" />

## 安装
首先需要安装[laravel-admin](https://github.com/z-song/laravel-admin)并保证能正常运行。然后执行命令
```
composer require tiderjian/la-filepond
```
接着执行laravel-admin 的扩展安装
```
php artisan admin:import filepond
```

## 配置
在config/admin.php的extensions代码段中，增加filepond的配置子项
```
'extensions' => [
    'filepond' => [
        // 控制扩展的使用开关
        'enable' => true,
        // 是否自动清除上传不再使用的文件(默认是关闭)
        'autodelete' => true
    ]
]
```

## 用法
```
//图片上传
$form->filepondImage(@数据库字段名, @标签名称)
//普通文件上传
$form->filepondFile(@数据库字段名, @标签名称)

//多图片上传
$form->filepondImage(@数据库字段名, @标签名称)->multiple()
//多文件上传
$form->filepondFile(@数据库字段名, @标签名称)->multiple()

//ps:由于多文件上传使用json类型存储到数据库，因此必须在model里设置字段json类型转换，否则会无法正常工作
protected $casts = [
    'images' => 'json',
    'files'  => 'json',
];

//设置为必填字段
$form->filepondImage(@数据库字段名, @标签名称)->rules('required')

//设置上传文件类型限制
$form->filepondFile(@数据库字段名, @标签名称)->mineType(['application/msword', 'application/pdf'])
$form->filepondFile(@数据库字段名, @标签名称)->mineType('application/msword')

//设置文件上传大小限制 单位为KB
$form->filepondFile(@数据库字段名, @标签名称)->size(30)
```

## 扩展
组件提供了扩展能力，我们已增加一个限制图片大小的插件为例说明如何进行组件功能的扩展。
1. 首先下载图片大小限制[filepond-plugin-image-validate-size](https://github.com/pqina/filepond-plugin-image-validate-size),并将其添加到public/vendor/laravel-admin-ext/la-filepond/js下

2. 在app/Admin/bootstrap.php里添加
```
\Encore\Admin\Admin::booting(function(){
    \Qs\La\Filepond\File::extendPluginJs(['/vendor/laravel-admin-ext/la-filepond/js/filepond-plugin-image-validate-size.min.js']);
    \Qs\La\Filepond\File::extendPlugin('FilePondPluginImageValidateSize');
    //如果该插件还需要添加css文件，可使用\Qs\La\Filepond\File::extendPluginCss方法添加
});
```

3. 查看插件说明，添加配置参数
```
//imageValidateSizeMinWidth、imageValidateSizeMaxWidth 为该插件使用到的配置参数，分别进行图片最小宽度和最大宽度的限制
$form->filepondImage('images', 'images')->multiple()->options(['imageValidateSizeMinWidth' => 200, 'imageValidateSizeMaxWidth' => 400]);
```

## 许可
[MIT License](https://github.com/tiderjian/la-filepond/blob/master/LICENSE)