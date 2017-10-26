<?php

// Return associative array of questions
function getQuestions($json) {
	$retval= array();
	foreach ($json as $org) {
		foreach($org['questions'] as $q) {
			$index = $q['q_id'];
			$retval[$index] = $q['text'];
		}
	}
	return $retval;
}

// Return a dedeuped list of answers to populate drop downs
function getAnswers($json) {
	$retval= array();
	foreach ($json as $org) {
		foreach($org['questions'] as $q) {
			$index = $q['q_id'];
			if(!isset($retval[$index])) { $retval[$index] = array(); }
			$retval[$index] = array_unique(array_merge($q['answers'],$retval[$index]));
		}
	} // foreach
	return $retval;
}




// Pass it the questions and the answers
// returns DOM ids for all questions on page
// Skips questions that have no added value.  IE:  Has less than 2 options
function dropDowns($q, $a) {
	$retval = "";
	foreach(array_keys($q) as $key) {
		// If we have fewer than 2 choices, we can skip
		if(count($a[$key]) > 1) {
			$qid = "question_".$key;
			$qtext = $q[$key];
			$qstr = <<<QUESTION
    <div class="form-group">
      <label class="control-label col-sm-2" for="$qid">$qtext</label>
      <select id="$qid" class="question selectpicker" data-max-options="2" multiple>
QUESTION;
		foreach($a[$key] as $answer) {
			$qstr .= "\n\t<option>$answer</option>\n";
		}
		$qstr.="\n</select>\n";
		$qstr.="</div>\n\n";
		$retval .= $qstr;

		} 
	} // foreach
	return $retval;
} //dropDowns


?>
