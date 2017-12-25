<?php
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

// TODO Bounce if we don't have a zip or a distance

?>
<!DOCTYPE html>
<html >
<head>
  <meta charset="UTF-8">
  <title>Movement Match</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
	<link rel="stylesheet prefetch" 
		href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" 
		integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" 
		crossorigin="anonymous">
<link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic'>
<link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/css/bootstrap-select.css'>
<link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.css'>

      <link rel="stylesheet" href="css/style.css">

  
</head>

<body>
  <center>
  <h1>Movement Match</h1></center>

<!-- Wrapping Form and Progres Bar in Continer TOP -->
<div class="container">

  <!-- Progress Bar -->
  <div class="progress">
    <div id="progcomplete" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">Filtered

    </div>
    <div id="progremain" class="progress-bar progress-bar-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:100%">
      Remaining
    </div>
  </div>
  <!-- /End of Progress Bar -->

  <!-- Form Start  -->
  <form  class="form form-horizontal">


    <!-- /Limited Multiple Select -->
<?php
echo dropDowns($mconfig['questions'], $mconfig['answers']); ?>
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
  <table data-toggle="table" id="table_results" class="table">
    <thead>
      <tr>
        <th data-field="org_name">Organization</th>
        <th data-field="mission">Mission Statement</th>
        <th data-field="org_website">Website</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Nothing</td>
        <td>Sometihng went Wrong</td>
        <td>Try reloading a new Zip and Distance</td>
      </tr>
    </tbody>
  </table>
</div>
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
<!-- /Results Table -->
<?php
echo "<script type='text/javascript' nonce='{$csp_nonce}'>\n";
echo "var orgs = {$mconfig['jsonraw']};\n"; 
echo "var qids = " . json_encode($mconfig['questionid']) . ";\n"; ?>
</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js" 
		integrity="sha384-nrOSfDHtoPMzJHjVTdCopGqIqeYETSXhZDFyniQ8ZHcVy08QesyHcnOUpMpqnmWq" 
		crossorigin="anonymous"></script>        
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.0/jquery-ui.min.js" 
		integrity="sha384-C/LoS0Y+QiLvc/pkrxB48hGurivhosqjvaTeRH7YLTf2a6Ecg7yMdQqTD3bdFmMO" 
		crossorigin="anonymous"></script>	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js" 
		integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
		crossorigin="anonymous"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js'></script>
<script src="js/match.js"></script>

</body>
</html>
