<?php
require_once('include/csp.php');
require_once ('include/inisets.php');
require_once ('include/returnOrgsForZipcodeFunction.php');
require_once ('include/jsonParse.php');

 // Parses PHP json objects for management
// session_start();
// "Global" to the page

$mconfig = array(
    "zipcode" => "63104",
    "distance" => "20"
);

function validateGetData()
{
    global $mconfig;
    if (isset($_GET['zipcode']) && isset($_GET["distance"])) {
        $mconfig['zipcode'] = FILTER_VAR($_GET["zipcode"], FILTER_SANITIZE_ENCODED); // Zips can start with a 0
        $mconfig['distance'] = FILTER_VAR($_GET["distance"], FILTER_VALIDATE_INT);
    }
    else {

        // Bounce to index.html

    }
}

validateGetData();
try {
    $mconfig['jsonraw'] = getZipCodeData($mconfig['zipcode'], $mconfig['distance']);
}

catch(Exception $e) {
    $mconfig['jsonraw'] = "[]";
}

$mconfig['jsondata'] = json_decode($mconfig['jsonraw'], true);
$mconfig['questions'] = getQuestions($mconfig['jsondata']);
$mconfig['answers'] = getAnswers($mconfig['jsondata']);
$mconfig['groupQs'] = getGroupQuestions($mconfig['jsondata']);
$mconfig['groupTs'] = getGroupText($mconfig['jsondata']);

// TODO Bounce if we don't have a zip or a distance

?>
<!DOCTYPE html>
<html >
<head>
  <!-- <meta http-equiv="Content-Security-Policy" content="default-src 'self' *.bootstrapcdn.com *.cloudflare.com;  img-src https://*;">   -->
  <meta charset="UTF-8">
  <title>Movement Match</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.0/css/bootstrap.min.css" />
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.0/css/bootstrap-select.min.css" />


      <link rel="stylesheet" href="css/style.css">

  
</head>

<body>
  <h1 class='text-center'>Movement Match</h1>

<!-- Wrapping Form and Progres Bar in Continer TOP -->
<div id="main" class="container">
  <!-- /End of Progress Bar -->

  <!-- Form Start  -->
  <form  class="form form-horizontal">


    <!-- /Limited Multiple Select -->
<?php
echo dropDowns($mconfig['questions'], $mconfig['answers'], $mconfig['groupQs'], $mconfig['groupTs']); ?>
<?php
$mconfig['questionid'] = questionIDs($mconfig['questions'], $mconfig['answers']); ?>


    <button id="toggle" type="submit" class="btn btn-default">Display/Hide Organizations</button>


  </form>
  <!-- /End of Form  -->
</div>
<!-- /Container TOP -->

<hr/>

<!-- Results -->
<div id="results" class="container hidden">
  <h2>You may be interested in these</h2>
  <p>We believe that these organizations are the best match for your interests.</p>
    <div id="orgresults">
    </div>
</div>
<div class="container" id="feedback">
    <div class="alert alert-danger hidden" id="no_orgs">
        <strong>No Organizations matched the combination of zipcode and distance</strong>
    </div>
    <div class="alert alert-danger hidden" id="all_filtered">
        <strong>All Organizations have been filtered out.  No Organizations match the combination of zipcode,distance and choice of answers selected</strong>
    </div>
    <div class="alert alert-info" id="disclaimer">
    <p>Choices are not comprehensive. You may not find what you are looking for. If you do not see what you want, it is because none of the organizations in your area created that choice you are looking for. Skip any question that does not help you to match to an activist organization.</p>
    </div>
    <div class="container border" id="footer">
    <a href="index.php" class="btn btn-info" role="button">Pick a New ZipCode or Distance</a>
    </div>
    <div class="container border hidden" id="debug">
    <pre>
    <?php

    // print_r($mconfig);

    ?>
    </pre>
    </div>
</div>
<!-- /Results Table -->
<?php
echo "<script type='text/javascript' nonce='{$csp_nonce}'>\n";
echo "var orgs = {$mconfig['jsonraw']};\n"; 
echo "var qids = " . json_encode($mconfig['questionid']) . ";\n"; 
echo "var groupQs = " . json_encode($mconfig['groupQs']) . ";\n"; 
echo "var groupTs = " . json_encode($mconfig['groupTs']) . ";\n"; 
echo "var questions = " . json_encode($mconfig['questions']) . ";\n"; 
echo "var answers = " . json_encode($mconfig['answers']) . ";\n"; ?>
</script>
<!-- JQuery, Popper, Bootstrap, Match -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.slim.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.0/js/bootstrap-select.bundle.min.js"></script>


<script src="js/match.js"></script>

</body>
</html>
