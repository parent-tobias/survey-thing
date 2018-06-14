<?php
class Answers{
  // database connection and table name
  private $conn;
  private $table_name = "answers";
  
  // Object properties
  public $id;
  public $responseId;
  public $answerOptionId;
  // At this point, we aren't allowing user comments on questions.
  //  Thus, we don't NEED the comments field. Is this a thing we'll
  //  want to allow for?
  public $comment;
  public $createdDate;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // "Select all rows for the given survey response" query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE responseId=:responseId
              ORDER BY ".$this->table_name.".id ASC";

    
    $this->responseId = htmlspecialchars(strip_tags($this->responseId));
    
    $stmt->bindParam(":responseId", $this->responseId);
      
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }

  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  responseId=:responseId,
                  answerOptionId=:answerOptionId, 
                  comment=:comment,
                  createdDate=:createdDate";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->responseId=htmlspecialchars(strip_tags($this->responseId));
      $this->answerOptionId=htmlspecialchars(strip_tags($this->answerOptionId));
      $this->comment=htmlspecialchars(strip_tags($this->comment));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':responseId', $this->responseId);
      $stmt->bindParam(':answerOptionId', $this->answerOptionId);
      $stmt->bindParam(':comment', $this->comment);
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
                  responseId=:responseId,
                  answerOptionId=:answerOptionId, 
                  comment=:comment,
                  createdDate=:createdDate
              WHERE
                  id = :id";

      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->responseId=htmlspecialchars(strip_tags($this->responseId));
      $this->answerOptionId=htmlspecialchars(strip_tags($this->answerOptionId));
      $this->comment=htmlspecialchars(strip_tags($this->comment));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':responseId', $this->responseId);
      $stmt->bindParam(':answerOptionId', $this->answerOptionId);
      $stmt->bindParam(':comment', $this->comment);
      $stmt->bindParam(':createdDate', $this->createdDate);
    
      $stmt->bindParam(':id', $this->id);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }
}
?>