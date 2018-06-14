<?php
// Output the headers...
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// get database and Survey classes
include_once '../config/database.php';
include_once '../objects/user.php';

$database = new Database();
$db = $database->getConnection();

$User = new User($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

 
// set product property values
$User->firstname = isset($data->firstname) ? $data->firstname : "";
$User->lastname = isset($data->lastname) ? $data->lastname : "";
$User->email = isset($data->email) ? $data->email : die;
$User->password = isset($data->password) ? $data->password : die;
 
// create the product
if($User->create()){
  echo '{ "userCreated" : true }';
} else {
  echo '{ "userCreated" : false }';
}
?>