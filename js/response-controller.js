/*******
 * Response Controller.
 *
 *   This is being built to enable survey responses to be a little more
 * responsive. The intent is to make survey responses interact on a change
 * by change basis -- when a user answers a question, that answer is saved
 * on the fly. This will also (in the case of radio buttons) need to remove
 * a current answer to this question.
 *
 *   This will also handle restoring a response. When the page is initially
 * hit, it should include a SurveyID. The responseController should call the
 * REST API using fetch() to get either a NEW response or an EXISTING response
 * for this survey. There is a limit of one response per user per survey, so 
 * this mechanism will handle that.
 *   When the response is returned, we need to check the ResponseStatus. This
 * will determine whether we are showing the AnswerChoices as simply plain
 * empty boxes, or whether we will include the heatMap information. There are
 * three possible options for ResponseStatus:
 * - 'incomplete' will display the survey information as a simple survey, with
 *    NO heatmap data.
 * - 'firstpass' will display the survey with heatmap coloring, allowing the
 *    user to emend their responses to match the herd.
 * - 'complete' will display the survey data with heatmap values, but without
 *    the ability to change those responses.
 ******/
var responseController = {
  response: {},
  formEl: null,
  
  /****
   *   init() gets the SurveyID from the url, then uses that to fetch() the 
   * response object.
   ****/
  init: function(formEl){
    var _RC = this;
    this.formEl = formEl;
    
    var SurveyID = GetURLParameter('SurveyID');
    if(!SurveyID){
      // No Survey ID, so we can't actually fetch anything. I think we need to
      //  return to the admin index in this case.
      window.location = '/admin-index.php';
    } else {
      /***
       * Here is where some magic happens.
       ***/
      var responseUrl = '/api/response/read-one.php?SurveyID='+SurveyID;
      var responsePromise = fetch(responseUrl, {credentials: 'include'})
        .then(response => response.json())
        .then(responseObj => {
          _RC.response = responseObj;
          if (_RC.response.ResponseStatus == 'incomplete' || _RC.response.ResponseStatus == 'firstpass'){
            /***
             *   In either of these cases, we may need to check/uncheck form input 
             * values.
             ***/
            _RC.updateFormValues();
          }
          if (_RC.response.ResponseStatus == 'firstpass'){
            /***
             *   In this case, we need to display heatmap values for
             * the current survey.
             ***/
            _RC.displayHeatmapValues();
          }
          if (_RC.response.ResponseStatus == 'complete'){
            /***
             *   In this particular case, we should redirect to the view results.
             ***/
            window.location = '/view-survey/index.php?SurveyID='+SurveyID;
          }
        });
      // Listen for radio buttons to be deselected. This is the ONLY WAY we
      //  can remove them from the collection of answers, I think!
      
      this.setupDeselectEvent();
      
      /***
       *   At this point, the survey has been displayed and updated, all works
       * great. Now we need to create the event listeners to handle the clicks
       * on input els.
       ***/
      $("form[name='survey-form'] input:radio").on("deselect", function(evt){
        var formEl = $(this);
          // Here we need to remove the given answer from the responseObj AND
          // remove it from the server.
        _RC.removeAnswer(formEl)
      })

      $(formEl).on("change", "input:checkbox, input:radio", function(evt){
        var formEl = $(this);
          // We will want to add an answer for the current question
        _RC.addAnswer(formEl);
      })
    }
  },
  /****
   * jQuery doesn't, by default, handle deselect well. We can use the following
   *  to set up a custom 'deselect' event on radio buttons, which will let us
   *  listen for those.
   * This is taken from nnnnnn's answer at:
   * https://stackoverflow.com/questions/11173685/how-to-detect-radio-button-deselect-event/11173862
   ****/
  setupDeselectEvent: function() {
      var selected = {};
      $('input[type="radio"]').on('click', function() {
          if (this.name in selected && this != selected[this.name])
              $(selected[this.name]).trigger("deselect");
          selected[this.name] = this;
      }).filter(':checked').each(function() {
          selected[this.name] = this;
      });
  },
  
  /****
   * updateFormValues() iterates over the returned response object, gets all answer values,
   *   and updates the form so that the given answer options are checked. This only applies
   *   to checkbox or radio els. Any text elements have their text value saved as a comment
   *   on the answer itself, so if we have a comment, we simply update the text el as needed.
   ****/
  updateFormValues: function(){
    var _RC = this;
    this.response.Answers.forEach(answer =>{
      $("[value='"+answer.AnswerChoiceID+"']").prop("checked", "checked");
      if (answer.Comment.length > 0){
        $("#"+ answer.AnswerChoiceID).val(answer.Comment);
      }
    });
  },
  
  /****
   * displayHeatmapValues() will, if the current instance is a 'firstpass',
   *  display the heatmap for each input field (checkbox and radio).
   ****/
  displayHeatmapValues: function(){
    var _RC = this;
    var answersArray = [];
    var surveyResponseCount = this.response.Survey.ResponseCount;
    this.response.Survey.Questions.forEach(function( question) { 
      question.AnswerChoices.forEach(answer => {
        answersArray.push(answer);
      });
    });
    
    answersArray.forEach(answer =>{
      var currentInput = $("[value="+answer.AnswerChoiceID+"]");
      var saturationVal = (answer.ResponseCount/surveyResponseCount)*100;
      var colorMeEl = currentInput.parents(".answer-choice");
      colorMeEl.css("background-color", "hsl(122, "+saturationVal+"%, 50%)");
    });
  }, // end displayHeatmapValues()
  
  /****
   * addAnswer() and removeAnswer() do much the same thing. If a given element is
   *  either checked or unchecked, we save them on the fly. They will ping different
   *  URLS, but functionally, they do much the same.
   ****/
  addAnswer: function(el){
    var _RC = this;
    var addAnswerUrl = '/api/answer/create.php';
    var addAnswerBody = {
      SurveyResponseID: _RC.response.SurveyResponseID,
      AnswerChoiceID: el.attr("id"),
      Comment: el.is("input:text") ? el.val() : ""
    }
    var addPromise = fetch(addAnswerUrl, {
      method: "post",
      credentials: "include",
      body: JSON.stringify( addAnswerBody)
    }).then(response => response.json())
    .then(responseObj => {
      _RC.response.Answers.push(responseObj);
    })
  },
  
  removeAnswer: function(el){
    var _RC = this;
    var answerObj = _RC.response.Answers.filter(answer => answer.AnswerChoiceID === el.attr("id"))[0];
    
    var removeAnswerUrl = '/api/answer/delete.php?AnswerID='+answerObj.AnswerID;

    var addPromise = fetch(removeAnswerUrl, {
      credentials: "include"
    }).then(response => response.text())
    .then(responseObj => {
      console.log(responseObj);
      if(responseObj.message == "Answer deleted."){
        _RC.response.Answers = _RC.response.Answers.filter(answer => answer.AnswerID != answerObj.AnswerID);
      }
    })
  }
}; // end responseController

function GetURLParameter(sParam)
{
  var sPageURL = window.location.search.substring(1);
  var sURLVariables = sPageURL.split('&');
  for (var i = 0; i < sURLVariables.length; i++) 
  {
      var sParameterName = sURLVariables[i].split('=');
      if (sParameterName[0] == sParam) 
      {
          return sParameterName[1];
      }
  }
  // If we're here, the param was not found. Return null;
  return null;
}

function dateFormat(inputDate) {
    var date = new Date(inputDate);
    if (!isNaN(date.getTime())) {
        var day = date.getDate().toString();
        var month = (date.getMonth() + 1).toString();
        // Months use 0 index.

        return (month[1] ? month : '0' + month[0]) + '/' +
           (day[1] ? day : '0' + day[0]) + '/' + 
           date.getFullYear();
    }
}
