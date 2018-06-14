<?php
class AnswerOption{
  // database connection and table name
  private $conn;
  private $table_name = "answerOptions";
  
  // Object properties
  public $id;
  public $questionId;
  public $answer;
  public $createdDate;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all related to a given question query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE questionId = :questionId 
              ORDER BY ".$this->table_name.".id ASC";
    
    $this->questionId = htmlspecialchars(strip_tags($this->questionId));
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":questionId", $this->questionId);
    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }

  // fetch a single answerOption
  function readOne(){

    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".id=?
              LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->id);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      $this->questionId = $row['questionId'];
      $this->answer = $row['answer'];
      $this->createdDate = $row['createdDate'];
  }
  
  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  questionId=:questionId,
                  answer=:answer,
                  createdDate=:createdDate";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->questionId=htmlspecialchars(strip_tags($this->questionId));
      $this->answer=htmlspecialchars(strip_tags($this->answer));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':questionId', $this->questionId);
      $stmt->bindParam(':answer', $this->answer);
      $stmt->bindParam(':createdDate', $this->createdDate);

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
                  questionId=:questionId,
                  answer=:answer,
                  createdDate=:createdDate
              WHERE
                  id=:id";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->questionId=htmlspecialchars(strip_tags($this->questionId));
      $this->answer=htmlspecialchars(strip_tags($this->answer));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':questionId', $this->questionId);
      $stmt->bindParam(':answer', $this->answer);
      $stmt->bindParam(':createdDate', $this->createdDate);
   
      $stmt->bindParam(':id', $this->id);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }
  
  function delete(){
    // Remove a single row from the answerOptions table
    $query = "DELETE FROM " . $this->table_name . " WHERE questionId=:questionId;";
    //Prepare the query
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":questionId", $this->questionId);
    
    if($stmt->execute()){
      return true;
    } else {
      return false;
    }
  }
}
?>