<?php
 
// get database and Survey classes
include_once '../config/database.php';
include_once '../objects/survey.php';
include_once '../objects/question.php';
include_once '../objects/answerOption.php';
include_once '../objects/user.php';

$database = new Database();
$db = $database->getConnection();
 
$Survey = new Survey($db);
$User = new User($db);


// Let's check if we have a session going on. If we do, then we can
//   create a User object, based off that session's email!
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true ) {
  $User->email =  $_SESSION['email'];
  $User->findByEmail();
  
} else {
  echo json_encode(array("error"=>"session does not exist."));
}

// If we have a valid session, the headers and all can follow.
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

 
// set product property values
$Survey->Title = isset($data->Title) ? $data->Title : "";
$Survey->Description = isset($data->Description) ? $data->Description : "";
$Survey->UserID = isset($User->UserID) ? $User->UserID : $data->UserID;
$Survey->StartDate = isset($data->StartDate) ? $data->StartDate : "";
$Survey->EndDate = isset($data->EndDate) ? $data->EndDate : "";
$Questions = isset($data->Questions) ? $data->Questions : [];
 
// create the product
if($Survey->create()){
  
  foreach($Questions as $QuestionObj){
    $Question = new Question($db);
    $Question->Text = $QuestionObj->Text;
    $Question->Comment = $QuestionObj->Comment;
    $Question->QuestionType = $QuestionObj->QuestionType;
    $Question->AnswerChoices = array();
    
    if($Question->create()){
      foreach($QuestionObj->AnswerChoices as $Answer){
        $AnswerChoice = new AnswerOption();
        $AnswerChoice->QuestionID = $Question->QuestionID;
        $AnswerChoice->answer = $Answer->answer;
        if ($AnswerChoice->create()){
          $Question->AnswerChoices[] = $AnswerChoice;
        }
      }
      $Survey->Questions[] = $Question;
      
    }
  }

  // make it json format
  print_r(json_encode($Survey));
}
 
// if unable to create the product, tell the user
else{
    echo '{';
        echo '"message": "Unable to create Survey."';
    echo '}';
}
?>