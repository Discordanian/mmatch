<?php

require_once('initializeDb.php');


function getZipCodeData($get_zipcode, $get_range_miles)
{
    global $dbh;

    /* sanitize these values brought in before I do any processing based upon them */
	/* this ensures the entered value is 5 numeric digits and that's it */
    sscanf($get_zipcode, "%05u", $zipcode);
	sscanf($get_range_miles, "%3u", $range_miles);

    if (($zipcode <= 0) || strlen($range_miles) < 0)
    {
		error_log("zip code or range does not follow proper format in returnOrgsForZipcodeFunction.php.");
		throw new Exception("An unknown error occurred (1). Please try again.");
		exit(); /* this should not be run, but just in case, we do not want to continue */
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
    		throw new Exception("An unknown error occurred (2). Please attempt to authenticate again.");
    		exit();
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
				$question->group_order = $row["group_order"];
				$question->group_text = $row["group_text"];
		        $question->answers = $choices;
		        
		        $questions[$j] = $question;
		      
		        $org->orgid = $row["orgid"];
		        $org->org_name = $row["org_name"];
		        $org->distance = round($row["distance"] / 1.609, 2); /* stored procedure works in km, return information in miles */
		        $org->customer_notice = $row["customer_notice"];
				$org->mission = $row["mission"];
				$org->customer_contact = $row["customer_contact"];
				$org->abbreviated_name = $row["abbreviated_name"];
				$org->org_website = $row["org_website"];
				$org->money_url = $row["money_url"];
		        		        
                
		        $org->questions = $questions;
		        //$org->answers = $answers;

		        $dataset[$i] = $org;
			    
		        $prevOrgId = $row["orgid"];
		        $prevQuestionId = $row["question_id"];

			}

            $stmt->closeCursor();
			
			if ($i < 0)
			{
        		throw new Exception("No data was found for that criteria.");
				return;
			}
			else 
			{
			    return json_encode($dataset);
			}
		}
		
    }
}


?>
