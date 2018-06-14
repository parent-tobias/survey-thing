<?php
// Output the headers...
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// get database and User classes
include_once '../config/database.php';
include_once '../objects/user.php';

// Create a session, set values if the user manages to log in.
session_start();
$database = new Database();
$db = $database->getConnection();

$User = new User($db);

 
// get posted data
$data = json_decode(file_get_contents("php://input"));

$User->email = isset($data->email) ? $data->email : die(json_encode(array('error'=>'No email given.'))) ;
$User->password = isset($data->password) ? $data->password : die(json_encode(array('error'=>'No password given.')));

if($User->login()) {
    $User->password = null;
    $User->UserID = null;
    echo json_encode(array('loggedIn'=>true, 'User'=>$User));

    $_SESSION['loggedin'] = true;
    $_SESSION['message'] = "logged in";
    $_SESSION['email'] = $User->email;
    //header("Location: Home.html");
  
} else {
    echo json_encode(array('loggedIn'=>false));
  
    $_SESSION['loggedin'] = false;
    $_SESSION['message'] = "not logged in";
    //header("Location: Home.html");
  session_destroy();
}

?>