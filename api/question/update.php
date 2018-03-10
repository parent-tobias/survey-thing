<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// include database and object files
include_once '../config/database.php';
include_once '../objects/survey.php';
 
// get database connection
$database = new Database();
$db = $database->getConnection();
 
$survey = new Survey($db);
 
// get posted data
$json_str = file_get_contents("php://input");
$data = json_decode($json_str);

// set ID property of product to be edited
$survey->id = $data->id;
 
// set product property values
$survey->title = isset($data->title) ? $data->title : "";
$survey->description = isset($data->description) ? $data->description : "";
$survey->userId = $data->userId;
$survey->startDate = isset($data->startDate) ? $data->startDate : "";
$survey->endDate = isset($data->endDate) ? $data->endDate : "";
$survey->createdDate = isset($data->createdDate) ? $data->createdDate : "";

 
// update the product
if($survey->update()){
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