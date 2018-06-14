<?php
 
// include database and object files
include_once '../config/database.php';
include_once '../objects/response.php';
include_once '../objects/survey.php';
include_once '../objects/user.php';

$database = new Database();
$db = $database->getConnection();
 
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

// This is gonna be funky. We have a few different filters on the read
//  that we can use.
//  First, if we've been passed a UserID and a status, we can get
//   an array of surveys for that. If only have a UserID, we can filter
//   for that. If we have a Status ['editing','open','closed'], filter
//   for THAT. Otherwise, just return EVERY survey.

// The first will get 
if( isset($_GET['ResponseStatus']) && isset($_GET['Status']) ){
  $Response->ResponseStatus = $_GET['ResponseStatus'];
  $Response->Survey = new Survey($db);
  $Response->Survey->Status = $_GET['Status'];
  $ResponseArray = $Response->readByResponseStatus();
} else {
  $ResponseArray = $Response->read();
}

if($ResponseArray){
  echo json_encode($ResponseArray);
} else {
  echo json_encode(
    array("message" => "No Responses found.")
  );
}
?>