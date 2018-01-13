<script src="js/footer.js" ></script>

<div id="footer-html" class="mm-footer-padding">
    <a data-toggle="modal" href="#aboutModal" class="mm-footer-spacing">About Movement Match</a>
    <a data-toggle="modal" href="#privacyModal" class="mm-footer-spacing">Privacy Policy</a>
    <a data-toggle="modal" href="#contactModal" class="mm-footer-spacing">Contact Us</a>
    
    <div id="aboutModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
          <div class="modal-header mm-modal" >
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">About Movement Match</h4>
          </div>
          <div class="modal-body mm-modal" >
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
          <div class="modal-header mm-modal" >
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Privacy Policy</h4>
          </div>
          <div class="modal-body mm-modal" >
            <h4>This is our privacy policy. It&apos;s simple: </h4>
            <p><b>We do not collect any personal identifying information,</b> so you don&apos;t have to trust us.</p>
            <p>None of the answers you give on the questionnaire ever leave your computer or device.</p>
            <p>What we do see: The zip code and possibly location information may be shared with us,
            which are used to narrow down the list of organizations to those which are physically nearby.
            Also, every web site you use needs to use your IP address in order to interact with you, 
			and we capture your IP address in our logs.</p>
            <p>We do not have any trackers, ads, bugs, frames, 
            or these types of things that modern web sites use to
            unmask their users&apos; identities.</p>
            <h4>That&apos;s it.</h4>
          </div>
        </div>
    </div>


    <div id="contactModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
          <div class="modal-header mm-modal" >
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Contact Information</h4>
          </div>
          <div class="modal-body mm-modal" >
          <p>Email: <a href="mailto:donotreply@movementmatch.org" id="email_decoded" ></a></p>
          </div>
        </div>
    </div>

    <div id="email_encoded" hidden="" ><?php echo base64_encode(CONTACT_EMAIL); ?></div>
    
</div>