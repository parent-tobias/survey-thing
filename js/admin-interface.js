var adminController = {
  /*****
   * surveyCollections[] will contain all the survey collections we fetch
   *   from the API. We will use each of the paths below to determine each
   *   collection. If we need to get more collections results, we can simply
   *   add another object to the readSurveys array with a title, ajaxUrl and
   *   targetUrl. If I figure this right, we use the title to set a data 
   *   attribute on the link to the collection, the ajaxUrl to tell us where 
   *   the API resides for this fetch, and the targetUrl to set the target
   *   URL for this collection.
   *****/
  surveyCollections: [],
  paths: {
    readSurveys: [
      {
        "title":"SurveysByUserAndStatus-editing",
        "ajaxUrl":"/api/survey/read.php?Status=editing&User=me",
        "targetUrl":"/new-survey/?SurveyID="
      },
      {
        "title":"SurveysByUserAndStatus-open",
        "ajaxUrl":"/api/survey/read.php?Status=open&User=me",
        "targetUrl":"/view-survey/?SurveyID="
      },
      {
        "title":"SurveysByUserAndStatus-closed",
        "ajaxUrl":"/api/survey/read.php?Status=closed&User=me",
        "targetUrl":"/view-survey/?SurveyID="
      },
      {
        "title":"AllSurveysByStatus-open",
        "ajaxUrl":"/api/survey/read.php?Status=open",
        "targetUrl":"/take-survey/?SurveyID="
      },
      {
        "title":"ByResponsesAndStatus-incomplete-open",
        "ajaxUrl":"/api/response/read.php?Status=open&ResponseStatus=incomplete",
        "targetUrl":"/take-survey/?SurveyID="
      },
      {
        "title":"ByResponsesAndStatus-firstpass-open",
        "ajaxUrl":"/api/response/read.php?Status=open&ResponseStatus=firstpass",
        "targetUrl":"/take-survey/?SurveyID="
      },
      {
        "title":"ByResponsesAndStatus-complete-open",
        "ajaxUrl":"/api/response/read.php?Status=open&ResponseStatus=complete",
        "targetUrl":"/view-survey/?SurveyID="
      }
    ]
  },
  init: function(){
    var that = this;
    this.containerEl = $(".main-container");
    // First, get all the collected survey sets from the urls above, then parse them to JSON
    var promises = this.paths.readSurveys.map(member => fetch(member.ajaxUrl, {credentials: 'include'}).then(response=>response.json()));
    // When ALL those promises are resolved, we can build the collection.
    Promise.all(promises).then(results => {
      results.map(result => {
//        console.log(result, result[0].resultType);
         var collectionObject = {};
         collectionObject.title = result[0].resultType;
             result.shift();
             collectionObject.results = result;
             that.surveyCollections.push(collectionObject);

             //At this point, the surveyCollections object contains all the survey results. Let's do some DOM!
             var domNode = $("."+collectionObject.title);
             domNode.data("title", collectionObject.title)
               .children(".surveys-count").text("("+collectionObject.results.length+" Items)");

      })
    });
    var uploadElPromise = fetch('/new-survey/upload-survey.html')
          .then(response=>response.text())
          .then(htmlFragment => that.uploadEl = $(htmlFragment));
    
    $(".show-surveys-list").on("click", function(){
      that.containerEl.empty();
      // Get the data-title of the clicked element. We can use this to get the config values
      //  for this collection of surveys.
      var collectionTitle = $(this).data("title"),
          
          // filter the paths array to a single element: the one with this title.
          collectionConfig = that.paths.readSurveys.filter(path => path.title == collectionTitle)[0],
          // Using that collectionConfig object, we can get the targetUrl
          collectionUrl = collectionConfig.targetUrl;
      
      
      // Depending on the element clicked, we have different processing.
      switch(collectionTitle){
        case "SurveysByUserAndStatus-editing":
        case "SurveysByUserAndStatus-open":
        case "SurveysByUserAndStatus-closed":
        case "AllSurveysByStatus-open":
          
          var collection = that.surveyCollections.filter(el => el.title == collectionTitle)[0];
          collection.results.map(surveyObject => {
            var surveyEl=$("<div><a href='"+collectionUrl+surveyObject.SurveyID+
                               "'>Title: "+surveyObject.Title+
                               "</a>, Description: "+surveyObject.Description+
                               " (created by "+surveyObject.User.firstname+" "+surveyObject.User.lastname+
                               " on "+surveyObject.CreatedAt+")</div>");
            that.containerEl.append(surveyEl);
          });
          break;

        case "ByResponsesAndStatus-incomplete-open":
        case "ByResponsesAndStatus-firstpass-open":
        case "ByResponsesAndStatus-complete-open":
          
          // responses have a totally different format. And they contain their survey.
          var collection = that.surveyCollections.filter(el => el.title == collectionTitle)[0];
          collection.results.map(responseObject => {
            //console.log(responseObject);
            var surveyEl=$("<div><a href='"+collectionUrl+responseObject.Survey.SurveyID+
                               "'>Title: "+responseObject.Survey.Title+
                               "</a>, Description: "+responseObject.Survey.Description+
                               ", Started on "+responseObject.CompletedAt+"</div>");
            that.containerEl.append(surveyEl);
          });
          break;
        default:
          collectionUrl="";
      }

    })
    $(".upload-survey-link").on("click", function(){
      that.containerEl.html(that.uploadEl);
    });
    this.containerEl.on("click", ".upload-survey-pane .cancelBtn", function(evt){
      evt.preventDefault();
      evt.stopPropagation();
      that.containerEl.empty();
    }).on("click", ".upload-survey-pane .saveBtn", function(evt){
      evt.preventDefault();
      evt.stopPropagation();
      var surveyText = $(".upload-survey-pane textarea").val();
      var uploadPromise = fetch('/api/survey/upload-all.php', {
        method: "POST",
        credentials: "include",
        body: surveyText
      }).then(response => response.json())
      .then(responseObj => {
        console.log(responseObj);
        if(responseObj.SurveyID){
          if(confirm("Your Survey was uploaded.") ){
            that.containerEl.empty();
            that.init();
          }
        } else {
          alert("There was a problem with your data. Please double-check the format with a JSON formatter.");
        }
      })
    });
    
    $(".logout-link").on("click", function(){
      // Handle the logging out of the user, destroy the session on the back end.
      var logoutPromise = fetch('/api/user/logout.php')
      // Regardless of the response, we simply return to the login screen.
      .then(response => window.location.href = "/index.php");
    });
    
  }
  
};

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