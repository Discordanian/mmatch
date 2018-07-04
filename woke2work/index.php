<!DOCTYPE html>
<?php 
// require_once('include/csp.php');
require_once ('../include/inisets.php');
require_once ('../include/returnOrgsForZipcodeFunction.php');
require_once ('../include/jsonParse.php');
//
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
<html>
  <head>
    
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

  </head>
  <body>
    <div class="scroll-wrapper">

      <div class="grid-bg container-fluid" data-zww_grid="341x256"></div>

      <div class="top-grid"></div>
      <!-- Floating content -->
      <div class="col-md-10 col-lg-8 floating-box">
        <div class="logo-wrapper">
          <a href="#"></a>
        </div>
        <div class="row no-gutters title">
          <div class="col">
            <h2>Woke<span>2</span>Work</h2>
            <h2>St Louis</h2>
            <p>Connecting people to organizations to further build the strength of the progressive movement</p>
            <a class="hashtag" href="#">#jointhemovement</a>
          </div>
        </div>
        
        <!-- form -->
        <div class="row form-wrap no-gutters">
          <div class="col col-lg-11 col-xl-9">
          <h6>Find a progressive org near you</h6>
          <form action="match.php" method="GET">
            <div class="form-group row no-gutters justify-content-center">
            <label for="Q1" class="col-sm-12 col-md-auto col-form-label text-sm-center"><!-- What are you interested in working on?--><?php echo question1Text($mconfig['questions']); ?></label>
              <div class="col-sm-12 col-md-6 col-xl-7">
                <select class="form-control" id="question_1" name="Q1">
                    <?php
                        // Function returns string
                        echo question1options($mconfig['questions'], $mconfig['answers'], $mconfig['groupQs'], $mconfig['groupTs']);
                    ?>
                </select>
              </div>
            </div>
            <div class="form-group form-inline row no-gutters justify-content-center">
              <div class="col-sm-12 col-md-5">
              <div class="row justify-content-center">
                <label for="zipcode" class="col-sm-12 col-md-auto col-form-label">Your zip code</label>
                <input type="text" class="form-control col-sm-12 col-md-4" id="zipcode" name="zipcode" placeholder="63010">
              </div>
              </div>
              <div class="col-sm-12 col-md-7">
                <div class="row justify-content-sm-center">
                  <label for="distance" class="col-sm-12 col-md-auto col-form-label">Search within (miles)</label>
                  <input type="text" name="distance" class="form-control col-sm-12 col-md-4" id="distance" placeholder="15 Miles">
                </div>
              </div>
            </div>
            <div class="form-group row justify-content-center">
              <div class="col-sm-auto button-holder">
                <button type="submit" class="btn mb-2 hvr-rectangle-out">Match Me</button>
              </div>
            </div>
          </form>
          </div>
        </div>
        <!-- Form end -->
        <!-- Modal Links -->
        <div class="link-wrapper">
          <a href="#" class="modal-link" data-toggle="modal" data-target="#about">About</a>
          <a href="#" class="modal-link" data-toggle="modal" data-target="#privacy">Privacy Policy</a>
          <a href="#" class="modal-link" data-toggle="modal" data-target="#contact">Contact</a>
        </div>
        <!-- Modal Links End -->
      </div>
      <div class="bottom-grid"></div>
    </div>

    <!-- end sm up -->





<!-- Modals -->

    <!-- About Modal -->
    <div class="modal fade" id="about" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
          <div class="zap-modal-head">
            <h4>About Movement Match</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              X
            </button>
          </div>
          <div class="modal-body">
            <h5>OUR VISION</h5>
            <p>A world in which some stride towards a utopia for all.</p>
            <h5>OUR MISSION</h5>
            <p>To answer the question, “What can I do to realize a better world for all?”</p>
            <h5>ABOUT YOU</h5>
            <p>You wish for a better world for all; even if that means your status is lessened.</p>
            <p>You value yourself and will only give of yourself if it is not not wasted.</p>
            <p>You can patiently, urgently focus and persist.</p>
            <p>You want to fix the problem at the source.</p>
            <p>If that is you, then we would like to make you aware of some organizations that might interest you.</p>
            <p><span>Us? The best use of our skills is to bring you this free directory. We believe we are meaningfully contributing to a global utopia, however you might envision that.</span></p>
          </div>
        </div>
      </div>
    </div>
    <!-- About End -->
    <!-- Privacy Policy -->
    <div class="modal fade" id="privacy" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
          <div class="zap-modal-head">
            <h4>Privacy Policy</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              X
            </button>
          </div>
          <div class="modal-body">
            <h5>This is our privacy Policy. It's simple:</h5>
            <p><span>We do not collect any personal identifying information,</span> so you don't have to trust us.</p>
            <p>None of the answers you give on the questionnaire ever leave your computer or device.</p>
            <p>What we do see: The zip code and possibly location information may be shared with us, which are used to narrow down the list of organizations to those which are physically nearby. Also, every web site you use needs to use your IP address in order to interact with you, and we capture your IP address in our logs.</p>
            <p>We do not have any trackers, ads, bugs, frames, or these types of things that modern web sites use to unmask their users' identities.</p>
            <h5 class="callout">That's it.</h5>
          </div>
        </div>
      </div>
    </div>
    <!-- privacy end -->
    <!-- Contact -->
    <div class="modal fade" id="contact" tabindex="-1" role="dialog" aria-labelledby="basicModal" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
          <div class="zap-modal-head">
            <h4>Contact Us</h4>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              X
            </button>
          </div>
          <div class="modal-body">
            <p>Email: <a href="mailto:quequegg@gmail.com" target='_blank'>quequegg@gmail.com</a></p>
          </div>
        </div>
      </div>
    </div>
    <!-- Contact end -->
<!-- Modals -->
    
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/index.js"></script>
    <script type="text/javascript" src="js/woke2work.js"></script>

  </body>
<?php
echo "<script type='text/javascript' nonce='{$csp_nonce}'>\n";
echo "var orgs = {$mconfig['jsonraw']};\n"; 
echo "var qids = " . json_encode($mconfig['questionid']) . ";\n"; 
echo "var groupQs = " . json_encode($mconfig['groupQs']) . ";\n"; 
echo "var groupTs = " . json_encode($mconfig['groupTs']) . ";\n"; 
echo "var questions = " . json_encode($mconfig['questions']) . ";\n"; 
echo "var answers = " . json_encode($mconfig['answers']) . ";\n"; ?>
</html>
