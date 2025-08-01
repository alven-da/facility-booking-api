<?php

namespace Provider;

use \PDO as PDO;

class MySQL {
  private static $instance = null;

  private $host = 'localhost';
  private $user = 'root';
  private $pass = '';
  private $dbname = '';

  private $conn = null;

  protected function __construct() {
    $conn_str = "mysql:host=$this->host;dbname=$this->dbname";
    
    $this->conn = new PDO($conn_str, $this->user, $this->pass);
    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }

  public static function getInstance(): MySQL {
    if (!isset(self::$instance)) {
        self::$instance = new MySQL();
    }

    return self::$instance;
  }

  public function getConnection() {
      return $this->conn;
  }
}

?>