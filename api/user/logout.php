<?php
// Output the headers...
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
 
// Create a session, set values if the user manages to log in.
session_start();

$_SESSION = array();
session_destroy()
  
echo "{ 'loggedIn': false }";
?>