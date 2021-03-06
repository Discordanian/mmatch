<!DOCTYPE html>
<?php require_once('../include/jsonParse.php'); 
// require_once('include/csp.php');
require_once ('../include/inisets.php');
require_once ('../include/returnOrgsForZipcodeFunction.php');
require_once ('../include/jsonParse.php');
//
 // Parses PHP json objects for management
// session_start();
// "Global" to the page

// TODO Assume defaults for no zip/distance as the W2W style implies defaults
$mconfig = array(
    "zipcode" => "63104",
    "distance" => "20"
);

function validateGetData()
{
    global $mconfig;
    if (isset($_GET['zipcode']) && isset($_GET["distance"])) {
        $mconfig['zipcode']  = empty(FILTER_VAR($_GET["zipcode"], FILTER_SANITIZE_ENCODED))? "63104": FILTER_VAR($_GET["zipcode"], FILTER_SANITIZE_ENCODED); // Zips can start with a 0
        $mconfig['distance'] = empty(FILTER_VAR($_GET["distance"], FILTER_VALIDATE_INT))? "20": FILTER_VAR($_GET["distance"], FILTER_VALIDATE_INT);
        $mconfig['Q1']       = empty($_GET["Q1"])? "(no selection made)": $_GET["Q1"];
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

$mconfig['jsondata']  = json_decode($mconfig['jsonraw'], true);
$mconfig['questions'] = getQuestions($mconfig['jsondata']);
$mconfig['answers']   = getAnswers($mconfig['jsondata']);
$mconfig['groupQs']   = getGroupQuestions($mconfig['jsondata']);
$mconfig['groupTs']   = getGroupText($mconfig['jsondata']);




?>
<html>
  <head>

    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

  </head>
  <body>    
    <div class="scroll-wrapper result-page">      

      <div class="grid-bg container-fluid" data-zww_grid="341x256"></div>

      <div class="top-grid"></div>
      <!-- Floating content -->
      <div class="row fixed-panel">
      </div>
        <div class="col-md-10 col-lg-8 floating-box">
          <div id="top" class="logo-wrapper">
            <a href="#"></a>
          </div>
          <div class="row no-gutters title">
            <div class="col">
              <h2>Woke<span>2</span>Work</h2>
              <h2>St Louis</h2>
              <p>We believe these organizations are a match for your interests.</p>
            </div>
          </div>
          <div class="row no-gutters result-wrapper">
            <div class="col">
              <p class="result-callout">You match <span id="numerator">__</span> out of <span id="denominator">__</span> organizations</p>
              <!-- These will be repeated based on results -->
                <!-- The second column set to col-sm to allow for there to not be an image if need be -->
              <div id="orgresults">
              <div class='row no-gutters align-items-top result'>
                <div class="col-md-3 col-xl-2 org-logo">
                  <img src="img/140x90.png">
                </div>
                <div class="col-sm">
                  <h5>This is a test title</h5>
                  <a href="#">This will be a link</a>
                  <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. </p>
                </div>
              </div> 
              <div class='row no-gutters align-items-top result'>
                <div class="col-md-3 col-xl-2 org-logo">
                  <img src="img/140x90.png">
                </div>
                <div class="col-sm">
                  <h5>This is a test title</h5>
                  <a href="#">This will be a link</a>
                  <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. </p>
                </div>
              </div>
              <div class='row no-gutters align-items-top result'>
                <div class="col-sm">
                  <h5>This is a test title</h5>
                  <a href="#">This will be a link</a>
                  <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. </p>
                </div>
              </div>
             </div> <!-- orgresults -->

            </div>
          </div>
          
          <div class="row to-top no-gutters">
            <a href="#top"><img src="img/topBtn.png"></a>
          </div>
        <!-- Modal Links -->
        <div class="link-wrapper">
          <a href="#" class="modal-link" data-toggle="modal" data-target="#about">About</a>
          <a href="#" class="modal-link" data-toggle="modal" data-target="#privacy">Privacy Policy</a>
          <a href="#" class="modal-link" data-toggle="modal" data-target="#contact">Contact</a>
        </div>
        <!-- Modal Links End -->
        </div>
      <div class="bottom-grid"></div>
      <div class="modal fade" id="questions-fade" tabindex="-1"></div>
    </div>
      <!-- end sm up -->

      <!-- This is the slide up questions -->
    <div class="fixed-bottom text-toggle" >
      <a class="slide-button" href="#" data-target="#slide-panel" aria-expanded="true" aria-controls="#slide-panel" ><span>Re-open questions to change results</span></a>
      
      <div id="slide-panel" class="slide-panel collapse" data-parent="fixed-bottom">

        <h6><span>_/4</span> How do you want to get involved</h6>
        <!-- Question slider -->        
        <div id="form-carousel" class="carousel slide" data-interval="false" data-ride="carousel">
          <div class="carousel-inner">
<?php  
    $mconfig['questionid'] = questionIDs($mconfig['questions'], $mconfig['answers']); 
    echo carouselQuestions($mconfig['questions'], $mconfig['answers'], $mconfig['groupQs'], $mconfig['groupTs']); 
?>
            </div>
            <!-- Controls -->
            <a class="carousel-control-prev" href="#form-carousel" role="button" data-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#form-carousel" role="button" data-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="sr-only">Next</span>
            </a>
            <!-- Mobile controls -->
          </div>
          <div class="slide-button-hldr">
            <a href="#form-carousel" data-slide="next" class="hvr-rectangle-out">More Questions</a>
            <a href="#" class="hvr-rectangle-out" >Match Me</a>
          </div>

        </div>
      </div>
    </div>
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
            <h5>This project started as a small group trying to brainstorm about these issues:</h5>
            <ul>
              <li><p><span>Things have already been hard, but it's going to get worse.</span> The rights of many individuals and communities are directly under attack and will suffer under current policy proposals and as a result of increased racist, misogynist, anti-immigrant, transphobic, and broadly xenophobic rhetoric.</p></li>
              <li><p><span>New people are fired up and ready to fight for justice!</span> We need ways to direct this new energy and power.</p></li>
              <li><p><span>We need to fight complacency and normalization.</span> We need ways for people to be ready for a long fight. How can this amazing mass of newly energized people build sustained, deeper engagement?</p></li>
              <li><p><span>We don't need to start from scratch.</span> How can we support all of the amazing organizations who have already been on the front lines, building this movement?</p></li>
            </ul>
            <p><a class="hashtag" href="#">#JoinTheMovement</a> was born from these questions.</p>
            <p>One thing we know for sure is that there are many groups and organizations that are ready to provide the training, community, and accountability that we’ll need to stay strong for the work ahead.</p>
            <p>Our goal is to connect more people to existing activist powerhouses to further build the strength of our movement and fight isolation, alienation, and complacency.</p>
            <p><span class="callout">People are imperfect. Organizations are imperfect. Movements are imperfect.</span></p>
            <p>But now is not the time for perfectionism. Now is the time to find your group, hunker down, and start planning the long game.</p>
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
            <p>Email: <a href="mailto:howismydriving@woke2work.org" target='_blank'>howismydriving@woke2work.org</a></p>
          </div>
        </div>
      </div>
    </div>

    
    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
    <script src="node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/woke2work.js"></script> 
    <script type="text/javascript" src="js/index.js"></script>
  </body>


  <script type="text/javascript">
    /*For smooth scrolling*/
    document.querySelectorAll('a[href^="#top"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    $(function(){

      /*For slide animation*/
      $(".slide-button").click(function(){

        $(".slide-panel").slideToggle("slow", function() {
          if($("#slide-panel").is(':visible')){
            $(".slide-button span").fadeOut(250, function() {
              $(".slide-button").addClass("active");
              $(".slide-button span").text('Questions').fadeIn(250);
            });
            $('#questions-fade').addClass('show');
            updateCarouselSizes();
          } else {
            $(".slide-button span").fadeOut(250, function() {
              $(".slide-button").removeClass("active");
              $(".slide-button span").text('re-open questions to change results').fadeIn(250);
            });
            $('#questions-fade').removeClass('show');
            
          }
        });

        return false;
      });

      function updateCarouselSizes(){
        $(".carousel-inner").each(function(){
          // I wanted an absolute minimum of 10 pixels
          var maxheight = 10;

          if($(this).find('.carousel-item').length) {   // We've found one or more item within the Carousel...
            // Initialise the carousel (include options as appropriate)
            $(this).carousel({interval: false}); 
            // Now we iterate through each item within the carousel...
            $(this).find('.carousel-item').each(function(){ 
              //console.log($(this).outerHeight());
              if($(this).outerHeight()>maxheight) { // This item is the tallest we've found so far, so store the result...
                maxheight = $(this).outerHeight();
              }
            });
            // Finally we set the carousel's min-height to the value we've found to be the tallest...
            $(this).css("min-height", (maxheight + 10) + "px");
          }
        });
      }
      $(window).on("resize",updateCarouselSizes);
    });

  </script>
<?php
echo "\n\n";
echo "<script type='text/javascript' nonce='{$csp_nonce}'>\n";
echo "var orgs = {$mconfig['jsonraw']};\n"; 
echo "\n\n";
echo "var qids = " . json_encode($mconfig['questionid']) . ";\n"; 
echo "\n\n";
echo "var groupQs = " . json_encode($mconfig['groupQs']) . ";\n"; 
echo "\n\n";
echo "var groupTs = " . json_encode($mconfig['groupTs']) . ";\n"; 
echo "\n\n";
echo "var questions = " . json_encode($mconfig['questions']) . ";\n"; 
echo "\n\n";
echo "var answers = " . json_encode($mconfig['answers']) . ";\n"; 
echo "\n\n";
echo "var q1 = " . json_encode($mconfig['Q1']) . ";\n"; 
echo "\n\n</script>\n"; ?>
</html>
