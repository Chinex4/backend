<?php
class CreateDbTables
{
  private $connect;
  private $database;

  public function __construct($connect)
  {
    $this->connect = $connect;
    $this->database = new Database;
  }

  public function createTable($table, $regArray)
  {

    if ($this->database->check_Table($table, $this->connect) === true || $this->database->check_Table($table, $this->connect) === 'created') {
      $getiD = $this->database->getLastColumnName($this->connect, $table);
      if ($this->database->AlterTable($table, $this->connect, $regArray, $getiD) === true || $this->database->AlterTable($table, $this->connect, $regArray, $getiD) === NULL) {
        return true;
      } else {
        return false;
      }


    } elseif ($this->database->check_Table($table, $this->connect) === false) {
      return 'exits';

    }
  }
  public function createTableWithTypes($table, $regArray)
  {
    if ($this->database->check_Table($table, $this->connect) === true || $this->database->check_Table($table, $this->connect) === 'created') {
      $getiD = $this->database->getLastColumnName($this->connect, $table);
      if ($this->database->AlterTableWithTypes($table, $this->connect, $regArray, $getiD) === true || $this->database->AlterTableWithTypes($table, $this->connect, $regArray, $getiD) === NULL) {
        return true;
      } else {
        return false;
      }


    } elseif ($this->database->check_Table($table, $this->connect) === false) {
      return 'exits';

    }
  }
}