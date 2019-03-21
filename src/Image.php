<?php

namespace Qs\La\Filepond;

class Image extends File{

    protected $rules = 'image';

    protected static $imagePlugins = [
        'FilePondPluginImagePreview'
    ];

    public function __construct($column, array $arguments = [])
    {
        parent::__construct($column, $arguments);

        self::extendPlugin(self::$imagePlugins);

        $this->mineType('image/*');
    }

}