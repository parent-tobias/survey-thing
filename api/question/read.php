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

/****

COMMENTING THIS OUT, AS I HAVEN'T GOTTEN THIS GOING ON MY SIDE.

// Let's check if we have a session going on. If we do, then we can
//   create a User object, based off that session's email!
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
  $User->email = $_SESSION['email'];
  $User->findByEmail();
} else {
    header("Location: notLogged.html");
}
// This is the only bit that needs a session -- once we have the user's
//   valid data, we can close the session and free the resource.
session_end();

****/

// If we have a valid session, the headers and all can follow.
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
// Initialize object
$Question = new Question($db);

// read all questions for the given survey.
$Question->SurveyID = isset($_GET['SurveyID']) ? (int) $_GET['SurveyID'] : die();

$QuestionsArray = $Question->read();

if($QuestionsArray){
  echo json_encode($QuestionsArray);
} else {
  echo json_encode(
    array("message" => "No questions found.")
  );
}
?>