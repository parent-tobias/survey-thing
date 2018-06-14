<?php
include_once "answerOption.php";
/****
 * Question class, v 0.2
 *  Rewritten to support the nky database. Also, dramatic changes
 *  to how dependent tables are being handled: When we read the 
 *  question table, we are also automatically bringing in all the
 *  answer choices with the question. The thought is, they are a
 *  unit. The answers choices don't exist without the question, so
 *  they should travel together. The only exception, thus far, is 
 *  when the question is created. I can't see a way to 'require'
 *  that answer choices be created with the question.
 * That said, here are the major changes:
 *  - variable names changed to match nky database tables.
 *  - read() now returns an array of questions, rather than the
 *    mysql statement handle. Within each member of the array,
 *    there is a nested array of answer choices.
 *  - readOne() sets the values on the Question object, but it
 *    now includes the AnswerChoices array.
 *  - create() does the exact same thing, it simply creates a single
 *    entry in the question table, and returns the new question's id.
 *  - update() does the exact same thing, it updates the question.
 *  - delete() handles the nested answer choices, automatically 
 *    removing the answer choices related to the given question.
 ****/
class Question{
  // database connection and table name
  private $conn;
  private $table_name = "Question";
  
  // Object properties
  public $QuestionID;
  public $SurveyID;
  public $QuestionTypeID;
  public $questionType;
  public $Text;
  public $Comment;
  public $AnswerChoices = array();
  public $CreatedAt;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*, QuestionType.TypeName as QuestionType 
              FROM ".$this->table_name."
              JOIN QuestionType 
              ON ".$this->table_name.".QuestionTypeID=QuestionType.QuestionTypeID 
              WHERE ".$this->table_name.".SurveyID=:SurveyID
              ORDER BY ".$this->table_name.".QuestionID ASC";
    

    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    $this->SurveyID = htmlspecialchars(strip_tags($this->SurveyID));
    $stmt->bindParam(":SurveyID", $this->SurveyID);
              
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();

    $QuestionsArray = array();
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        $QuestionItem = array(
          "QuestionID" => $QuestionID,
          "SurveyID" => $SurveyID,
          "Text" => $Text,
          "QuestionTypeID" =>$QuestionTypeID,
          "QuestionType" => $QuestionType,
          "Comment" => html_entity_decode($Comment),
          "CreatedAt" => $CreatedAt
        );
        
        // Having extracted the question, we also need
        //  to extract its answer options.
        $AnswerChoices = new AnswerOption($this->conn);
        $AnswerChoices->QuestionID = $QuestionID;
        $QuestionItem["AnswerChoices"] = $AnswerChoices->read();

        $QuestionsArray[] = $QuestionItem;
      }
    }
    
    return $QuestionsArray;
  }

  // fetch a single question
  function readOne(){

    $query = "SELECT ".$this->table_name.".*, QuestionType.TypeName as QuestionType 
              FROM ".$this->table_name."
              LEFT JOIN QuestionType 
              ON ".$this->table_name.".QuestionTypeID=QuestionType.QuestionTypeID 
              WHERE ".$this->table_name.".QuestionID=?
              LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->QuestionID);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
      if (isset($row)){

        // set values to object properties
        $this->SurveyID = $row['SurveyID'];
        $this->QuestionTypeID  = $row['QuestionTypeID'];
        $this->QuestionType = $row['QuestionType'];
        $this->Text = $row['Text'];
        $this->Comment = $row['Comment'];
        $this->CreatedAt = $row['CreatedAt'];

        // Having extracted the question, we also need
        //  to extract its answer options.
        $AnswerChoices = new AnswerOption($this->conn);
        $AnswerChoices->QuestionID = $this->QuestionID;
        $this->AnswerChoices = $AnswerChoices->read();
        
        return true;
      } else {
        return false;
      }
  }

  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  SurveyID=:SurveyID,
                  QuestionTypeID=:QuestionTypeID,
                  Text=:Text,
                  Comment=:Comment,
                  CreatedAt=:CreatedAt";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->SurveyID=htmlspecialchars(strip_tags($this->SurveyID));
      $this->QuestionTypeID=htmlspecialchars(strip_tags($this->QuestionTypeID));
      $this->Text=htmlspecialchars(strip_tags($this->Text));
      $this->Comment=htmlspecialchars(strip_tags($this->Comment));
      $this->CreatedAt=htmlspecialchars(strip_tags($this->CreatedAt));

      // bind values
      $stmt->bindParam(':SurveyID', $this->SurveyID);
      $stmt->bindParam(':QuestionTypeID', $this->QuestionTypeID);
      $stmt->bindParam(':Text', $this->Text);
      $stmt->bindParam(':Comment', $this->Comment);
      $stmt->bindParam(':CreatedAt', $this->CreatedAt);

      // execute query
      if($stmt->execute()){
        $this->QuestionID = $this->conn->lastInsertId();
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
                  QuestionTypeID=:QuestionTypeID,
                  Text=:Text,
                  Comment=:Comment
              WHERE
                  QuestionID = :QuestionID";
      
      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->QuestionTypeID=htmlspecialchars(strip_tags($this->QuestionTypeID));
      $this->Text=htmlspecialchars(strip_tags($this->Text));
      $this->Comment=htmlspecialchars(strip_tags($this->Comment));
    
      $this->QuestionID=htmlspecialchars(strip_tags($this->QuestionID));

      // bind values
      $stmt->bindParam(':QuestionTypeID', $this->QuestionTypeID);
      $stmt->bindParam(':Text', $this->Text);
      $stmt->bindParam(':Comment', $this->Comment);
    
      $stmt->bindParam(':QuestionID', $this->QuestionID);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }

  
  function delete(){
    // Remove a single row from the answerOptions table
    $query = "DELETE FROM " . $this->table_name . " WHERE QuestionID=:QuestionID;";
    
    //Prepare the query
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":QuestionID", $this->QuestionID);
    $AnswerChoices = new AnswerOption($this->conn);
    $AnswerChoices->QuestionID = $this->QuestionID;
    
    if($AnswerChoices->delete()){
      if($stmt->execute()){
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
    
  }
}
?>