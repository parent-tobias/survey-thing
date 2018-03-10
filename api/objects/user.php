<?php
class User{
  // database connection and table name
  private $conn;
  private $table_name = "users";
  
  // Object properties
  public $id;
  public $username;
  public $firstName;
  public $lastName;
  public $email;
  public $password;
  public $createdDate;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name." 
              ORDER BY ".$this->table_name.".username DESC";
    
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
      $this->username = $row['username'];
      $this->firstName = $row['firstName'];
      $this->lastName = $row['lastName'];
      $this->email = $row['email'];
      $this->createdDate = $row['createdDate'];
  }

  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  username=:username,
                  firstName=:firstName,
                  lastName=:lastName,
                  email=:email,
                  password=:password,
                  createdDate=:createdDate";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->username=htmlspecialchars(strip_tags($this->username));
      $this->firstName=htmlspecialchars(strip_tags($this->firstName));
      $this->lastName=htmlspecialchars(strip_tags($this->lastName));
      $this->email=htmlspecialchars(strip_tags($this->email));
      $this->password=htmlspecialchars(strip_tags($this->password));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':username', $this->username);
      $stmt->bindParam(':firstName', $this->firstName);
      $stmt->bindParam(':lastName', $this->lastName);
      $stmt->bindParam(':email', $this->email);
      $stmt->bindParam(':password', $this->password);
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
                  username=:username,
                  firstName=:firstName,
                  lastName=:lastName,
                  email=:email,
                  password=:password,
                  createdDate=:createdDate
              WHERE
                  id=:id";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->username=htmlspecialchars(strip_tags($this->username));
      $this->firstName=htmlspecialchars(strip_tags($this->firstName));
      $this->lastName=htmlspecialchars(strip_tags($this->lastName));
      $this->email=htmlspecialchars(strip_tags($this->email));
      $this->password=htmlspecialchars(strip_tags($this->password));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':username', $this->username);
      $stmt->bindParam(':firstName', $this->firstName);
      $stmt->bindParam(':lastName', $this->lastName);
      $stmt->bindParam(':email', $this->email);
      $stmt->bindParam(':password', $this->password);
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