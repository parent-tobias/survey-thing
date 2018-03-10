<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// get database connection
include_once '../config/database.php';
 
// instantiate product object
include_once '../objects/survey.php';
 
$database = new Database();
$db = $database->getConnection();
 
$survey = new Survey($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

 
// set product property values
$survey->title = isset($data->title) ? $data->title : "";
$survey->description = isset($data->description) ? $data->description : "";
$survey->userId = $data->userId;
$survey->startDate = isset($data->startDate) ? $data->startDate : "";
$survey->endDate = isset($data->endDate) ? $data->endDate : "";
$survey->createdDate = date('Y-m-d H:i:s');
 
// create the product
if($survey->create()){

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
}
 
// if unable to create the product, tell the user
else{
    echo '{';
        echo '"message": "Unable to create product."';
    echo '}';
}
?>