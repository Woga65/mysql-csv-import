<?php

class Csv {

    private $fileName;
    private $fileHandle;
    private $separator;
    private $enclosure;
    protected $headerRow;
    protected $csvFilePointer = 0;

    public function __construct($fileName, $separator = ";", $enclosure = '"') 
    {
        $this->fileName = $fileName;
        $this->fileHandle = null;
        $this->separator = $separator;
        $this->enclosure = $enclosure;
        $this->headerRow = [];
        if ($this->invalidParameters()) {
            exit();
        }
    }


    protected function open() {
        if (!$this->emptyFileName() && !$this->invalidFileName()) {
            try {
                $this->fileHandle = fopen('../../data/' . $this->fileName . '.csv', "r");
                if (!$this->fileHandle) {
                    throw new Exception("CSV file open failed!");
                }
            } catch ( Exception $e ) {
                $this->fileHandle = null;
            }
        }
        $this->csvFilePointer = 0;
        return $this->fileHandle ? true : false;
    }


    protected function close() {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
        $this->fileHandle = null;
        return true;
    }


    protected function eof() {
        if (!$this->fileHandle) {
            return false;
        }
        return feof($this->fileHandle);
    }


    protected function readHeaderRow() {
        if (!$this->fileHandle) {
            return false;
        }
        $this->skipBom();
        if (feof($this->fileHandle)) {
            return false;
        }
        return $this->headerRow = fgetcsv($this->fileHandle, 65536, $this->separator, $this->enclosure);
    }


    protected function readNextRow() {
        if (!$this->fileHandle) {
            return false;
        }
        if (feof($this->fileHandle)) {
            return false;
        }
        $nextRow = fgetcsv($this->fileHandle, 65536, $this->separator, $this->enclosure);
        $this->csvFilePointer = ftell($this->fileHandle);
        return $nextRow;
    }



    /* if $offset is > 0, moves file pointer to $offset 
     * returns false if something went wrong, otherwise true */
    protected function skipTo($offset) {
        $skip = $offset && is_numeric($offset) ? true : false;
        return $skip ? (fseek($this->fileHandle, $offset, SEEK_SET) !== -1) : true;
    }


    protected function getFileSize() {
        return fstat($this->fileHandle)['size'];
    }


    /* skip the Byte Order Mark, if present */
    private function skipBom() {
        $bom = "\xef\xbb\xbf";
        if (fgets($this->fileHandle, 4) !== $bom) {
            rewind($this->fileHandle);
        }
    }


    private function invalidParameters() {
        if ($this->invalidFileName() || $this->emptyFileName()) {
            echo JsonHttp::errResp("invalid file name", ["fields" => ["inputfile"]]);   // no or invalid file name given
            return true;
        }
        if (strlen($this->separator) !== 1 && strlen($this->enclosure) !== 1) {
            echo JsonHttp::errResp("one character only", ["fields" => ["separator", "enclosure"]]);
            return true;
        }
        if (strlen($this->separator) !== 1) {
            echo JsonHttp::errResp("invalid separator", ["fields" => ["separator"]]);
            return true;
        }
        if (strlen($this->enclosure) !== 1) {
            echo JsonHttp::errResp("invalid enclosure", ["fields" => ["enclosure"]]);
            return true;
        }
        return false;
    }


    private function emptyFileName() {
        return empty($this->fileName);        
    }


    private function invalidFileName() {
        return !preg_match("/^[a-zA-Z0-9._-]*$/", $this->fileName);       
    }
}