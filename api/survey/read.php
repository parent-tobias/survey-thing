<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json; charset=UTF-8");

// include database and object files
include_once '../config/database.php';
include_once '../objects/survey.php';

// Instantiate database and survey object
$database = new Database();
$db = $database->getConnection();

// Initialize object
$survey = new Survey($db);

// query surveys
$stmt = $survey->read();
$num = $stmt->rowCount();

// Do we have records?
if ($num>0){
  // Surveys array
  $surveys_arr = array();
  $surveys_arr["records"] = array();
  
  // Get out table contents
  // fetch() is faster than fetchAll()
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    
    extract($row);
    $survey_item = array(
      "id" => $id,
      "title" => $title,
      "description" => html_entity_decode($description),
      "userId" => $userId,
      "publisherName" => $publisher,
      "startDate" => $startDate,
      "endDate" => $endDate,
      "createdDate" => $createdDate
    );
    array_push($surveys_arr["records"], $survey_item);
  }
  
  echo json_encode($surveys_arr);
} else {
  echo json_encode(
    array("message" => "No surveys found.")
  );
}
?>