<?php
/****
 * AnswerOption class, v 0.2
 *  Rewritten to support the nky database. Also, return values have
 *   been changed -- rather than returning the database handle, we
 *   take care of all database interaction here and return either
 *   the AnswerOption instance populated with values, or we return
 *   an array of AnswerOptions.
 ****/
class AnswerOption{
  // database connection and table name
  private $conn;
  private $table_name = "AnswerChoices";
  
  // Object properties
  public $AnswerChoiceID;
  public $QuestionID;
  public $answer;
  public $ResponseCount;
  public $CreatedAt;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all related to a given question query
    $query = "SELECT ".$this->table_name.".*, COUNT(Answer.AnswerID) AS ResponseCount
              FROM ".$this->table_name."
              LEFT JOIN Answer 
              ON ".$this->table_name.".AnswerChoiceID = Answer.AnswerChoiceID
              WHERE QuestionID = :QuestionID 
              GROUP BY ".$this->table_name.".AnswerChoiceID
              ORDER BY ".$this->table_name.".AnswerChoiceID ASC";
    
    $this->QuestionID = htmlspecialchars(strip_tags($this->QuestionID));
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":QuestionID", $this->QuestionID);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $AnswerChoicesArray = array();
    if($num>0) {
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        $AnswerChoiceItem = array(
          "AnswerChoiceID" => $AnswerChoiceID,
          "QuestionID" => $QuestionID,
          "answer" => html_entity_decode($answer),
          "ResponseCount" => $ResponseCount,
          "CreatedAt" => $CreatedAt
        );
        $AnswerChoicesArray[] = $AnswerChoiceItem;
      }

     }
   return $AnswerChoicesArray;
  }

  // fetch a single answerOption
  function readOne(){

    $query = "SELECT ".$this->table_name.".*, COUNT(Answer.AnswerID) AS ResponseCount
              FROM ".$this->table_name."
              JOIN Answer 
              ON ".$this->table_name.".AnswerChoiceID = Answer.AnswerChoiceID
              WHERE ".$this->table_name.".AnswerChoiceID=?
              LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->AnswerChoiceID);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      $this->QuestionID = $row['QuestionID'];
      $this->answer = $row['answer'];
      $this->ResponseCount = $row['ResponseCount'];
      $this->CreatedAt = $row['CreatedAt'];
  }
  
  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  QuestionId=:QuestionID,
                  answer=:answer,
                  CreatedAt=:CreatedAt";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->QuestionID=htmlspecialchars(strip_tags($this->QuestionID));
      $this->answer=htmlspecialchars(strip_tags($this->answer));
      $this->CreatedAt=htmlspecialchars(strip_tags($this->CreatedAt));

      // bind values
      $stmt->bindParam(':QuestionID', $this->QuestionID);
      $stmt->bindParam(':answer', $this->answer);
      $stmt->bindParam(':CreatedAt', $this->CreatedAt);

      // execute query
      if($stmt->execute()){
        $this->AnswerChoiceID = $this->conn->lastInsertId();
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
                  QuestionID=:QuestionID,
                  answer=:answer,
                  CreatedAt=:CreatedAt
              WHERE
                  AnswerChoiceID=:AnswerChoiceID";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->QuestionID=htmlspecialchars(strip_tags($this->QuestionID));
      $this->answer=htmlspecialchars(strip_tags($this->answer));
      $this->CreatedAt=htmlspecialchars(strip_tags($this->CreatedAt));

      // bind values
      $stmt->bindParam(':QuestionID', $this->QuestionID);
      $stmt->bindParam(':answer', $this->answer);
      $stmt->bindParam(':CreatedAt', $this->CreatedAt);
   
      $stmt->bindParam(':AnswerChoiceID', $this->AnswerChoiceID);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }
  
  function delete(){
    // Remove all rows associated with a given question from the
    //   answer choices table.
    $query = "DELETE FROM " . $this->table_name . " WHERE QuestionID=:QuestionID;";
    //Prepare the query
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":QuestionID", $this->QuestionID);
    
    if($stmt->execute()){
      return true;
    } else {
      return false;
    }
  }
}
?>