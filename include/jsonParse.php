<?php

// Return associative array of questions

function getQuestions($json)
{
    $retval = array();
    foreach($json as $org) {
        foreach($org['questions'] as $q) {
            $index = $q['q_id'];
            $retval[$index] = $q['text'];
        }
    }

    return $retval;
}

// Return a dedeuped list of answers to populate drop downs

function getAnswers($json)
{
    $retval = array();
    foreach($json as $org) {
        foreach($org['questions'] as $q) {
            $index = $q['q_id'];
            if (!isset($retval[$index])) {
                $retval[$index] = array();
            }

            $retval[$index] = array_unique(array_merge($q['answers'], $retval[$index]));
        }
    } // foreach
    return $retval;
}

// Pass it the questions and the answers
// returns string
// Skips questions that have no added value.  IE:  Has less than 2 options

function dropDowns($q, $a)
{
    $counter = 0;
    $retval = "<div class=\"container\">";
    $leftside = "<div class=\"col-sm-6\">\n";
    $rightside = "<div class=\"col-sm-6\">\n";
    foreach(array_keys($q) as $key) {

        // If we have fewer than 2 choices, we can skip

        if (count($a[$key]) > 1) {
            $counter++;
            $qid = "question_" . $key;
            $qtext = $q[$key];
            $qstr = <<<QUESTION
    <div class="form-group">
      <label class="control-label col-sm-6" for="$qid">$qtext</label>
      <select id="$qid" class="question selectpicker" data-max-options="3" multiple>
QUESTION;
            foreach($a[$key] as $answer) {
                $qstr.= "\n\t<option>$answer</option>\n";
            } // foreach answer
            $qstr.= "\n</select>\n";
            $qstr.= "</div>\n";
            if ($counter % 2) {
                $leftside.= $qstr;
            }
            else {
                $rightside.= $qstr;
            }
        } // end answers > 1
    } // foreach
    $leftside.= "\n</div>\n"; // close the col-sm-6 div
    $rightside.= "\n</div>\n"; // close the col-sm-6 div
    $retval.= "$leftside\n$rightside\n</div>\n"; // left side and right side and closing container div
    return $retval;
} //dropDowns

// Return relevant question IDs for the DOM in an array

function questionIDs($q, $a)
{
    $retval = array();
    foreach(array_keys($q) as $key) {

        // If we have fewer than 2 choices, we can skip

        if (count($a[$key]) > 1) {
            $qid = "question_" . $key;
            array_push($retval, $qid);
        } // end answers > 1
    } // foreach
    return $retval;
} //dropDowns

?>
