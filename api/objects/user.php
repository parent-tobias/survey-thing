<?php
/****
 * User class, v 0.1
 * Totally new class, created to facilitate interfacing with the
 *   user authentication bit. 
 * Available functions:
 *  - read() now returns an array of Users. This contains first,
 *    last, email and CreatedDate.
 *  - readOne() returns a User with a given ID.
 *  - findByEmail() returns a User with a given email.
 *  - exists() checks for the existence of a User account with a
 *    given email address. A use case for this might be, when the
 *    user is creating their account, using AJAX to check if that
 *    email already has an account, or on login, checking if there
 *    is an account for a given email. It will simply return true
 *    or false.
 *  - login() retrieves a User record for a given email address, then
 *    compares the password entered with the password hash for that
 *    user. It will return true or false. In the event it returns
 *    true, it also fully populates the User object.
 *  - create() does the exact same thing, it simply creates a single
 *    entry in the UserInfo table, hashes the password, and returns
 *    the new UserID.
 *  - update() does the exact same thing, it updates the UserInfo.
 *******
 * Currently not implemented:
 *
 *  - delete() 
 *
 ****/
class User{
  // database connection and table name
  private $conn;
  private $table_name = "UserInfo";
  
  // Object properties
  public $UserID;
  public $firstname;
  public $lastname;
  public $email;
  public $password;
  public $CreatedAt;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT ".$this->table_name.".*
              FROM ".$this->table_name." 
              ORDER BY ".$this->table_name.".CreatedAt DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    $num = $stmt->rowCount();
    
    $UserArray = array();
    if($num>0){
      // Get out table contents
      // fetch() is faster than fetchAll()
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

        extract($row);
        $UserItem = array(
          "UserID" => $UserID,
          "firstname" => $firstname,
          "lastname" => $lastname,
          "email" => $email,
          "CreatedAt" => $CreatedAt
        );
        $UserArray[] = $UserItem;
      }
    }
  }

  // fetch a single survey
  function readOne(){
      // query to read single record
      $query = "SELECT ".$this->table_name.".* 
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".UserID = ?
                LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->UserID);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      $this->firstname = $row['firstname'];
      $this->lastname = $row['lastname'];
      $this->email = $row['email'];
      $this->CreatedAt = $row['CreatedAt'];
  }
  
   // fetch a single User, given an email addy
  function findByEmail(){
      // query to read single record
      $query = "SELECT ".$this->table_name.".* 
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".email = ?
                LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->email);

      // execute query
      $stmt->execute();
      $num = $stmt->rowCount();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      // set values to object properties
      $this->UserID = $row['UserID'];
      $this->firstname = $row['firstname'];
      $this->lastname = $row['lastname'];
      $this->email = $row['email'];
      $this->CreatedAt = $row['CreatedAt'];
    
      // If it exists, we can also send a return value.
      if($num>0){
        return true;
      } else {
        return false;
      }
  }
  
  function exists(){
      // This does the exact same as the findByEmail function,
      //  so I'm simply wrapping it. That one doesn't need to be
      //  assigned to a variable, but assigning $User->exists() seems
      //  a little more intuitive.
      return $this->findByEmail();
  }
  
  function login(){
      // query to read single record
      $query = "SELECT ".$this->table_name.".* 
                FROM ".$this->table_name."
                WHERE ".$this->table_name.".email = :email
                LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // sanitize
      $this->email=htmlspecialchars(strip_tags($this->email));

      $stmt->bindParam(':email', $this->email);
    
      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if(password_verify($this->password, $row['password'])) {
        // set values to object properties
        $this->firstname = $row['firstname'];
        $this->lastname = $row['lastname'];
        $this->email = $row['email'];
        $this->CreatedAt = $row['CreatedAt'];

        return true;
      } else {
        return false;
      }
  }

  // create the user account
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  firstname=:firstname,
                  lastname=:lastname,
                  email=:email,
                  password=:password";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->firstname=htmlspecialchars(strip_tags($this->firstname));
      $this->lastname=htmlspecialchars(strip_tags($this->lastname));
      $this->email=htmlspecialchars(strip_tags($this->email));

      // Encrypt the password!
      // $password_string = mysqli_real_escape_string($this->conn, $this->password);
      $this->password=password_hash($this->password, PASSWORD_BCRYPT);

      // bind values
      $stmt->bindParam(':firstname', $this->firstname);
      $stmt->bindParam(':lastname', $this->lastname);
      $stmt->bindParam(':email', $this->email);
      $stmt->bindParam(':password', $this->password);

      // execute query
      if($stmt->execute()){
        $this->UserID = $this->conn->lastInsertId();
        $this->readOne();
        return true;
      }

      return false;

  }

  // update the user
  function update(){

      // update query
      $query = "UPDATE
                  " . $this->table_name . "
              SET
                  firstname=:firstname,
                  lastname=:lastname,
                  email=:email,
                  password=:password
              WHERE
                  UserID=:UserID";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->firstName=htmlspecialchars(strip_tags($this->firstName));
      $this->lastName=htmlspecialchars(strip_tags($this->lastName));
      $this->email=htmlspecialchars(strip_tags($this->email));

      // Encrypt the password!
      $password_string = mysqli_real_escape_string($this->password);
      $this->password=password_hash($password_string, PASSWORD_BCRYPT);

      // bind values
      $stmt->bindParam(':firstname', $this->firstname);
      $stmt->bindParam(':lastname', $this->lastname);
      $stmt->bindParam(':email', $this->email);
      $stmt->bindParam(':password', $this->password);
    
      $stmt->bindParam(":UserID", $this->UserID);

      // execute the query
      if($stmt->execute()){
          return true;
      }

      return false;
  }
}
?>