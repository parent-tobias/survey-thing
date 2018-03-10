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
    var step1Promise = fetch('./new-survey.html')
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
                      // Populate our initial display screen.
                      that.containerEl.html(that.stepsEls[0]);
                      // Set up a click listener for the next button
                      that.stepsEls[0].on("click", "button.nextBtn", function(){
                        // If the next button is clicked, we need to save the survey.
                        //  Note that we haven't done any validation or anything, and
                        //  that could be a bug.
                        //  ***?***?***?***?***
                        that.saveSurvey(that.stepsEls[0]);
                      })
                    });
    /***
     * Here we do the same as above. We will also add a listener to the next and prev
     *   buttons here, I simply haven't as yet. 'Next' should take us to some sort of
     *   confirmation and finalization step in the survey creation, perhaps displaying
     *   the survey itself. Confirmation at THAT point should change the survey's status
     *   from 'editing' to either 'open' or 'pending', depending on its start date.
     ***/
    var step2Promise = fetch('./new-survey-questions.html')
                      .then(function(response){
                        return response.text();
                      })
                      .then(function(text){
                        that.stepsEls[1] = $(text);
                        
                        /***
                         * Here, we're going to have a couple listeners. First, we
                         *  need to listen to the save question button, as that will
                         *  send a request to the REST API to save the current question
                         *  Also, we'll need to listen to the prev/next buttons here
                         ***/
                      });
    /****
     * Each screen in the stepsEls will have a nextBtn and prevBtn, I think.
     *  We want to be able to move between the screens and set the current
     *  step's HTML DOM fragment as appropriate.
     ****/
    this.containerEl.on("click", "button.nextBtn", function(){
      switch(that._step){
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
          // The following takes the form we've edited, and copies it entirely into
          //   the stepsEls[0], replacing our empty form.
          that.stepsEls[0] = that.containerEl.find(":first-child").clone();
          // Remove the survey meta information screen, and display the
          //  questions entry screen.
          that.containerEl
            .find(":first-child")
              .slideUp("slow", function(){
                this.remove();
              }).end()
            .append(that.stepsEls[1]);
          // Handle clicks on the 'Add question' button.
          that.containerEl.on("click", "#addq", function(){
            that.addQuestion("#questions");
          });
          break;
        case 1:
          // Here, we're going to simply set the Status value of the survey
          //   itself as 'Open'. Up to this point, it should be 'Editing'.
          that._step++;
          console.log("Finalizing...");
          break;
        default:
          that._step=0;
          break;
      }
    })

  },
  /****
   * surveyController.saveSurvey()
   *
   * Function to populate the survey object, and then send that to the back end
   *  When the fetch returns, we are simply getting a survey object back, and we
   *  just pull the id from that and stick it into our survey object itself.
   ****/
  saveSurvey: function(surveyForm){      
    var that = this;
    // surveyController.survey contains the survey Object. We need to populate it
    //  so we can then stringify it.
    this.survey.title = surveyForm.find("[name='title']").val() || null;
    this.survey.description = surveyForm.find("[name='description']").val() || null;
    this.survey.startDate = surveyForm.find("[name='startDate']").val() || null;
    this.survey.endDate = surveyForm.find("[name='endDate']").val() || null;
    this.survey.questions = [];

    // This one will need to be wired to get the current user id!
    this.survey.userId = 1;

    // The form body needs to be sent as a string.
    var mySurvey = JSON.stringify(this.survey);

    /****
     * Fetch is again being used, so as not to tie up the rest of the application.
     *  At this point, we are pinging the RESTful API we created to create a new
     *  survey.
     ****/
    var saveSurveyPromise = fetch('/api/survey/create.php', {
      method: 'POST',
      body: mySurvey
    })
    .then(function(response){
      // When we  get a response, turn the string back to an object...
      return response.json(); 
    })
    .then(function(returnedSurveyObject){
      // ... and remove the ID from the returned object to use in OUR object.
      that.survey.id = returnedSurveyObject.id;
    })
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
      }).on("keyup", function() {
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
      }).on("keyup", function() {
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
      newQTypeArr[0] = $("<label>").append(newQTypeRadioEl, " Radio");

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
      }).on("click", function() {
        that.togglePreview(previewContainerEl);
      }).append(previewComment, previewQuestion, previewAnswer).hide()

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
        that.saveQuestion(newQContainerEl);
        that.togglePreview(previewContainerEl);
      });
      var deleteButton = $("<input>").prop({
        type: "button",
        value: "Remove queston"
      }).on("click", function() {
        if (confirm("Are you sure you want to remove this question? Action cannot be undone.")) {
          newQContainerEl.remove();
        }
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
      }).append(newQuestion, newAnswer, previewContainerEl, questionControls);

      // Remember the container? Now that we've created this DOM structure, we
      //  stick it into the question container. It is a completely self-contained
      //  structure, and its listeners only listen to their own element -- so we
      //  don't need to worry about tracking which element is being edited or
      //  deleted, the DOM elements themselves listen for it.
      this.questionsEl.append(newQContainerEl);

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
      var answerOption = answerPane.find(":visible");
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
      question.surveyId = this.survey.id;
      question.questionText = questionEl.find(".question-el").val();
      question.comment = questionEl.find(".comment-el").val();
      question.questionType = questionEl.find("[name^='qType']:checked").val();
      question.answerOptions = [];

      switch(true){
        case (question.questionType == 1):
          questionEl
            .find("div.radio-answer-options input.answer-option[type='text']")
            .each(function(){
              question.answerOptions.push($(this).val());
            });
          break;
        case (question.questionType == 2):
          questionEl
            .find("div.checkbox-answer-options input.answer-option[type='text']")
            .each(function(){
              question.answerOptions.push($(this).val());
            });          
          break;
        case (question.questionType == 3):
          questionEl
            .find("div.text-answer-options input.answer-option[type='text']")
            .each(function(){
              question.answerOptions.push($(this).val());
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
        body: myQuestion
      })
      .then(function(response){
        // When we  get a response, turn the string back to an object...
        return response.json(); 
      })
      .then(function(returnedQuestionObject){
        // ... we can push the returned question onto the survey itself.
        question.id = returnedQuestionObject.id
        that.survey.questions.push(question);
        console.log(that.survey);
      }).catch(function(err){ console.log(err)})
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
