<?php

 
// get database connection
include_once '../config/database.php';
 
// instantiate product object
include_once '../objects/question.php';
include_once '../objects/questionType.php';
include_once '../objects/answerOption.php';
include_once '../objects/user.php';
 
$database = new Database();
$db = $database->getConnection();
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

// We have a valid session, let's go ahead and set up the headers, run
//  the API as normal.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
 
$Question = new Question($db);
$QuestionType = new QuestionType($db);
$AnswerOption = new AnswerOption($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

 
// set product property values
$Question->SurveyID = $data->SurveyID;
// Defaulting to text input if nothing is given
$Question->QuestionTypeID = isset($data->QuestionType) ? $data->QuestionType : 3;
$Question->Text = isset($data->QuestionText) ? $data->QuestionText : "";
$Question->Comment = isset($data->Comment) ? $data->Comment : "";
 
// create the product
if($Question->create()){
  
  // create array
  foreach($data->AnswerOptions as $option){
    $AnswerOption->QuestionID = $Question->QuestionID;
    $AnswerOption->answer = $option;
    
    $AnswerOption->create();
  }
  $AnswersArray = $AnswerOption->read();
  
  $QuestionArray = array(
    "QuestionID" => $Question->QuestionID,
    "QuestionType" => $Question->QuestionTypeID,
    "Text" => $Question->Text,
    "Comment" => $Question->Comment,
    "CreatedAt" => $Question->CreatedAt,
    "AnswerChoices" => $AnswersArray
  );


  // make it json format
  print_r(json_encode($QuestionArray));
}
 
// if unable to create the product, tell the user
else{
    echo '{';
        echo '"message": "Unable to create Question."';
    echo '}';
}
?>