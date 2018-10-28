<?php
require_once('include/inisets.php');
require_once('include/secrets.php');
require_once('include/initializeDb.php');
require_once('include/utility_functions.php');
require_once('class/fpdf.php');

class PDF extends FPDF
{
    protected $m_org_name;
    protected $m_print_date;
    protected $m_site_brand;

    function setOrganizationName($p_orgname)
    {
        $this->m_org_name = $p_orgname;
    }

    function setPrintDate($p_print_date_time)
    {
        $this->m_print_date = strftime("%Y-%m-%d %H:%M:%S %Z" , $p_print_date_time);
    }

    function setSiteBrand($p_site_brand)
    {
        $this->m_site_brand = $p_site_brand;
    }


    // Page header
    function Header()
    {

        // Logo
        if ($this->m_site_brand == "Woke2Work") 
        {
            $this->Image('img/MovementMatch-logo.png',10,6,30);
        }
        else
        {
            $this->Image('img/mmlogo.jpg',10,6,30);
        }
        // Arial bold 15
        $this->SetTextColor(0); /* Black */
        $this->SetLineWidth(0.5);
        $this->SetFont('Arial','B',15);
        // Move to the right, far enough that the box will be centered
        $this->Cell(33);
        // Title
        $this->Cell(150,10,$this->m_site_brand . ' - Organization Details',"TLR",0,'C');
        // Line break
        $this->Ln(10);
        $this->Cell(33);

        /* figure out what size to make the text and still fit */
        $size = 14;
        while ($this->GetStringWidth($this->m_org_name) > 145)
        {
            $this->SetFontSize(--$size);
        }

        $this->Cell(150,10,$this->m_org_name,"BLR",0,'C');
        $this->Ln(20);

    }

    // Page footer
    function Footer()
    {
        // Position at bottom
        $this->SetY(-12);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
	// print date
        $this->SetX(160);
        $this->Cell(40, 6, $this->m_print_date, 0, 0, 'R');
        // Page number
	$this->SetY(-18);
        $this->SetX(160);
        $this->Cell(40,6,'Page '.$this->PageNo().'/{nb}', 0, 0,'R');

	/* Show the legend */

	$this->SetY(-18);
	$this->SetFont('Zapfdingbats', '', 8);
	$this->SetX(10);
	$this->Write(6, "4");
	$this->SetFont('Arial', 'I', 8);
	$this->SetTextColor(0); /*back to black text */
	$this->SetX(10);
	$this->MultiCell(45, 6, "  Indicates this choice has been selected by the user");

	$this->SetY(-18);
	$this->SetX(60);
	$this->SetFont('Zapfdingbats', '', 8);
	$this->SetTextColor(120); /*gray text */
	$this->Write(6, "o");
	$this->SetFont('Arial', 'I', 8);
	$this->SetX(60);
	$this->MultiCell(45, 6, "  Indicates this choice was available but not chosen");



	$this->SetY(-18);
	$this->SetTextColor(255, 0, 0);
	$this->SetFont('Zapfdingbats', '', 8);
	$this->SetX(110);
	$this->Write(6, "6");
	$this->SetFont('Arial', 'I', 8);
	$this->SetTextColor(0); /*back to black text */
	$this->SetX(110);
	$this->MultiCell(45, 6, "  Indicates this choice was not available at the time of selection");

	/* draw a border around the legend */
	$this->Rect(10, 260, 195, 15);

    }
}


my_session_start();

try 
{

    initializeDb();

    // Instanciation of inherited class
    $pdf = new PDF("P", "mm", "Letter");

    /* just put the user ID into the author metadata */
    $pdf->SetAuthor($_SESSION["my_user_id"], TRUE);
    $pdf->setSiteBrand($site_brand);
    $pdf->setPrintDate(time()); /* set current date/time */

    getParameter(); /* check authorization, get the org ID from the $_GET */

    $pdf->AliasNbPages();


    printOrgData();
    selectQuestionsWithResponses();
    printZipcodes();

    $pdf->Output("I", "OrganizationalDetail.pdf");    
}
catch (Exception $e)
{
	
	switch ($e->getMessage())
	{
	    case "USER_NOT_LOGGED_IN_ERROR":
	       header("Location: login.php?errmsg=USER_NOT_LOGGED_IN_ERROR");
	       exit();
        break;
	    default:
	       header("Location: login.php?errmsg=true");
	       exit();
	    
	}

    
}

function getParameter()
{
    global $orgid;
    $orgid = filter_var($_REQUEST["orgid"], FILTER_VALIDATE_INT);

    if ($orgid < 1)
    {
        error_log("Required organization ID not supplied");
        throw new Exception("An unknown error was encountered (1). Please attempt to reauthenticate.");
        exit();    
    }

    /* make sure orgid from session matches org ID requested */
    if (!in_array($orgid, $_SESSION["orgids"]) && $_SESSION["admin_user_ind"] == FALSE)
    {
        error_log("Unauthorized org ID requested. Possible parameter tampering.");
        throw new Exception("Unauthorized org ID requested. Possible parameter tampering.");
        exit();
    }

}

function printOrgData()
{

    try {

        global $dbh, $orgid, $pdf;

        $stmt = $dbh->prepare("CALL selectOrganization(:orgid);");
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_OBJ);


        if (isset($row))
        {

            global $abbreviated_name, $customer_notice, $customer_contact, $admin_contact, $active_ind, $user_id;
            $pdf->setOrganizationName($row->org_name);
            $pdf->SetTitle($row->org_name, TRUE);
            $pdf->AddPage();

            $pdf->SetLineWidth(0.5);
            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->Cell(196, 10, "Representative Info", 1, 1, "C", TRUE);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "User ID: ", "TL", 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row->user_id, "TR", 1);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Name: ", "L", 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row->person_name, "R", 1);
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Email Address: ", "BL", 0);

            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row->email, "BR", 0);
            $saveX = $pdf->GetStringWidth($row->email) + 58;

            /* make a fake "verified" check mark */
            if ($row->email_is_verified == TRUE)
            {
                $pdf->SetFont('zapfdingbats', 'B', 26);
                $pdf->Ln(1);
                //$pdf->SetX(190);
                /* gonna use a little cheat here to get a little circle */
                $pdf->SetX($saveX);
                $pdf->SetTextColor(100, 100, 255); /* blue */
                $pdf->Cell(6, 6, "Y", 0, 0, "C"); /* the Y character is a starburst in this font */
                $pdf->Ln(1);
                $pdf->SetX($saveX + 1.25);
                /* now draw the check mark inside the circle */
                $pdf->SetFontSize(11);
                $pdf->SetTextColor(255, 255, 255); /* white */
                $pdf->SetFillColor(100, 100, 255); /* blue */
                $pdf->Cell(3.5, 3.5, "4", 0, 0, "C", TRUE); /* The C character is the check mark */
                /* move up slightly to display the informational text */
                $pdf->Ln(-2);
                $pdf->SetX($saveX + 5);
                $pdf->SetFont('Arial', '', 8);
                $pdf->SetTextColor(0); /* back to black */
                $pdf->Cell(25, 10, "(Email is verified)");
            }
            else
            {
                $pdf->SetFontSize(8);
                $pdf->SetX($saveX);
                $pdf->Cell(25, 10, "(Email is not verified)");
            }

            $pdf->SetTextColor(0); /* back to black */
            $pdf->Ln(20);
            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->Cell(196, 10, "Organization Info", 1, 1, "C", TRUE);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Organization ID: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(20, 10, $row->orgid, 0, 0);

            $pdf->SetFont('Arial','B',12);
            $pdf->SetX(115);
            $pdf->Write(10, "Status: ");
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, ($row->active_ind ? "Active" : "Inactive"));

            $pdf->Ln(10);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Name: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row->org_name, 0, 1);
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Abbreviation: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row->abbreviated_name, 0, 0);

            $pdf->SetFont('Arial','B',12);
            $pdf->SetX(115);
            $pdf->Write(10, "Date of Update: ");
            $pdf->SetFont('Arial', '', 12);
            /* because mysql returns the date automatically in the local time zone of the server */
            $pdf->Write(10, strftime("%Y-%m-%d %H:%M:%S %Z", $row->update_timestamp));

            $pdf->Ln(10);
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Web Site: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row->org_website, 0, 1, "L", FALSE, $row->org_website);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "URL for Donations: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row->money_url, 0, 1, "L", FALSE, $row->money_url);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Customer Notice: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, $row->customer_notice);
            $pdf->Ln(10);
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Public Contact Info: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, $row->customer_contact);
            $pdf->Ln(10);
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Private Contact Info: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, $row->admin_contact);
            /* complete the border around the details section */
            $pdf->Rect(10, 90, 196, $pdf->GetY() - 80);

//            $pdf->Ln(10);

            /* put the mission statement on another page because it might be quite long */
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->Cell(196, 10, "Mission Statement", 1, 1, "C", TRUE);

            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(8, $row->mission);
            $pdf->Ln(10);
            /* draw a border around the mission statement */
            $pdf->Rect(10,50,196, $pdf->GetY() - 50);
            
            $user_id = $row->user_id;
            
            $active_ind = $row->active_ind;
        }
        else
        {
			error_log("Failed to get the org record with that ID.");
			throw new Exception("An unknown error was encountered (18). Please attempt to reauthenticate.");
            exit();
        }
        $stmt->closeCursor();


    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in orgReport.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (20). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database SELECT query in orgReport.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (21). Please attempt to reauthenticate.");
		exit();
    }

}




function printZipcodes()
{
    global $dbh, $orgid, $pdf;

    try
    {   /* now get the zip codes from the database and put into an array */

        $stmt = $dbh->prepare("CALL selectZipcodesForOrganization(:orgid);");
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);

        $stmt->execute();

        /* put the zip codes on a new page */
        $pdf->AddPage();

        $pdf->SetLineWidth(0.5);
        $pdf->SetFont('Arial','B',13);
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(196, 10, "Selected Localities", 1, 1, "C", TRUE);
        

        $i = 0;
        $pdf->SetFont('Arial', '', 10);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {
            $i++; /* keep track of number of rows */

            $text = sprintf("%05u", $row["zip_code"]) . " - " . $row["city"] . ", " . $row["state"];
            $pdf->Cell(65, 8, $text, 1, 0);

            if ($i % 3 == 0)
            {
                $pdf->Ln(8);
            }
        }
        
        //$pdf->Ln(8);
        /* draw a border around the things */
        //$pdf->Rect(10,50,196, $pdf->GetY() - 50);

        $stmt->closeCursor();

        
    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in orgReport.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (20). Please attempt to reauthenticate.");
		exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database SELECT query in orgReport.php: " . $e->getMessage());
		/* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (21). Please attempt to reauthenticate.");
		exit();
    }

}

function selectQuestionsWithResponses()
{
    
    global $dbh, $orgid;

    try
    {   /* now get the questions from the database and put into an array */

        $stmt = $dbh->prepare("CALL selectQuestionsWithOrg(:orgid);");
        $stmt->bindValue(':orgid', $orgid, PDO::PARAM_INT);

        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
        {

            /* construct an array of pages, which is an array of questions, which is an array of choices */
            /* this is a little hacky, but it is a pretty convenient way to automatically sort these */
            /* rows into their respective pages, questions, etc. without doing a bunch of repetitive queries or complicated matching logic */

            $qu_aire[$row["page_num"]][$row["question_id"]][$row["choice_id"]] = $row;  

        }
    
        printQuestionsWithResponses($qu_aire);


        $stmt->closeCursor();

    }
    catch (PDOException $e)
    {
        error_log("Database error during SELECT query in orgReport.php: " . $e->getMessage());
        throw new Exception("An unknown error was encountered (20). Please attempt to reauthenticate.");
        exit();
    }
    catch(Exception $e)
    {
        error_log("Error during database SELECT query in orgReport.php: " . $e->getMessage());
        /* We most likely got here from the SQL error above, so just bubble up the exception */
        throw new Exception("An unknown error was encountered (21). Please attempt to reauthenticate.");
        exit();
    }
    
}

function printQuestionsWithResponses($qr)
{
    global $pdf;

    /* put the questions on a new page */
    $pdf->AddPage();

    foreach($qr as $page_num => $page)
    {
        /* before we do anything, count the number of questions and choices */
        /* so we can estimate how much space this will take */
        /* if it's too much, break the page up */
        $rcount = 0;

        foreach($page as $question_id => $question)
        {
            /* 1 row for the question */
            $rcount++; 
            /* the choices are displayed 2 per row, so count the # of rows required */
            $rcount+= ceil(count($question) / 2);
        }

	/* check for enough room, except if we are at the top of the page */

        if ((($pdf->GetY() + 10 + $rcount * 10) > 262) && $pdf->GetY() > 41)
        {
            $pdf->AddPage();
        }
        
        $group_text = "Question Group: " . current(current($page))["group_text"];

        $pdf->SetFont('Arial','B',13);
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(196, 10, $group_text, 1, 1, "C", TRUE);

        foreach($page as $question_id => $question)
        {

            /* check to see if there's room to display this question (along with its choices ) */
            if (($pdf->GetY() + 10 + (ceil(count($question) / 2) * 10)) > 262)
            {
                $pdf->AddPage();
            }

            $saveY = $pdf->GetY(); /* save the Y position for help with creating the border */
            $question_text = current($question)["org_question_text"];

            $pdf->SetFont('Arial','',11);
            $pdf->Cell(196, 10, $question_text);
            
            $i = 0; /* keep track of number of choices */
            foreach($question as $choice_id => $choice)
            {
                if (($i % 2) == 0)
                {
                    $pdf->Ln(10);
                }

                $pdf->SetFont('Arial','',11);
                $pdf->SetX(($i % 2) * 90 + 15); 
                $size = 11;

                if ($choice["selected"] == TRUE)
                {
                    $pdf->SetFont('Zapfdingbats','',12);
                    $pdf->Write(8, "4 "); /* The #4 is the check mark in the zapfdingbats font */

                    $pdf->SetFont('Arial','',$size);
                    $pdf->SetTextColor(0); /* Black */
                    /* make sure it can fit */
                    while ($pdf->GetStringWidth($choice["choice_text"]) > 89)
                    {
                        $pdf->SetFontSize(--$size);
                    }
                    $pdf->Cell(96, 8, $choice["choice_text"]);

                }
                else if ($choice["org_response_id"] <=  0) /* if the question/choice has never been answered for this org */                
                {
                    $pdf->SetTextColor(255, 0, 0); /* Red Text */
                    $pdf->SetFont('Zapfdingbats','',12);
                    $pdf->Write(8, "6 "); /* The #6 is the X mark in the zapfdingbats font */

                    $pdf->SetFont('Arial','B',11);
                    $pdf->SetTextColor(150); /* Gray Text */
                    /* make sure it can fit */
                    while ($pdf->GetStringWidth($choice["choice_text"]) > 89)
                    {
                        $pdf->SetFontSize(--$size);
                    }
                    $pdf->Cell(96, 8, $choice["choice_text"]);
    
                }
                else /* the value is present but it's FALSE so gray it out */
                {
                    $pdf->SetTextColor(150); /* Gray */
                    $pdf->SetFont('Zapfdingbats','',12);
                    $pdf->Write(8, "o "); /* The o is the unchecked box in the zapfdingbats font */

                    $pdf->SetFont('Arial','B',11);
                    /* make sure it can fit */
                    while ($pdf->GetStringWidth($choice["choice_text"]) > 89)
                    {
                        $pdf->SetFontSize(--$size);
                    }
                    $pdf->Cell(96, 8, $choice["choice_text"]);
                }

                $pdf->SetTextColor(0); /* Back in Black */

                $i++;

            }
            $pdf->Ln(8);
            $pdf->SetLineWidth(0.5);
            $pdf->Rect(10, $saveY, 196, $pdf->GetY() - $saveY); /* draw heavy border */
        }
        $pdf->Ln(8);

    }
    

}
