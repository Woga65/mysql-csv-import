<?php

class Dbh {

    protected function connect() {
        try {
            $hostname = "localhost";
            $dbname   = "csv_test";
            $username = "root";
            $password = "";
            $options  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $dbh = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password, $options);
            return $dbh;
        } catch (PDOException $e) {
            echo JsonHttp::errResp("connection failed: " . $e->getMessage());
            die();
        }
    }

}