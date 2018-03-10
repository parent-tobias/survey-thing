<?php
class Response{
  // database connection and table name
  private $conn;
  private $table_name = "responses";
  
  // Object properties
  public $id;
  public $usersId;
  public $surveysId;
  public $submittedDate;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query. Useless, really. Use one of the filtered options.
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              ORDER BY ".$this->table_name.".id ASC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }
  
  function readBySurvey(){
    // Select all responses to a given survey query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".surveysId = :surveysId
              ORDER BY ".$this->table_name.".submittedDate DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Sanitize values
    $this->surveysId=htmlspecialchars(strip_tags($this->surveysId));
    
    // bind new values
    $stmt->bindParam(':surveysId', $this->surveysId);    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }
  
  function readByUser(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".usersId = :usersId
              ORDER BY ".$this->table_name.".submittedDate DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Sanitize values
    $this->usersId=htmlspecialchars(strip_tags($this->usersId));
    
    // bind new values
    $stmt->bindParam(':usersId', $this->usersId);  
    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }
  
  // fetch a single survey
  function readOne(){

      // query to read single record
      $query = "SELECT ".$this->table_name.".*
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".id = ? LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->id);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      $this->usersId = $row['usersId'];
      $this->surveysId = $row['surveysId'];
      $this->submittedDate = $row['submittedDate'];
  }

  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  usersId=:usersId,
                  surveysId=:surveysId,
                  submittedDate=:submittedDate";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->usersId=htmlspecialchars(strip_tags($this->usersId));
      $this->surveysId=htmlspecialchars(strip_tags($this->surveysId));
      $this->submittedDate=htmlspecialchars(strip_tags($this->submittedDate));

      // bind values
      $stmt->bindParam(':usersId', $this->usersId);
      $stmt->bindParam(':surveyId', $this->surveyId);
      $stmt->bindParam(':submittedDate', $this->submittedDate);

      // execute query
      if($stmt->execute()){
        $this->id = $this->conn->lastInsertId();
        $this->readOne();
        return true;
      }

      return false;

  }

  // update the product
  function update(){

      // update query
      $query = "UPDATE
                  " . $this->table_name . "
              SET
                  usersId=:usersId,
                  surveysId=:surveysId,
                  submittedDate=:submittedDate
              WHERE
                  id = :id";

      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->usersId=htmlspecialchars(strip_tags($this->usersId));
      $this->surveysId=htmlspecialchars(strip_tags($this->surveysId));
      $this->submittedDate=htmlspecialchars(strip_tags($this->submittedDate));
    
      $this->id=htmlspecialchars(strip_tags($this->id));

      // bind values
      $stmt->bindParam(':usersId', $this->usersId);
      $stmt->bindParam(':surveyId', $this->surveyId);
      $stmt->bindParam(':submittedDate', $this->submittedDate);
    
      $stmt->bindParam(':id', $this->id);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }
}
?>