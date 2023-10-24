<?php

class ImportCsv extends Csv {

    private $search = array('ae', 'oe', 'ue', 'AE', 'OE', 'UE');
    private $replace = array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü');

    public function importCsvData($params)
    {
        $chunkSize = $params->chunksize ?? 0;
        $formatDate = property_exists($params, 'date') ? true : false;
        $maxRowsCsv = property_exists($params, 'inchunks') && is_numeric($chunkSize) ? (int)$chunkSize : 0;

        if (!$this->open()) {
            return ["rowsWritten" => 0, "rowsRead" => 0, "chunkSize" => $maxRowsCsv];
        }

        $headerRow = $this->readHeaderRow();

        if ($headerRow === false) {
            $this->close();
            return ["rowsWritten" => 0, "rowsRead" => 0, "chunkSize" => $maxRowsCsv];
        }

        if (!$this->skipTo($params->offset)) {
            $this->close();
            return ["rowsWritten" => 0, "rowsRead" => 0, "chunkSize" => $maxRowsCsv];    
        }
        $fileSize = $this->getFileSize();

        $contacts = new Contacts();
        $dataColumns = $contacts->getDataColumns();

        $rowCountCsv = 0;
        $rowCount = 0;

        while (!$this->eof() && ($maxRowsCsv === 0 || $rowCountCsv < $maxRowsCsv)) {

            if (($row = $this->readNextRow()) === false) break;

            $rowCountCsv++;

            $rowAssoc = $this->toAssoc($row, $params);
            $rowAssoc = $this->toDbRow($rowAssoc, $params, $dataColumns);
 
            if ($formatDate === true) {
                $rowAssoc['birthdate'] = $this->dateToYearMonthDay($rowAssoc['birthdate']);
            }

            $rowsRead = $contacts->contactExists($rowAssoc);
            
            if ($rowsRead === 1) {
                $rowCount += $contacts->updateContact($rowAssoc);
            }
            if ($rowsRead === 0) {
                $rowCount += $contacts->createContact($rowAssoc);
            }
            if ($rowsRead > 1) {
                $rowCount += 0;     // the handling or logging of ambiguous records goes here
            }
        }

        $this->close();
        return [
            "rowsWritten" => $rowCount,
            "rowsRead" => $rowCountCsv,
            "csvOffset" => $this->csvFilePointer,
            "chunkSize" => $maxRowsCsv,
            "fileSize" => $fileSize,
        ];
    }


    public function getHeaderRow() {
        if (!$this->open()) {
            return [];
        }
        $row = $this->readHeaderRow();
        $this->headerRow = $row !== false ? $row : [];
        $this->close();
        return $this->headerRow;
    }


    private function toAssoc($row, $params) {
        $rowAssoc = array();
        $replaceUmlaut = property_exists($params, 'umlaut') ? true : false;

        foreach ($row as $index => $column) {
            $rowAssoc[$this->headerRow[$index]] = $replaceUmlaut === true ? $this->replaceUmlaut($column) : $column;
        }
        return $rowAssoc;
    }


    private function toDbRow($rowAssoc, $params, $dataColumns) {
        $dbRow = array('id' => '');
        foreach ($dataColumns as $index) {
            $value = $params->$index;
            $dbRow[$index] = $rowAssoc[$value] ?? '';
        }
        return $dbRow;
    }


    private function dateToYearMonthDay($localDate) {
        if ($localDate) {
            $d = strtotime($localDate);
            return $d === false ? $localDate : date('Y-m-d', $d);
        }
        return $localDate;
    }


    private function replaceUmlaut($column) {
        return str_replace($this->search, $this->replace, $column);
    }

}