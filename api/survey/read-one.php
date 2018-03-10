<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
 
// include database and object files
include_once '../config/database.php';
include_once '../objects/survey.php';
 
// get database connection
$database = new Database();
$db = $database->getConnection();
 
// prepare product object
$survey = new Survey($db);
 
// set ID property of product to be edited
$survey->id = isset($_GET['id']) ? $_GET['id'] : die();
 
// read the details of product to be edited
$survey->readOne();
 
// create array
$survey_arr = array(
  "id" => $survey->id,
  "title" => $survey->title,
  "description" => html_entity_decode($survey->description),
  "userId" => $survey->userId,
  "publisherName" => $survey->publisher,
  "startDate" => $survey->startDate,
  "endDate" => $survey->endDate,
  "createdDate" => $survey->createdDate
);
 
// make it json format
print_r(json_encode($survey_arr));
?>