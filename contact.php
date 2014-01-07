<?php
//contact.php

// This script is part of the SimpleContactForm package by Jesse Smith of Mardesco
// Copyright 2013, licensed under the MIT License 
// and available for free download from https://github.com/mardesco/SimpleContactForm

// HEY!!  DO THIS FIRST!!

// don't forget to change this to your own e-mail address.
$recipient_email = 'you@yourdomain.tld';

// you can optionally auto-ban any messages that contain words in your blacklist array (profanity or common spam terms).
// WARNING: the test is greedy, so you might consider the use of spaces.
// otherwise, if you blacklist a certain common spam keyword, you could inadvertently block all messages containing the word "specialist"
$blacklist = array();// I leave this up to you. populate the array with words you wish to blacklist (enclosed in quotation marks.).



// that's it, shouldn't require additional configuration.  You're done!  Just upload and enjoy.

if(!function_exists('filter_list')){
		die('Your server does not support this contact form.  Please upgrade to PHP5.2 or higher.');
	}

//hopefully, using sessions won't generate an error.  errant spaces or returns, anyone?
session_start();
?>
<!doctype html>
<html>
<head>
	<title>Contact Me</title>
	<link rel="stylesheet" type="text/css" href="css/SimpleContactForm.css" />
</head>
<body>

	<?php
	if(!isset($_POST['send_message'])){
	
		// a simple way to determine if the client's browser accepts cookies.
		if(!isset($_SESSION['secret_key'])){
			$_SESSION['secret_key'] = true;
		}
	?>

	<h1>Contact me</h1>

	<p>Please fill out the following form to contact me online.  All fields are required.</p>
	
    <div id="contactFormContainer">
        <form name="contact" id="contact_form" action="" method="post">
            <p>
                Your name:<br />
                <input type="text" required name="person_name" />
            </p>
            <p>
                Your company name:<br />
                <input type="text" required name="company" />
            </p>
            <p>
                Your e-mail address:<br />
                <input type="email" required name="email" />
            </p>
            <p>
                Your phone number:<br />
                <input type="tel" required name="phone" />
            </p>
            <p>
                Tell us about your project:<br />
                <textarea name="message" required ></textarea>
            </p>
            <p><input class="btn btn-primary btn-lg btn-fancy contact-btn" type="submit" name="send_message" value="Send Message" /></p>
        </form>
    </div>	
	
	<?php
		}else{
	// else the form has been submitted.
	// parse the inputs before deciding on a response.  
		
	$passed = true;// optimistic initial condition
	$errors = array();
	
	function submissionFailed($errors){
		session_destroy();
		echo "<h1>There was a problem.</h1><p>The following errors occurred:</p>";
		if($errors){
			echo "<ul>";
			foreach($errors as $e){
				echo "<li>$e</li>";
				}
			echo "</ul>";	
			echo "<p>Your message was not sent.</p>";
			}	
		echo "</body></html>";
		exit();
		}

	
	
	//check for the existence of the secret key.  you could also get fancy and send it through the form to see if they match.
	if(!isset($_SESSION['secret_key'])){
		$passed = false;
		$errors[] = "Your browser must accept cookies to use this contact form.";
		}
		
		
	//check to see how they got here.
	//NOTE THAT THIS CAN BE SPOOFED.  It will keep the script kiddies busy though.
	// note also that some browsers may not set the referrer header.
	// which means that this is a restrictive setting, not a friendly setting.
	// but legitimate users are unlikely to have a problem with it, whereas it may stop certain automated bots.
	if(!isset($_SERVER['HTTP_REFERER']) || $_SERVER['HTTP_REFERER']=='' || stristr($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false){
		$passed = false;
		$errors[] = "A required field was missing or could not be read.  Your browser settings may be preventing you from using this form.";
		}	
		
	
	//see if all the variables were submitted.
	
	$requiredFields = array('person_name', 'company', 'email', 'phone', 'message');
	
	foreach($requiredFields as $field){
		if(!isset($_POST[$field]) || empty($_POST[$field])){
			$passed = false;
			$errors[] = "Please fill out all the fields.";
		}
	}
	
	// no need to parse the inputs if the submission has already failed.
	if(!$passed){
		submissionFailed($errors);
		}
	
	//first, compile the variables individually.  
	$person_name = filter_input(INPUT_POST, 'person_name', FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );
	
	if(strlen($person_name) < 6){
		$errors[] = 'Please provide your full name.';
	}
	
	$company = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );
	
	if(strlen($company) < 6){
		$errors[] = 'You did not provide a recognizable company name.';
	}
	

	//check to be sure the e-mail and phone number at least have a valid format.
	$email_input = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );//);
	$email = filter_var($email_input, FILTER_SANITIZE_EMAIL);
	
	
	if(!$email || strpos($email, '.') === false){
		$passed = false;
		$errors[] = "Please enter a valid e-mail address.";
		}

	$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);// FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_LOW );

	$pattern = "/^(1[-\.\s])?(\([2-9]\d{2}\)|[2-9]\d{2})[-\.\s]?\d{3}[-\.\s]?\d{4}$/";//validates a phone number
	$invalid_pattern = '/^\(?(\d)\1{2}\)?[-\.\s]?(\d)\1{2}[-\.\s]?(\d)\1{3}$/';	// identifies some commonly used fake numbers.

	// The manual says to use the boolean identical test with preg_match.  The manual is wrong! 
	// This function returns 0 if the match is not found, and false on error.  
	// So the boolean false is only testing for an error, and will miss the fact that there was not a match.
	// in other words, DON'T use the === operator.
	
	
	// Phone number fails if it does NOT match the pattern, or if it DOES match the invalid_pattern.
	if(!preg_match($pattern, $phone) || preg_match($invalid_pattern, $phone)){
		$passed = false;
		$errors[] = "Please enter a valid phone number.";
		}
	$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );

	//check again.
	if(!$passed){
		submissionFailed($errors);
		}
	

	//next, concatenate all variables into a message and prepare to send it.
	$to = $recipient_email;
	$from = "From: $email";
	$subject = $person_name . " sent you a message from " . $_SERVER['SERVER_NAME'];
	$body = "
	Name: $person_name
	Company: $company
	E-mail: $email
	Phone: $phone
	
	Message:
	$message
	
	Sender's IP address: " . $_SERVER['REMOTE_ADDR'];
	
	
	//test the final message for blacklisted terms, and reject inappropriate submissions.	
	foreach($blacklist as $word){
		if(stristr($body, $word) !== false){
			$errors[] = "Inappropriate message content detected.";
			submissionFailed($errors);
			}
		}
	
	//calling session_destroy does not necessarily prevent the session from being re-used.
	// (might depend on the users's browser)
	session_destroy();
	
	//send the message
	if(@mail($to, $subject, $body, $from)){
		$response = 'Thank you for your message.  I look forward to reading it.  Have a great day.';
		}else{
			$response = 'Unfortunately, there was an internal system error, and the message could not be sent.  I apologize for the inconvenience.  Please try again later or contact me through another channel.  Thank you!';
			}	
	// output the response.
	printf('<p>%s</p>', $response);	
	
	
	}// end form submission data check
	

	?>	
	
	<?php
	// and of course, the JavaScript goes in the footer.
	if(!isset($_POST['send_message'])){
		// include the JavaScript
		?>
		<script type="text/javascript" src="js/SimpleContactForm.js" ></script>
		<?php
	}
	?>
</body>
</html>