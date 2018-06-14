<?php
class Question{
  // database connection and table name
  private $conn;
  private $table_name = "questions";
  
  // Object properties
  public $id;
  public $surveyId;
  public $questionTypeId;
  public $questionType;
  public $questionText;
  public $comment;
  public $answerOptions = array();
  public $createdDate;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*, questionTypes.typeName as questionType 
              FROM ".$this->table_name."
              JOIN questionTypes 
              ON ".$this->table_name.".questionTypeId=questionTypes.id 
              WHERE ".$this->table_name.".surveyId=:surveyId
              ORDER BY ".$this->table_name.".id ASC";
    
    $this->surveyId = htmlspecialchars(strip_tags($this->surveyId));

    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":surveyId", $this->surveyId);
              
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }

  // fetch a single question
  function readOne(){

    $query = "SELECT ".$this->table_name.".*, questionTypes.typeName as questionType 
              FROM ".$this->table_name."
              LEFT JOIN questionTypes 
              ON ".$this->table_name.".questionTypeId=questionTypes.id 
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
      $this->surveyId = $row['surveyId'];
      $this->questionType = $row['questionType'];
      $this->questionText = $row['questionText'];
      $this->comment = $row['comment'];
      $this->createdDate = $row['createdDate'];
  }

  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  surveyId=:surveyId,
                  questionTypeId=:questionTypeId,
                  questionText=:questionText,
                  comment=:comment,
                  createdDate=:createdDate";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->surveyId=htmlspecialchars(strip_tags($this->surveyId));
      $this->questionTypeId=htmlspecialchars(strip_tags($this->questionTypeId));
      $this->questionText=htmlspecialchars(strip_tags($this->questionText));
      $this->comment=htmlspecialchars(strip_tags($this->comment));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':surveyId', $this->surveyId);
      $stmt->bindParam(':questionTypeId', $this->questionTypeId);
      $stmt->bindParam(':questionText', $this->questionText);
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
                  questionTypeId=:questionTypeId,
                  questionText=:questionText,
                  comment=:comment
              WHERE
                  id = :id";
      
      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->questionTypeId=htmlspecialchars(strip_tags($this->questionTypeId));
      $this->questionText=htmlspecialchars(strip_tags($this->questionText));
      $this->comment=htmlspecialchars(strip_tags($this->comment));
    
      $this->id=htmlspecialchars(strip_tags($this->id));

      // bind values
      $stmt->bindParam(':questionTypeId', $this->questionTypeId);
      $stmt->bindParam(':questionText', $this->questionText);
      $stmt->bindParam(':comment', $this->comment);
    
      $stmt->bindParam(':id', $this->id);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }

  
  function delete(){
    // Remove a single row from the answerOptions table
    $query = "DELETE FROM " . $this->table_name . " WHERE id=:id;";
    
    //Prepare the query
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":id", $this->id);
    
    if($stmt->execute()){
      return true;
    } else {
      return false;
    }
  }
}
?>