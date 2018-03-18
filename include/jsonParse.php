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

function getGroups($json)
{
    $retval = array();
    foreach($json as $org) {
        foreach($org['questions'] as $q) {
            $index = $q['group_order'];
            $q_array = array();
            array_push($q_array, $q['q_id']);
            
        if(!isset($retval[$index])) {
        $retval[$index] = array();
        }
            // merge arrays, de-dupe the merged array, then sort
            $retval[$index] = array_unique(array_merge($q_array, $retval[$index]));
            sort($retval[$index]);
        } // foreach question
    } // foreach org

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

// openCollapsible creates all the opening divs for a collapsible panel in html/css
function openCollapsible($groupid) {
    $collapseid = "collapse_".$groupid;
    $retval = <<<CSSHELL
<div class="panel-group">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title"><a data-toggle="collapse" href="#$collapseid">Group $groupid</a></h4>
        </div>
    <div id="$collapseid" class="panel-collapse">
CSSHELL;

    return $retval;
}


// closeCollapsible creates all the closing divs for a collapsible panel in html/css
function closeCollapsible($gid) {
    $retval = <<<CSSHELL
        </div>
    </div>
</div>
CSSHELL;

    return $retval;
}

// Pass it the questions, answers and the groups
// returns string
// Skips questions that have no added value.  IE:  Has less than 2 options

function dropDowns($q, $a, $g)
{
    $retval = "<div id=\"dropdowns\">\n";
    foreach(array_keys($g) as $gid) {
        $counter = 0;
        $questions = $g[$gid];
        $retval .= openCollapsible($gid);

        $retval .= "<div class=\"container\">";
        $leftside = "<div class=\"col-sm-6\">\n";
        $rightside = "<div class=\"col-sm-6\">\n";
        foreach($questions as $key) {

            // If we have fewer than 2 choices, we can skip

            if (count($a[$key]) > 1) {
                $counter++;
                $qid = "question_" . $key;
                $qtext = $q[$key];
                $qstr = <<<QUESTION
<div class="form-group">
  <label class="control-label col-sm-6" for="$qid">$qtext</label>
  <select id="$qid" class="question selectpicker" multiple>
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
        $retval.=closeCollapsible($gid);
    }
    $retval .="\n</div>\n";

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
