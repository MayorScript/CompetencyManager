<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use  App\CompetenceProficiencyLevel;

Route::get('/', function () {
    return view('welcome');
});


Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

/* User has to be authenticated to acess all of the routes listed below*/
Route::group(['middleware' => 'auth'], function() {
	
	Route::get('tasks/show_form/{taskId}', 'TaskController@showForm');
	
	/*
	Route::get('/dashboards/tasks','DashboardController@taskReports');
	Route::get('/dashboards/competences','DashboardController@competencesReports');
	Route::get('/dashboards/users','DashboardController@usersReports');
	Route::get('/dashboards/collaboration','DashboardController@collaborationReports');
	Route::get('/dashboards/other','DashboardController@otherReports'); */
	
    Route::resource('tasks', 'TaskController');
    //Route::resource('teams', 'TeamController');
    Route::resource('competences', 'CompetenceController');
    Route::resource('users','UserController');
	Route::resource('jobroles','JobRoleController');
	Route::resource('learningaids','LearningAidController');
	Route::resource('dashboards', 'DashboardController');
	
	/* dashboard routes */
	Route::get('/dashboards/tasks/finished','DashboardController@finishedTasksReport');
	Route::get('/dashboards/tasks/not-finished','DashboardController@notFinishedTasksReport');
	Route::get('/dashboards/tasks/not-initialized','DashboardController@notInitializedTasksReport');
	Route::get('/dashboards/tasks/unfeasible','DashboardController@unfeasibleTasksReport');
	Route::get('/dashboards/competences/covered','DashboardController@coveredCompetencesReport');
	Route::get('/dashboards/competences/needed','DashboardController@neededCompetencesReport');
	Route::get('/dashboards/competences/most-learned','DashboardController@mostLearnedCompetencesReport');
	Route::get('/dashboards/collaboration/most-collaborative-users','DashboardController@mostCollaborativeUsersReport');
	Route::get('/dashboards/collaboration/most-collaborative-groups','DashboardController@mostCollaborativeGroupsReport');
	//Route::get('/dashboards/collaboration/unanswered-collaboration-form','DashboardController@usersWhoDidntAnswerCollaborationFormReport');
	Route::get('/dashboards/users/highest-competence-number','DashboardController@usersWithHighestCompetenceNumberReport');
	Route::get('/dashboards/users/most-tasks-performed','DashboardController@usersWithMoreTasksPerformedReport');

    /* pivot tables deletion routes */
    Route::delete('/user-team/{teamId}', array('as'=>'user-team','uses'=>'UserController@deleteUserFromTeam'));
    Route::delete('/user-competence/{competenceId}', 'UserController@deleteCompetenceFromUser');
    Route::delete('/task-competency/{taskId}/{competencyId}', 'TaskController@deleteCompetencyFromTask');
    Route::delete('/jobrole-competency/{jobroleId}/{competencyId}', 'JobRoleController@deleteCompetencyFromJobRole');
    Route::delete('/team-member/{teamId}/{memberId}', 'TeamController@deleteMemberFromTeam');
    Route::delete('/learningaid-competency/{learningAidId}/{competencyId}','LearningAidController@deleteCompetencyFromLearningAid');
	
	Route::get('/learningaid-finish/{learningAidId}', 'LearningAidController@finishLearningAid');

    Route::post('/user-competences', 'UserController@addCompetences');
    Route::post('/user-endorsements', 'EndorsementController@addEndorsement');
	
	Route::get('/task-initialize/{taskId}', 'TaskController@initializeTask');
	Route::get('/task-finish/{taskId}', 'TaskController@finishTask');
	
	Route::post('/task-answer-form', 'AnswerController@addAnswer');
	

    Route::get('competence-proficiency-level',function(){
        $competenceProficiencyLevels = CompetenceProficiencyLevel::all();
        $lista = [];
        foreach ($competenceProficiencyLevels as $level) {
            $lista[$level->id] = $level->name;
        }
        return Response::json($lista);
    })->name('competence-proficiency-level');

    /* autocomplete-related routes */
    Route::get('search-competence',array('as'=>'search-competence','uses'=>'SearchController@autocompleteCompetence'));
    Route::get('search-user',array('as'=>'search-user','uses'=>'SearchController@autocompleteUser'));
});





