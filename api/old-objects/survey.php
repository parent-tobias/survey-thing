<?php
include_once("question.php");
include_once("answerOption.php");

class Survey{
  // database connection and table name
  private $conn;
  private $table_name = "surveys";
  
  // Object properties
  public $id;
  public $title;
  public $description;
  public $publisher;
  public $userId;
  public $questions = array();
  public $startDate;
  public $endDate;
  public $createdDate;
  
  // Constructor with $db as database connection
  public function __construct($db){
    $this->conn = $db;
  }
  
  function read(){
    // Select all query
    $query = "SELECT *, users.firstName as publisher FROM ".$this->table_name." LEFT JOIN users ON ".$this->table_name.".userId = users.id ORDER BY ".$this->table_name.".createdDate DESC";
    
    // Prepare query statement
    $stmt = $this->conn->prepare($query);
    
    // Execute query
    $stmt->execute();
    
    return $stmt;
  }

  // fetch a single survey
  function readOne(){
      $question = new Question($this->conn);
      $answerOption = new AnswerOption($this->conn);

      // query to read single record
      $query = "SELECT *, users.firstName as publisher FROM ".$this->table_name." LEFT JOIN users ON ".$this->table_name.".userId = users.id WHERE ".$this->table_name.".id = ? LIMIT 0,1";

      // prepare query statement
      $stmt = $this->conn->prepare( $query );

      // bind id of product to be updated
      $stmt->bindParam(1, $this->id);

      // execute query
      $stmt->execute();

      // get retrieved row
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      
      $question->surveyId = $this->id;
      $question_stmt = $question->read();
      $num = $question_stmt->rowCount();
    
      // Do we have records?
      if ($num>0){
        // Surveys array
        $questions_arr = array();
        $questions_arr["questions"] = array();

        // Get out table contents
        // fetch() is faster than fetchAll()
        while ($question_row = $question_stmt->fetch(PDO::FETCH_ASSOC)){

          extract($question_row);
          $question_item = array(
            "id" => $id,
            "questionText" => $questionText,
            "comment" => html_entity_decode($comment),
            "questionTypeId" => $questionTypeId,
            "questionType" => $questionType,
            "answerOptions" => array(),
            "createdDate" => $createdDate
          );
          // For the current question, we need to fetch all the answer options.
          // First, we store the question ID on the answerOption object,
          //  then we tell that to read all its records for that question.
          $answerOption->questionId = $question_item["id"];
          $answer_stmt = $answerOption->read();
          // How many answer options do we have?
          $answerNum = $answer_stmt->rowCount();
          if ($answerNum > 0){
            // answer options array
            while($answer_row = $answer_stmt->fetch(PDO::FETCH_ASSOC)){
              extract($answer_row);
              $answerOption_item = array(
                "id" => $id,
                "answer" => $answer,
                "createdDate" => $createdDate
              );
            array_push($question_item["answerOptions"], $answerOption_item);
            }
          }
          array_push($questions_arr["questions"], $question_item);
        }
      }

      // set values to object properties
      $this->title = $row['title'];
      $this->description = $row['description'];
      $this->userId = $row['userId'];
      $this->publisher = $row['publisher'];
      $this->startDate = $row['startDate'];
      $this->endDate = $row['endDate'];
      $this->questions = isset($questions_arr['questions']) ? $questions_arr['questions'] : array();
      $this->createdDate = $row['createdDate'];
  }

  // create product
  function create(){

      // query to insert record
      $query = "INSERT INTO
                  " . $this->table_name . "
              SET
                  title=:title, description=:description, userId=:userId, startDate=:startDate, endDate=:endDate, createdDate=:createdDate";

      // prepare query
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->title=htmlspecialchars(strip_tags($this->title));
      $this->description=htmlspecialchars(strip_tags($this->description));
      $this->userId=htmlspecialchars(strip_tags($this->userId));
      $this->startDate=htmlspecialchars(strip_tags($this->startDate));
      $this->endDate=htmlspecialchars(strip_tags($this->endDate));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));

      // bind values
      $stmt->bindParam(':title', $this->title);
      $stmt->bindParam(':description', $this->description);
      $stmt->bindParam(':userId', $this->userId);
      $stmt->bindParam(':startDate', $this->startDate);
      $stmt->bindParam(':endDate', $this->endDate);
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
                  title = :title,
                  description = :description,
                  userId = :userId,
                  startDate = :startDate,
                  endDate = :endDate,
                  createdDate = :createdDate
              WHERE
                  id = :id";

      // prepare query statement
      $stmt = $this->conn->prepare($query);

      // sanitize
      $this->title=htmlspecialchars(strip_tags($this->title));
      $this->description=htmlspecialchars(strip_tags($this->description));
      $this->userId=htmlspecialchars(strip_tags($this->userId));
      $this->startDate=htmlspecialchars(strip_tags($this->startDate));
      $this->endDate=htmlspecialchars(strip_tags($this->endDate));
      $this->createdDate=htmlspecialchars(strip_tags($this->createdDate));
    
      $this->id=htmlspecialchars(strip_tags($this->id));

      // bind new values
      $stmt->bindParam(':title', $this->title);
      $stmt->bindParam(':description', $this->description);
      $stmt->bindParam(':userId', $this->userId);
      $stmt->bindParam(':startDate', $this->startDate);
      $stmt->bindParam(':endDate', $this->endDate);
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