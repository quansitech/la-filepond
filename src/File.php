<?php
namespace Qs\La\Filepond;

use Encore\Admin\Form;
use Encore\Admin\Form\Field;
use http\Exception\RuntimeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class File extends Field {

    const FILE_UPLOAD_FLAG = '_filepond_upload_';

    protected $view = 'laravel-admin-filepond::filepond';

    /**
     * Storage instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $storage = '';

    /**
     * Upload directory.
     *
     * @var string
     */
    protected $directory = '';

    /**
     * File name.
     *
     * @var null
     */
    protected $name = null;

    /**
     * If use unique name to store upload file.
     *
     * @var bool
     */
    protected $useUniqueName = false;

    /**
     * If use sequence name to store upload file.
     *
     * @var bool
     */
    protected $useSequenceName = false;

    /**
     * Controls the storage permission. Could be 'private' or 'public'.
     *
     * @var string
     */
    protected $storage_permission;

    /**
     *  if upload validator is fail, then return response
     *
     * @var  Response
     */
    protected $uploadValidatorResponse;

    /**
     * Css.
     *
     * @var array
     */
    protected static $css = [
        '/vendor/laravel-admin-ext/la-filepond/css/filepond.min.css?v=3.8.2',
    ];

    protected $rules = 'file';

    /**
     * Js.
     *
     * @var array
     */
    protected static $js = [
        '/vendor/laravel-admin-ext/la-filepond/js/json_parse_state.js',
        '/vendor/laravel-admin-ext/la-filepond/js/filepond-plugin-file-validate-size.min.js',
        '/vendor/laravel-admin-ext/la-filepond/js/filepond-plugin-file-validate-type.min.js',
        '/vendor/laravel-admin-ext/la-filepond/js/filepond.min.js?v=3.8.2',
        '/vendor/laravel-admin-ext/la-filepond/js/filepond.jquery.js'
    ];

    protected static $plugins = [
        'FilePondPluginFileValidateType',
        'FilePondPluginFileValidateSize'
    ];

    protected static $renderPlugin = false;

    protected static $injectSubmittedCb = false;

    protected static $injectSavingCb = false;

    /**
     * Options for specify elements.
     *
     * @var array
     */
    protected $options = [];


    public function __construct($column, array $arguments = [])
    {
        $this->initStorage();

        parent::__construct($column, $arguments);
    }

    /**
     * Initialize the storage instance.
     *
     * @return void.
     */
    protected function initStorage()
    {
        $this->disk(config('admin.upload.disk'));
    }

    /**
     * Set disk for storage.
     *
     * @param string $disk Disks defined in `config/filesystems.php`.
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function disk($disk)
    {
        try {
            $this->storage = Storage::disk($disk);
        } catch (\Exception $exception) {
            if (!array_key_exists($disk, config('filesystems.disks'))) {
                admin_error(
                    'Config error.',
                    "Disk [$disk] not configured, please add a disk config in `config/filesystems.php`."
                );

                return $this;
            }

            throw $exception;
        }

        return $this;
    }

    /**
     * Default directory for file to upload.
     *
     * @return mixed
     */
    public function defaultDirectory()
    {
        return config('admin.upload.directory.file');
    }

    /**
     * Get directory for store file.
     *
     * @return mixed|string
     */
    public function getDirectory()
    {
        if ($this->directory instanceof \Closure) {
            return call_user_func($this->directory, $this->form);
        }

        return $this->directory ?: $this->defaultDirectory();
    }


    /**
     * Generate a unique name for uploaded file.
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    protected function generateUniqueName(UploadedFile $file)
    {
        return md5(uniqid()).'.'.$file->getClientOriginalExtension();
    }

    /**
     * Generate a sequence name for uploaded file.
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    protected function generateSequenceName(UploadedFile $file)
    {
        $index = 1;
        $extension = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();
        $newName = $originalName.'_'.$index.'.'.$extension;

        while ($this->storage->exists("{$this->getDirectory()}/$newName")) {
            $index++;
            $newName = $originalName.'_'.$index.'.'.$extension;
        }

        return $newName;
    }

    /**
     * Get store name of upload file.
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    protected function getStoreName(UploadedFile $file)
    {
        if ($this->useUniqueName) {
            return $this->generateUniqueName($file);
        }

        if ($this->useSequenceName) {
            return $this->generateSequenceName($file);
        }

        if ($this->name instanceof \Closure) {
            return $this->name->call($this, $file);
        }

        if (is_string($this->name)) {
            return $this->name;
        }

        return $file->getClientOriginalName();
    }

    /**
     * @param Form $form
     *
     * @return $this
     */
    public function setForm(Form $form = null)
    {
        $this->form = $form;

        $this->setupSubmittedCb();
        $this->setupSavingCb();
        return $this;
    }

    /**
     * Prepare for a field value before update or insert.
     *
     * @param $value
     *
     * @return mixed
     */
    public function prepare($value)
    {
        if(is_array($value)){

            return tap($value, function(&$val){
                $val = collect($val)->filter()->flatten()->all();
            });

        }


        return $value;
    }

    protected function setupDefaultOptions(){
        $defaultOptions = [
            'instantUpload' => false,
            'labelIdle' => trans('filepond.label'),
        ];

        $this->options($defaultOptions);
    }

    public function formatInput($form){
        if(!$form->input($this->column())){
            $form->input($this->column(), []);
        }
    }

    protected function setupSavingCb(){
        if(self::$injectSavingCb === false){
            $this->form->saving(function($form){
                $data = Input::all();
                if (!array_key_exists(File::FILE_UPLOAD_FLAG, $data)) {
                    foreach ($form->builder()->fields() as $field) {

                        if (get_class($field) == File::class) {
                            $field->formatInput($form);
                        }
                    }
                }
            });

            self::$injectSavingCb = true;
        }
    }


    protected function setupSubmittedCb(){
        if(self::$injectSubmittedCb === false){
            $this->form->submitted(function($form){
                $data = Input::all();

                if (array_key_exists(File::FILE_UPLOAD_FLAG, $data)) {
                    $field = File::getFieldFromInput($data, $form);

                    $validator = $field->getUploadValidator($data);
                    if(($validator instanceof \Illuminate\Validation\Validator) && !$validator->passes() && $field->uploadValidatorResponse instanceof Response){
                        return $field->uploadValidatorResponse;
                    }

                    foreach($data as $k => $v){
                        $uploadFile = File::formatFile($v);
                        if($uploadFile instanceof  UploadedFile){
                            $field->name = $field->getStoreName($uploadFile);
                            $path = $field->upload($uploadFile);
                            return response()->json(['classSelector' => $field->getElementClassSelector(), 'id' => $path]);
                        }
                    }
                }
            });

            self::$injectSubmittedCb = true;
        }
    }

    /**
     * Upload file and delete original file.
     *
     * @param UploadedFile $file
     *
     * @return mixed
     */
    protected function upload(UploadedFile $file)
    {
        $this->renameIfExists($file);

        if (!is_null($this->storage_permission)) {
            return $this->storage->putFileAs($this->getDirectory(), $file, $this->name, $this->storage_permission);
        }

        return $this->storage->putFileAs($this->getDirectory(), $file, $this->name);
    }


    /**
     * If name already exists, rename it.
     *
     * @param $file
     *
     * @return void
     */
    public function renameIfExists(UploadedFile $file)
    {
        if ($this->storage->exists("{$this->getDirectory()}/$this->name")) {
            $this->name = $this->generateUniqueName($file);
        }
    }

    protected function existsRequiredRule()
    {
        if (!is_string($this->rules)) {
            return false;
        }

        $pattern = "/required[^\|]?(\||$)/";
        return preg_match($pattern, $this->rules) ? true : false;
    }


    public function mineType($mType){
        $this->options(['acceptedFileTypes' => (array)$mType]);
        if(is_array($mType)){
            $mType = implode(',', $mType);
        }
        $this->rules('mimetypes:' . $mType);
        return $this;
    }

    //file size unit KB
    public function size($size){
        $this->options(['maxFileSize' => $size . "KB"]);
        $this->rules('max:' . $size);;
        return $this;
    }

    protected function getUploadValidator(array $input){
        if(!array_key_exists(File::FILE_UPLOAD_FLAG, $input)){
            return false;
        }

        $rules = $attributes = [];

        if ($this->validator) {
            return $this->validator->call($this, $input);
        }


        if(!array_key_exists($this->column, $input)){
            return false;
        }

        $file = array_get($input, $this->column);
        if(array_has($input, $this->column) && is_array($file)){
            $input[$this->column] = $file[0];
        }

        if (!$fieldRules = $this->getRules()) {
            return false;
        }

        $rules[$this->column] = $fieldRules;

        $validator = Validator::make($input, $rules, $this->validationMessages, $attributes);

        $validator->after(function($validator){
            if(!$validator->errors()->isEmpty()){
                $content = view($this->getView(), $this->variables())->withErrors($validator->messages());
                $this->uploadValidatorResponse = response($content, 500)->send();
            }
        });

        return $validator;
    }


    /**
     * {@inheritdoc}
     */
    public function getValidator(array $input)
    {

        $attributes = [];

        $attributes[$this->column] = $this->label;

        if(!array_key_exists(File::FILE_UPLOAD_FLAG, $input)){
            /*
         * Make input data validatable if the column data is `null`.
         */
            if (array_has($input, $this->column) && is_null(array_get($input, $this->column))) {
                $input[$this->column] = '';
            }

            //form editable mode, no validation
            if(array_key_exists('_editable', $input)){
                return false;
            }

            return $this->existsRequiredRule() ? Validator::make($input, [$this->column => 'required'], $this->validationMessages, $attributes)  : false;

        }

        return false;
    }

    public static  function getFieldFromInput($input, $form){
        foreach($input as $column => $value){
            $file = self::formatFile($value);

            if($file instanceof UploadedFile){
                return $form->builder()->fields()->first(function($field, $key) use ($column){
                    return $field->column == $column;
                });
            }
        }
    }

    public static function formatFile($file){
        if(is_array($file)){
            return $file[0];
        }
        else{
            return $file;
        }
    }


    public function multiple(){
        $this->attribute('multiple', 'true');
        $this->setElementName($this->column . '[]');
        return $this;
    }

    public static function extendPluginJs($jses = []){
        $jsc = collect(self::$js);
        foreach((array)$jses as $js){
            $jsc->prepend($js);
        }
        self::$js = $jsc->unique()->all();
    }

    public static function extendPluginCss($csses = []){
        $cssc = collect(self::$css);
        foreach((array)$csses as $css){
            $cssc->prepend($css);
        }
        self::$css = $cssc->unique()->all();
    }

    public static function extendPlugin($plugins = []){
        $pluginc = collect(self::$plugins);
        foreach((array)$plugins as $plugin){
            $pluginc->push($plugin);
        }
        self::$plugins = $pluginc->unique()->all();
    }

    public function storage_permission($permission)
    {
        $this->storage_permission = $permission;

        return $this;
    }

    protected function renderMethod(){
        if($this->form->builder()->isMode(Form\Builder::MODE_EDIT)){
            return <<<EOT
formdata.append('_method', 'put');
EOT;
        }
    }


    public function render(){

        $this->setupDefaultOptions();

        $filepondFiles = [];
        foreach((array)old($this->column, $this->value()) as $file){
            if($file){
                $filepondFile['source'] = $file;
                $filepondFile['options'] = ['type' => 'local'];
                $filepondFiles[] = $filepondFile;
            }
        }

        !empty($filepondFiles) && $this->options(['files' => $filepondFiles]);

        $options = json_encode($this->options);

        if(count(self::$plugins)>0 && self::$renderPlugin === false){

            $plugins = join(',', self::$plugins);

            $csrfToken = csrf_token();
            $uploadKey = self::FILE_UPLOAD_FLAG;

            $this->script = <<<EOT
function clearErrorLabel(classNameSelector){
    $( classNameSelector).siblings().each(function(){
        $(this).remove();
    });
    $(classNameSelector).parent().parent().removeClass('has-error');
}
$.fn.filepond.registerPlugin({$plugins});
$.fn.filepond.setDefaults({
    server: {
        process: {
            url: '{$this->form->builder()->getAction()}',
            headers: {
                'X-CSRF-TOKEN': '{$csrfToken}'
            },
            ondata: function(formdata){
                formdata.append('{$uploadKey}', 1);
                {$this->renderMethod()}
                return formdata;
            },
            onload:function(response){
                response = JSON.parse(response);
                clearErrorLabel(response.classSelector);
                return response.id;
            },
            onerror:function(response){
                var cls = $(response).find('input[type=file]').attr('class');
                if(cls){
                    clearErrorLabel('.' + cls);
                    $(response).find('input[type=file]').siblings().each(function(el){
                        $(this).insertBefore('.' + cls);
                    });              
                    $('.' + cls).parent().parent().addClass('has-error');
                }
                return false;
            }
        },
        load:'{$this->storage->url('')}'
   }
});
EOT;
            self::$renderPlugin = true;
        }


        $this->script .= <<<EOT

$("input{$this->getElementClassSelector()}").filepond({$options});
EOT;

        return parent::render();
    }
}