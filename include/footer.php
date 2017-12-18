<script src="js/footer.js" ></script>

<div id="footer-html" style="padding-top:50px">
    <a data-toggle="modal" href="#aboutModal" style="padding:0% 10% 0% 5%;">About Movement Match</a>
    <a data-toggle="modal" href="#privacyModal" style="padding:0% 10% 0% 5%;">Privacy Policy</a>
    <a data-toggle="modal" href="#contactModal" style="padding:0% 10% 0% 5%;">Contact Us</a>
    
    <div id="aboutModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
          <div class="modal-header" style="background-color:powderblue;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">About Movement Match</h4>
          </div>
          <div class="modal-body" style="background-color:powderblue;">
            <h4>This project started as a small group trying to brainstorm about these issues: </h4>
            
            <ol>
            <li><b>Things have already been hard, but it's going to get worse.</b> The rights of many individuals and communities are directly under attack and will suffer under current policy proposals and as a result of increased racist, misogynist, anti-immigrant, transphobic, and broadly xenophobic rhetoric.</li>
            <li><b>New people are fired up and ready to fight for justice!</b> We need ways to direct this new energy and power. </li>
            <li><b>We need to fight complacency and normalization.</b> We need ways for people to be ready for a long fight. How can this amazing mass of newly energized people build sustained, deeper engagement? </li>
            <li><b>We don't need to start from scratch.</b> How can we support all of the amazing organizations who have already been on the front lines, building this movement? </li>
            </ol>
            
            <p>#JoinTheMovement was born from these questions.</p>
            
            <p>One thing we know for sure is that there are many groups and organizations that are ready to provide the training, community, and accountability that we’ll need to stay strong for the work ahead.</p>
            ​
            <p>Our goal is to connect more people to existing activist powerhouses to further build the strength of our movement and fight isolation, alienation, and complacency.</p>
            
            <h4>People are imperfect. Organizations are imperfect. Movements are imperfect.</h4>
            ​<p>But now is not the time for perfectionism. Now is the time to find your group, hunker down, and start planning the long game.</p>
           </div>
        </div>
    </div>
    
    <div id="privacyModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
          <div class="modal-header" style="background-color:powderblue;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Privacy Policy</h4>
          </div>
          <div class="modal-body" style="background-color:powderblue;">
            <h4>This is our privacy policy. It's simple: </h4>
            <p><b>We do not collect any identifying information,</b> so you don't have to trust us.</p>
            <p>Some of the questions that are asked on the questionnaire can be considered private.
            Everyone should feel comfortable answering these questions because, outside of the
            location information, none of the answers ever leaves the browser.
            </p>
            <p>Of course, we use the location information (primarily zip code) to drive the search algorithm
            and try to only show those organizations that are physically located close to the user.
            If you, as a user, decline to provide that information, that is your right, but it gets
            difficult to provide meaningful search results.</p>
            <p>We do not have any trackers, ads, bugs, frames, 
            or these types of things that modern web sites use to
            unmask their users' identities.</p>
            <p>In order to send these web pages to our users, we always must have
            their IP address, because it is basically impossible to have a web site without knowing that much.
            We may log that IP address along with which resources are accessed in order to help us run the site,
            but we always purge that information within 30 days.</p>
            <h4>That's it.</h4>
          </div>
        </div>
    </div>


    <div id="contactModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
          <div class="modal-header" style="background-color:powderblue;">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Contact Information</h4>
          </div>
          <div class="modal-body" style="background-color:powderblue;">
          <p>Email: <a href="mailto:donotreply@movementmatch.org" id="email_decoded" ></a></p>
          </div>
        </div>
    </div>

    <div id="email_encoded" hidden="" ><?php echo base64_encode(CONTACT_EMAIL); ?></div>
    
</div>