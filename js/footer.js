$(function(){

        $("#contactModal").on('show.bs.modal', showEmailAddress);
        
        //$("#email_decoded").text("document ready happened");
        //console.log("document ready happened");
        
});


function showEmailAddress() 
{
    var email = window.atob($("#email_encoded").text());
    
    $("#email_decoded").text(email);
    $("#email_decoded").attr('href', 'mailto:' + email);
}


