<?php
 
// include database and object files
include_once '../config/database.php';
include_once '../objects/response.php';
include_once '../objects/survey.php';
include_once '../objects/user.php';

$database = new Database();
$db = $database->getConnection();
 
$Survey = new Survey($db);
$Response = new Response($db);
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
 
// set ID property of product to be edited
$Survey->SurveyID = isset($_GET['SurveyID']) ? $_GET['SurveyID'] : die();

$Response->UserID = $User->UserID;
$Response->SurveyID = $Survey->SurveyID;
 
// read the details of product to be edited
$Response->readByUserAndSurvey();
 
// make it json format
if($Response->SurveyResponseID){
  // Do something here?
} else {
  $Response->create();
}

print_r(json_encode($Response));
?>