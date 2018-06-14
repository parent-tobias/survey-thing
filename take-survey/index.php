<?php
// include database and object files
include_once '../api/config/database.php';
include_once '../api/objects/user.php';
include_once '../api/objects/survey.php';

$database = new Database();
$db = $database->getConnection();

$User = new User($db);
$Survey = new Survey($db);

// Let's check if we have a session going on. If we do, then we can
//   create a User object, based off that session's email!
session_start();

if (isset($_SESSION['loggedin']) && ($_SESSION['loggedin'] == true) ) {
  // we have a valid session, let's create a User.
  $User->email = $_SESSION['email'];
  $User->findByEmail();
} else {
  header("Location: index.php");
}
// This is the only bit that needs a session -- once we have the user's
//   valid data, we can close the session and free the resource.

/****
 * At this point, we can create the survey itself from the survey ID
 *  we were passed via $_GET -- or, if we don't have one, return to 
 *  the admin index.
 ****/
if(!isset($_GET['SurveyID'])) {
  header("Location: index.php"); 
}
$Survey->SurveyID = $_GET['SurveyID'];
$Survey->readOne();

/****
 * Now that we have a complete Survey object, we can use that to create
 *   the survey itself.
 ****/

?>
  <!DOCTYPE html>
  <html>

  <head>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <script src="//code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <script src="../js/response-controller.js"></script>
    <link rel="stylesheet" type="text/css" href="../css/surveyform.css" />
    <link rel="stylesheet" type="text/css" href="../css/take-survey.css" />
  </head>

  <body>
    <header>
      <h1>
        Take Survey:
      </h1>
      <h2>
        <?= $Survey->Title; ?> 
      </h2>
    </header>
    <div class="container">
      <form name="survey-form" action="submit-survey.php" method="POST">
        <input type="hidden" name="SurveyID" value="<?= $Survey->SurveyID ?>" />
        <div class="row">
          <div class="description-container col container" style="margin-top: 5%">
            <?= $Survey->Description; ?>
          </div>
        </div>
        <?php foreach($Survey->Questions as $Question){ ?>
        <div class="row">
          <div class="col-sm-3">
            <?= $Question['Text']; ?>
          </div>
          <div class="col-sm-9">
            <div class="row">
              <?php
                  $colsValue=floor(12/sizeof($Question['AnswerChoices']) ); 
                  foreach($Question['AnswerChoices'] as $AnswerChoice){
                   ?>
              <div style="border: 1px solid #ccc;" class="col col-sm-<?= $colsValue ?> answer-choice">
                <label><?= $AnswerChoice['answer'] ?>
                  <input 
                         name="<?= $Question['QuestionID'] ?><?php if($Question['QuestionType'] == 'Checkbox') {?>[]<?php } ?>"
                         type="<?= $Question['QuestionType'] ?>" 
                         id="<?= $AnswerChoice['AnswerChoiceID']; ?>"
                         value="<?php if($Question['QuestionType']!= 'Text') echo $AnswerChoice['AnswerChoiceID']; ?>" >
                </label>
              </div>
          <?php } ?>
            </div>
          </div>
        </div>
        <?php } ?>
        <div class="row">
          <div class="col col-sm-4">
          </div>
          <div class="col col-sm-4">
            <input type="reset" value="Reset" name="resetBtn" /> | <input type="submit" value="Submit" name="submitBtn" /> 
          </div>
          <div class="col col-sm-4">
          </div>
        </div>
      </form>
    </div>
    <script>
      responseController.init($("form[name='survey-form']"));
    </script>
  </body>

  </html>