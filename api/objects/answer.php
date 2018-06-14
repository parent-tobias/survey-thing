<?php
/****
 * Answer class, v 0.2
 *  Rewritten to support the nky database. Primarily, changes to
 *    variable names. Also, all database interactions have been
 *    migrated here. This means that we either return a populated
 *    Answer, or we return an array of Answers. Thus, when this is
 *    called, there is no need to worry about the database access
 *    mechanism.
 ****/
class Answer{
  // database connection and table name
  private $conn;
  private $table_name = "Answer";
  
  // Object properties
  public $AnswerID;
  public $SurveyResponseID;
  public $AnswerChoiceID;
  public $AnswerText;
  // At this point, we aren't allowing user comments on questions.
  //  Thus, we don't NEED the comments field. Is this a thing we'll
  //  want to allow for?
  public $Comment;
  public $CompletedAt;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // "Select all rows for the given survey response" query
    $query = "SELECT ".$this->table_name.".*, AnswerChoices.answer AS AnswerText
              FROM ".$this->table_name."
              JOIN AnswerChoices
              ON ".$this->table_name.".AnswerChoiceID=AnswerChoices.AnswerChoiceID
              WHERE SurveyResponseID=:SurveyResponseID
              ORDER BY ".$this->table_name.".AnswerID ASC";

    
    $this->SurveyResponseID = htmlspecialchars(strip_tags($this->SurveyResponseID));

    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":SurveyResponseID", $this->SurveyResponseID);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
        
    $AnswersArray = array();
    if($num>0){
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        $AnswerItem = array(
          "AnswerID" => $AnswerID,
          "SurveyResponseID" => $SurveyResponseID,
          "Comment" => html_entity_decode($Comment),
          "AnswerChoiceID" => $AnswerChoiceID,
          "AnswerText" => $AnswerText,
          "CompletedAt" => $CompletedAt
        );
        
        $AnswersArray[] = $AnswerItem;
      }
    }
    
    return $AnswersArray;
  }
  
  // Read a single answer
  function readOne(){

    $query = "SELECT ".$this->table_name.".*, AnswerChoices.answer as AnswerText 
              FROM ".$this->table_name."
              LEFT JOIN AnswerChoices 
              ON ".$this->table_name.".AnswerChoiceID=AnswerChoices.AnswerChoiceID 
              WHERE ".$this->table_name.".AnswerID=?
              LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->AnswerID);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      $this->SurveyResponseID = $row['SurveyResponseID'];
      $this->Comment = $row['Comment'];
      $this->AnswerChoiceID = $row['AnswerChoiceID'];
      $this->AnswerText = $row['AnswerText'];
      $this->CompletedAt = $row['CompletedAt'];  }

  // create an answer
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  SurveyResponseID=:SurveyResponseID,
                  AnswerChoiceID=:AnswerChoiceID, 
                  Comment=:Comment";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->SurveyResponseID=htmlspecialchars(strip_tags($this->SurveyResponseID));
      $this->AnswerChoiceID=htmlspecialchars(strip_tags($this->AnswerChoiceID));
      $this->Comment=htmlspecialchars(strip_tags($this->Comment));

      // bind values
      $stmt->bindParam(':SurveyResponseID', $this->SurveyResponseID);
      $stmt->bindParam(':AnswerChoiceID', $this->AnswerChoiceID);
      $stmt->bindParam(':Comment', $this->Comment);

      // execute query
      if($stmt->execute()){
        $this->AnswerID = $this->conn->lastInsertId();
        $this->readOne();
        return true;
      } else {
        return false;
      }


  }

  // update the product
  function update(){

      // update query
      $query = "UPDATE
                  " . $this->table_name . "
              SET
                  SurveyResponseID=:SurveyReponseID,
                  AnswerChoiceID=:AnswerChoiceID, 
                  Comment=:Comment
              WHERE
                  AnswerID = :AnswerID";

      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->SurveyResponseID=htmlspecialchars(strip_tags($this->SurveyResponseID));
      $this->AnswerChoiceID=htmlspecialchars(strip_tags($this->AnswerChoiceID));
      $this->Comment=htmlspecialchars(strip_tags($this->Comment));

      // bind values
      $stmt->bindParam(':SurveyReponseID', $this->SurveyReponseID);
      $stmt->bindParam(':AnswerChoiceID', $this->AnswerChoiceID);
      $stmt->bindParam(':Comment', $this->Comment);
    
      $stmt->bindParam(':AnswerID', $this->AnswerID);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }
  
  // delete an answer
  function delete(){
    // Remove a single row from the answer table
    $query = "DELETE FROM " . $this->table_name . " WHERE AnswerID=:AnswerID;";
    
    //Prepare the query
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":AnswerID", $this->AnswerID);

    if($stmt->execute()){
      return true;
    } else {
      return false;
    }
  }
}
?>