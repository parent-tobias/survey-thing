<?php
// include database and object files
include_once './api/config/database.php';
include_once './api/objects/user.php';

$database = new Database();
$db = $database->getConnection();

$User = new User($db);

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

?>
  <!DOCTYPE html>
  <html>

  <head>
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
    <script src="//code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    <link rel="stylesheet" type="text/css" href="../css/surveyform.css" />
    <link rel="stylesheet" type="text/css" href="../css/admin-index.css" />

    <script src="./js/admin-interface.js"></script>

  </head>

  <body>
    <header>
      <h1>
        Surveys :: Create, edit, deploy
      </h1>
    </header>
    <div class="container">
      <div class="row">
        <nav class="admin-menu col-sm-3">
          <div id="accordion">
            <div class="card">
              <div class="card-header" id="headingOne">
                <h5 class="mb-0">
                  <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                New Survey
              </button>
                </h5>
              </div>

              <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                <div class="card-body">
                  <ul>
                    <li>
                      <a href="/new-survey" id="create-survey">New Survey</a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="headingTwo">
                <h5 class="mb-0">
                  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                My Surveys
              </button>
                </h5>
              </div>
              <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                <div class="card-body">
                  <ul>
                    <li>
                      <a class="SurveysByUserAndStatus-editing show-surveys-list">My Editable Surveys <span class="surveys-count"></span></a>
                    </li>
                    <li>
                      <a class="SurveysByUserAndStatus-open show-surveys-list">My Open Surveys <span class="surveys-count"></span></a>
                    </li>
                    <li>
                      <a class="SurveysByUserAndStatus-closed show-surveys-list">My Closed Surveys <span class="surveys-count"></span></a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="headingThree">
                <h5 class="mb-0">
                  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                All Open Surveys
              </button>
                </h5>
              </div>
              <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordion">
                <div class="card-body">
                  <ul>
                    <li>
                      <a class="AllSurveysByStatus-open show-surveys-list">View Open Surveys <span class="surveys-count"></span></a>
                    </li>
                    <li>
                      <a class="ByResponsesAndStatus-incomplete-open show-surveys-list">View Surveys I've started<span class="surveys-count"></span></a>
                    </li>
                    <li>
                      <a class="ByResponsesAndStatus-firstpass-open show-surveys-list">View Surveys pending my review <span class="surveys-count"></span></a>
                    </li>
                    <li>
                      <a class="ByResponsesAndStatus-complete-open show-surveys-list">View Surveys I've completed <span class="surveys-count"></span></a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header" id="headingFour">
                <h5 class="mb-0">
                  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                Other Admin Actions
              </button>
                </h5>
              </div>
              <div id="collapseFour" class="collapse" aria-labelledby="headingFour" data-parent="#accordion">
                <div class="card-body">
                  <ul>
                    <li>
                      <a class="upload-survey-link">Upload a survey</a>
                    </li>
                    <li>
                      <a class="logout-link">Log out</a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </nav>
        <div class="main-container col-lg-9 container" style="margin-top: 5%">

          <script>
            adminController.init();
          </script>
        </div>
      </div>
    </div>
  </body>

  </html>