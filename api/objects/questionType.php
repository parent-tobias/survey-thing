<?php
/****
 * QuestionType objects don't need to be created or updated 
 *   in the database, as these are a fairly static thing. Once
 *   created manually, they should be fixed. Thus, I only have
 *   two functions:
 *  - read() 
 *  - readOne()
 * At this point, the only usefulness to this object will be getting
 *  the question type id, so that when we save the question, it is
 *  storing the proper questionType. Still needs to be implemented.
 * As an interesting aside, the QuestionType could be completely
 *  eliminated by using an ENUM on the Question table, but they are
 *  more computationally intense for the MySQL server. So, we'll let
 *  it stand. 
 *
 * QuestionType, v 0.2
 *   Updated to support the field names in the nky db. Also, the read()
 *    function now returns an array of QuestionTypes rather than the
 *    database handle, thus abstracting the front end from needing to
 *    handle the database mechanicals.
 *
 ****/
class QuestionType{
  // database connection and table name
  private $conn;
  private $table_name = "QuestionType";
  
  // Object properties
  public $QuestionTypeID;
  public $TypeName;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              ORDER BY ".$this->table_name.".QuestionTypeID DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $QuestionTypeArray = array();
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        $QuestionTypeItem = array(
          "QuestionTypeID" => $QuestionTypeID,
          "TypeName" => $TypeName,
        );
        $QuestionTypeArray[] = $QuestionTypeItem;
      }
    }
    
    return $QuestionTypeArray;
  }

  // fetch a single survey
  function readOne(){

      // query to read single record
      $query = "SELECT ".$this->table_name.".*
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".QuestionTypeID = ?
                LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->QuestionTypeID);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      $this->TypeName = $row['TypeName'];
  }

  // fetch a single survey
  function findByType(){

      // query to read single record
      $query = "SELECT ".$this->table_name.".*
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".TypeName = ?
                LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->TypeName);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      return $row['QuestionTypeID'];
  }
}
?>