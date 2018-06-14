<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// include database config and any required classes
include_once '../config/database.php';
include_once '../objects/question.php';
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
 
// get posted data
$Question->QuestionID = isset($_GET['QuestionID']) ? $_GET['QuestionID'] : die;
 
/***
 * We had been explicitly deleting answerOptions here. That is no longer
 *   required, as the Question class itself now handles that internally.
 ***/
if($Question->delete()){
  echo '{';
    echo '"message": "Question deleted."';
  echo '}';
} else {
  echo '{';
    echo '"message": "Unable to delete question."';
  echo '}';
}
?>