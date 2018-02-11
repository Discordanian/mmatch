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
    protected $m_footnote = FALSE;

    function setOrganizationName($p_orgname)
    {
        $this->m_org_name = $p_orgname;
    }

    function setPrintDate($p_print_date)
    {
        $this->m_print_date = "Generated on: " . $p_print_date->format("Y-m-d H:i:s e");
    }

    function setWarningFootnote()
    {
        $this->m_footnote = TRUE;
    }

    // Page header
    function Header()
    {

        // Logo
        $this->Image('img/mmlogo.jpg',10,6,30);
        // Arial bold 15
        $this->SetTextColor(0); /* Black */
        $this->SetLineWidth(0.5);
        $this->SetFont('Arial','B',15);
        // Move to the right, far enough that the box will be centered
        $this->Cell(33);
        // Title
        $this->Cell(150,10,'Movement Match - Organization Details',"TLR",0,'C');
        // Line break
        $this->Ln(10);
        $this->Cell(33);

        /* figure out what size to make the text and still fit */
        $size = 15;
        do
        {
            $size--;
            $this->SetFontSize($size);
        } while ($this->GetStringWidth($this->m_org_name) > 145);

        $this->Cell(150,10,$this->m_org_name,"BLR",0,'C');
        $this->Ln(20);

    }

    // Page footer
    function Footer()
    {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        $this->SetX(0); /* 1.5 cm from right of page */
        $this->Cell(0, 5, $this->m_print_date, 0, 0, 'C');
        // Page number
        $this->SetX(190);
        $this->Cell(20,5,'Page '.$this->PageNo().'/{nb}',0,0,'R');

        /* Show the footnote if necessary */
        if ($this->m_footnote == TRUE)
        {
            $this->SetTextColor(255, 0, 0);
            $this->SetFont('Zapfdingbats', '', 8);
            $this->SetX(9);
            $this->Write(5, "6");
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(0); /*back to black text */
            $this->MultiCell(45, 5, " Indicates no response has been selected for this choice");
            $this->m_footnote = FALSE;
        }
    }
}


my_session_start();

try 
{

    initializeDb();

    // Instanciation of inherited class
    $pdf = new PDF("P", "mm", "Letter");

    $pdf->setPrintDate(new DateTime());

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

        $row = $stmt->fetch(PDO::FETCH_ASSOC);


        if (isset($row))
        {
            global $org_name;
            global $person_name;

            global $email_is_verified;
            global $email;

            global $org_website;
            global $money_url;
            global $mission;

            global $abbreviated_name, $customer_notice, $customer_contact, $admin_contact, $active_ind, $user_id;
            $pdf->setOrganizationName($row["org_name"]);
            $pdf->AddPage();

            $pdf->SetLineWidth(0.5);
            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->Cell(196, 10, "Representative Info", 0, 1, "C", TRUE);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Name: ", "TL", 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row["person_name"], "TR", 1);
            
            $email_is_verified = $row["email_is_verified"];

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Email Address: ", "BL", 0);

            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row["email"], "BR", 1);
            

            $pdf->Ln(10);
            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->Cell(196, 10, "Organization Info", 0, 1, "C", TRUE);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Name: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row["org_name"], 0, 1);
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Abbreviation: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row["abbreviated_name"], 0, 1);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Web Site: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row["org_website"], 0, 1, "L", FALSE, $row["org_website"]);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "URL for Donations: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, $row["money_url"], 0, 1, "L", FALSE, $row["money_url"]);

            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Customer Notice: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, $row["customer_notice"]);
            $pdf->Ln(10);
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Public Contact Info: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, $row["customer_contact"]);
            $pdf->Ln(10);
            
            $pdf->SetFont('Arial','B',12);
            $pdf->Cell(45, 10, "Private Contact Info: ", 0, 0);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(10, $row["admin_contact"]);
            /* complete the border around the details section */
            $pdf->Rect(10, 90, 196, $pdf->GetY() - 80);

//            $pdf->Ln(10);

            /* put the mission statement on another page because it might be quite long */
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',13);
            $pdf->SetFillColor(200, 220, 255);
            $pdf->Cell(196, 10, "Mission Statement", 0, 1, "C", TRUE);

            $pdf->SetFont('Arial', '', 12);
            $pdf->Write(8, $row["mission"]);
            $pdf->Ln(10);
            /* draw a border around the mission statement */
            $pdf->Rect(10,50,196, $pdf->GetY() - 50);
            
            $user_id = $row["user_id"];
            
            $active_ind = $row["active_ind"];
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
        $pdf->Cell(196, 10, "Selected Localities", 0, 1, "C", TRUE);
        

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
            /* the choices are displayed 3 per row, so count the # of rows required */
            $rcount+= ceil(count($question) / 3);
        }

        if (($pdf->GetY() + 10 + $rcount * 8) > 264)
        {
            $pdf->AddPage();
        }
        
        $group_text = "Question Group: " . current(current($page))["group_text"];

        $pdf->SetFont('Arial','B',13);
        $pdf->SetFillColor(200, 220, 255);
        $pdf->Cell(196, 10, $group_text, 0, 1, "C", TRUE);

        foreach($page as $question_id => $question)
        {
            $saveY = $pdf->GetY(); /* save the Y position for help with creating the border */
            $question_text = current($question)["org_question_text"];

            $pdf->SetFont('Arial','',11);
            $pdf->Cell(196, 10, $question_text);
            
            $i = 0; /* keep track of number of choices */
            foreach($question as $choice_id => $choice)
            {
                if (($i % 3) == 0)
                {
                    $pdf->Ln(8);
                }

                $pdf->SetFont('Arial','',11);
                $pdf->SetX(($i % 3) * 60 + 20); 

                if ($choice["selected"] == TRUE)
                {
                    $pdf->SetTextColor(0); /* Black */
                    $pdf->Write(8, $choice["choice_text"]);
                    $pdf->SetFont('Zapfdingbats','',12);
                    $pdf->Write(8, " 4"); /* The #4 is the check mark in the zapfdingbats font */
                }
                else if ($choice["org_response_id"] <=  0) /* if the question/choice has never been answered for this org */                
                {
                    $pdf->SetFont('Arial','B',11);
                    $pdf->SetTextColor(150); /* Gray Text */
                    //$pdf->SetFillColor(255, 120, 120); /* red/pink background */
                    $pdf->Write(8, $choice["choice_text"]);
                    $pdf->SetTextColor(255, 0, 0); /* Red Text */
                    $pdf->SetFont('Zapfdingbats','',12);
                    $pdf->Write(8, " 6"); /* The #6 is the X mark in the zapfdingbats font */
                    $pdf->setWarningFootnote();
    
                }
                else /* the value is present but it's FALSE so gray it out */
                {
                    $pdf->SetTextColor(150); /* Gray */
                    $pdf->Write(8, $choice["choice_text"]);
                }

                $pdf->SetTextColor(0); /* Black */

                $i++;

            }
            $pdf->Ln(8);
            $pdf->SetLineWidth(0.5);
            $pdf->Rect(10, $saveY, 196, $pdf->GetY() - $saveY); /* draw heavy border */
        }
        $pdf->Ln(8);

    }
    

}