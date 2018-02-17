'use strict';

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

    $("#org_website").blur(addhttp);
    $("#money_url").blur(addhttp);
    
	$("#zip_select").click(select_zip);

    $("#zip_unselect").click(remove_zip);
    
	/* change the icon for the collapse elements that are opened, not collapsed */
	/* identify those with the class of "in", and then navigate up through the DOM */
	/* to get to the span that has the icon in it */
	$("div.collapse.in").prev().children("h4.panel-title").children("a").children("span").removeClass("glyphicon-plus");
	$("div.collapse.in").prev().children("h4.panel-title").children("a").children("span").addClass("glyphicon-minus");

	/* then set up events to switch the icon when hidden/shown */
	$("div.collapse").on("hide.bs.collapse", function(){
		$(this).prev().children("h4.panel-title").children("a").children("span").removeClass("glyphicon-minus");
		$(this).prev().children("h4.panel-title").children("a").children("span").addClass("glyphicon-plus");
	});

	$("div.collapse").on("show.bs.collapse", function(){
		$(this).prev().children("h4.panel-title").children("a").children("span").removeClass("glyphicon-plus");
		$(this).prev().children("h4.panel-title").children("a").children("span").addClass("glyphicon-minus");
	});
	
    inactivityTimer = setTimeout(backToLogoutPage, 4 * 60 * 60 * 1000); /* logout after 4 hours of nothing */
    
    /* reset the timer on mostly everything on the page */
    $("div").click(resetInactivityTimer);
    $(":input").focus(resetInactivityTimer);
    
    $(":input").change(disablePrint);

	var rules = "<p>Password must be at least 8 characters long and must contain 3 of the following 4 categories:</p>" +
		"<ol><li>Upper case alphabet characters</li><li>Lower case alphabet characters</li>" +
		"<li>Numeric digits</li><li>Special (non-alphabet) characters</li></ol>";
		
	$("#password1").tooltip({title: rules, html: "true", placement: "auto top", trigger: "focus"});
	$("#password2").tooltip({title: rules, html: "true", placement: "auto top", trigger: "focus"});

});


function save_data_click()
{

    /* validate all data points */
    if (validate_person_name() && validate_email() && validate_password() &&
         validate_org_name() && validate_website() && validate_money_url()) 
    {
        /* make sure that all zip codes in the zip select box are selected
            so that they get submitted with the POST */
        $("#zip_list > option").prop("selected", "true");

        $("#save_data").prop("disabled", "true");
        
        $("form#org_save_form").submit();
    }    
    else
    {
		/* show all panels that contain inputs that are considered invalid */
		$(".form-control:invalid").parent().parent().parent().collapse("show")
    	return false;
    }
}

function select_zip()
{
    /* Move zip code typed in over to select list */
    /* TODO: need lots of input validation here */
    var pattern = /\d{5}/;

    if (pattern.test($("#zip_entry").val()))
    {    
        var opt = document.createElement("option");
        /* trim to 5 digits in case more were entered */
        var zip = $("#zip_entry").val().substring(0,5);
        opt.value = zip;
        opt.text = zip;

        /* now check for duplicates */

        if ($("#zip_list > option[value='" + zip + "']").length == 0)
        {
            $("#zip_list").append($(opt));
			
			/* generate the request to look up the city & state */
			var url = "service/zipcode.php?zip_code=" + zip;
			
			var jxhr = $.get(url, populateZipFromService);
			jxhr.fail(failedToFindZip);
			
        }
    }


    /* clear the value out of the entry field */
    $("#zip_entry").val("");
}

function populateZipFromService(data, status)
{
	/* data contains the zip code, city, and state */
	var obj = JSON.parse(data);
		
	if (status == "success") /* populate the city and state */
	{
		var txt = obj.postal_code + " - " + obj.city + ", " + obj.state;
		
		$("#zip_list > option[value='" + obj.postal_code + "']").text(txt);

			/* remove the "nothing selected" option */
            $("#zip_list option[value='NULL']").remove();
		}

}

function failedToFindZip(jqxhr, textStatus, error)
{
	//Must remove the zip that was not found from the list otherwise, a db integrity error will be registered
	// when we try to insert
	//not sure how to just remove the ones without a city+state using just one selector
	//so just quickly iterate through
	$("#zip_list > option").each(remove_invalid_zip);
	
}

function remove_invalid_zip()
{
	/* remove any zips in the list that don't have the city and state tagged onto them
	in other words, any where the length of the text() is <= 5 characters */
	if ($(this).text().length <= 5)
	{
		$(this).remove();
	}
}

function remove_zip()
{
    /* remove the selected option from the zip code list */
    $("#zip_list option:selected").remove();

    /* check to see if the select list box is empty, if so put the placeholder back in */
    if ($("#zip_list > option").length == 0)
    {
        var opt = document.createElement("option");
        opt.value = "NULL";
        opt.text = "<No zip codes selected>";

        $("#zip_list").append($(opt));
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
        $("#intro1").collapse("show");
        return false;
    }


    if ($("#email").val().length > 255)
    {
        $("#email_invalid_msg").text("Email address should not exceed 255 characters in length.");
        $("#email_invalid_msg").show();
        $("#intro1").collapse("show");
        return false;
    }

    if (!isValidEmailAddress($("#email").val()))
    {
        $("#email_invalid_msg").text("The email address does not appear to follow the proper form.");
        $("#email_invalid_msg").show();
        $("#intro1").collapse("show");
        return false;
    }

    $("#email_invalid_msg").hide();
    return true;
}

function validate_password()
{
	var password = $("#password1").val();

	/* the password is only required in certain situations /*
	/* so if it's not specified, and not required, exit */
	
    if (password.length < 1)
    {
		/* not required and not specified, so nothing to validate */
		return true;
    }
	
	if (password != $("#password2").val())
	{
        $("#pwd_msg").text("Passwords must match.");
        $("#pwd_msg").show();
        return false;
	}
	
	if (password.length > 128)
	{
        $("#pwd_msg").text("The password exceeds the maximum length of 128 characters.");
        $("#pwd_msg").show();
        return false;
	}

	if (password.length < 8)
	{
        $("#pwd_msg").text("The password must be a minimum length of 8 characters.");
        $("#pwd_msg").show();
        return false;
	}
	
    
	/* check password for complexity */

	var hasUpperCase = (password != password.toLowerCase() ? 1 : 0); 
	var hasLowerCase = (password != password.toUpperCase() ? 1 : 0); 
	var hasNumbers = /\d/.test(password);
	var hasNonalphas = /\W/.test(password);
	if (hasUpperCase + hasLowerCase + hasNumbers + hasNonalphas < 3)
    {
        $("#pwd_msg").text("The password does not meet the complexity rules.");
        $("#pwd_msg").show();
        return false;        
    }
	
	$("#pwd_msg").hide();
    return true;
}


function validate_org_name() 
{
    if ($("#org_name").val().length <= 3)
    {
        $("#org_name_msg").text("The organization name must have at least 4 characters.");
        $("#org_name_msg").show();
        $("#intro2").collapse("show");
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
        $("#intro2").collapse("show");
        return false;
    }
    else
    {
        $("#org_name_msg").hide();
    }

    return true;
}

function validate_website()
{
    $("#org_website_msg").hide();

    if ($("#org_website").val().length > 255)
    {
        $("#org_website_msg").text("The website URL should not exceed 255 characters in length.");
        $("#org_website_msg").show();
        $("#intro2").collapse("show");
        return false;
    }


    if ($("#org_website").val().length > 1)
    {
        if (!isValidURL($("#org_website").val()))
        {
            $("#org_website_msg").text("The website URL does not follow the proper pattern for a valid URL.");
            $("#org_website_msg").show();
            $("#intro2").collapse("show");
            return false;
        }
    }
    
    $("#org_website_msg").hide();        
    return true;
}


function validate_money_url()
{

    $("#money_url_msg").hide();

    if ($("#money_url").val().length > 255)
    {
        $("#money_url_msg").text("The donations URL should not exceed 255 characters in length.");
        $("#money_url_msg").show();
        $("#intro2").collapse("show");
        return false;
    }


    if ($("#money_url").val().length > 1)
    {
        if (!isValidURL($("#money_url").val()))
        {
            $("#money_url_msg").text("The donations URL does not follow the proper pattern for a valid URL.");
            $("#money_url_msg").show();
            $("#intro2").collapse("show");
            return false;
        }
    }
    
    $("#money_url_msg").hide();        
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

function backToLogoutPage()
{

    window.location.replace("login.php?errmsg=LOGGED_OFF_INACTIVITY");

}

function generateVerificationEmail()
{
    /* does an asynchronous get to the URL which was passed from the server and hidden in the DIV */
    /* The reason that it's hidden is not because it needs to remain secret, it's just the user does not need to see it */
    var url=$("#generateVerficationEmailUrl").val();

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

function addhttp()
{
    if ($(this).val().substr(0, 4) != "http" && $(this).val().length > 0)
    {
        $(this).val("http://" + $(this).val());
    }
    
    /* if the value is just the protocol and nothing else, just blank it out */
    if ($(this).val() == "http://")
    {
        $(this).val("");
    }
}

function disablePrint()
{
    /* disable the print button */
    $("#printButton").attr("disabled", "disabled");
    /* but the previous only makes it appear disabled */
    /* must also preventDefault so that clicking does nothing */
    $("#printButton").click(function (e) {
        e.preventDefault();
    });

    /* and then display a tooltip so that the user knows what's up */
    $("#printButton").attr("data-toggle", "tooltip");
    $("#printButton").attr("title", "Record must be saved before it can be printed.");
    
}