// MM Configuration Object
// http://javascript.crockford.com/code.html
var config = {
  debug: true,
  thresh: 4
};

// If debug is enabled and we have a console log, write to console log
var logger = function(e) {
  if (config.debug && console.log) {
    console.log(e);
  }
};

// given a DOM id (question_1234) return "1234";
var getKey = function(x) {
	return x.split("_")[1];
}

var mm = {
  resultsVisible: false,
  complete: 0,
  amihere: function() {
    logger("amihere was called");
  },
  toggle: function() {
    mm.resultsVisible = ! mm.resultsVisible;
  },
  displayResults: function() {
        if (mm.resultsVisible) {
              $("#table_results").bootstrapTable(
      "load",
      orgs.filter(mm.filterResults)
    );
      $("#results").removeClass("hidden").addClass("visible");
    } else {
      $("#results").removeClass("visible").addClass("hidden");
    }
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
	logger(x.org_name);
	qids.forEach(function(q) {
		var k = '#'+q;
		// logger(k);
		// logger($(k).val());
		
	});
	
    return true;
  },
  updateProgress: function() {
    var selected = orgs.filter(mm.filterResults).length;
    var total = orgs.length;
    if (selected <=config.thresh && selected !== 0) { mm.resultsVisible = true; mm.displayResults();} else { mm.resultsVisible = false; mm.displayResults();}
    

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
  $("#toggle").click(function(e) {
    $("#table_results").bootstrapTable(
      "load",
      orgs.filter(mm.filterResults)
    );
    e.preventDefault(); // prevent the default anchor functionality
    mm.toggle();
    mm.displayResults();
  }); // end Show Me function binding
  
  // Update the progress bar on page load
  mm.updateProgress();
  // Change this to selectpicker and we break the UI
  // Any change in the UI and we update the progress bar
  $(".selectpicker").change(function(e) {
    //e.preventDefault(); // prevent the default anchor functionality
    //e.default();

    mm.updateProgress();
  });
}); // onReady
