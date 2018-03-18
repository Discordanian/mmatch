// MM Configuration Object
// http://javascript.crockford.com/code.html
var config = {
    debug: false,
    thresh: 5
};

// If debug is enabled and we have a console log, write to console log
var logger = function(e) {
    if (config.debug && console.log) {
        console.log(e);
    }
};
var hrefWebsite =  function() {
	orgs.forEach(function(org) {
		var url = "";
		if(org.customer_contact) {
			// We have /something/ in customer_contact
			var volunteer = org.customer_contact;
			if (volunteer.toUpperCase().indexOf("HTTP") !== -1) {
				url = "Volunteer: <a href=\""+volunteer+"\" target='_blank' rel='noopener'>"+volunteer+"</a><br />";
			} else {
				url = "Volunteer Contact: <em>"+volunteer+"</em><br />";
			}
		}
		if (org.money_url) { 
		url += "Donate: <a href=\""+org.money_url+"\" target='_blank' rel='noopener'>"+org.money_url+"</a><br />";
		}
		if (org.org_website) { 
		url += "Info: <a href=\""+org.org_website+"\" target='_blank' rel='noopener'>"+org.org_website+"</a>";
		}
		org.org_website = url;
        });
};

// given a DOM id (question_1234) return "1234";
var getKey = function(x) {
    return x.split("_")[1];
};

var mm = {
    resultsVisible: false,
    complete: 0,
    amihere: function() {
        logger("amihere was called");
    },
    toggle: function() {
        mm.resultsVisible = !mm.resultsVisible;
    },
    displayResults: function() {
        if (mm.resultsVisible) {
            // $("#table_results").bootstrapTable( "load", orgs.filter(mm.filterResults));
            mm.renderOrgs();
            $("#results").removeClass("hidden").addClass("visible");
	    document.getElementById('toggle').innerHTML = "Hide Organizations";
        } else {
            $("#results").removeClass("visible").addClass("hidden");
	    document.getElementById('toggle').innerHTML = "Show Organizations";
        }
    },
    renderOrgs: function() {
        var html = "";
        var len = orgs.filter(mm.filterResults).forEach(function(o) {
            html += "<div class=\"panel panel-default\">";
            html += "<div class=\"panel-heading text-center\"><strong>"+o.org_name+"</strong></div>"; // Panel Heading will be org name
            html +="<div class=\"panel-body\">";
            html +="<div class=\"col-sm-6\"><em>Mission:</em> " + o.mission + "</div>";
            html +="<div class=\"col-sm-6\">" + o.org_website + "</div>";
            html +="</div>"; // close panel body
            html +="</div>"; // close panel
        });

        document.getElementById('orgresults').innerHTML = html;
    },
    showNoOrgs: function() {
        $("#no_orgs").removeClass("hidden").addClass("visible");
	mm.hideAllFiltered();
        $("#toggle").removeClass("visible").addClass("hidden");
    },
    showAllFiltered: function() {
        $("#all_filtered").removeClass("hidden").addClass("visible");
	document.getElementById('toggle').innerHTML = "Hide Organizations";
    },
    hideAllFiltered: function() {
        $("#all_filtered").removeClass("visible").addClass("hidden");
	document.getElementById('toggle').innerHTML = "Show Organizations";
    }, 
    filterResults: function(x) {
        var retval = true;
        /*
            // Location is an example of a single selection
            var location_filter = x.location == $("#locationSelect").val();
            // Flowers is an example of a multiple selection.  Returns an array
            var selected_flowers = $("#flowerSelect").val();
            // If nothing is selected, assume ALL
            var flower_filter =
              selected_flowers.length === 0 || selected_flowers.includes(x.flower);
            mm.amihere();

            // return location_filter && flower_filter;
        */
        // logger(x.org_name);
        qids.forEach(function(q) {
            var k = '#' + q;
            var key = getKey(q);
            var selectedA = $(k).val();
            // logger(k);
            // logger(selectedA);

            // skip the parse if we already failed OR if nothing was selected for the question
            if (retval && selectedA.length) {
                x.questions.forEach(function(orgq) {
                    if (orgq.q_id === key) {
                        var orgAnswers = orgq.answers;
                        logger("Found this question in this org " + x.org_name);
                        // logger(orgAnswers);
                        var answerMatches = false;
                        selectedA.forEach(function(a) {
                            // See if answer 'a' is in the array for this org.
                            // Was using 'includes' but older versions of IE didnt' suport it.
                            // KAS - I know that indexOf has problems with undefined and NaN but hope that we don't hit that.
                            if (orgAnswers.indexOf(a) > -1) {
                                answerMatches = true;
                                logger("Match for answer " + x.org_name + " " + key);
                            }
                        });
                        retval = answerMatches;
                    } // if the org has an answer for the given question

                }); // forEach x.questions
            }

        }); // forEach qids

        return retval;
    },
    updateProgress: function() {
        var selected = orgs.filter(mm.filterResults).length;
        var total = orgs.length;
        if (selected <= config.thresh && selected !== 0) {
            mm.resultsVisible = true;
            mm.displayResults();
        } else {
            mm.resultsVisible = false;
            mm.displayResults();
        }
        if (selected === 0) {
            mm.showAllFiltered();
        } else {
            mm.hideAllFiltered();
        }
        if (total === 0) {
            mm.showNoOrgs();
        }


        mm.complete = Math.floor(100 * ((total - selected) / total));

        logger(
            "Evaluate Filter selected, total, complete = [" +
            selected +
            "," +
            total +
            "," +
            mm.complete +
            "]"
        );
        $("#progcomplete")
            .css("width", mm.complete + "%")
            .attr("aria-valuenow", mm.complete);
        $("#progremain")
            .css("width", 100 - mm.complete + "%")
            .attr("aria-valuenow", 100 - mm.complete);


    }
};

$(function() {
    // Bind a show all function to the 'Just Show Me' button
    // Randomize orgs
    var j,x,i;
    for ( i = orgs.length - 1; i > 0; i--) {
	j = Math.floor(Math.random() * (i+1));
        x = orgs[i];
        orgs[i] = orgs[j];
        orgs[j] = x;        
    }    
    $("#toggle").click(function(e) {
        // $("#table_results").bootstrapTable( "load", orgs.filter(mm.filterResults));
        mm.renderOrgs();
        e.preventDefault(); // prevent the default anchor functionality
        mm.toggle();
        mm.displayResults();
    }); // end Show Me function binding

    // Update the progress bar on page load
    hrefWebsite();
    mm.updateProgress();
    // Change this to selectpicker and we break the UI
    // Any change in the UI and we update the progress bar
    $(".selectpicker").change(function(e) {
        //e.preventDefault(); // prevent the default anchor functionality
        //e.default();

        mm.updateProgress();
    });
}); // onReady
