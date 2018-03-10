<?php
class Database{
  // credentials
  private $host = "localhost";
  private $db_name = "surveybuilder";
  private $username = "root";
  private $password = "survey-madness";
  public $conn;
  
  // Get the database connection
  public function getConnection(){
    $this->conn = null;
    
    try{
      $this->conn = new PDO("mysql:host=".$this->host.";dbname=".$this->db_name, $this->username, $this->password);
      $this->conn->exec("set name utf8");
    } catch(PDOException $exception){
      echo "Connection error: ".$exception->getMessage();
    }
    
    return $this->conn;
  }
}
?>