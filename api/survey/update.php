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
 
// get posted data
$json_str = file_get_contents("php://input");
$data = json_decode($json_str);

// set ID property of product to be edited
$Survey->SurveyID = $data->SurveyID;
 
// set product property values
$Survey->Title = isset($data->Title) ? $data->Title : "";
$Survey->Description = isset($data->Description) ? $data->Description : "";
$Survey->UserID = $data->UserID;
$Survey->StartDate = isset($data->StartDate) ? $data->StartDate : "";
$Survey->EndDate = isset($data->EndDate) ? $data->EndDate : "";

 
// update the product
if($Survey->update()){
    echo '{';
        echo '"message": "Survey was updated."';
    echo '}';
}
 
// if unable to update the product, tell the user
else{
    echo '{';
        echo '"message": "Unable to update survey."';
    echo '}';
}
?>