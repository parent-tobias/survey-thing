<?php
 
// include database and object files
include_once '../config/database.php';
include_once '../objects/survey.php';
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

// We have a valid session, let's go ahead and set up the headers, run
//  the API as normal.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// set ID property of product to be edited
if(isset($_GET['SurveyID']) && isset($_GET['Status'])){
  $Survey->SurveyID = $_GET['SurveyID'];
  $Survey->Status = $_GET['Status'];
  if($Survey->changeStatus()){
        echo json_encode(array(
          "message" => "Survey closed for editing, available for polling",
          "open" => true
        ));
  }// if unable to update the product, tell the user
  else{
        echo json_encode(array(
          "message" => "Unable to finalize survey",
          "open" => false
        ));
  }
} else {
  // No SurveyID or status passed.
        echo json_encode(array(
          "message" => "Unable to finalize survey. No SurveyID and/or status given.",
          "open" => false
        ));
}
 
?>