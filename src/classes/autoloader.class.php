<?php

class Autoloader {
    private function __construct() {}

    public static function register() {
        spl_autoload_register(function($className) {
            switch ($className) {
                case('ImportCsv'):
                    $fileName = 'import-csv';
                    break;
                case('JsonHttp'):
                    $fileName = 'json-http';
                    break;
                case('ListDirectory'):
                    $fileName = 'list-directory';
                    break;
                default:
                    $fileName = strtolower($className);
            }
            include __DIR__ . "/$fileName.class.php";
        });
    }
}