<?php

 
// get database connection
include_once '../config/database.php';
 
// instantiate answer and user object
include_once '../objects/answer.php';
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
 
$Answer = new Answer($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));
 
// set product property values
$Answer->SurveyResponseID = isset($data->SurveyResponseID) ? $data->SurveyResponseID : die (json_encode(array("message"=>"SurveyResponseID required.") ));
// Defaulting to text input if nothing is given
$Answer->AnswerChoiceID = isset($data->AnswerChoiceID) ? $data->AnswerChoiceID : die (json_encode(array("message"=>"AnswerChoiceID required.") ));
$Answer->Comment = isset($data->Comment) ? $data->Comment : "";

// create the product
if($Answer->create()){
  // make it json format
  print_r(json_encode($Answer));
}
 
// if unable to create the answer, tell the user
else{
    echo '{';
        echo '"message": "Unable to create Answer."';
    echo '}';
}
?>