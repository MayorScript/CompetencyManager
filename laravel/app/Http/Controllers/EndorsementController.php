<?php
/**
 * Created by PhpStorm.
 * User: Jéssica
 * Date: 2017-06-14
 * Time: 12:34 PM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;



class EndorsementController
{
    public function addEndorsement(Request $request) {
        $competenceId = $request->get('competence_id');
        $competenceLevel = $request->get('competence_level');
        $endorsedUserId = $request->get('endorsed_user_id');
        $endorser = \Auth::user();
        $endorserId = $endorser->id;
        $endorser->addEndorsement($endorsedUserId, $competenceId, $competenceLevel);
        return redirect("users/$endorsedUserId");
    }

}