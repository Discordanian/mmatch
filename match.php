<?php

require_once('include/inisets.php');
require_once('service/getJSON.php');

session_start();




function validatePostData()
{
    $zipcode  = FILTER_VAR($_GET["zipcode"],  FILTER_SANITIZE_ENCODED);
    $distance = FILTER_VAR($_GET["distance"], FILTER_VALIDATE_INT);

}


?>
<!DOCTYPE html>
<html >
<head>
  <meta charset="UTF-8">
  <title>STL MM Search Demo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css'>
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
  <form class="form form-horizontal">


    <!-- Limited Multiple Select -->
    <div class="form-group">
      <label class="control-label col-sm-2" for="location">Location:</label>
      <select id="locationSelect" class="question selectpicker">
  <option>New York</option>
  <option>St Louis</option>
  
</select>
    </div>
    <!-- /Limited Multiple Select -->


    <!-- Limited Multiple Select -->
    <div class="form-group">
      <label class="control-label col-sm-2" for="flower">Flowers:</label>
      <select id="flowerSelect" class="selectpicker" multiple data-max-options="2" multiple>
  <option>Rose</option>
  <option>Violet</option>
  <option>Lilly</option>
</select>
    </div>
    <!-- /Limited Multiple Select -->


    <button id="toggle" type="submit" class="btn btn-default">Just Show Me</button>


  </form>
  <!-- /End of Form  -->
</div>
<!-- /Container TOP -->

<hr/>

<!-- Results -->
<div id="results" class="container hidden">
  <h2>Your Match</h2>
  <p>We believe that these organizations are the best match for your interests.</p>
  <table data-toggle="table" id="table_results" class="table">
    <thead>
      <tr>
        <th data-field="rank">Rank</th>
        <th data-field="organization">Organization</th>
        <th data-field="location">Location</th>
        <th data-field="url">Website</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td>Walk a Dog</td>
        <td>St Louis</td>
        <td>http://walkadog.gov</td>
      </tr>
      <tr>
        <td>2</td>
        <td>Not the NSA</td>
        <td>New York</td>
        <td>http://nsa.gov</td>
      </tr>
      <tr>
        <td>3</td>
        <td>Meals on Wheels</td>
        <td>Everywhere</td>
        <td>http://mealsonwheels.com</td>
      </tr>
    </tbody>
  </table>
</div>
<div id="debug">
<?php 
validatePostData();
$arr = get_defined_vars(); print_r($arr); 
?>
</div>
<!-- /Results Table -->
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.0/jquery-ui.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js'></script>
<script src="js/index.js"></script>

</body>
</html>
