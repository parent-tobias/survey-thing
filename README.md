# survey-thing
## Overview
There are a number of moving parts to a system such as this survey manager. 

There is a database component, both for the creation of the surveys themselves, but also to track the results of users taking the surveys. 
The server component provides consumable data models.

The client component, built largely from scratch, providing a controller mechanism, as well as view generation and maintenance.

In between each of these, there are communication channels -- PDO providing the database access functionality and a RESTful API passed between the server and client.

While taking the survey can likely be a static PHP page, the level of complexity involved in creating the surveys themselves lends itself well to creating a single-page app (or SPA). By doing this, as each part of the survey is created or edited on the server, the client is updated to match. As the survey itself is created, a Survey object is created on the client machine. As questions are added to the survey, each Question is created on the server and immediately appended to its Survey object. Each part remains editable, and the Survey object stored in the browser’s memory is updated to reflect any changes made on the server. Thus, the decision to use AJAX, implemented in most modern browsers using the fetch() Promise function.

One of the first lessons of professional development, web or otherwise, is “program to an interface, not an implementation.” What this means, in real terms, is that objects should be as self-contained as possible, providing a consistent, logical means of using them. Thus, we start something like this from a data design -- rather than worrying about how to make all this work, start from what is to be represented. By doing this, we can create a Survey class, a Question class, a User class, and create interfaces for each that will return predictable data.

Creating data models that connect to the database requires, of course, that the database be created. Walking through the interactions between data models will give an idea of their relations.

* Users create surveys. A one-to-many relationship.
* Surveys contain questions. Again, one-to-many.
* Surveys require a status (editing, open, closed). This is a one-to-one relationship.
* Questions have a type (radio, checkbox, text…). Again, one-to-one relationship.
* Questions may contain multiple options for answers. One-to-many.
* Users can respond to surveys. One-to-many.
* Responses may contain multiple answers. One-to-many.

Thus, our database tables are:
* Answer (response answers)
* AnswerChoice (survey question answers)
* Question
* QuestionType (**)
* Status (**)
* Survey
* SurveyResponse
* UserInfo

The tables marked with (**) represent the one-to-one relationships. These are a deeper conversation, as to whether to use a table for this, or to simply represent that data as an ENUM on the first table. According to Chris Komlenic, the use of ENUM is poor design, and I tend to agree. However, we will implement each approach, simply to show how each would be used. The use of ENUM vs lookup tables is largely dependend, I think, on the expected scale of growth. For our purpose, either would work. So the Status will be represented as an enum, and the QuestionType will be a lookup table.

Given those tables, and the interactions we’ve defined, we can define the tables themselves.

Thus, when the data models are being used in the API, we set the data on the model itself, and simply ask the model to perform some action (for example, set the Status on the Survey model to ‘open’ and ask the Survey for an array of Survey objects matching that by $Survey->read() ). Further, a consistent series of responses from those data models will making interacting with them predictable:

|    Function:    |    Setup:    |    Action:    |    Returns:    |
|-----------------|--------------|---------------|----------------|
| [model]->create() | set values on the data model instance. | Creates a record in the dataset. |sets the created record’s ID on the instance, true/false indicating success. |
| [model]->read() | none | Returns all records in the dataset. | An array of data model instances. |
| [model]->readOne() | set an ID on the instance. | Returns a single record from the dataset. | Sets the data on the created instance. |
| [model]->update() | set all values on the instance. | Updates the record indicated by the instance ID. | true/false indicating success. |
| [model]->delete() | set an ID on the instance | Deletes the record from the dataset. | true/false indicating success. |

Some classes require more than the generic functions:

### QuestionType
|    Function:    |    Setup:    |    Action:    |    Returns:    |
|-----------------|--------------|---------------|----------------|
| QuestionType->findByType() |set a TypeName on the instance | Read a single record from the dataset. | sets the ID on the instance |
### Response
|    Function:    |    Setup:    |    Action:    |    Returns:    |
|-----------------|--------------|---------------|----------------|
| Response->readByResponseType() | Set a response type | Read matching records. | array of Response objects |
| Response->readBySurvey() | Set a SurveyID | Read matching records. | array of Response objects |
| Response->readByUser() | Set a UserID | Read matching records. | array of Response objects |
### Survey
|    Function:    |    Setup:    |    Action:    |    Returns:    |
|-----------------|--------------|---------------|----------------|
| Survey->readByStatus() | Set a status | Read matching records. | array of Survey objects |
| Survey->readByUser() | Set a UserID | Read matching records. | array of Survey objects |
| Survey->readByUserAndStatus() | Set a status and UserID | Read matching records. | array of Survey objects |
| Survey->changeStatus() | Set a status and SurveyID | Update matching record. | true/false indicating success |
### User
|    Function:    |    Setup:    |    Action:    |    Returns:    |
|-----------------|--------------|---------------|----------------|
| User->findByEmail() | Set an email | Read matching record. | update User instance |
| User->exists() | Set an email | Read matching record. calls findByEmail() | update User instance, return true/false |
| User->login() | Set an email and password | Read matching record, verify password | true/false indicating valid credentials |

With the models defined, implementing the data to be passed between server and client begins to make more sense. A survey being created in javascript might define the JSON object:

```javascript
var SurveyJSONObject = {
    "Title" : "Technical Skills and Interests, 04-2018",
    "Description": "This is a survey intended to learn about the current skill-state of our users, and to better determine how we may serve the needs of our community.",
    "StartDate": 2018-04-01,
    "EndDate": 2018-04-30
};
```

On creating that via the server, the returned object is a little different, in the event of a successful creation. So, in memory, we might now have:

```javascript
ourNameSpaceObject.Survey = {
    “SurveyID” : 344,
    "Title" : "Technical Skills and Interests, 04-2018",
    "Description" : "This is a survey intended to learn about the current skill-state of our users, and to better determine how we may serve the needs of our community.",
    "UserID": "5",
    "User": {
        "UserID": "5",
        "firstname": "Charlie",
        "lastname": "Brown",
        "email": "cbrown21@peanuts.com",
        "password": null,
        "CreatedAt": "2018-03-28 18:44:12"
  },
    “Questions” : [],
    "StartDate" : 2018-04-01,
    "EndDate" : 2018-04-30
};
```

As no question objects exist for this survey, that value is an empty array. As the front end adds a question to this survey, the data structure might grow to:

```javascript
ourNameSpaceObject.Survey = {
    “SurveyID” : 344,
    "Title" : "Technical Skills and Interests, 04-2018",
    "Description" : "This is a survey intended to learn about the current skill-state of our users, and to better determine how we may serve the needs of our community.",
    "UserID": "5",
    "User": {
        "UserID": "5",
        "firstname": "Charlie",
        "lastname": "Brown",
        "email": "cbrown21@peanuts.com",
        "password": null,
        "CreatedAt": "2018-03-28 18:44:12"
  },
    “Questions” : [
      {
        "QuestionID": "30",
        "SurveyID": "143",
        "Text": "Gender",
        "QuestionTypeID": "1",
        "QuestionType": "Radio",
        "Comment": "Your current physical gender",
        "CreatedAt": "2018-03-23 12:17:34",
        "AnswerChoices": [
          {
            "AnswerChoiceID": "77",
            "QuestionID": "30",
            "answer": "Male",
            "CreatedAt": "2018-03-23 12:17:37"
          },
          {
            "AnswerChoiceID": "78",
            "QuestionID": "30",
            "answer": "Female",
            "CreatedAt": "2018-03-23 12:17:37"
          },
          {
            "AnswerChoiceID": "79",
            "QuestionID": "30",
            "answer": "Prefer not to say",
            "CreatedAt": "2018-03-23 12:17:37"
          }
        ]
      }],
      "StartDate" : 2018-04-01,
      "EndDate" : 2018-04-30,
      "Status" : "editing",
  };
```

Thus we can see the data models being represented, and the relationship between them. In order to implement the back end controllers, we need to convert the JSON data coming from the client into the model instance, in order to interact with the database. Thus, our back end becomes a multi-layer entity: database to data model to API controllers.

A great resource with a lot of information on this subject, and one I referenced repeatedly, is codeofaninja.com -- his article listed in the citations below served as a strong springboard for this entire project. The only nit I could really pick with that article is that there are database calls (model-layer) in the API layer. Using his object encapsulation as a starting point, I moved the PDO references into the models themselves, and simply returned an object, or array of objects, or boolean. Doing this allows users to implement whatever database mechanism they like in the model layer, and so long as the return values are consistent, the whole thing will continue to work.

## The API Layer

Within the API, each model should be kept discrete, while allowing for dependencies. For example, the /API/Survey/* interacts solely with the survey model. Saving or updating at the API level interact with the model instance, without regard for related nodes. Reading or deleting may require interaction with dependent models; when a single Survey is read, we also read the User, and the Questions (which in turn read related AnswerChoices) for the given Survey.

Just as the models had clearly defined functions for interaction, so must the API. Thus, we can create some common endpoints:


|    Function:    |    (Method)/Parameters    |    Action:    |    Returns:    |
|-----------------|---------------------------|---------------|----------------|
| /API/[model]/create.php | (POST) JSON object | Creates a record in the dataset. | updated JSON object with possible dependencies. |
| /API/[model]/read.php | (GET) | Returns all records in the dataset. | JSON array of model objects. |
| /API/[model]/read-one.php | (GET) [Model]ID | Returns a single record from the dataset. | JSON model object |
| /API/[model]/update.php | (POST) JSON object | Updates the record indicated by the instance ID. | JSON model object, status message |
| /API/[model]/delete.php | (GET) [MODEL]ID | Deletes the record from the dataset. | true/false indicating success. |

Note that this is not a true implementation of a RESTful API; rather, individual routes were defined for each model action. In a fully RESTful implementation, the /API/model itself would have different routes based on the method (GET/POST/PUT/DELETE), reducing our API endpoints from five to one.
And, as in the data models, some have additional API requirements. 

### Survey
|    Function:    |    (Method)/Parameters    |    Action:    |    Returns:    |
|-----------------|---------------------------|---------------|----------------|
| /API/survey/finalize.php | (GET) SurveyID | Changes the Status of the given Survey | success or failure |
| /API/survey/upload-all.php | (POST) Full Survey JSON object | Creates the Survey, with all dependent Questions | Complete updated JSON Survey object |

### User
|    Function:    |    (Method)/Parameters    |    Action:    |    Returns:    |
|-----------------|---------------------------|---------------|----------------|
| /API/user/check-email.php | (GET) email | checks the existence of a given email | true or false |
| /API/user/login.php | (POST) email & password | Attempts to log user with the given credentials | new Session and User JSON object or failure |

In addition, we can create custom API endpoints. As an example, to review the results of a given survey, we can create the /API/survey-results/*.php endpoints, which don’t have a model of their own: rather, we can create a JSON object using the Survey and Response objects.

## The Client Side

The survey creation process begins with the display of an empty modal dialog, and using fetch() Promise objects to retrieve the individual screens for each step of of the process, storing them into an array as DOM nodes. Survey creation requires a three-step system:

* Survey summary information (Title, Description, Start and End date)  (surveyController.steps[0])
* Survey question entry   (surveyController.steps[1])
* Confirmation and finalization   (surveyController.steps[2])

Each screen has previous and next buttons, allowing navigation between each step. Prior to displaying anything, the main interactions are connected (cancel, next, previous and finalize buttons). At this point, the first HTML snippet is displayed, allowing the user to enter the survey summary information.

When the user clicks ‘Next’, the surveyController object creates a survey object, and passes that via a fetch() Promise to our API endpoint. When that Promise returns, a survey property is created on the surveyController object. This will be used throughout to build a complete survey object, with all questions etc. When this property is created, the survey summary HTML snippet is stored back into surveyController.steps[0], allowing the system to redisplay the appropriate DOM node should the user click ‘Back’. Then the displayed node is removed, and surveyController.steps[1], the question entry DOM node, is placed in the modal.

The question entry DOM node is the most dynamically interactive of the screens. In addition to the ‘Next’ and ‘Previous’ buttons, there is an ‘Add a question’ button. The surveyController listens for a click event on this button, which calls surveyController.addQuestion(). When this is called, the display changes, allowing the user to choose a question type and enter relevant information.

Structurally, this function creates a containing div element, with two text inputs, three radio buttons, and related labels. Hidden but also created are four additional div elements: one for each of the question types, and one containing a ‘preview’ of the question. As each visible element is edited (text edited or radio checked), there is interaction. As text is entered, the preview element is updated to match. As a radio button is clicked, the relevant hidden pane is displayed. Within the hidden panes are further interactive elements: in the event of ‘radio’ or ‘checkbox’, an additional option to add more is displayed. All this interaction is handled by surveyController, toggling displayed panes or adding radio/checkbox elements, or even toggling the editable and preview displays.

When the question has been completed, clicking ‘Save question’ creates a question object, using the SurveyID from the surveyController.Survey and the information from the question form, and again uses a fetch() Promise. This time, the call is /API/question/create.php - which is the one object that, when created, also creates its dependent objects. In this case, AnswerChoices are created with the Question. The fetch(), if successful, returns a Question object, which we push onto the surveyController.Survey object’s Questions array.

Editing or deleting questions will POST to /API/question/update.php or GET to /API/question/delete.php respectively, and will update both the DOM view and surveyController.Survey. By doing this, the information in the object in memory is current, and we can use that to display the appropriate information when displaying the finalize step, surveyController.steps[2].

Before the final step is displayed, the DOM structure is updated. Questions are appended to the appropriate div element, the survey’s summary information is displayed in the relevant placeholders, and buttons for ‘Previous’ and ‘Finalize’ are displayed. In any step, clicking on ‘Previous’ or ‘Next’ stores the current step back into surveyController.steps[], allowing return to the same display. Clicking ‘Finalize’ on the last step uses a fetch() Promise to GET /API/survey/finalize.php, which then closes the survey creator and returns to the admin screen.

Largely, the front end is a new creation. The question editing portion was based off a discussion on StackOverflow, which was a good source of ideas on what a survey question editor might look like, but which was limited in its dependencies on other libraries. As it is, the only dependencies in the iteration we’ve implemented are jQuery, bootstrap, and a ‘modern’ browser.

Modern browsers are those that implement ECMAscript 6 functions. Notably, the display uses map() and filter() throughout. The advantage to these functions are, rather than worrying about “how do I iterate over this collection?”, we solely worry about “with every member of this collection, do something.” A much more robust and understandable dynamic. Fernando Daciuk (5) provides a great discussion on the use of map(), which helps to make code much more understandable.

## Takeaways

The beauty of a system like this, with each model class being completely encapsulated, is that further use of those classes becomes transparent. For example, we can use the User object as the user authentication/authorization module, but we can also use that to contain social information on the user, which we can then utilize elsewhere (for example, as the creator of a Survey). In the event that we want to retrieve a complete Survey object (perhaps to create a static survey), we can simply instantiate the Survey class, set its SurveyID, and call $Survey->readOne() to retrieve the complete nested object representing the entire thing. While we don’t have a surveyResults object in the classes, we don’t really need one: we can create a Survey and Response instance on the back end, set the SurveyID on both to the same value, call $Survey->readOne() and $Response->readBySurvey() to create the same data structure the API returns.

It is a robust and extensible structure, capable of being adapted to many different options. Changing out the data source, consuming the API in other ways, filtering returned Survey data… there is quite a bit of flexibility to the system itself. And that is due, in very large part, to the first lesson of programming: “Code to an interface, not an implementation”.

## References Cited:

* http://ctarda.com/2016/11/program-to-an-interface-not-an-implementation/ 
* https://www.codeofaninja.com/2017/02/create-simple-rest-api-in-php.html 
* http://komlenic.com/244/8-reasons-why-mysqls-enum-data-type-is-evil/
* https://stackoverflow.com/questions/43050466/creating-a-survey-form-using-javascript/43074456#43074456
* https://medium.com/daily-js-tips/replace-your-loops-by-array-methods-map-4e9af2e18427

