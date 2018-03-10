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
include_once '../objects/question.php';
include_once '../objects/questionType.php';
 
$database = new Database();
$db = $database->getConnection();
 
$question = new Question($db);
$questionType = new QuestionType($db);
$answerOption = new AnswerOption($db);
 
// get posted data
$data = json_decode(file_get_contents("php://input"));

 
// set product property values
$question->surveyId = $data->surveyId;
// Defaulting to text input if nothing is given
$question->questionTypeId = isset($data->questionType) ? $data->questionType : 3;
$question->questionText = isset($data->questionText) ? $data->questionText : "";
$question->comment = isset($data->comment) ? $data->comment : "";
$question->createdDate = date('Y-m-d H:i:s');
 
// create the product
if($question->create()){
  
  // create array
  $survey_arr = array(
    "id" => $question->id,
    "questionType" => $question->questionTypeId,
    "questionText" => html_entity_decode($question->questionText),
    "comment" => html_entity_decode($question->comment),
    "createdDate" => $question->createdDate,
    "answerOptions" => array()
  );
  foreach($data->answerOptions as $option){
    $answerOption->questionId = $question->id;
    $answerOption->answer = $option;
    $answerOption->createdDate = date('Y-m-d H:i:s');
    
    if($answerOption->create()){
      //Do something, I don't know.
      $question_arr->answerOptions->array_push($answerOption->answer);
    }
  }



  // make it json format
  print_r(json_encode($question_arr));
}
 
// if unable to create the product, tell the user
else{
    echo '{';
        echo '"message": "Unable to create product."';
    echo '}';
}
?>