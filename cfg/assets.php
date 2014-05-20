<?php

use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;

$base_path = dirname(__DIR__);
$bower_path = $base_path.'/bower_components';

return array(
    'jquery' => new FileAsset($bower_path.'/jquery/dist/jquery.js'),
    'jquery_ui' => new FileAsset($bower_path.'/jquery-ui/ui/jquery-ui.js'),
    'angularjs' => array(
        new FileAsset($bower_path.'/angular/angular.js'),
        new FileAsset($bower_path.'/angular-animate/angular-animate.js'),
    ),
    'angular_sortable' => array(
        'angularjs',
        'jquery_ui',
        new FileAsset($bower_path.'/angular-ui-sortable/sortable.js'),
    ),
    'blueimp_fileupload' => array(
        'angularjs',
        'jquery_ui',
        new FileAsset($bower_path.'/blueimp-load-image/js/load-image.min.js'),
        new FileAsset($bower_path.'/blueimp-canvas-to-blob/js/canvas-to-blob.js'),
        new FileAsset($bower_path.'/blueimp-file-upload/js/jquery.iframe-transport.js'),
        new FileAsset($bower_path.'/blueimp-file-upload/css/jquery.fileupload.css'),
        new FileAsset($bower_path.'/blueimp-file-upload/css/jquery.fileupload-noscript.css'),
        new FileAsset($bower_path.'/blueimp-file-upload/js/jquery.fileupload.js'),
        new FileAsset($bower_path.'/blueimp-file-upload/js/jquery.fileupload-process.js'),
        new FileAsset($bower_path.'/blueimp-file-upload/js/jquery.fileupload-image.js'),
        new FileAsset($bower_path.'/blueimp-file-upload/js/jquery.fileupload-validate.js'),
        new FileAsset($bower_path.'/blueimp-file-upload/js/jquery.fileupload-angular.js'),
    ),
    'bootstrap' => array(
        new HttpAsset('//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js'),
    ),

    'bootstrap_css' => array(
        new HttpAsset('//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css'),
        new HttpAsset('//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css'),
    ),
    'blueimp_fileupload_css' => array(
        new FileAsset($bower_path.'/blueimp-file-upload/css/jquery.fileupload.css'),
        new FileAsset($bower_path.'/blueimp-file-upload/css/jquery.fileupload-noscript.css'),
    ),
);