<?php require_once('include/secrets.php'); ?>
<!DOCTYPE html>
<html >
<head>
  <meta charset="UTF-8">
  <title>Movement Match</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
        <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css'>
        <link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic'>
        <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/css/bootstrap-select.css'>
        <link rel='stylesheet prefetch' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.css'>
        <link rel="stylesheet" href="css/style.css">

</head>

<body>
  <center>
    <h1>Movement Match</h1>
  </center>

<!-- TODO Shouldn't span entire page, but looks good on mobile so leaving it for now. -->
<!-- Wrapping Form and Progres Bar in Continer TOP -->
<div class="container">


  <!-- Form Start  -->
<form action="match.php" method="get">
<div class="form-group row">
  <label for="example-text-input" class="col-2 col-form-label">Zip Code</label>
  <div class="col-10">
    <input class="form-control" type="text" value="<?php echo DEFAULT_ZIP ?>" maxlength=5 name="zipcode" id="zipcode">
  </div>
</div><!-- form-group row zipcode -->
<div class="form-group row">
  <label for="example-search-input" class="col-2 col-form-label">Distance in Miles</label>
  <div class="col-10">
    <input class="form-control" type="number" value="<?php echo DEFAULT_RANGE ?>" id="distance" name="distance">
  </div>
</div><!-- form-group row distance -->

<input id="submit" type="submit" label="Next" value="Start Match" class="btn btn-primary">
</form>

  <!-- /End of Form  -->
</div><!-- /Container TOP -->

	<!-- Not using any of these in this form BUT it will load them to cache in advance of the next page -->
        <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.0/jquery-ui.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js'></script>

</body>
</html>
