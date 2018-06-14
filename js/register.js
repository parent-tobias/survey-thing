$(document).ready(function(){
  var passwordEl = $("input[type='password']"),
      firstnameEl = $("input#firstname"),
      lastnameEl = $("input#lastname"),
      emailEl = $("input#email"),
      submitBtn = $("#submit");
  

  function isEmpty(element){
    if(element.val().length === 0){
      return true;
    } else {
      return false;
    }
  }
  
  function check_password(){
    $("#pswd_info").show();
    var pswd = passwordEl.val();
    var passwordIsValid = true;
    
    if ( pswd.length < 8 ) {
      $('#length').removeClass('valid').addClass('invalid');
      passwordIsValid = false;
    } else {
      $('#length').removeClass('invalid').addClass('valid');
    }

    if ( pswd.match(/[A-z]/) ) {
      $('#letter').removeClass('invalid').addClass('valid');
    } else {
      $('#letter').removeClass('valid').addClass('invalid');
      passwordIsValid = false;
    }

    if ( pswd.match(/[A-Z]/) ) {
      $('#capital').removeClass('invalid').addClass('valid');
    } else {
      $('#capital').removeClass('valid').addClass('invalid');
      passwordIsValid = false;
    }

    if ( pswd.match(/\d/) ) {
      $('#number').removeClass('invalid').addClass('valid');
    } else {
      $('#number').removeClass('valid').addClass('invalid');
      passwordIsValid = false;
    }

    if ( pswd.match(/[^a-zA-Z0-9\-\/]/) ) {
      $('#space').removeClass('invalid').addClass('valid');
    } else {
      $('#space').removeClass('valid').addClass('invalid');
      passwordIsValid = false;
    }
    
    return passwordIsValid;
  }
  
  function toggleDisplayPasswordInfo(){
    if($("#pswd_info li").hasClass("invalid")){
      $("#pswd_info").show();
    } else {
      $("#pswd_info").hide();
    }
  }

  function check_email(){
    var email = emailEl.val();
    var atpos  = email.indexOf("@");
    var dotpos = email.lastIndexOf(".");


    if (atpos<1 || dotpos<atpos+2 || dotpos+2>=email.length) {
      $("#emailExistsNote").html("<span style='color:red;'> <b>Invalid email</b> </span>");
      emailEl.val("");
      return false;
    }

    var emailPromise = fetch("/api/user/check-email.php?email="+email)
                      .then(function(response) {
                          if (!response.ok) {
                              throw Error(response.statusText);
                          }
                          return response.json();
                      }).then(function(emailStatus) {
                          if(emailStatus.accountExists){
                            // The email account is already in use. Error!
                            $("#emailExistsNote").html("<span style='color: red'>In use.</span>");
                            emailEl.val("");
                          } else {
                            // No problem, the email is available.
                            $("#emailExistsNote").html("<span style='color:#41f456;'>Available</span>");
                          }
                      }).catch(function(error) {
                          console.log(error);
                      });
  }
  
  submitBtn.on("click", function(evt){
    var userDataIsValid = true;
    evt.preventDefault();
    evt.stopPropagation();
    
    // Now, validate all the fields!
    if (isEmpty(firstnameEl)) {
      userDataIsValid = false;
      $('#Error1').modal({
          show: true
      });
        //alert("Error: First name is missing.");
    }
    if (isEmpty(lastnameEl)) {
      userDataIsValid = false;
      $('#Error2').modal({
          show: true
      });
      //alert("Error: Last name is missing.");
    }
    if (isEmpty(emailEl)) {
      userDataIsValid = false;
      $('#Error3').modal({
          show: true
      });
      //alert("Error: Email is missing.");
    }
    if (isEmpty(passwordEl) && !check_password()) {
      userDataIsValid = false;
      $('#Error4').modal({
          show: true
      });
      //alert("Error: Password is missing or invalid.");
    }
    
    if(userDataIsValid){
      // At this point, all the form fields have validated, so we can create
      //  a Promise to send the user account.
      var User = {
        firstname: firstnameEl.val(),
        lastname: lastnameEl.val(),
        email: emailEl.val(),
        password: passwordEl.val()
      };
      console.log(JSON.stringify(User));
      
      var createUserPromise = fetch("/api/user/create.php", {
                                    method: "POST",
                                    body: JSON.stringify(User)
                                    })
                    .then(function(response) {
                          if (!response.ok) {
                              throw Error(response.statusText);
                          }
                          return response.json();
                      }).then(function(accountStatus) {
                          console.log(accountStatus);
                      }).catch(function(error) {
                          console.log(error);
                      });
    }
  });
  passwordEl
    .on("keyup focus", check_password)
    .on("blur", toggleDisplayPasswordInfo);
  
  emailEl.on("blur", check_email);
});
