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
			if(!$retval[$index]) { $retval[$index] = array(); }
			$retval[$index] = array_unique(array_merge($q['answers'],$retval[$index]));
		}
	} // foreach
	return $retval;
}


?>
