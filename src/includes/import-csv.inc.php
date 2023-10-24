<?php
require_once "../classes/autoloader.class.php";
Autoloader::register();


switch($_SERVER['REQUEST_METHOD']) {

    case("OPTIONS"):                                        // Allow preflighting to take place.
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Allow-Headers: content-type");
        exit;


    case("POST"):                                           // Perform csv
        // grab parameters
        $json = file_get_contents('php://input');
        $params = json_decode($json);

        if ($params) {
            $inputFile = $params->inputfile;
            $separator = $params->separator;
            $enclosure = $params->enclosure;
            $startImport = $params->start && strtolower($params->start) === 'yes' ? true : false;
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . " 500 Internal Server Error", true, 500);  // no valid json
            exit();
        }

        // instantiate csv class
        $csv = new ImportCsv($inputFile, $separator, $enclosure);

        // return csv header and the database's data columns
        if (!$startImport) {
            $header = $csv->getHeaderRow();
            if (empty($header)) {
                echo JsonHttp::errResp("invalid csv", ["fields" => ["inputfile"]]);     // no or invalid csv header
                exit();
            }
            $contacts = new Contacts();
            $dataColumns = $contacts->getDataColumns();
            echo JsonHttp::okResp([
                "data" => [
                    "source" => $header,
                    "target" => $dataColumns,
                ]
            ]);
            exit();
        }

        // process data import
        $rowCount = $csv->importCsvData($params);
        echo JsonHttp::okResp([ "data" => $rowCount ]);

        // end of csv processing
        exit();


    default:    //Reject any non POST or OPTIONS requests.
        header("Allow: POST", true, 405);
        exit;
} 