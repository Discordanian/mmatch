var inactivityTimer = 0;

$(function(){
	
	
    $("#save_data").click(save_data_click);


    $("#generateVerificationEmail").click(generateVerificationEmail);


	if ($("#general_alert_msg").is(":visible"))
	{
		setTimeout(closeAlertMsg,3000);
	}

    /* automatically trim fields for all input fields */
    $("input.form-control").blur(function(){
        $(this).val($(this).val().trim());
    });

    inactivityTimer = setTimeout(backToLogoutPage, 4 * 60 * 60 * 1000); /* logout after 4 hours of nothing */
    
    /* reset the timer on mostly everything on the page */
    $("div").click(resetInactivityTimer);
    $(":input").focus(resetInactivityTimer);

});


function save_data_click()
{

    /* validate all data points */
    if (validate_person_name() && validate_email() && validate_password() ) 
    {
        $("#save_data").prop("disabled", "true");
        
        $("form#org_save_form").submit();
    }    
    else
    {
        /* return false which cancels the submission */
    	return false;
    }
}





function validate_person_name() 
{

    /* first validate the person name and email */

    if ($("#person_name").val().length <= 3)
    {
        $("#person_name_msg").show();
        $("#intro1").collapse("show");
        return false;
    }
    else
    {
        $("#person_name_msg").hide();
    }

    return true;
}

function validate_email()
{
    /* lowercase the email */
    $("#email").val($("#email").val().toLowerCase());

    if ($("#email").val().length < 1)
    {
        $("#email_invalid_msg").text("A valid email address is required.");
        $("#email_invalid_msg").show();
        return false;
    }


    if ($("#email").val().length > 255)
    {
        $("#email_invalid_msg").text("Email address should not exceed 255 characters in length.");
        $("#email_invalid_msg").show();
        return false;
    }

    if (!isValidEmailAddress($("#email").val()))
    {
        $("#email_invalid_msg").text("The email address does not appear to follow the proper form.");
        $("#email_invalid_msg").show();
        return false;
    }

    $("#email_invalid_msg").hide();
    return true;
}

function validate_password()
{
	if ($("#password1").val() != $("#password2").val())
	{
        $("#pwd_msg").text("Passwords must match.");
        $("#pwd_msg").show();
        return false;
	}
	
	if ($("#password1").val().length > 128)
	{
        $("#pwd_msg").text("The password exceeds the maximum length of 128 characters.");
        $("#pwd_msg").show();
        return false;
	}
	
    /* check to make sure a password is specified on insert */
    if ($("#action").val() == "I" && $("#password1").val().length < 1)
    {
        $("#pwd_msg").text("A password is required in order to continue.");
        $("#pwd_msg").show();
        return false;        
    }
    
	$("#pwd_msg").hide();
    return true;
}






function isValidEmailAddress(emailAddress) 
{
    /* I know this is not a perfectly valid email validation expression
       just checking to make sure the email entered *seems* like an email
        address. On the server side, we will put it through additional checking
        and actually validate the email with an email loop. */
    var pattern = /^([\S-\.]+@([\S-]+\.)+[\S-]{2,10})?$/
    //var pattern = new RegExp("");
    return emailAddress.length > 0 && pattern.test(emailAddress);
}



function closeAlertMsg()
{
	$("#general_alert_msg").hide("fast");
}

function backToLogoutPage()
{

    window.location.replace("login.php?errmsg=LOGGED_OFF_INACTIVITY");

}

function generateVerificationEmail()
{
    /* does an asynchronous get to the URL which was passed from the server and hidden in the DIV */
    /* The reason that it's hidden is not because it needs to remain secret, it's just the user does not need to see it */
    var url=$("#generateVerficationEmailUrl").text();

    $.get(url, function(data, status){
        /* it's almost impossible to have a meaningful return value here */
        $("#email_unverified_msg").text("An email to verify the address has been sent.");
        });
}

function resetInactivityTimer()
{

    clearTimeout(inactivityTimer);
        
    inactivityTimer = setTimeout(backToLogoutPage, 4 * 60 * 60 * 1000); /* logout after 4 hours of nothing */

}

