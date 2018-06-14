<?php
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
$User->email = isset($_GET['email']) ? $_GET['email'] : die;

if ($User->exists()){
  echo '{ "accountExists" : true }';
} else {
    echo '{ "accountExists": false }';
}
?>