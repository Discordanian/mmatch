<?php

require_once('../include/inisets.php');
require_once('../include/secrets.php');

/* This program takes a zip code and range as a parameter, validates it,
	and returns the list of orgs that are within range of that zip code
	along with their responses */
/* TODO: Should this function require authentication? It could be abused. */
if (array_key_exists("zip_code", $_GET) && isset($_GET["zip_code"]) 
	&& array_key_exists("range_miles", $_GET) && isset($_GET["range_miles"]))
{
    getZipCodeData($_GET["zip_code"], $_GET["range_miles"]);
}
else
{
	header("HTTP/1.0 500 Server Error", true, 500);
    echo "An unknown error (1) occurred trying to look up the zip code.";
	exit();
}


function getZipCodeData($get_zipcode, $get_range_miles)
{
    global $dbh;

    /* sanitize these values brought in before I do any processing based upon them */
	/* this ensures the entered value is 5 numeric digits and that's it */
    sscanf($get_zipcode, "%05u", $zipcode);
	sscanf($get_range_miles, "%3u", $range_miles);

    if (($zipcode <= 0) || strlen($range_miles) < 0)
    {
		header("HTTP/1.0 500 Server Error", true, 500);
		echo "An unknown error (2) occurred trying to look up the zip code.";
    }
    else
    {

		initializeDb();
        $stmt = $dbh->prepare("CALL selectNearbyOrgResponses(:zip_code, :range) ; ");

        $stmt->bindValue(':zip_code', $zipcode, PDO::PARAM_INT);
		$stmt->bindValue(':range', $range_miles * 1.609); /* the stored procedure uses data in kilometers */
	    $stmt->execute();

        if ($stmt->errorCode() != "00000") 
        {
            $erinf = $stmt->errorInfo();
            error_log("Query failed. Error code:" . $stmt->errorCode() . $erinf[2]); /* the error message in the returned error info */
			header("HTTP/1.0 500 Server Error", true, 500);
			echo "An unknown error (3) occurred trying to look up the zip code.";
        }
		else
		{
			$i = -1;
			$j = 0;
			$prevOrgId = 0;
			$prevQuestionId = 0;
			//echo "[ "; /* start the response with an array */

			$dataset = array();
			
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) 
			{
			    if ($prevQuestionId != $row["question_id"] || $prevOrgId != $row["orgid"])
			    {
			        $question = new stdClass();
			        
			        $j++;
			        $k = 0;
			        $choices = array(); /* this is the array of choices that were selected for each question, initialize it for each question */
			    }

			    if ($prevOrgId != $row["orgid"])
			    {
			        $org = new stdClass();
			        
    				$i++;
    				
    				$questions = array();
    				$answers = array();
    				$j = 0;
			    }
			    
			    
			    $choices[$k] = $row["choice_text"];
			    $answers[$j] = $choices;
			    $k++;

		        $question->q_id = $row["question_id"];
		        $question->text = $row["customer_question_text"];
		        
		        
		        $questions[$j] = $question;
		      
		        $org->orgid = $row["orgid"];
		        $org->org_name = $row["org_name"];
		        $org->distance = round($row["distance"] / 1.609, 2); /* stored procedure works in km, return information in miles */
		        $org->customer_notice = $row["customer_notice"];
		        		        
                
		        $org->questions = $questions;
		        $org->answers = $answers;

		        $dataset[$i] = $org;
			    
		        $prevOrgId = $row["orgid"];
		        $prevQuestionId = $row["question_id"];

			}

			
			if ($i == 0)
			{
				header("HTTP/1.0 404 Not found", true, 404);
				echo(json_encode(array($zipcode, "No data was found that meets that criteria.")));
				//printf("%05u - That zip code was not found.", $zipcode);;
			}
			else 
			{
			    //var_dump($dataset);
			    //echo "\n";
			    echo(json_encode($dataset));
			}
		}
		
        $stmt->closeCursor();
    }
}


function initializeDb()
{

    try 
    {
        if (!isset($dbh))
        {
            global $dbh, $dbhostname, $dbusername, $dbpassword;
            $dbh = new PDO("mysql:dbname=MoveM;host={$dbhostname}" , $dbusername, $dbpassword);
        }
    }
    catch (PDOException $e)
    {
        die("Database Connection Error: " . $e->getMessage());
        /* TODO: much better/cleaner handling of errors */
    }
    catch(Exception $e)
    {
        die($e->getMessage());
        /* TODO: much better/cleaner handling of errors */
    }

}



?>
