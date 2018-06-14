<?php
include_once "survey.php";
include_once "user.php";
include_once "answer.php";
/****
 * SurveyResponse class, v 0.2
 *  Rewritten to support the nky database. Also, dramatic changes
 *  to how dependent tables are being handled: When we read the 
 *  SurveyResponse table, we are also automatically bringing in all the
 *  Answers in.
 * That said, here are the major changes:
 *  - variable names changed to match nky database tables.
 *  - read() returns an array of all SurveyResponses. Kind of useless.
 *  - readBySurvey() returns an array of all SurveyResponses for a given
 *    SurveyID. Just as the readOne(), this includes an array of Answers.
 *  - readByUser() returns an array of all SurveyReponses for a given 
 *    UserID. Thus, you can see which surveys a given user had answered.
 *  - readOne() is a 'deep read', includes a nested array of Answers.
 *  - create() creates a SurveyReponse, and simply updates the SurveyResponseID
 *    on the SurveyResponse object.
 *  - update() updates a SurveyReponse and returns true or false.
 ****/
class Response{
  // database connection and table name
  private $conn;
  private $table_name = "SurveyResponse";
  
  // Object properties
  public $SurveyResponseID;
  public $SurveyID;
  public $UserID;
  public $User;
  public $Survey;
  public $Answers = array();
  public $CompletedAt;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    $User = new User($this->conn);
    // Select all query. Useless, really. Use one of the filtered options.
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              ORDER BY ".$this->table_name.".SurveyResponseID ASC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $SurveyResponseArray = array();
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        
        $User->UserID = $UserID;
        $User->readOne();

        $SurveyResponseItem = array(
          "SurveyResponseID" => $SurveyResponseID,
          "SurveyID" => $SurveyID,
          "UserID" => $UserID,
          "User" => $User,
          "CompletedAt" =>$CompletedAt,
          "ResponseStatus" => $ResponseStatus
        );
        $SurveyResponseArray[] = $SurveyResponseItem;
      }
    }
    
    return $SurveyResponseArray;
  }
  
  function readByResponseStatus(){
    $User = new User($this->conn);
    
    // Query to retrieve all records by response status.
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              JOIN Survey
              ON ".$this->table_name.".SurveyID=Survey.SurveyID
              WHERE ".$this->table_name.".ResponseStatus=:ResponseStatus
              AND Survey.Status=:Status
              ORDER BY ".$this->table_name.".SurveyResponseID ASC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Sanitize values
    $this->ResponseStatus=htmlspecialchars(strip_tags($this->ResponseStatus));
    $this->Survey->Status=htmlspecialchars(strip_tags($this->Survey->Status));
    
    // bind new values
    $stmt->bindParam(':ResponseStatus', $this->ResponseStatus);    
    $stmt->bindParam(':Status', $this->Survey->Status);    
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $SurveyResponseArray = array();
    $SurveyResponseArray[] = array("resultType"=>"ByResponsesAndStatus-".$this->ResponseStatus."-".$this->Survey->Status);

    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        
        // Retrieve a User for this response.
        $User->UserID = $UserID;
        $User->readOne();
        
        $Survey = new Survey($this->conn);
        $Survey->SurveyID = $SurveyID;
        $Survey->readOne();    // Select all query. Useless, really. Use one of the filtered options.
        $SurveyResponseItem = array(
          "SurveyResponseID" => $SurveyResponseID,
          "SurveyID" => $SurveyID,
          "UserID" => $UserID,
          "User" => $User,
          "Survey" => $Survey,
          "CompletedAt" =>$CompletedAt,
          "ResponseStatus" => $ResponseStatus
        );

        $SurveyResponseArray[] = $SurveyResponseItem;
      }
    }
    
    return $SurveyResponseArray;
  }
  
  function readBySurvey(){
    // Select all responses to a given survey query
    $Answer = new Answer($this->conn);
    $User = new User($this->conn);
    
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".SurveyID = :SurveyID
              ORDER BY ".$this->table_name.".CompletedAt DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Sanitize values
    $this->SurveyID=htmlspecialchars(strip_tags($this->SurveyID));
    
    // bind new values
    $stmt->bindParam(':SurveyID', $this->SurveyID);    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $SurveyResponseArray = array();
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
          // Also, given that this a single SurveyResponse item, we
          //  will include the full-depth object: we include the
          //  Answer array for this SurveyResponse. Remember, we
          //  changed the Answer class to return an array of Answer
          //  objects when we read that, so we can simply stick that
          //  right into our SurveyResponse.
          $Answer->SurveyResponseID = $SurveyResponseID;
          $User->UserID = $UserID;
          $User->readOne();
        
        $SurveyResponseItem = array(
          "SurveyResponseID" => $SurveyResponseID,
          "SurveyID" => $SurveyID,
          "CompletedAt" =>$CompletedAt,
          "Answers" => $Answer->read()
        );
        $SurveyResponseArray[] = $SurveyResponseItem;
      }
    }
    
    return $SurveyResponseArray;
  }
  
  function readByUser(){
    $User = new User($this->conn);
    
    // Select all query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".UserID = :UserID
              ORDER BY ".$this->table_name.".CompletedAt DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Sanitize values
    $this->UserID=htmlspecialchars(strip_tags($this->UserID));
    
    // bind new values
    $stmt->bindParam(':UserID', $this->UserID);  
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    // And get the user record...
    $User->UserID = $this->UserID;
    $User->readOne();
    
    $SurveyResponseArray = array();
    $SurveyResponseArray[] = $User;
    
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        $SurveyResponseItem = array(
          "SurveyResponseID" => $SurveyResponseID,
          "SurveyID" => $SurveyID,
          "UserID" => $UserID,
          "User" => $User,
          "CompletedAt" =>$CompletedAt
        );
        $SurveyResponseArray[] = $SurveyResponseItem;
      }
    }
    
    return $SurveyResponseArray;
  }
  function readByUserAndSurvey(){
    $Survey = new Survey($this->conn);
    $Answers = new Answer($this->conn);
    
    // Select all query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              WHERE ".$this->table_name.".UserID = :UserID
              AND ".$this->table_name.".SurveyID = :SurveyID
              LIMIT 0,1";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Sanitize values
    $this->UserID=htmlspecialchars(strip_tags($this->UserID));
    $this->SurveyID=htmlspecialchars(strip_tags($this->SurveyID));
    
    // bind new values
    $stmt->bindParam(':UserID', $this->UserID);  
    $stmt->bindParam(':SurveyID', $this->SurveyID);  
    
    // Execute query
    $stmt->execute();
    
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
      extract($row);
      
      $Survey->SurveyID = $this->SurveyID;
      $Survey->readOne();
      $Answers->SurveyResponseID = $SurveyResponseID;
      

      $this->SurveyResponseID = $SurveyResponseID;
      $this->CompletedAt = $CompletedAt;
      $this->Survey = $Survey;
      $this->ResponseStatus = $ResponseStatus;
      $this->Answers = $Answers->read();
      
      
      return true;
    } else {
      return false;
    }
  }
  
  // fetch a single survey
  function readOne(){
      // We'll need an answer object at the end of this...
      $Answer = new Answer($this->conn);
      $User = new User($this->conn);
        
      // query to read single record    
      $query = "SELECT ".$this->table_name.".*
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".SurveyResponseID = ? LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->SurveyResponseID);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $User->UserID = $row['UserID'];
      $User->readOne();

      // set values to object properties
      $this->UserID = $row['UserID'];
      $this->SurveyID = $row['SurveyID'];
      $this->CompletedAt = $row['CompletedAt'];
      $this->User = $User;
      // Also, given that this a single SurveyResponse item, we
      //  will include the full-depth object: we include the
      //  Answer array for this SurveyResponse. Remember, we
      //  changed the Answer class to return an array of Answer
      //  objects when we read that, so we can simply stick that
      //  right into our SurveyResponse.
      $Answer->SurveyResponseID = $this->SurveyResponseID;
      $this->Answers = $Answer->read();
  }

  // create product
  function create(){
    $Survey = new Survey($this->conn);

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  UserID=:UserID,
                  SurveyID=:SurveyID,
                  ResponseStatus='incomplete'";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->UserID=htmlspecialchars(strip_tags($this->UserID));
      $this->SurveyID=htmlspecialchars(strip_tags($this->SurveyID));

      // bind values
      $stmt->bindParam(':UserID', $this->UserID);
      $stmt->bindParam(':SurveyID', $this->SurveyID);

      // execute query
      if($stmt->execute()){
        $this->SurveyResponseID = $this->conn->lastInsertId();
        $this->readOne();
        
        $Survey->SurveyID = $this->SurveyID;
        $Survey->readOne();
                
        $this->Survey = $Survey;
        $this->ResponseStatus = 'incomplete';
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
                  UserID=:UserID,
                  SurveyID=:SurveyID,
                  CompletedAt=:CompletedAt
              WHERE
                  SurveyResponseID = :SurveyResponseID";

      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->UserID=htmlspecialchars(strip_tags($this->UserID));
      $this->SurveyID=htmlspecialchars(strip_tags($this->SurveyID));
      $this->CompletedAt=htmlspecialchars(strip_tags($this->CompletedAt));
    
      $this->SurveyResponseID=htmlspecialchars(strip_tags($this->SurveyResponseID));

      // bind values
      $stmt->bindParam(':UserID', $this->UserID);
      $stmt->bindParam(':SurveyID', $this->SurveyID);
      $stmt->bindParam(':CompletedAt', $this->CompletedAt);
    
      $stmt->bindParam(':SurveyResponseID', $this->SurveyResponseID);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }
}
?>