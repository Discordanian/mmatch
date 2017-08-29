$(document).ready(function(){


	$("form#login_form").submit(function(){

        /* all the client side validation */

        return validate();

	});


    $("input#email").blur(function(){
        $(this).val($(this).val().trim());
    });

});


function validate() 
{


    if ($("#email").val().length < 1)
    {
        $("#auth_fail_msg").text("A valid email address is required in order to authenticate.");
        $("#auth_fail_msg").show();
        return false;
    }


    if ($("#email").val().length > 128)
    {
        $("#auth_fail_msg").text("Email address should not exceed 128 characters in length.");
        $("#auth_fail_msg").show();
        return false;
    }

    if (!isValidEmailAddress($("#email").val()))
    {
        $("#auth_fail_msg").text("The email address does not appear to follow the proper form.");
        $("#auth_fail_msg").show();
        return false;
    }


    if ($("#password").val().length < 1)
    {
        $("#auth_fail_msg").text("A password is required in order to authenticate.");
        $("#auth_fail_msg").show();
        return false;
    }
    
    if ($("#password").val().length > 128)
    {
        $("#auth_fail_msg").text("The maximum length of a password is 128 characters.");
        $("#auth_fail_msg").show();
        return false;
    }
    
    $("#auth_fail_msg").hide();
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



