<?

class SendMail {

	var $SenderName;
	var $SenderEmail;
	var $To;
	var $Cc;
	var $Bcc;
	var $Recipient = array();
	var $Attachment = array();
	var $Subject;
	var $Body; // html
	var $BodyAlt; // txt
	var $Error;
	var $Headers;
	var $TextOnly;


	function get_mime($filename){ 
		preg_match("/\.(.*?)$/", $filename, $m);    # Get File extension for a better match 
		switch(strtolower($m[1])){ 
			case "js": return "application/javascript"; 
			case "json": return "application/json"; 
			case "jpg": case "jpeg": case "jpe": return "image/jpg"; 
			case "png": case "gif": case "bmp": return "image/".strtolower($m[1]); 
			case "css": return "text/css"; 
			case "xml": return "application/xml"; 
			case "html": case "htm": case "php": return "text/html"; 
			default: 
				if(function_exists("mime_content_type")){ # if mime_content_type exists use it. 
				   $m = mime_content_type($filename); 
				}else if(function_exists("")){    # if Pecl installed use it 
				   $finfo = finfo_open(FILEINFO_MIME); 
				   $m = finfo_file($finfo, $filename); 
				   finfo_close($finfo); 
				}else{    # if nothing left try shell 
				   if(strstr($_SERVER[HTTP_USER_AGENT], "Windows")){ # Nothing to do on windows 
					   return ""; # Blank mime display most files correctly especially images. 
				   } 
				   if(strstr($_SERVER[HTTP_USER_AGENT], "Macintosh")){ # Correct output on macs 
					   $m = trim(exec('file -b --mime '.escapeshellarg($filename))); 
				   }else{    # Regular unix systems 
					   $m = trim(exec('file -bi '.escapeshellarg($filename))); 
				   } 
				} 
				$m = split(";", $m); 
				return trim($m[0]); 
		} 
	} 

	/*
	* type:
	* 1: normal
	* 2: cc
	* 3: bcc
	*/
	function AddRecipients($Name, $Email, $Type = 1){
		$Counter = count($this->Recipient);
		$this->Recipient[$Counter]['Name']  = str_replace(",","",$Name);
		$this->Recipient[$Counter]['Email'] = str_replace(",","",$Email);
		$this->Recipient[$Counter]['Type']  = $Type;
	}

	function AddAttachments($File){
		$Counter = count($this->Attachment);
		$this->Attachment[$Counter]  = $File;
	}

	function Send($AttachBlob = false){

		// gather & format recipients
		$To  = "";
		$CC  = "";
		$BCC = "";

		foreach($this->Recipient As $Recipient){
			if($Recipient['Type'] == 1){
				$To  .= $Recipient['Name'] . " <" . $Recipient['Email'] . ">,";
			} elseif($Recipient['Type'] == 2){
				$CC  .= $Recipient['Name'] . " <" . $Recipient['Email'] . ">,";
			} elseif($Recipient['Type'] == 3){
				$BCC .= $Recipient['Name'] . " <" . $Recipient['Email'] . ">,";
			}
		}

		$headers  = "From: " . $this->SenderName . " <" . $this->SenderEmail . ">\r\n";
		$headers .= "Reply-To: " . $this->SenderName . " <" . $this->SenderEmail . ">\r\n";

		if(trim($To) <> "")  $To = trim($To, ",") . "\r\n";
		if(trim($CC) <> "")  $headers = "Cc: " . trim($CC, ",") . "\r\n";
		if(trim($BCC) <> "") $headers = "Bcc: " . trim($BCC, ",") . "\r\n";
		$this->To = $To;
		$this->Cc = $CC;
		$this->Bcc = $BCC;

		$semi_rand = md5(time()); 
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

		// handle attachments
		if(count($this->Attachment) > 0){

			// headers for attachment 
			$headers .= "MIME-Version: 1.0\n" . 
						"Content-Type: multipart/mixed;\n" . 
						"	boundary=\"{$mime_boundary}\""; 

			 // Add a multipart boundary above the plain message   
			 if($this->TextOnly){
				 $msg = "This is a multi-part message in MIME format.\n\n" .     
							"--{$mime_boundary}\n" .     
							"Content-Type: text/plain; charset=\"iso-8859-1\"\n" .     
							"Content-Transfer-Encoding: 7bit\n\n" .     
							$this->Body . "\n\n";
			 } else {
				 $msg = "This is a multi-part message in MIME format.\n\n" .     
							"--{$mime_boundary}\n" .     
							"Content-Type: text/html; charset=\"iso-8859-1\"\n" .     
							"Content-Transfer-Encoding: 7bit\n\n" .     
							$this->Body . "\n\n";
			 }

			if($AttachBlob){

				$result = mysql_query("SELECT Name, Content, Size, Type FROM EmailAttachments WHERE EmailID = " . $this->Attachment[0] );
				if(mysql_num_rows($result) > 0){
					while($row = mysql_fetch_array($result)){

						$data = chunk_split(base64_encode($row['Content']));
						$FileType = $row['Type'];

						$msg .= "--{$mime_boundary}\n" .  
						"Content-Type: {$FileType};\n" .     
						" name=\"" . $row['Name'] . "\"\n" .     
						"Content-Disposition: attachment;\n" .     
						" filename=\"" . $row['Name'] . "\"\n" .     
						"Content-Transfer-Encoding: base64\n\n" .     
						$data . "\n\n";
					}
				}

			} else {
				// attachments code starts
				foreach($this->Attachment As $AttFile)
				{
					$file = fopen($AttFile,"rb");
					$data = fread($file,filesize($AttFile));
					fclose($file);
					$data = chunk_split(base64_encode($data));

					$FileType = mime_content_type($AttFile);

					$msg .= "--{$mime_boundary}\n" .  
					"Content-Type: {$FileType};\n" .     
					" name=\"" . basename($AttFile) . "\"\n" .     
					"Content-Disposition: attachment;\n" .     
					" filename=\"" . $this->FormatAttFileName(basename($AttFile)) . "\"\n" .     
					"Content-Transfer-Encoding: base64\n\n" .     
					$data . "\n\n";

				}
			}

			$msg .= "--{$mime_boundary}--\n";

			//$this->Body = $msg;

		} else {
			if($this->TextOnly){
				$headers .= "Content-Type: text/plain; charset=ISO-8859-1 ";
			} else {
				$headers .= "Content-Type: text/html; charset=ISO-8859-1 ";
			}
			$headers .= "MIME-Version: 1.0 ";
			$msg = $this->Body;

		}

		//if($AttachBlob) echo $headers; exit; // debug

		$this->Headers = $headers;
		$response = mail($To, $this->Subject, $msg, $headers);

		//Common::mymail($To, $this->Subject, $msg, $headers);

		//echo $this->SMTP();// exit;
		if($response){
			return true;
		} else {
			$this->Error = $response;
			return false;
		}

	}

	function FormatAttFileName($string){
		if($string[0] == 'W'){
			$FileParse = explode("-", $string);
			return $FileParse[0] . ".jpg";
		} else {
			return $string;
		}
	}

	// SMTP - Pear
	function SMTP(){
		require_once("Mail.php");
		require_once('Mail/mime.php');

		// add recipients
		$recipients = trim(trim($this->To) . "," . trim($this->Bcc), ",");

		$headers["From"]     = $this->SenderName . " <" . $this->SenderEmail . ">";
		$headers["Reply-To"] = $this->SenderName . " <" . $this->SenderEmail . ">";
		//$headers["To"]       = trim($this->To);//"angstsix@gmail.com";
		$headers["Subject"]  = $this->Subject;
		if(trim($this->Cc) <> "") $headers["Cc"] = trim($this->Cc);
		if(trim($this->Bcc) <> "") $headers["Bcc"] = trim($this->Bcc);

		$params["host"] = SMTP_HOST;
		$params["port"] = SMTP_PORT;
		$params["auth"] = true;
		$params["username"] = SMTP_USER;
		$params["password"] = SMTP_PASS;

        // Creating the Mime message
        $mime = new Mail_mime("\n");
 
        // Setting the body of the email
        if(trim($this->Body) <> "")    $mime->setHTMLBody(trim($this->Body));
        if(trim($this->BodyAlt) <> "") $mime->setTXTBody(trim($this->BodyAlt));

		// add attachments
		foreach($this->Attachment As $AttFile)
		{
			$file = $AttFile;
			$file_name = basename($AttFile);
			$content_type = get_mime($AttFile);
			$mime->addAttachment($file, $content_type, $file_name, 1);
		}

		$body = $mime->get();
		$headers = $mime->headers($headers);

		// Create the mail object using the Mail::factory method 
		$mail_object =& Mail::factory("smtp", $params); 

		return $mail_object->send($recipients, $headers, $body); 
	}

}
?>