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
$Answer->AnswerID = isset($_GET['AnswerID']) ? $_GET['AnswerID'] : die (json_encode(array("message"=>"No AnswerID given.") ) );

echo json_encode($Answer);
/***
 * We had been explicitly deleting answerOptions here. That is no longer
 *   required, as the Question class itself now handles that internally.
 ***/
if($Answer->delete()){
  echo '{';
    echo '"message": "Answer deleted."';
  echo '}';
} else {
  echo '{';
    echo '"message": "Unable to delete answer."';
  echo '}';
}
?>