<?php
// include database and object files
include_once '../api/config/database.php';
include_once '../api/objects/user.php';
include_once '../api/objects/survey.php';
include_once '../api/objects/response.php';
include_once '../api/objects/answer.php';
include_once '../api/objects/question.php';

$database = new Database();
$db = $database->getConnection();

$User = new User($db);
$Survey = new Survey($db);
$SurveyResponse = new Response($db);

// Let's check if we have a session going on. If we do, then we can
//   create a User object, based off that session's email!
session_start();

if (isset($_SESSION['loggedin']) && ($_SESSION['loggedin'] == true) ) {
  // we have a valid session, let's create a User.
  $User->email = $_SESSION['email'];
  $User->findByEmail();
} else {
  header("Location: ../index.php");
}
// This is the only bit that needs a session -- once we have the user's
//   valid data, we can close the session and free the resource.

if (!isset($_POST['SurveyID'])) {
  header("Location: ../index.php");
}

$Survey->SurveyID = $_POST['SurveyID'];
$Survey->ReadOne();

/****
 * Now that we have a complete Survey object, we can use that to create
 *   the survey itself.
 ****/
$SurveyResponse->SurveyID = $_POST['SurveyID'];
$SurveyResponse->UserID = $User->UserID;
$SurveyResponse->ResponseStatus = 'incomplete';

if($SurveyResponse->create()){
  /***
   * Iterate over all the post responses, check if the KEY is a valid QuestionID
   ***/  
  
  foreach($_POST as $QuestionID => $AnswerValue){
    
    // We'll check if the current element of the POST array is a valid question
    //  for this survey. If it is NOT, we bypass the loop.
    if(surveyContainsQuestionID($Survey->Questions, $QuestionID)){
      // If we get here, the question is a valid one for this survey, and we should
      //  create the Question object to get the question type, so we can determine
      //  how to save this to the database.
      $Question = new Question($db);
      $Question->QuestionID = $QuestionID;
      $Question->readOne();
      
      if($Question->QuestionType == 'Text'){
        // in the event of a text entry, the ID is the question but there will only be
        //  one answerChoice. So we set the Answer value from the Question we just retrieved,
        //  and set the comment to the text.
        $Answer = new Answer($db);

        $Answer->SurveyResponseID = $SurveyResponse->SurveyResponseID;
        $Answer->AnswerChoiceID = $Question->AnswerChoices[0]['AnswerChoiceID'];
        $Answer->Comment = $AnswerValue;

        // We will have to create the Answer in each of the possible QuestionTypes.
        //  The reason is, if we have a checkbox type, we will be creating multiple
        //  Answers.
        if(!$Answer->create()){
          echo json_encode(array("error"=> "An error occured. An answer could not be included", $Answer));
        }
      } elseif ($Question->QuestionType == 'Checkbox') {
        // If we have a checkbox, the returned value will be a comma-delimited string.
        //   We'll need to split the string on the commas, and create an answer for each.
        
        // First, we need to parse the AnswerValue from a string like:
        //   "143,144,146" into an array: [143,144,146]
        foreach($AnswerValue as $CheckboxAnswerValue){
          // Exactly the same as the other options. Just that we had to do a little 
          //  creative doctoring to convert the string to an array to create multiple
          //   answers.
          
          $Answer = new Answer($db);

          $Answer->SurveyResponseID = $SurveyResponse->SurveyResponseID;
          $Answer->AnswerChoiceID = $CheckboxAnswerValue;

          // We will have to create the Answer in each of the possible QuestionTypes.
          //  The reason is, if we have a checkbox type, we will be creating multiple
          //  Answers.
          if(!$Answer->create()){
            echo json_encode(array("error"=> "An error occured. An answer could not be included", $Answer));
          }
        }
      } else {
        // Otherwise, we have a radio box. Optional comment? We aren't
        //   really handling that yet.
        $Answer = new Answer($db);

        $Answer->SurveyResponseID = $SurveyResponse->SurveyResponseID;
        $Answer->AnswerChoiceID = $AnswerValue;
        
        if(!$Answer->create()){
          echo json_encode(array("error"=> "An error occured. An answer could not be included", $Answer));
        }
      }

    }
  }
  header("Location: /view-survey/?SurveyID=".$Survey->SurveyID );
} else {
  echo json_encode(array("error", "Couldn't create survey response.") );
}

function surveyContainsQuestionID(array $myArray, $id) {
    foreach ($myArray as $element) {
        if ($element['QuestionID'] == $id) {
            return true;
        }
    }
    return false;
}

?>