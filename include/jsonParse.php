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

function getAnswers($json) {
	$retval= array();
	foreach ($json as $org) {
		for ( $i = 0; $i < count($org['questions']); $i++) {
			$index = $q['q_id'];
			$retval[$index] = $q['text'];
		}
	} // foreach
	return $retval;
}

// get valid answers for nth question
function getAnswersNth($json,$n) {
	$retval= array();
	foreach ($json as $org) {
		foreach($org['questions'] as $q) {
			$index = $q['q_id'];
			$retval[$index] = $q['text'];
		}
	}
	return $retval;
}



?>
