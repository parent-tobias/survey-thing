<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Stirphi</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <link href='https://fonts.googleapis.com/css?family=Raleway:300,200' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">

  <script src="/js/login.js"></script>
  <link rel="stylesheet" href="css/style.css">
</head>

<body>

  <div class="jumbotron">
    <h1 id="title" style="colour:white;">Stirphi</h1>
    <p>Welcome to the University of Stirling eDelphi platform</p>
  </div>


  <div id="Error1" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 style="font-size: 30px; ">Error</h1>
        </div>
        <div class="modal-body">
          <p style="font-size: 25px;">
            Invalid username/password
          </p>
        </div>
        <div class="modal-footer">
          <a href="#" class="btn" data-dismiss="modal">Ok</a>
        </div>
      </div>
    </div>
  </div>

  <div id="Error2" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 style="font-size: 30px; ">Error</h1>
        </div>
        <div class="modal-body">
          <p style="font-size: 25px;">
            Fields cannot be empty.
          </p>
        </div>
        <div class="modal-footer">
          <a href="#" class="btn" data-dismiss="modal">Ok</a>
        </div>
      </div>
    </div>
  </div>
  <div class="form login-pane">

    <div class="forceColor"></div>
    <div class="topbar">
      <div class="container">
        <h1> Sign into your account </h1>
      </div>

      <div class="spanColor"></div>
      <form action="Login.php" method="POST">
        <input type="email" class="input" id="email" placeholder="Username" />
        <input type="password" class="input" id="password" placeholder="Password" />

        <div class="checkbox">
          <label><input type="checkbox" name="remember"> Remember me</label>
        </div>

        <input id="submit" name="submit" type="button" value="Login" />
        <div class="Signup">
          <label> <p id="Signup"> Don't have an account? <a id="llink" href="register/signup.html"> Sign-up here! </a></p> </label>
          <label> <p id="Forgot">Forgotten password? <a id="flink" href="Forgotten.html"> Click here! </a></p> </label>
        </div>
      </form>
    </div>
  </div>
</body>

</html>