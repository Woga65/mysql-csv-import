<?php

class JsonHttp {
    private function __construct() {}

    public static function okResp(array $result) {
        self::jsonHeader();
        return json_encode(
            [
                "err" => "",
                "ok" => true,
            ] 
            + $result
        );
    }
    
    public static function errResp(string $err, array $result = []) {
        self::jsonHeader();
        return json_encode(
            [
                "err" => $err,
                "ok" => false,
            ] 
            + $result
        );
    }

    private static function jsonHeader() {
        header("Content-Type: application/json");
    }
    
}