<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json; charset=UTF-8");

// include database and object files
include_once '../config/database.php';
include_once '../objects/question.php';
include_once '../objects/user.php';

// Instantiate database and survey object
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

// Initialize object
$Question = new Question($db);

// read all questions for the given survey.
$Question->QuestionID = isset($_GET['QuestionID']) ? $_GET['QuestionID'] : die();

$Question->readOne();

echo json_encode($Question);

?>