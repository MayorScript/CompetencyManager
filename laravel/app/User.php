<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'role', 'email', 'password',
    ];

    public function competences()
    {
        return $this->belongsToMany('App\Competency', 'user_competences', 'user_id', 'competence_id')
            ->withPivot('competence_proficiency_level_id')->orderBy('name');
    }


    public function hasCompetence($competenceId)
    {
        $userHasCompetence = $this->competences()->where("competence_id", $competenceId)->get();
        return !$userHasCompetence->isEmpty();
    }

    public function hasCompetenceInAcceptableLevel($competenceId, $acceptableCompetenceLevels)
    {
        $competenceAndSubcategoriesIds = Competency::descendantsAndSelf($competenceId)->pluck('id');
        $userHasCompetence = $this->competences()->whereIn("competence_id", $competenceAndSubcategoriesIds)->wherePivotIn('competence_proficiency_level_id', $acceptableCompetenceLevels)->get();
        return !$userHasCompetence->isEmpty();
    }

    public function isManager()
    {
        return $this->role == 'manager';
    }

    public function getNumberOfEndorsementsForCompetence($userEndorsements, $competence)
    {
        return $userEndorsements->where('competence_id', $competence->id)->count();
    }

    public function userEndorsementsForCompetence($competenceId)
    {
        return $this->endorsements()->where('competence_id', $competenceId)->get();
    }

    public function getNumberOfEndorsementsPerLevelForCompetence($competence, $user)
    {
        $meuMapa = [];
        $allCompetenceLevels = CompetenceProficiencyLevel::all()->pluck('id')->toArray();
        foreach ($allCompetenceLevels as $competenceLevelId) {
            $endorsersIds = $user->endorsements()->where('competence_id', $competence->id)->where('competence_proficiency_endorsement_level_id', $competenceLevelId)->pluck('endorser_id')->toArray();
            $endorsers = User::whereIn('id', $endorsersIds)->get();
            $meuMapa[$competenceLevelId] = ["endorsementPerLevel" => $user->endorsements()->where('competence_id', $competence->id)->where('competence_proficiency_endorsement_level_id', $competenceLevelId)->count(),
                "proficiencyLevelName" => \App\CompetenceProficiencyLevel::findOrFail($competenceLevelId)->name,
                "endorsers" => $endorsers];
        }
        return $meuMapa;
    }


    public function getInitialsFromName(){
        $fullName = $this->name;
        $splitName = preg_split("/[\s,_-]+/", $fullName);
        $acronym = "";

        foreach ($splitName as $w) {
            $acronym .= $w[0];
        }
        return strtoupper($acronym);
    }

    public function getEndorsersPerLevel($user, $competence)
    {
        $meuMapa = [];
        $allCompetenceLevels = CompetenceProficiencyLevel::all()->pluck('id')->toArray();

        foreach ($allCompetenceLevels as $competenceLevelId) {
            $meuMapa[$competenceLevelId] = [$user->endorsements()->where('competence_id', $competence->id)->wherePivot('competence_proficiency_endorsement_level_id', $competenceLevelId)];
        }
        return $meuMapa;
    }

    public function computeThings($competenceId)
    {
        $meuMapa = [];
        $totalEndorsements = 0;
        $maximumValue = 0;
        $maximumKeys = [];
        foreach ($this->userEndorsementsForCompetence($competenceId) as $competenceEndorsement) {
            $totalEndorsements++;
            $endorsementLevel = $competenceEndorsement->pivot->competence_proficiency_endorsement_level_id;
            if (isset($meuMapa[$endorsementLevel])) {
                $meuMapa[$endorsementLevel]++;
            } else {
                $meuMapa[$endorsementLevel] = 1;
            }
            if ($meuMapa[$endorsementLevel] > $maximumValue) {
                $maximumValue = $meuMapa[$endorsementLevel];
                $maximumKeys = [];
                $maximumKeys[] = $endorsementLevel;
            } else {
                if ($meuMapa[$endorsementLevel] == $maximumValue) {
                    $maximumValue = $meuMapa[$endorsementLevel];
                    $maximumKeys[] = $endorsementLevel;
                }
            }
        }
        if (isset($maximumKeys)) {
            return [($maximumValue / $totalEndorsements) * 100, $this->getCompetenceProficiencyLevelNamesFromIds($maximumKeys)];
        } else {
            return [];
        }
    }

    private function getCompetenceProficiencyLevelNamesFromIds($competenceProficiencyLevelIds)
    {
        $names = [];
        foreach ($competenceProficiencyLevelIds as $competenceProficiencyLevelId) {
            $names[] = \App\CompetenceProficiencyLevel::findOrFail($competenceProficiencyLevelId)->name;
        }
        return $names;
    }

    public function loggedUserEndorsedCompetence($shownUserEndorsements, $competenceId)
    {
        $loggedUserId = \Auth::user()->id;
        $numberOfTimesLoggedUserEndorsedCompetenceOfShownUser = $shownUserEndorsements->where([
            ['endorser_id', '=', $loggedUserId],
            ['competence_id', '=', $competenceId],
        ])->count();
        return $numberOfTimesLoggedUserEndorsedCompetenceOfShownUser;
    }

    public function getEndorsementLevel($competenceId)
    {
        $loggedUserId = \Auth::user()->id;
        $competenceProficiencyLevelId = $this->endorsements()->where([
            ['endorser_id', '=', $loggedUserId],
            ['competence_id', '=', $competenceId],
        ])->first()->pivot->competence_proficiency_endorsement_level_id;
        return \App\CompetenceProficiencyLevel::findOrFail($competenceProficiencyLevelId)->name;
    }

    public function createdTasks()
    {
        return $this->hasMany('App\Task', 'author_id')->orderBy('title');
    }

    //endorsements where the current user is the endorsed entity
    public function endorsements()
    {
        return $this->belongsToMany('App\Competency', 'user_endorsements', 'endorsed_id', 'competence_id'/*,'endorser_id'*/)
            ->withPivot('competence_proficiency_endorsement_level_id');
    }

    //endorsements where the current user is the endorser entity
    public function endorsements_endorser()
    {
        return $this->belongsToMany('App\Competency', 'user_endorsements', 'endorser_id', 'competence_id'/*,'endorser_id'*/)
            ->withPivot('competence_proficiency_endorsement_level_id');
    }

    public function addEndorsement($endorsedId, $competenceId, $competenceLevel)
    {
        $shownUser = User::find($endorsedId);
        $numberOfEndorsementsToTheCompetence = $this->loggedUserEndorsedCompetence($shownUser->endorsements(), $competenceId);
        if ($numberOfEndorsementsToTheCompetence == 0) {
            //add
            $this->endorsements_endorser()->attach([$competenceId => ['competence_proficiency_endorsement_level_id' => $competenceLevel, 'endorsed_id' => $endorsedId]]);
        } else {
            //update endorsement
            $this->endorsements_endorser()->updateExistingPivot($competenceId, ['competence_proficiency_endorsement_level_id' => $competenceLevel]);
        }
    }

    public function teams()
    {
        return $this->belongsToMany('App\Team', 'team_members');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
}
