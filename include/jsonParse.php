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

function getGroupQuestions($json)
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

function getGroupText($json)
{
    $retval = array();
    foreach($json as $org) {
        foreach($org['questions'] as $q) {
            $index = $q['group_order'];
            $text  = $q['group_text'];
            
            $retval[$index] = $text;
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
function openCollapsible($groupid,$text) {
    $collapseid = "collapse_".$groupid;
    $retval = <<<CSSHELL
<div class="panel-group">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title"><a data-toggle="collapse" href="#$collapseid">$text</a></h4>
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

// Questions, Answers, GroupQuestions, GroupText
function dropDowns($q, $a, $g, $gt)
{
    $retval = "<div id=\"dropdowns\">\n";
    foreach(array_keys($g) as $gid) {
        $counter = 0;
        $text = $gt[$gid];
        $questions = $g[$gid];
        $retval .= openCollapsible($gid,$text);

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
} //questionIDs

// Return Text for 'Core' Question W2W
function question1Text($q)
{
    $retval = "";
    $done = false;
    foreach(array_keys($q) as $key) {
        if(!$done) {
            $done = true;
            $retval = $q[$key];
        }
    } // foreach
    return $retval;
} //questionIDs

// Return option list for 'Core' question
function question1options($q, $a, $g, $gt) {
    $retval = "";
    $done = false;
    foreach(array_keys($g) as $gid) {
        if (!$done) {
        $questions = $g[$gid];
            foreach($questions as $key) {
                if(!$done) {
                    $done = true; // as soon as we are in first group and first question
                    foreach($a[$key] as $answer) {
                        $retval.= "\n\t<option>$answer</option>\n";
                    } // foreach answer
                } // if !$done questions
            } // foreach
        } // if !$done groups
    } // foreach

    return $retval;
}

// Parse actual question array W2W
// Questions, Answers, GroupQuestions, GroupText
function carouselQuestions($q, $a, $g, $gt) {
    $retval = "";
    $first = true;

    foreach(array_keys($g) as $gid) {
        $questions = $g[$gid];

        $retval.="\n\t\t<!-- Slide # $gid -->\n";

        if($first) {
            $retval.="\t\t<div class=\"carousel-item active\">\n";
            $first=false;
        } else {
            $retval.="\t\t<div class=\"carousel-item\">\n";
        }
        $groupText = $gt[$gid];

        $retval.="\t\t <p>$groupText</p>\n\t\t <form>\n";
        // For each Question in this Slide
        foreach($questions as $key) {
            $questionText = $q[$key];
            $divid="question_$key";
            $retval.="\t\t  <div class=\"form-group row align-items-center\">\n";
            $retval.="\t\t    <label for=\"\" class=\"col-form-label col-sm-6\">$questionText</label>\n";
            $retval.="\t\t    <div class=\"col-sm-6\">\n";
            $retval.="\t\t    <select class=\"form-control\" id=\"$divid\">\n";
            $retval.="\t\t     <option>(no selection made)</option>\n";

                foreach($a[$key] as $answer) {
                    $retval.= "\t\t     <option>$answer</option>\n";
                } // foreach $answer
            $retval.="\t\t   </select>\n";
            $retval.="\t\t  </div>\n";
            $retval.="\t\t </div>\n";
        } // foreach $question
        $retval.="\t\t</form>\n";
        $retval.="\t\t</div>\n";
    } // foreach $gid -- End Slide

    return $retval;




    /*
    $retval =<<<EOS
              <!-- carouselQuestions START {{{ -->
              <!-- Slide one -->
              <div class="carousel-item active">
                <p>Describe the types of issues you are focused on.</p>
                <form>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I want to work on these systems:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>Test 1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I'd like to support organziations that who foster these policies:</label>
                      <div class="col-sm-6">
                        <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I'd like to support organizations that work to lift the oppression from these groups:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
              <!-- Slide two -->
              <div class="carousel-item">
                <p>Tell us information about the organization</p>
                <form>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">Regarding the scope of an organization, I would prefer:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">Regarding the maturity of an organization, I would prefer a:</label>
                      <div class="col-sm-6">
                        <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
              <!-- Slide three -->
              <div class="carousel-item">
                <p>Do your beliefs line up to those of the organization?</p>
                <form>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I believe that sustainable change comes from:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I believe change is possible</label>
                      <div class="col-sm-6">
                        <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
              <!-- Slide four -->
              <div class="carousel-item">
                <p>Questions about what a volunteer would be doing:</p>
                <form>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I will best contribute by:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I plan to devote a few hours:</label>
                      <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                        </select>
                    </div>
                  </div>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">Do you want to use a particular skill?</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select> 
                    </div>
                  </div>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">I am willing to effect change by:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select> 
                    </div>
                  </div>
                </form>
              </div>
              <!-- Slide five -->
              <div class="carousel-item">
                <p>Questions about the kind of organization you would like to work in:</p>
                <form>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">Some organizations charge dues to their membership. Would you consider:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
              <!-- Slide six -->
              <div class="carousel-item">
                <p>Questions about the organizations membership and how you relate to it:</p>
                <form>
                  <div class="form-group row align-items-center">
                    <label for="" class="col-form-label col-sm-6">Select one of these ages that apply to you:</label>
                    <div class="col-sm-6">
                      <select class="form-control" id="">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                      </select>
                    </div>
                  </div>
                </form>
              </div>
              <!-- carouselQuestions END }}} -->

EOS;
    return $retval;
*/

}

?>
