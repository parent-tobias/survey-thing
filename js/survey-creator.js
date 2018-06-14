/*****
 * surveyController
 *   An object controlling the survey creation process, it handles 
 *   switching screens, saving and retrieving server data, creating
 *   the survey object, and maintaining it throughout.
 *****/
var surveyController = {
  // .init() sets a lot of startup values, and creates the
  //  various screens. this.stepsEls[] contains the HTML for
  //  each step. 
  init: function(el){
    // Save a reference to this, a scoped reference that we'll lose in
    //   nested functions
    var that = this;
    // Our parent container
    this.containerEl = el;
    // What step of the survey creation we're on. This will let prev/next
    //  function appropriately.
    this._step = 0;
    // an array of jQuery-wrapped HTML DOM nodes. These are the survey meta,
    //  the questions step, and probably some sort of finalization. And crap:
    //  'finalization' means we probably need a 'finalized' field in the surveys
    //  table, in order to make the survey available to users after its done 
    //  being edited.
    this.stepsEls = [];
    
    // ** This is the survey object itself. We are creating this, so that when we
    //   save the survey at the end of step 1, we can store its id somewhere. Then,
    //   when we create the questions, we can link them directly to that id and create
    //   the questions in a questions array on this survey object.
    this.survey = {};
    
    // Display the survey creator in a modal dialog. Currently this requires the
    //   jQueryUI library.
    this.mySurveyModal = this.containerEl.dialog({
        autoOpen: true,
        title: "New Survey",
        height: $(window).height()-50,
        width: $(window).width()-50,
        modal: true,
    })
    
    // If the window is resized, fit the dialog to the window.
    $(window).on('resize', function(){
      that.containerEl
          .dialog("option", "width", $(window).width()-50)
        .dialog("option", "height", $(window).height()-50);
    })
    // I don't want button els to take us somewhere unexpected, so cancel
    //   default behavior of buttons.
    this.containerEl.on("click", "button", function(evt){
      evt.stopPropagation();evt.preventDefault();

    })
    /***
     * This is the part that gets a little weird. In order to minimize the amount of
     *   clutter in the index page, I've created a series of snippets, HTML DOM fragments,
     *   that are saved in their own HTML files. These are then fetched, and stored into
     *   the stepsEls array. That allows us to fetch them in advance, and simply put them
     *   in place as we need them.
     *
     * Using fetch(), we create Promises. using then() we can extract the text from the
     *   response once the Promise has returned.
     * Each promise does much the same thing, so I only commented the first one.
     *
     ***/
    var step1Promise = fetch('./new-survey.html', {credentials: "include"})
                    // After a Promise has been completed, we call .then() to
                    //  do something with its returned value.
                    .then(function(response){ 
                      // fetch() is a modern replacement for XmlHttpRequest, 
                      //  and we use text() to get the text off that.
                      //  NOTE: THIS ALSO RETURNS A PROMISE, with a text argument
                      return response.text();
                    })
                    // Here, we take that text, which is the DOM node for our survey
                    //   creation data, and put it in step1 on our stepsEls array.
                    //   Also, we inject it into the container, and set up listeners.
                    .then(function(text){
                      // Set the stepsEl to the returned text
                      that.stepsEls[0] =  $(text) ;
                     /***
                     * Here we do the same as above. We will also add a listener to the next and prev
                     *   buttons here, I simply haven't as yet. 'Next' should take us to some sort of
                     *   confirmation and finalization step in the survey creation, perhaps displaying
                     *   the survey itself. Confirmation at THAT point should change the survey's status
                     *   from 'editing' to either 'open' or 'pending', depending on its start date.
                     ***/
                    var step2Promise = fetch('./new-survey-questions.html', {credentials: "include"})
                                      .then(function(response){
                                        return response.text();
                                      })
                                      .then(function(text){
                                        that.stepsEls[1] = $(text);
                                        if(GetURLParameter("SurveyID")){
                                          // Here, if we were passed a survey object, we can populate
                                          //  the questions. I think.
                                          that.loadExistingSurvey(Number(GetURLParameter("SurveyID")) )
                                        }
                                        
                                        that.stepsEls[1].on("click", "#addq", function(){
                                          that.addQuestion("#questions")
                                        })
                                        // this is the screen for the third step, to finalize a survey.
                                        //  finalizing will set the status from 'editing' to 'open'.
                                        var step3Promise = fetch('./new-survey-finalize.html', {credentials: "include"})
                                                           .then(function(response){
                                                                 return response.text();
                                                           })
                                                           .then(function(text){
                                                             that.stepsEls[2] = $(text);
                                                           })
                                      });
                        // Populate our initial display screen.
                        that.containerEl.html(that.stepsEls[0]);
                      // Set up a click listener for the next button
                      that.containerEl.on("click", ".survey-summary-pane button.nextBtn", function(){
                        // If the next button is clicked, we need to save the survey.
                        //  Note that we haven't done any validation or anything, and
                        //  that could be a bug.
                        //  ***?***?***?***?***
                        if(that.stepsEls[0].data("surveyid")) {
                          that.updateSurvey(that.stepsEls[0]);
                        } else {
                          that.saveSurvey(that.stepsEls[0]);
                        }
                      })
                      .on("click", ".survey-questions-pane button.nextBtn", function(){
                        that.nextStep();
                      })
                      .on("click", ".survey-finalize-pane button.nextBtn", function(){
                        that.finalizeSurvey();
                      });
                    });

    
    /****
     * Each screen in the stepsEls will have a nextBtn and prevBtn, I think.
     *  We want to be able to move between the screens and set the current
     *  step's HTML DOM fragment as appropriate.
     ****/
    this.containerEl.on("click", "button.prevBtn", function(){
      switch(that._step){
        case 0:
          /****
           * Processing for the Prev button. This logic should be exactly the
           *  same as the Next button, just in reverse. Get the step number, and
           *  determine which step to display. This will allow editing and updating
           *  of (for example), the survey form.
           *  0 = step 1, the survey metadata entry screen.
           *  1 = step 2, the survey questions entry screen.
           *  2 = step 3, the finalize screen (? this is still blurry in my head)
           *
           * In the case of 0, we want to increment that step value, and we want
           *   to update the DOM fragment in our stepsEls to reflect the actual
           *   survey metadata. Doing this, if the user clicks 'Prev' from the 
           *   question entry screen, we can simply use stepsEls[0] to redisplay
           *   that form. 
           ****/
          if(confirm("Are you sure you want to leave the survey editor?")){
            window.location = '/admin-index.php';
          }
          break;
        case 1:
          // Here, we're going from the question-editing screen BACK to the
          //   the survey editing screen. So we decrement that step variable,
          //   we copy the current version of the question editing DOM chunk
          //   to our array, we remove that DOM node, and we insert the survey
          //   editor back into the container.
          that._step--;
          that.stepsEls[1] = that.containerEl.find(".survey-questions-pane").clone(true);
          that.containerEl
            .find(".survey-questions-pane")
              .slideUp("slow", function(){
                this.remove();
              });
            that.containerEl.html(that.stepsEls[0].show());
          break;
        case 2:
          // If we ever get to the point of having a finalize screen, this would
          //   let us revert to the question editing screen from that, allowing
          //   last-minute editing prior to finalizing the survey.
          // Finalizing, when it is finally implemented, should solely be
          //   setting the status column on the surveys table from the 'Editing'
          //   to the 'Open' or 'Pending' status, depending on the date.
          that._step--;
          that.stepsEls[2] = that.containerEl.find(".survey-finalize-pane").clone(true);
          that.containerEl
            .find(".survey-finalize-pane")
              .slideUp("slow", function(){
                this.remove();
              });
            that.containerEl.html(that.stepsEls[1].show());
          break;
        default:
          that._step=0;
          break;
      }
    })

  },
  /****
   * surveyController.nextStep()
   *  Originally, this was working as a separate listener, so the save and the
   *  storyboard flow were two separate concerns. By doing this, I think there
   *  is a conflict -- when I save the survey form, for example, I want to be able
   *  to add a data attribute to that form, and also to store the form itself
   *  into our steps variable. If I save the form in a separate handler, then
   *  update the id in a separate thread, there is no guarantee that the two
   *  won't destructively crash. So instead, when the save happens, it should call
   *  nextStep and/or previousStep itself. I think.
   ****/
  nextStep: function(){
    var that=this;
    switch(this._step){
      case 0:
        /****
         * So the user clicked on the next button. At this poing, I don't
         *  know if its the next on the first step, the second step, or the
         *  finalize step (for example), so we have a _step property.
         *  0 = step 1, the survey metadata entry screen.
         *  1 = step 2, the survey questions entry screen.
         *  2 = step 3, the finalize screen (? this is still blurry in my head)
         *
         * In the case of 0, we want to increment that step value, and we want
         *   to update the DOM fragment in our stepsEls to reflect the actual
         *   survey metadata. Doing this, if the user clicks 'Prev' from the 
         *   question entry screen, we can simply use stepsEls[0] to redisplay
         *   that form. 
         ****/
        that._step++;
        // Remove the survey meta information screen, and display the
        //  questions entry screen.
        that.stepsEls[0] = that.containerEl.find(".survey-summary-pane").clone(true);
        that.containerEl
          .find(".survey-summary-pane")
            .slideUp("slow", function(){
              this.remove();
            }).end()
          .append(that.stepsEls[1]);
        break;
      case 1:
        // Here, we're going to simply set the Status value of the survey
        //   itself as 'Open'. Up to this point, it should be 'Editing'.
        that._step++;
        // Remove the survey questions screen, and update and display the
        //  survey finalize screen.
        that.fillFinalizeScreen();

        that.stepsEls[1] = that.containerEl.find(".survey-questions-pane").clone(true);
        that.containerEl
          .find(".survey-questions-pane")
            .slideUp("slow", function(){
              this.remove();
            }).end()
          .append(that.stepsEls[2]);
        break;
      default:
        that._step=0;
        break;
    }
  },
  /****
   * surveyController.saveSurvey()
   *
   * Function to populate the survey object, and then send that to the back end
   *  When the fetch returns, we are simply getting a survey object back, and we
   *  just pull the id from that and stick it into our survey object itself.
   ****/
  saveSurvey: function(surveyForm){  
    console.log("creating a new survey...");
    var that = this;
    // surveyController.survey contains the survey Object. We need to populate it
    //  so we can then stringify it.
    this.survey.Title = surveyForm.find("[name='Title']").val() || null;
    this.survey.Description = surveyForm.find("[name='Description']").val() || null;
    this.survey.StartDate = surveyForm.find("[name='StartDate']").val() || null;
    this.survey.EndDate = surveyForm.find("[name='EndDate']").val() || null;
    this.survey.Questions = [];

    // This one will need to be wired to get the current user id!
    this.survey.UserID = 1;

    // The form body needs to be sent as a string.
    var mySurvey = JSON.stringify(this.survey);

    /****
     * Fetch is again being used, so as not to tie up the rest of the application.
     *  At this point, we are pinging the RESTful API we created to create a new
     *  survey.
     ****/
    var saveSurveyPromise = fetch('/api/survey/create.php', {
      method: 'POST',
      credentials: "include",
      body: mySurvey
    })
    .then(function(response){
      // When we  get a response, turn the string back to an object...
      return response.json(); 
    })
    .then(function(returnedSurveyObject){
      // ... and remove the ID from the returned object to use in OUR object.
      that.survey.SurveyID = returnedSurveyObject.SurveyID;
      that.survey.CreatedAt = returnedSurveyObject.CreatedAt;
      // ALso, as we've created a new survey, we should append that survey's
      //  id to the survey screen. I first clone the survey DOM chunk back into
      //  our stepsEls variable, then I add a data-attribute to that. Later,
      //  if the user clicks the previous button to return to editing the survey,
      //  or if they come back to a survey later for editing, we can simply check
      //  for the existence of that ID and know whether we're creating or updating
      //  a survey.
      that.stepsEls[0] = that.containerEl.find(".new-survey-pane").clone(true);
      that.stepsEls[0].attr("data-SurveyID", that.survey.SurveyID);
      
      // And last, call the nextStep function, to move on to entering questions!
      that.nextStep();
    })
  },
  
  /****
   * surveyController.updateSurvey()
   *
   * Function to populate the survey object, and then send that to the back end
   *  When the fetch returns, we are simply getting a survey object back, and we
   *  just pull the id from that and stick it into our survey object itself.
   ****/
  updateSurvey: function(surveyForm){
    console.log("updating, not creating.");
    var that = this;
    // surveyController.survey contains the survey Object. We need to populate it
    //  so we can then stringify it.
    this.survey.SurveyID = surveyForm.data("surveyid");
    this.survey.Title = surveyForm.find("[name='Title']").val() || null;
    this.survey.Description = surveyForm.find("[name='Description']").val() || null;
    this.survey.StartDate = surveyForm.find("[name='StartDate']").val() || null;
    this.survey.EndDate = surveyForm.find("[name='EndDate']").val() || null;

    // The form body needs to be sent as a string.
    var mySurvey = JSON.stringify(this.survey);

    /****
     * Fetch is again being used, so as not to tie up the rest of the application.
     *  At this point, we are pinging the RESTful API we created to create a new
     *  survey.
     ****/
    var saveSurveyPromise = fetch('/api/survey/update.php', {
      method: 'POST',
      credentials: "include",
      body: mySurvey
    })
    .then(function(response){
      // When we  get a response, turn the string back to an object...
      return response.json(); 
    })
    .then(function(returnedSurveyObject){
      // ... and remove the ID from the returned object to use in OUR object.
      that.nextStep();
    })
  },
  /****
   * surveyController.fillFinalizeScreen()
   *
   * Function to populate the finalize display. This displays all the survey data,
   *   allowing the user to double-check that all the survey values are what they
   *   expect. Once reviewed, the user can click on the 'Finalize' button, which 
   *   changes the survey's status from editable to open, allowing it to be consumed
   *   by other users.
   *
   *   This particular function simply populates placeholders in the finalize
   *   DOM snip for user consumption.
   ****/
  fillFinalizeScreen: function(){
    // create references for necessary spaces.
    var that = this,
        finalizeEl = this.stepsEls[2],
        questionsEl = finalizeEl.find(".survey-questions-finalize-pane");
        survey = this.survey;
    questionsEl.empty();
    
    
    finalizeEl.find(".survey-title").text(survey.Title).end()
              .find(".survey-description").text(survey.Description).end()
              .find(".survey-startdate").text(survey.StartDate).end()
              .find(".survey-enddate").text(survey.EndDate);
    survey.Questions.map((Question, Index) => {
      var questionContainer = $("<div>").addClass("row question-container container-fluid").css({
                     "border-bottom": "1px solid black",
                     "margin": "10px 0"
          }),
          commentEl = $("<div>").addClass("row").html("<div class='col'>"+Question.Comment+"</div>"),
          questionEl = $("<div>").addClass("row").css("width", "100%"),
          indexEl=$("<div>").addClass("col-1").text(Index+1),
          textEl = $("<div>").addClass("col-3").text(Question.Text),
          typeEl = $("<div>").addClass("col-2").text( Question.QuestionType ),
          answerChoicesEl = $("<div>").addClass("col-6 ");
      Question.AnswerChoices.map(Answer => {
        var answerEl = $("<div>").css({"display":"inline-block", "padding": "0 5px"}).text(Answer.answer);
        answerChoicesEl.append(answerEl);
      });
      questionEl.append(indexEl, textEl, typeEl, answerChoicesEl);
      questionContainer.append(commentEl, questionEl);
      questionsEl.append(questionContainer);
      
    });    
    
  },
  
  /****
   * surveyController.updateSurvey()
   *
   * Function to populate the survey object, and then send that to the back end
   *  When the fetch returns, we are simply getting a survey object back, and we
   *  just pull the id from that and stick it into our survey object itself.
   ****/
  finalizeSurvey: function(surveyForm){
    console.log("Finalizing your survey now...");
    var that = this;

    /****
     * Fetch is again being used, so as not to tie up the rest of the application.
     *  At this point, we are pinging the RESTful API we created to create a new
     *  survey.
     ****/
    var finalizeSurveyPromise = fetch('/api/survey/finalize.php?SurveyID='+this.survey.SurveyID+"&Status=open", {
      credentials: "include"
    })
    .then(function(response){
      // When we  get a response, turn the string back to an object...
      return response.json(); 
    })
    .then(function(returnedSurveyObject){
      // ... and remove the ID from the returned object to use in OUR object.
      //  Go back to the main survey admin screen!
      if(returnedSurveyObject.open){
        // Survey is now open for editing, no problems found.
        window.location.href='/admin-index.php';
      } else {
        console.log(returnedSurveyObject.message);
      }
    })
  },
  /****
   * surveyController.loadExistingSurvey()
   *
   * This is very in development at the moment. It is run when two conditions
   *   are met: first, the first step DOM chunk has loaded into stepsEls (that
   *   is where this function is called), and second, there is a URL parameter
   *   called id (for example, /new-survey/?id=140). That id is the survey id,
   *   which we can use here to run /api/survey/read-one.php which will return
   *   a survey object to us. We can then populate the DOM node which was just
   *   loaded, and render it into the appropriate container.
   *
   ****/
  loadExistingSurvey: function(SurveyID){
    // Avoid the scope gotcha, create a hard reference to 'this'
    var that = this;

    // Start the fetch going. This will simply fetch the survey object, no
    //  question objects.
    var loadSurveyPromise = fetch('/api/survey/read-one.php?SurveyID='+SurveyID, {credentials: "include"})
    .then(function(response){
      // Convert the returned response to a JSON object
      var responseObj = response.json();
      return responseObj;
    }).then(function(surveyObject){
      // ... and take that JSON object, inject it into our surveyController.
      //  NOTE: As of 3/15/2018, this object includes the questions.
      that.survey = surveyObject;
     
      // Also at this point, we want to fill in the survey DOM node with the
      //   correct information that we just retrieved...
      
      that.stepsEls[0]
          .attr("data-SurveyID", surveyObject.SurveyID)
          .find("[name='Title']").val(surveyObject.Title).end()
          .find("[name='Description']").val(surveyObject.Description).end()
          .find("[name='StartDate']").val(surveyObject.StartDate).end()
          .find("[name='EndDate']").val(surveyObject.EndDate);
      
      // We've loaded up the survey, now we can do the same with the questions
      //  frame. At this point, both the panes have been loaded (via their Promises)
      //  and the survey object itself is loaded. Thus, we should have everything
      //  we need to populate the questions!
      that.loadExistingQuestions(that.stepsEls[1].find("#questions"));
    })
    // And dump the newly-populated DOM node back into the container.
    that.containerEl.html(that.stepsEls[0]);
  },
  
  /***
   * loadExistingQuestions()
   *   This should populate the stepsEls[1] question container with populated
   *   questions. We have, in surveyController.survey.questions, an array of
   *   all current questions within this survey.
   ***/
  loadExistingQuestions: function(containerEl){
    var that = this;
    var questions = this.survey.Questions;
    
    for(var i=0; i < questions.length; i++){
      var questionEl = that.addQuestion(containerEl);
      var answerOptionsEl = questionEl.find("."+questions[i].QuestionType.toLowerCase()+"-answer-options");

      questionEl
        .attr("data-QuestionID", questions[i].QuestionID)
        .find(".comment-el")
             .val(questions[i].Comment)
             .trigger("change").end()
        .find(".question-el")
             .val(questions[i].Text)
             .trigger("change").end()
        .find("[name^=qType][value="+questions[i].QuestionTypeID+"]")
             .prop("checked", true).trigger("change").end();
      answerOptionsEl.show().find("label").remove();

      for (var j=0; j<questions[i].AnswerChoices.length; j++){
        var myAnswerOptionEl;
        switch (questions[i].QuestionType){
          case "Radio":
            myAnswerOptionEl = that.addRadioOptions(answerOptionsEl);
            break;
          case "Checkbox":
            myAnswerOptionEl = that.addCheckboxOptions(answerOptionsEl);
            break;
          case "Text":
            myAnswerOptionEl = that.addTextOptions(answerOptionsEl);
            break;  
          default:
            break;
        }
        myAnswerOptionEl
               .find("input[type='text']")
               .val(questions[i].AnswerChoices[j].answer).end()
               .attr("data-id", questions[i].AnswerChoices[j].AnswerChoiceID)
               .attr("data-changed", false).end();
        answerOptionsEl.trigger("change");
      }

      that.togglePreview(questionEl.find(".preview-pane"));
    }
  },

    /***
     * function addQuestion(container)
     *    Used to create a question collection within the container. The
     *    question collection contains the question, the answer set (which in
     *    turn contains the answer option toggle and the actual answers), the
     *    preview pane and the question controls.
     ***/
    addQuestion: function(questionsEl) {
      // A reference to the current object (surveyController)
      var that = this;
      
      // A reference to the questions root element.
      this.questionsEl = $(questionsEl);
      // Every time we add another question, we need to check how many questions
      //  we currently have. For the sake of my poor brain, any new question is
      //  inserted at the end of the list, so getting the count of questions is
      //  a means of indexing the new question.
      var i = this.questionsEl.find(".question-container").length + 1;

      // First we create the question element, simply a text input wrapped in a label.
      //  This is where we use that index we created above -- the name of the question
      //  is based off that artificial index.
      //  ** WITHOUT THAT, WE HAVE NO MECHANISM FOR ORDERING QUESTIONS IN THE ACTUAL
      //     SURVEY!! ** There are other options, but I think they may be beyond the
      //     scope of what we're doing.
      var newQuestionEl = $("<input>").prop({
        "type": "text",
        "name": "q" + i,
        "id": "q" + i,
        "class": "form-control question-el"
      }).on("keyup change", function() {
        // As the user types into the question el, we want to automatically update
        //  the preview pane (the plain HTML version of the question, displayed when
        //  the user is editing another question or when the save the current one)
        that.updatePreview(newQuestion, newAnswerEl, previewContainerEl);
      });
      var newQuestionCommentEl = $("<textarea>").prop({
        "name": "q-comm"+i,
        "id": "q-comm"+i,
        "class": "form-control comment-el",
        "placeholder":"Custom comment..."
      }).on("keyup change", function() {
        // Same with the comment: keep the static preview in sync with this version.
        that.updatePreview(newQuestion, newAnswerEl, previewContainerEl);
      });
      // This div is used to contain the actual question within the total 
      //  question-container div.
      var newQuestion = $("<div>").prop({
        "class": "question-pane"
      }).append("Question #" + i + ": ", newQuestionCommentEl, newQuestionEl );

      // Next, we create an array of options to determine what type of question this is:
      //  radio, checkbox or text. The returned value corresponds to the value in our
      //  MySQL table on the back end.
      /***
       * ---> NEEDS TO BE DONE!!! <---
       *
       * Ideally, I should fetch the collection of questionTypes from the REST API, then
       *  use that to create each of these. At this point, it is hard-coded in, but we will
       *  need to change that in a future revision.
       ***/
      var newQTypeArr = [];
      var newQTypeRadioEl = $("<input>").prop({
        name: "qType" + i,
        id: "qType" + i,
        type: "radio",
        value: "1",
        class: "choices radiobox"
      }).on("click", function() {
        that.showOptionsPane(radioOptions);
      });
      newQTypeArr[0] = $("<label>").append(newQTypeRadioEl, "Radio");

      var newQTypeCheckEl = $("<input>").prop({
        name: "qType" + i,
        id: "qType" + i,
        type: "radio",
        value: "2",
        class: "choices radiobox"
      }).on("click", function() {
        that.showOptionsPane(checkboxOptions);
      });
      newQTypeArr[1] = $("<label>").append(newQTypeCheckEl, "Checkbox");

      var newQTypeTextEl = $("<input>").prop({
        name: "qType" + i,
        id: "qType" + i,
        type: "radio",
        value: "3",
        class: "choices radiobox"
      }).on("click", function() {
        that.showOptionsPane(textOptions);
      });
      newQTypeArr[2] = $("<label>").append(newQTypeTextEl, "Text");

      // The following are the three answer option panels. The first two include
      //  an "add choices" button as well as an initial answer option. The text
      //  box option, by its nature, uses only a single input -- no need for more.

      var addRadioChoiceButton = $("<input>").prop({
        "type":  "button",
        "class": "btn btn-primary add-radio-choice answer-option",
        "value": "Add Radio button"
      }).on("click", function() {
        that.addRadioOptions(radioOptions);
      });

      var radioOptions = $("<div>").prop({
        class: "radio-answer-options"
      }).data("control-type", "radio").append(addRadioChoiceButton).on("change", function() {
        that.updatePreview(newQuestion, newAnswerEl, previewContainerEl);
      }).hide();
      this.addRadioOptions(radioOptions);

      var addCheckboxChoiceButton = $("<input>").prop({
        "type": "button",
        "class": "btn btn-primary add-checkbox-choice answer-option",
        "value": "Add Checkbox"
      }).on("click", function() {
        that.addCheckboxOptions(checkboxOptions);
      });
      var checkboxOptions = $("<div>").prop({
        class: "checkbox-answer-options"
      }).append(addCheckboxChoiceButton).on("change", function() {
        that.updatePreview(newQuestion, newAnswerEl, previewContainerEl);
      }).data("control-type", "checkbox").hide();
      this.addCheckboxOptions(checkboxOptions);

      var textOptions = $("<div>").prop({
        class: "text-answer-options"
      }).on("change", function() {
        that.updatePreview(newQuestion, newAnswerEl, previewContainerEl);
      }).data("control-type", "text").hide();
      this.addTextOptions(textOptions);

      // Now we create the answer options pane. containing this separately from the
      //  answers will allow them to be manipulated as needed.
      var newAnswerEl = $("<div>").prop({
        class: "answer-options-pane"
      }).append(radioOptions, checkboxOptions, textOptions);

      // Just as we wrapped the question in a label, we're going to wrap the answer options
      //   in an answer pane. This is where all of the answer work will happen.
      var newAnswer = $("<div>").prop({
        class: "answer-pane"
      }).append(newQTypeArr, newAnswerEl);

      /***
       * The preview pane will be hidden initially, but any change to the above
       *   will automagically update the preview pane, which contains a question
       *   and answer div. Note that only the currently displayed answer option
       *   will appear in the preview pane.
       ***/
      var previewComment = $("<div>").prop({
        class: "preview-comment"
      });
      var previewQuestion = $("<div>").prop({
        class: "preview-question"
      });
      var previewAnswer = $("<div>").prop({
        class: "preview-answer"
      })
      var previewContainerEl = $("<div>").prop({
        class: "preview-pane"
      }).append(previewComment, previewQuestion, previewAnswer).hide();
      

      /***
       * The last component of the "question collection" is the controls, allowing
       *   save or delete of this question. At this point, save simply toggles
       *   the visibility of the preview pane vs the Q&A panes, and the delete
       *   completely removes the question collection.
       ***/
      var saveButton = $("<input>").prop({
        type: "button",
        value: "Save question"
      }).on("click", function() {
        if(newQContainerEl.data("questionid")){
          that.updateQuestion($(this).parents(".question-container"));
        } else {
          that.saveQuestion($(this).parents(".question-container"));
        }
        that.togglePreview($(this).parents(".question-container").find(".preview-pane"));
      });
      var deleteButton = $("<input>").prop({
        type: "button",
        value: "Remove question"
      }).on("click", function() {
          that.removeQuestion($(this).parents(".question-container"));
      })
      var questionControls = $("<div>").prop({
        class: "controls-pane"
      }).append(saveButton, deleteButton);

      // The question container pane will contain four sub-panes: 
      //   - question
      //   - answer
      //   -preview (hidden by default)
      //   -controls
      //   The whole point of this is to create a logical structure for the entire question,
      //   making it a discrete logical piece.
      var newQContainerEl = $("<div>").prop({
                               class: "question-container",
                            })
                            .append(
                              newQuestion,
                              newAnswer,
                              previewContainerEl,
                              questionControls
                            )
                            .on("click", ".preview-pane", function(){
                              that.togglePreview($(this));
                              newQContainerEl.siblings().each(function(){
                                if ($(this).find(".preview-pane").is(":hidden") ){
                                  that.togglePreview($(this).find(".preview-pane"))
                                }
                              })
                            });

      // Remember the container? Now that we've created this DOM structure, we
      //  stick it into the question container. It is a completely self-contained
      //  structure, and its listeners only listen to their own element -- so we
      //  don't need to worry about tracking which element is being edited or
      //  deleted, the DOM elements themselves listen for it.
      this.questionsEl.append(newQContainerEl);
      
      // And we return a reference to the new question, so that we can populate
      //    that in the event that we have existing questions that we're making.
      return newQContainerEl;

    }, //end addQuestion()
    addRadioOptions: function(radioPane) {
      /***
       * Another DOM element creation function. This creates the radio
       *   button text option, and if it's the first, a button to add
       *   more options. 
       ***/

      // We want to get the length of the current choices, 
      //  as this will give us an index for the new option

      var radioChoice = radioPane.find(".radio-choice");
      var choice_c = radioChoice.length;

      var radioTempEl = $("<input>").prop({
        type: "radio",
        "class": "answer-option radio-choice"
      });

      var radioChoiceTextEl = $("<input>").prop({
        "type": "text",
        "class": "form-control answer-option radio-choice radiochoice" + choice_c,
        "name": "radiochoice" + choice_c,
      });

      var radioChoiceEl = $("<label>").append(radioTempEl, radioChoiceTextEl);
      // Make sure to add the new text element BEFORE the 
      //    add more button.
      radioPane.find(".add-radio-choice").before(radioChoiceEl);
      
      return radioChoiceEl;
    },
    addTextOptions: function(textPane) {
      this.textPane = textPane;

      var textChoiceTextEl = $("<input>").prop({
        "type" : "text",
        "class": "form-control answer-option text-choice",
        "name": "text-placeholder",
      });

      var textChoiceEl = $("<label>").append("Placeholder text: ", textChoiceTextEl);
      textPane.append(textChoiceEl);
      return textChoiceEl;
    },
    addCheckboxOptions: function(checkboxPane) {
      // We want to get the length of the current choices, 
      //  as this will give us an index for the new option

      var checkboxChoice = checkboxPane.find(".checkbox-choice");
      var choice_c = checkboxChoice.length;

      var checkboxTempEl = $("<input>").prop({
        "type": "checkbox",
        "class": "answer-option checkbox-choice"
      });
      var checkboxChoiceTextEl = $("<input>").prop({
        "type": "text",
        "class": "form-control answer-option checkbox-choice checkboxchoice" + choice_c,
        "name": "checkboxchoice" + choice_c,
      });

      var checkboxChoiceEl = $("<label>").append(checkboxTempEl, checkboxChoiceTextEl);
      // Make sure to add the new text element BEFORE the 
      //    add more button.
      checkboxPane.find(".add-checkbox-choice").before(checkboxChoiceEl);
      
      return checkboxChoiceEl;
    },
    showOptionsPane: function(optionsPane) {
      // This is what toggles the various options panes within the answer panel
      //  All it does is, if the given pane is hidden, show it, then hide its
      //  sibling panes. THat was the reason for the oddly nested structure:
      //  as the options panes are the ONLY siblings within their container, we
      //  can do this. 
      if (optionsPane.not(":visible")) optionsPane.slideDown().siblings().slideUp();
    },
    updatePreview: function(questionPane, answerPane, previewPane) {
      // Every time any part of the question is changed, this will be called
      //  to update the preview pane element.

      var previewQuestion = previewPane.find(".preview-question").empty();
      var previewComment = previewPane.find(".preview-comment").empty();
      var previewAnswer = previewPane.find(".preview-answer").empty();

      var question = questionPane.text() + questionPane.find("input.question-el").val();
      var comment = questionPane.find(".comment-el").val();

      var answerType = "."+answerPane.parent()
                      .find("[name^='qType']:checked")
                      .parent().text().toLowerCase()+
                      "-answer-options";
      var answerOption = answerPane.find(answerType);
      var answers = answerOption.find("input[type='text']");

      previewQuestion.text(question);
      previewComment.text(comment);

      switch (answerOption.data("control-type")) {
        case "radio":
          answers.each(function() {
            var labelText = $(this).val();
            var rbEl = $("<input>").prop("type", "radio");
            var answerLabelEl = $("<label>").append(rbEl, labelText);
            previewAnswer.append(answerLabelEl);
          });
          break;
        case "checkbox":
          answers.each(function() {
            var cbEl = $("<input>").prop("type", "checkbox");
            var answerLabelEl = $("<label>").append(cbEl, $(this).val());
            previewAnswer.append(answerLabelEl);
          });
          break;
        case "text":
          answers.each(function() {
            var textblockEl = $("<input>").prop("type", "text").attr({
              placeholder: $(this).val()
            });
            previewAnswer.append(textblockEl);
          });
          break;
      }
    },
    saveQuestion: function(questionEl){
      var that = this;
      
      var question = {};
      question.SurveyID = this.survey.SurveyID;
      question.QuestionText = questionEl.find(".question-el").val();
      question.Comment = questionEl.find(".comment-el").val();
      question.QuestionType = questionEl.find("[name^='qType']:checked").val();
      question.AnswerOptions = [];

      switch(true){
        case (question.QuestionType == 1):
          questionEl
            .find("div.radio-answer-options input.answer-option[type='text']")
            .each(function(){
              question.AnswerOptions.push($(this).val());
            });
          break;
        case (question.QuestionType == 2):
          questionEl
            .find("div.checkbox-answer-options input.answer-option[type='text']")
            .each(function(){
              question.AnswerOptions.push($(this).val());
            });          
          break;
        case (question.QuestionType == 3):
          questionEl
            .find("div.text-answer-options input.answer-option[type='text']")
            .each(function(){
              question.AnswerOptions.push($(this).val());
            });
          break;
        default:
          console.log("How did you end up here?!");
          break;
      }
      /**
       * At this point, the question object is everything we need to send via
       *  our RESTful API. We can't send a JSON object, however; we need to
       *  turn it to a string which the back end can convert BACK to a JSON
       *  object.
       **/
      var myQuestion = JSON.stringify(question);

      /****
       * Fetch is again being used, so as not to tie up the rest of the application.
       *  At this point, we are pinging the RESTful API we created to create a new
       *  survey.
       ****/
      var saveQuestionPromise = fetch('/api/question/create.php', {
        method: 'POST',
        credentials: "include",
        body: myQuestion
      })
      .then(function(response){
        // When we  get a response, turn the string back to an object...
        return response.json(); 
      })
      .then(function(returnedQuestionObject){
        // ... we can push the returned question onto the survey itself.
        // question.QuestionID = returnedQuestionObject.QuestionID
        that.survey.Questions.push(returnedQuestionObject);
        questionEl.attr("data-QuestionID", returnedQuestionObject.QuestionID);
      }).catch(function(err){ console.log(err)})
    },
    updateQuestion: function(questionEl){
      var that = this;
      
      var question = {};
      question.QuestionID = questionEl.data("questionid");
      question.SurveyID = this.survey.SurveyID;
      question.QuestionText = questionEl.find(".question-el").val();
      question.Comment = questionEl.find(".question-pane .comment-el").val();
      question.QuestionType = questionEl.find("[name^='qType']:checked").val();
      question.AnswerOptions = [];

      switch(true){
        case (question.QuestionType == 1):
          questionEl
            .find("div.radio-answer-options input.answer-option[type='text']")
            .each(function(){
              question.AnswerOptions.push($(this).val());
            });
          break;
        case (question.QuestionType == 2):
          questionEl
            .find("div.checkbox-answer-options input.answer-option[type='text']")
            .each(function(){
              question.AnswerOptions.push($(this).val());
            });          
          break;
        case (question.QuestionType == 3):
          questionEl
            .find("div.text-answer-options input.answer-option[type='text']")
            .each(function(){
              question.AnswerOptions.push($(this).val());
            });
          break;
        default:
          console.log("How did you end up here?!");
          break;
      }
      /**
       * At this point, the question object is everything we need to send via
       *  our RESTful API. We can't send a JSON object, however; we need to
       *  turn it to a string which the back end can convert BACK to a JSON
       *  object.
       **/
      var myQuestion = JSON.stringify(question);
      /****
       * Fetch is again being used, so as not to tie up the rest of the application.
       *  At this point, we are pinging the RESTful API we created to create a new
       *  survey.
       ****/
      var updateQuestionPromise = fetch('/api/question/update.php', {
        method: 'POST',
        credentials: "include",
        body: myQuestion
      })
      .then(function(response){
        // When we  get a response, turn the string back to an object...
        return response.json(); 
      })
      .then(function(returnedQuestionObject){
        // ... we can push the returned question onto the survey itself.
        //  This time its a little more involved: we need to iterate over
        //  the questions array, and when we find the question in the array
        //  with the matching id, we need to splice the current question in
        //  its place.
        var index= that.survey.Questions.findIndex(x=>x.id == question.id);
        that.survey.Questions[index] = returnedQuestionObject;
      }).catch(function(err){ console.log(err)})
    },
    removeQuestion: function(questionEl){
      var that = this;
      var questionID = questionEl.data('questionid').toString();
      
      if(confirm("Are you sure you want to remove question "+questionEl.data("questionid"))){
        var deleteQuestionPromise = fetch('/api/question/delete.php?QuestionID='+questionEl.data("questionid"), {
          method: 'GET',
          credentials: "include",
        }).then(function(response){
          return response.json();
        })
        .then(function(returnedQuestionObject){
          // Here, we need to remove this particular question element, AND ALSO
          //   need to remove the question from the survey object in memory. The
          //   reason we need to do this second step is, the memory object is what
          //   will be used to populate the finalize screen.
          questionEl.remove();
          that.survey.Questions = that.survey.Questions.filter(Question => Question.QuestionID !== questionID);
        }).catch(function(err){
          console.log(err);
        })

      }
    },
    togglePreview: function(previewPane) {
      // This is what displays the preview versus the editable elements.

      if (previewPane.is(":visible")) {
        previewPane.hide().siblings().show();
      } else {
        previewPane.show().siblings().hide();
      }
    }
};

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
