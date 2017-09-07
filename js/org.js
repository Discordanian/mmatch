$(document).ready(function(){


	$("a#p1_goto_p2").click(function(){


        /* all the client side validation has been passed for page 1 */

        if (validate_page1())
        {
		    $("div#page1").hide();
		    $("div#page2").show();
	    }

	});


	$("a#p2_goto_p1").click(function(){

        if (validate_page2()) 
        {

		    $("div#page2").hide();
		    $("div#page1").show();
        }
	
	});

	$("a#p2_goto_p3").click(function(){

        if (validate_page2()) 
        {

		    $("div#page2").hide();
		    $("div#page3").show();
	    } 
   	});
    

	$("a#p3_goto_p2").click(function(){

		$("div#page3").hide();
		$("div#page2").show();
	
	});

	
	$("a#p3_goto_p4").click(function(){

		$("div#page3").hide();
		$("div#page4").show();
	
	});

	$("a#p4_goto_p3").click(function(){

		$("div#page4").hide();
		$("div#page3").show();
	
	});


	$("a#p4_goto_p5").click(function(){

		$("div#page4").hide();
		$("div#page5").show();
	
	});

	$("a#p5_goto_p4").click(function(){

		$("div#page5").hide();
		$("div#page4").show();
	
	});


	$("a#p5_goto_p6").click(function(){

		$("div#page5").hide();
		$("div#page6").show();
	
	});
	

	$("a#p5_goto_p4").click(function(){

		$("div#page5").hide();
		$("div#page4").show();
	
	});


	$("a#p5_goto_p6").click(function(){

		$("div#page5").hide();
		$("div#page6").show();
	
	});

	$("a#p6_goto_p5").click(function(){

		$("div#page6").hide();
		$("div#page5").show();
	
	});


	$("a#p6_goto_p7").click(function(){

		$("div#page6").hide();
		$("div#page7").show();
	
	});

	$("a#p7_goto_p6").click(function(){

		$("div#page7").hide();
		$("div#page6").show();
	
	});


	$("a#p7_goto_p8").click(function(){

        showSummary();
		$("div#page7").hide();
		$("div#page8").show();
	
	});

	$("a#p8_goto_p7").click(function(){

		$("div#page8").hide();
		$("div#page7").show();
	
	});


    $("a#save_data").click(function(){
        $("form#org_save_form").submit();
        
    });


    $("#generateVerificationEmail").click(function(){
        generateVerificationEmail();
    });

	if ($("#general_alert_msg:visible"))
	{
		setTimeout(closeAlertMsg,3000);
	}

    /* automatically trim fields for all input fields */
    $("input.form-control").blur(function(){
        $(this).val($(this).val().trim());
    });


});


function validate_page1() 
{

    /* first validate the person name and email */

    if ($("#person_name").val().length <= 3)
    {
        $("#person_name_msg").show();
        return false;
    }
    else
    {
        $("#person_name_msg").hide();
    }

    /* lowercase the email */
    $("#email").val($("#email").val().toLowerCase());

    if ($("#email").val().length < 1)
    {
        $("#email_invalid_msg").text("A valid email address is required.");
        $("#email_invalid_msg").show();
        return false;
    }


    if ($("#email").val().length > 128)
    {
        $("#email_invalid_msg").text("Email address should not exceed 128 characters in length.");
        $("#email_invalid_msg").show();
        return false;
    }

    if (!isValidEmailAddress($("#email").val()))
    {
        $("#email_invalid_msg").text("The email address does not appear to follow the proper form.");
        $("#email_invalid_msg").show();
        return false;
    }

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
	
    
    $("#email_invalid_msg").hide();
	$("#pwd_msg").hide();
    return true;
}


function validate_page2() 
{
    if ($("#org_name").val().length <= 3)
    {
        $("#org_name_msg").text("The organization name must have at least 4 characters.");
        $("#org_name_msg").show();
        return false;
    }
    else
    {
        $("#org_name_msg").hide();
    }


    if ($("#org_name").val().length > 128)
    {
        $("#org_name_msg").text("The organization name should not exceed 128 characters in length.");
        $("#org_name_msg").show();
        return false;
    }
    else
    {
        $("#org_name_msg").hide();
    }

    $("#org_website_msg").hide();

    if ($("#org_website").val().length > 255)
    {
        $("#org_website_msg").text("The website URL should not exceed 255 characters in length.");
        $("#org_website_msg").show();
        return false;
    }


    if ($("#org_website").val().length > 1)
    {
        if (!isValidURL($("#org_website").val()))
        {
            $("#org_website_msg").text("The website URL does not follow the proper pattern for a valid URL.");
            $("#org_website_msg").show();
            return false;
        }
        else
        {
                $("#org_website_msg").hide();        
        }
        
    }
    else
    {
            $("#org_website_msg").hide();        
    }


    $("#money_url_msg").hide();

    if ($("#money_url").val().length > 255)
    {
        $("#money_url_msg").text("The donations URL should not exceed 255 characters in length.");
        $("#money_url_msg").show();
        return false;
    }


    if ($("#money_url").val().length > 1)
    {
        if (!isValidURL($("#money_url").val()))
        {
            $("#money_url_msg").text("The donations URL does not follow the proper pattern for a valid URL.");
            $("#money_url_msg").show();
            return false;
        }
        else
        {
                $("#money_url_msg").hide();        
        }
    }
    else
    {
            $("#money_url_msg").hide();        
    }
    
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

function isValidURL(theUrl)
{
    /* I know this is not a perfectly valid url validation expression
       just checking to make sure the url entered *seems* like a url
        On the server side, we will put it through additional checking. */
    var pattern = /^(http|https):\/\/(([a-zA-Z0-9$\-_.+!*'(),;:&=]|%[0-9a-fA-F]{2})+@)?(((25[0-5]|2[0-4][0-9]|[0-1][0-9][0-9]|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[0-1][0-9][0-9]|[1-9][0-9]|[0-9])){3})|([a-zA-Z0-9\-\u00C0-\u017F]+\.)+([a-zA-Z]{2,}))(:[0-9]+)?(\/(([a-zA-Z0-9$\-_.+!*'(),;:@&=]|%[0-9a-fA-F]{2})*(\/([a-zA-Z0-9$\-_.+!*'(),;:@&=]|%[0-9a-fA-F]{2})*)*)?(\?([a-zA-Z0-9$\-_.+!*'(),;:@&=\/?]|%[0-9a-fA-F]{2})*)?(\#([a-zA-Z0-9$\-_.+!*'(),;:@&=\/?]|%[0-9a-fA-F]{2})*)?)?$/;

    return pattern.test(theUrl);

}

function closeAlertMsg()
{
	$("#general_alert_msg").hide("fast");
}

function showSummary()
{

    $("#person_name_summary").text($("#person_name").val());
    $("#email_summary").text($("#email").val());
    $("#org_name_summary").text($("#org_name").val());
    $("#org_website_summary").text($("#org_website").val());
    $("#money_url_summary").text($("#money_url").val());
    $("#mission_summary").text($("#mission").val());

}

function generateVerificationEmail()
{
    /* does an asynchronous get to the URL which was passed from the server and hidden in the DIV */
    /* it's not hidden because it needs to remain secret, it's just the user does not need to see it */
    var url=$("#generateVerficationEmailUrl").text();

    $.get(url, function(data, status){
        $("#email_unverified_msg").text("An email to verify the address has been sent.");
        });
}

