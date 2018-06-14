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
 *
 ****/
class QuestionType{
  // database connection and table name
  private $conn;
  private $table_name = "questionTypes";
  
  // Object properties
  public $id;
  public $typeName;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name."
              ORDER BY ".$this->table_name.".id DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }

  // fetch a single survey
  function readOne(){

      // query to read single record
      $query = "SELECT ".$this->table_name.".*
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".id = ?
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
      $this->typeName = $row['typeName'];
  }
}
?>