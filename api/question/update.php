<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// include database and object files
include_once '../config/database.php';
include_once '../objects/question.php';
include_once '../objects/user.php';

// get database connection
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
 
// get posted data
$json_str = file_get_contents("php://input");
$data = json_decode($json_str);

// set ID property of product to be edited
$Question->QuestionID = $data->QuestionID;
 
// Defaulting to text input if nothing is given
$Question->SurveyID = $data->SurveyID;
$Question->QuestionTypeID = isset($data->QuestionType) ? $data->QuestionType : 3;
$Question->Text = isset($data->QuestionText) ? $data->QuestionText : "";
$Question->Comment = isset($data->Comment) ? $data->Comment : "";

// update the Question
if($Question->update()){
  echo json_encode($Question);
}
 
// if unable to update the product, tell the user
else{
    echo '{';
        echo '"message": "Unable to update question."';
    echo '}';
}
?>