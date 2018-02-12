<?php require_once('include/csp.php'); ?>
<?php require_once ('include/inisets.php');?>
<?php require_once('include/secrets.php'); ?>
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
	<link rel="stylesheet prefetch" 
		href="https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" 
		crossorigin="anonymous">
<!--        <link rel='stylesheet prefetch' 
	href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/css/bootstrap-select.css'> -->
<!--        <link rel='stylesheet prefetch' 
	href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.css'> --> 

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js" 
		integrity="sha384-nrOSfDHtoPMzJHjVTdCopGqIqeYETSXhZDFyniQ8ZHcVy08QesyHcnOUpMpqnmWq" 
		crossorigin="anonymous"></script>        
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.0/jquery-ui.min.js" 
		integrity="sha384-C/LoS0Y+QiLvc/pkrxB48hGurivhosqjvaTeRH7YLTf2a6Ecg7yMdQqTD3bdFmMO" 
		crossorigin="anonymous"></script>	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js" 
		integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" 
		crossorigin="anonymous"></script>
<!--        <script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js'></script> -->
<!--        <script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js'></script> -->

        <link rel="stylesheet" href="css/style.css">

</head>

<body>
  <center>
    <h1>Movement Match</h1>
  </center>

<!-- TODO Shouldn't span entire page, but looks good on mobile so leaving it for now. -->
<!-- Wrapping Form and Progress Bar in Container TOP -->
<div class="container">


  <!-- Form Start  -->
<form action="match.php" method="get">
<div class="form-group row">
  <p><small class="text-muted">This allows you to search for organizations based on locality.</small></p>
  <label for="zipcode" class="col-xs-4 col-form-label">Enter Zip Code</label>
  <div class="col-xs-4">
    <input class="form-control" type="text" value="<?php echo DEFAULT_ZIP ?>" maxlength="5" name="zipcode" id="zipcode">
  </div>
</div><!-- form-group row zipcode -->
<div class="form-group row">
  <label for="distance" class="col-xs-4 col-form-label">Search Radius (in Miles)</label>
  <div class="col-xs-4">
    <input class="form-control" type="number" value="<?php echo DEFAULT_RANGE ?>" min="0" max="99999" id="distance" name="distance">
  </div>
</div><!-- form-group row distance -->

<input id="submit" type="submit" label="Next" value="Next" class="btn btn-primary">
</form>
  <!-- /End of Form  -->

<?php require('include/footer.php'); ?>

</div><!-- /Container TOP -->


	<!-- Not using any of these in this form BUT it will load them to cache in advance of the next page -->

</body>
</html>
