<?php


class Contacts extends Dbh {

    private $dataFields;
    private $rowCount;
    private $readRows;

    /* criteria to determine whether a record exists and    * 
     * needs updating or a new record needs to be added.    *
     * for example: $findBy = ['last_name', 'first_name',]; */
    private $findBy = ['email',];


    public function __construct()
    {
        $stmt = $this
            ->connect()
            ->prepare('SHOW COLUMNS FROM contacts;');
        if (!$stmt->execute()) {
            $stmt = null;
            echo JsonHttp::errResp("stmtfailed: Get Columns");
            exit();
        }
        $dataFields = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->dataFields = array();
        foreach ($dataFields as $field) {
            $this->dataFields[] = $field['Field'];
        } 
    }


    // does it make sense to check for valid email in imported data?
    private function invalidEmail($email) {
        return !filter_var($email, FILTER_VALIDATE_EMAIL);
    }


    public function contactExists($rowAssoc)
    {
        $sql = 'SELECT id, last_name FROM contacts WHERE ';
        $values = array();
        foreach ($this->findBy as $key => $colName) {
            if ($rowAssoc[$colName] !== '' && !is_null($rowAssoc[$colName])) {
                $sql .= $key === 0 ? $colName . ' = ? ' : 'AND (' . $colName . ' IS NULL OR ' . $colName . ' = ?) ';
                $values[] = $rowAssoc[$colName];
            }
        }
        $sql .= ';';

        $stmt = $this
            ->connect()
            ->prepare($sql);

        if (!$stmt->execute($values)) {
            $stmt = null;
            echo JsonHttp::errResp("stmtfailed: Check exists");
            exit();
        }

        $this->rowCount = $stmt->rowCount();
        $this->readRows = $this->rowCount > 0 ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return $this->rowCount;
    }


    public function updateContact($rowAssoc)
    {
        if ((empty($rowAssoc['id']) || is_null($rowAssoc['id'])) && $this->rowCount !== 1) {    // if multiple records have been
            return 0;                                                                           // previously read and no ID is
        }                                                                                       // provided, do nothing.

        $id = empty($rowAssoc['id']) || is_null($rowAssoc['id'])        // if no ID has been provided, update
            ? $this->readRows[0]['id']                                  // the previous read row else update
            : $rowAssoc['id'];                                          // the row with the provided ID. 

        $sql = 'UPDATE contacts SET ';
        $values = array();

        foreach ($rowAssoc as $index => $value) {
            if ($value !== '' && !is_null($value)) {
                $sql .= $index . ' = ?, ';
                $values[] = $value;
            }
        }

        $sql = substr($sql, 0, -2) . ' WHERE id = ' . $id . ';'; 
        $stmt = $this->connect()->prepare($sql);

        if ($values === [] || !$stmt->execute($values)) {
            $stmt = null;
            echo JsonHttp::errResp("stmtfailed: Update row");
            exit();
        }

        return $stmt->rowCount();
    }


    public function createContact($rowAssoc)
    {
        $sqlHead = 'INSERT INTO contacts (';
        $sqlTail = ') VALUES (';
        $values = array();
        foreach ($rowAssoc as $index => $value) {
            if ($value !== '' && !is_null($value)) {
                $sqlHead .= $index . ', ';
                $sqlTail .= '?, ';
                $values[] = $value;
            }
        }

        $sql = substr($sqlHead, 0, -2) . substr($sqlTail, 0, -2) . ');';
        $stmt = $this->connect()->prepare($sql);

        if ($values === [] || !$stmt->execute($values)) {
            $stmt = null;
            echo JsonHttp::errResp("stmtfailed: Insert row");
            exit();
        }

        return $stmt->rowCount();
    }


    public function getDataColumns()
    {
        return array_slice($this->dataFields, 1, sizeof($this->dataFields) - 3);
    }

}