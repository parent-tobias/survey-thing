<?php
include_once("user.php");
include_once("question.php");
/****
 * Survey class, v 0.2
 *  Rewritten to support the nky database. Also, dramatic changes
 *  to how dependent tables are being handled. The Question class
 *  now handles its own dependencies, and simply returns us an 
 *  array of question objects -- so rather than having to parse
 *  the Question table data twice, we simply include the Question.
 *
 * That said, here are the major changes:
 *  - variable names changed to match nky database tables.
 *  - read() now returns an array of Survey objects. It doesn't
 *    filter, so you're getting ALL Surveys. Might be mostly useless.
 *  - readByUser() ** NEW function, this gets all surveys created
 *    by a given User. This is not a deep Survey, doesn't include
 *    the nested Question array.
 *  - readByStatus() ** NEW function, this gets all surveys with
 *    a given status. So calling this would give users a pool of
 *    open surveys they could take.
 *  - readByUserAndStatus() *** NEW function, we can use this to
 *    get all of a given user's surveys, filtered by one of three
 *    status options: 'editing','open', or 'closed'.
 *  - readOne() returns the Survey object, with all related Questions
 *    imported. It also should include the User object, containing
 *    data on the survey's creator.
 *  - create() does the exact same thing, it simply creates a single
 *    entry in the Survey table, and returns the new Survey's id.
 *  - update() does the exact same thing, it updates the Survey.
 *  - changeStatus() sets the status for a given survey. Useful to
 *    toggle a survey to 'open', allowing users to start submitting it.
 *
 * *** Not currently implemented:
 *  - delete() functionality is not available for the surveys. 
 *
 ****/
class Survey{
  // database connection and table name
  private $conn;
  private $table_name = "Survey";
  
  // Object properties
  public $SurveyID;
  public $Title;
  public $Description;
  public $UserID;
  public $User;
  public $ResponseCount;
  public $Questions = array();
  public $StartDate;
  public $EndDate;
  public $CreatedAt;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT *, COUNT(SurveyResponse.SurveyResponseID) as ResponseCount
              FROM ".$this->table_name."
              JOIN SurveyResponse
              ON ".$this->table_name.".SurveyID = SurveyResponse.SurveyID
              ORDER BY ".$this->table_name.".CreatedAt DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
        
    $SurveyArray = array();
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        // In addition to the survey object, I should include the
        //   survey's creator User object.
        $Creator = new User($this->conn);
        $Creator->UserID = $UserID;
        $Creator->readOne();

        $SurveyItem = array(
          "SurveyID" => $SurveyID,
          "Title" => $Title,
          "Description" => $Description,
          "UserID" => $UserID,
          "ResponseCount" => $ResponseCount,
          "User" => array(
            "firstname" => $Creator->firstname,
            "lastname" => $Creator->lastname,
            "email" => $Creator->email,
            "CreatedAt" => $Creator->CreatedAt
          ),
          "StartDate" => $StartDate,
          "EndDate" => $EndDate,
          "CreatedAt" =>$CreatedAt,
          "Status" => $Status
        );
        
        $SurveyArray[] = $SurveyItem;
      }
    }
    
    return $SurveyArray;
  }

  function readByUser(){
    // Select all query
    $query = "SELECT *, COUNT(SurveyResponse.SurveyResponseID) as ResponseCount
              FROM ".$this->table_name."
              JOIN SurveyResponse
              ON ".$this->table_name.".SurveyID = SurveyResponse.SurveyID
              WHERE ".$this->table_name.".UserID=:UserID
              ORDER BY ".$this->table_name.".CreatedAt DESC";

    $this->UserID = htmlspecialchars(strip_tags($this->UserID));

    // Prepare query statement
    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":UserID", $this->UserID);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $SurveyArray = array();
    $SurveyArray[] = array("resultType"=>"AllSurveysByUser");
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        
        // In addition to the survey object, I should include the
        //   survey's creator User object.
        $Creator = new User($this->conn);
        $Creator->UserID = $UserID;
        $Creator->readOne();

        $SurveyItem = array(
          "SurveyID" => $SurveyID,
          "Title" => $Title,
          "Description" => $Description,
          "ResponseCount" => $ResponseCount,
          "UserID" => $UserID,
          "User" => array(
            "firstname" => $Creator->firstname,
            "lastname" => $Creator->lastname,
            "email" => $Creator->email,
            "CreatedAt" => $Creator->CreatedAt
          ),
          "StartDate" => $StartDate,
          "EndDate" => $EndDate,
          "CreatedAt" =>$CreatedAt,
          "Status" => $Status
        );
        
        $SurveyArray[] = $SurveyItem;
      }
    }
    
    return $SurveyArray;
  }

  function readByStatus(){
    // Select all query
    $query = "SELECT *, COUNT(SurveyResponse.SurveyResponseID) as ResponseCount
              FROM ".$this->table_name."
              JOIN SurveyResponse
              ON ".$this->table_name.".SurveyID = SurveyResponse.SurveyID
              WHERE ".$this->table_name.".Status=:Status
              ORDER BY ".$this->table_name.".CreatedAt DESC";

    $this->Status = htmlspecialchars(strip_tags($this->Status));

    // Prepare query statement
    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":Status", $this->Status);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $SurveyArray = array();
    $SurveyArray[] = array("resultType"=>"AllSurveysByStatus-".$this->Status);

    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        // In addition to the survey object, I should include the
        //   survey's creator User object.
        $Creator = new User($this->conn);
        $Creator->UserID = $UserID;
        $Creator->readOne();

        $SurveyItem = array(
          "SurveyID" => $SurveyID,
          "Title" => $Title,
          "Description" => $Description,
          "ResponseCount" => $ResponseCount,
          "UserID" => $UserID,
          "User" => array(
            "firstname" => $Creator->firstname,
            "lastname" => $Creator->lastname,
            "email" => $Creator->email,
            "CreatedAt" => $Creator->CreatedAt
          ),
          "StartDate" => $StartDate,
          "EndDate" => $EndDate,
          "CreatedAt" =>$CreatedAt,
          "Status" => $Status
        );
        
        
        $SurveyArray[] = $SurveyItem;
      }
    }
    
    return $SurveyArray;
  }

  function readByUserAndStatus(){
    // Select all query
    $query = "SELECT *, COUNT(SurveyResponse.SurveyResponseID) as ResponseCount
              FROM ".$this->table_name."
              JOIN SurveyResponse
              ON ".$this->table_name.".SurveyID = SurveyResponse.SurveyID
              WHERE ".$this->table_name.".UserID=:UserID
              AND  ".$this->table_name.".Status=:Status
              ORDER BY ".$this->table_name.".CreatedAt DESC";

    $this->UserID = htmlspecialchars(strip_tags($this->UserID));
    $this->Status = htmlspecialchars(strip_tags($this->Status));
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":UserID", $this->UserID);
    $stmt->bindParam(":Status", $this->Status);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $SurveyArray = array();
    $SurveyArray[] = array("resultType"=>"SurveysByUserAndStatus-".$this->Status);
    
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        // In addition to the survey object, I should include the
        //   survey's creator User object.
        $Creator = new User($this->conn);
        $Creator->UserID = $UserID;
        $Creator->readOne();

        $SurveyItem = array(
          "SurveyID" => $SurveyID,
          "Title" => $Title,
          "Description" => $Description,
          "ResponseCount" => $ResponseCount,
          "UserID" => $UserID,
          "User" => array(
            "firstname" => $Creator->firstname,
            "lastname" => $Creator->lastname,
            "email" => $Creator->email,
            "CreatedAt" => $Creator->CreatedAt
          ),
          "StartDate" => $StartDate,
          "EndDate" => $EndDate,
          "CreatedAt" =>$CreatedAt,
          "Status" => $Status
        );
        
        
        $SurveyArray[] = $SurveyItem;
      }
    }
    
    return $SurveyArray;
  }

  // fetch a single survey
  function readOne(){
      $Question = new Question($this->conn);
      $User = new User($this->conn);

      // query to read single record
      $query = "SELECT *, COUNT(SurveyResponse.SurveyResponseID) as ResponseCount
                FROM ".$this->table_name." 
                JOIN SurveyResponse
                ON ".$this->table_name.".SurveyID = SurveyResponse.SurveyID
                WHERE ".$this->table_name.".SurveyID = ? LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->SurveyID);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      
      $Question->SurveyID = $this->SurveyID;
      $Questions = $Question->read();
      
      $User->UserID = $row['UserID'];
      $User->readOne();

      // set values to object properties
      $this->Title = $row['Title'];
      $this->Description = $row['Description'];
      $this->UserID = $row['UserID'];
      $this->User = $User;
      $this->ResponseCount = $row['ResponseCount'];
      $this->StartDate = $row['StartDate'];
      $this->EndDate = $row['EndDate'];
      $this->Questions = $Questions;
      $this->CreatedAt = $row['CreatedAt'];
      $this->Status = $row['Status'];
  }

  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  Title=:Title,
                  Description=:Description,
                  UserID=:UserID,
                  StartDate=:StartDate,
                  EndDate=:EndDate,
                  Status='editing'";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->Title=htmlspecialchars(strip_tags($this->Title));
      $this->Description=htmlspecialchars(strip_tags($this->Description));
      $this->UserID=htmlspecialchars(strip_tags($this->UserID));
      $this->StartDate=htmlspecialchars(strip_tags($this->StartDate));
      $this->EndDate=htmlspecialchars(strip_tags($this->EndDate));

      // bind values
      $stmt->bindParam(':Title', $this->Title);
      $stmt->bindParam(':Description', $this->Description);
      $stmt->bindParam(':UserID', $this->UserID);
      $stmt->bindParam(':StartDate', $this->StartDate);
      $stmt->bindParam(':EndDate', $this->EndDate);

      // execute query
      if($stmt->execute()){
        $this->SurveyID = $this->conn->lastInsertId();
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
                  Title = :Title,
                  Description = :Description,
                  UserID = :UserID,
                  StartDate = :StartDate,
                  EndDate = :EndDate
              WHERE
                  SurveyID = :SurveyID";

      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->Title=htmlspecialchars(strip_tags($this->Title));
      $this->Description=htmlspecialchars(strip_tags($this->Description));
      $this->UserID=htmlspecialchars(strip_tags($this->UserID));
      $this->StartDate=htmlspecialchars(strip_tags($this->StartDate));
      $this->EndDate=htmlspecialchars(strip_tags($this->EndDate));
    
      $this->SurveyID=htmlspecialchars(strip_tags($this->SurveyID));

      // bind new values
      $stmt->bindParam(':Title', $this->Title);
      $stmt->bindParam(':Description', $this->Description);
      $stmt->bindParam(':UserID', $this->UserID);
      $stmt->bindParam(':StartDate', $this->StartDate);
      $stmt->bindParam(':EndDate', $this->EndDate);
    
      $stmt->bindParam(':SurveyID', $this->SurveyID);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  } // end update();
  
  // Change the status of a survey from editable to open, or
  //   open to close.
  function changeStatus(){
      $query = "UPDATE ".$this->table_name."
                SET Status = :Status 
                WHERE SurveyID = :SurveyID";
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->Status=htmlspecialchars(strip_tags($this->Status));
      $this->SurveyID=htmlspecialchars(strip_tags($this->SurveyID));

      $stmt->bindParam(":Status", $this->Status);
      $stmt->bindParam(":SurveyID", $this->SurveyID);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
    
  } // end changeStatus()
}
?>