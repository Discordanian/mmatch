// w2w Configuration Object
// http://javascript.crockford.com/code.html

var w2w = {
    getKey : function(x) {
        return x.split("_")[1];
    }, // getKey
    hrefWebsite: function() {
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
    }, // hrefWebsite
    renderOrgs: function() {
        var html = "";
        var len = orgs.filter(w2w.filterResults).forEach(function(o) {
            html += "<div class=\"panel panel-default\">";
            html += "<div class=\"panel-heading text-center\"><strong>"+o.org_name+"</strong></div>"; // Panel Heading will be org name
            html +="<div class=\"panel-body\">";
            html +="<div class=\"col-sm-6\"><em>Mission:</em> " + o.mission + "</div>";
            html +="<div class=\"col-sm-6\">" + o.org_website + "</div>";
            html +="</div>"; // close panel body
            html +="</div>"; // close panel
        });

        document.getElementById('orgresults').innerHTML = html;
    }, // renderOrgs
    filterResults: function(x) {
        var retval = true;
        qids.forEach(function(q) {
            var k = '#' + q;
            var key = q.split("_")[1]; // Return string after underscore
            var selectedA = $(k).val();

            // skip the parse if we already failed OR if nothing was selected for the question
            if (retval && selectedA !== "(no selection made)" ) {
                x.questions.forEach(function(orgq) {
                    if (orgq.q_id === key) {
                        var orgAnswers = orgq.answers;
                        var answerMatches = false;
                        if (orgAnswers.indexOf(selectedA) > -1) {
                            answerMatches = true;
                        }
                        /*
                        selectedA.forEach(function(a) {
                            // See if answer 'a' is in the array for this org.
                            // KAS - I know that indexOf has problems with undefined and NaN but hope that we don't hit that.
                            if (orgAnswers.indexOf(a) > -1) {
                                answerMatches = true;
                            }
                        });
                        */
                        retval = answerMatches;
                    } // if the org has an answer for the given question

                }); // forEach x.questions
            }

        }); // forEach qids

        return retval;
    }, //filterResults
    updateProgress: function() {
        var selected = orgs.filter(w2w.filterResults).length;
        var total = orgs.length;

        document.getElementById("numerator").innerHTML = selected;
        document.getElementById("denominator").innerHTML = total;
    }, // updateProgress
    init: function() {
        this.updateProgress();
        var fcs = document.getElementsByClassName("form-control");

        for (var i = 0; i < fcs.length; i++) {
            fcs[i].addEventListener('change', this.updateProgress, false);

        }

    } // init

};
