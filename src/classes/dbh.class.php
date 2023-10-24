<?php

class Dbh {

    protected function connect() {
        try {
            $hostname = "localhost";
            $dbname   = "csv_test";
            $username = "root";
            $password = "";
            $dbh = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);
            return $dbh;
        } catch (PDOException $e) {
            echo JsonHttp::errResp("connection failed: " . $e->getMessage());
            die();
        }
    }

}