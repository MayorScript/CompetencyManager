<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\AnswerFormRequest;

class AnswerController extends Controller
{
    public function addAnswer(AnswerFormRequest $request) {
		/* 
		$answer = \App\Answer;
        $answer->personal_competence_id = $request->get('personal_competence_id');
        $answer->personal_competence_proficiency_level = $request->get('personal_competence_proficiency_level');
        $answer->judge_user_id = $request->get('judge_user_id');
		$answer->evaluated_user_id = $request->get('evaluated_user_id');
		$answer->task_id = $request->get('task_id');
		$answer->save();
		*/
		
		//$answersAllUsers = $request->get('personal_competence_level_id');
		$personalCompetencesId = $request->get('personal_competence_id'); 
		$judge_user_id = $request->get('judge_user_id');
		$evaluated_users_id = $request->get('evaluated_user_id');
		$task_id = $request->get('task_id');
		
		$countAnswers = count($personalCompetencesId) * count($evaluated_users_id);
		
		$evaluated_user_id = $evaluated_users_id[0];
		for ($i = 0; $i < $countAnswers; $i++) {
			if ($i == 0) {
				$j = 0;
			}
			else if (($i % count($personalCompetencesId)) == 0) {
				$j++;
				$evaluated_user_id = $evaluated_users_id[$j];
			}
			
			$personal_competence_id = $personalCompetencesId[$i % count($personalCompetencesId)];
			$personal_competence_level_id = $request->get('personal_competence_level_id'.strval($j).strval($i % count($personalCompetencesId)));
			$values = array('personal_competence_id' => $personal_competence_id, 'personal_competence_level_id' => $personal_competence_level_id, 'judge_user_id' => $judge_user_id, 'evaluated_user_id' => $evaluated_user_id, 'task_id' => $task_id);
			\DB::table('answers')->insert($values);
		}
		return redirect("tasks/$task_id");
		
    }
}
