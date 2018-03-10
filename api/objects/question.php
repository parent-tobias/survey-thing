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
  public $createdDate;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*, questionTypes.typeName as questionType 
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".surveyId=:surveyId
              LEFT JOIN questionTypes 
              ON ".$this->table_name.".questionTypeId=questionTypes.id 
              ORDER BY ".$this->table_name.".id ASC";
    
    $this->surveyId = htmlspecialchars(strip_tags($this->surveyId));
    
    $stmt->bindParam(":surveyId", $this->surveyId);
          
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }

  // fetch a single survey
  function readOne(){

    $query = "SELECT ".$this->table_name.".*, questionTypes.typeName as questionType 
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".id=?
              LEFT JOIN questionTypes 
              ON ".$this->table_name.".questionTypeId=questionTypes.id 
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
                  surveyId=:surveyId,
                  questionTypeId=:questionTypeId,
                  questionText=:questionText,
                  comment=:comment,
                  createdDate=:createdDate
              WHERE
                  id = :id";

      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->surveyId=htmlspecialchars(strip_tags($this->surveyId));
      $this->questionTypeId=htmlspecialchars(strip_tags($this->questionTypeId));
      $this->questionText=htmlspecialchars(strip_tags($this->questionText));
      $this->comment=htmlspecialchars(strip_tags($this->comment));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));
    
      $this->id=htmlspecialchars(strip_tags($this->id));

      // bind values
      $stmt->bindParam(':surveyId', $this->surveyId);
      $stmt->bindParam(':questionTypeId', $this->questionTypeId);
      $stmt->bindParam(':questionText', $this->questionText);
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