<?php

// Returns the JSON object that I can also get via web service but as a string

function getJSON($zip,$dist)
{
	return file_get_contents("orgs.json");
	
}



?>
