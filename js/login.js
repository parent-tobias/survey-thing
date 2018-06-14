$(document).ready(function() {
  $("#submit").click(function(evt) {
    evt.stopPropagation();
    evt.preventDefault();

    var email = $("#email").val();
    var password = $("#password").val();

    if (email.length === 0 || password.length === 0) {
      $('#Error2').modal({
        show: true
      });
      //alert( "Empty fields" );
    } else {
      var userObj = {
        email: email,
        password: password
      };
      console.log(JSON.stringify(userObj));
      
      var loginPromise = fetch("./api/user/login.php", {
                               method: "POST",
                               credentials: "include",
                               body: JSON.stringify(userObj)})
                .then(response => {return response.json();} )
                .then(jsonObj => {
                  var loginCookie = jsonObj.User.email;
                  ///console.log(loginCookie);
                  setCookie("email", loginCookie, 2);
//                  createCookie("loggedIn", loginCookie, 1);
                if(jsonObj.loggedIn){
                  window.location.href="./admin-index.php";
                } else {
                  alert("Login failed. please try again");
                }
                });

      }
  });
});

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}