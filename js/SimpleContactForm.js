//SimpleContactForm.js
/*
SimpleContactForm.js is copyright 2013 by Jesse Smith 
The script is made available under the MIT license, which allows you do do whatever you want with it, so long as the authorship attribution is retained.
You can download the complete SimpleContactForm package from the GitHub repository at https://github.com/mardesco/SimpleContactForm
or get ahold of me through my personal website at http://www.jesse-smith.net
*/

	
	// a quickie throwaway version of addEvent, not the fat six-pager.
	// reference:  http://www.quirksmode.org/js/events_advanced.html
	function addEvent(elem, event, func){
		if(elem.attachEvent){// Microsoft
			elem.attachEvent(event, func);
		}else{// real browsers
			elem.addEventListener(event, func, false);
		}
	}
	
	function removeEvent(elem, event, func){// ditto
		if(elem.detachEvent){//Microsoft
			elem.detachEvent(event, func);
		}else{// real browsers
			elem.removeEventListener(event, func, false);
		}
	}
	
	//	Christian Heilmann recommends using JSON or XML to store validation rules that can be shared by the front-end and back-end, to simplify maintenance.
	//	Well.  Maybe in a future version.  For now:
	function appendErrorMessage(element, message){
		var container = document.createElement('span');
		container.className = 'errorMessage';
		var txt = document.createTextNode(message);
		container.appendChild(txt);
		element.parentNode.insertBefore(container, element);
		element.focus();
		// can't call removeErrorMesage on blur, because the blur event is triggered by the script if there's more than one error!
		// BUT, I'm seeing inconsistent results with the onchange event in FireFox v24.0
		element.onchange = function(){removeErrorMessage(element);}
		return;
	}
	
	// you could make this longer, but is it necessary?
	function removeErrorMessage(elem){
		if(elem.previousSibling.className == 'errorMessage'){
			elem.parentNode.removeChild(elem.previousSibling);
			removeEvent(elem, 'change', removeErrorMessage);
			}
	}
	
	function validationFailed(e){
		// another tip o' the hat to @codepo8 and his book that I bought way back in 2006!
		if(window.event && window.event.cancelBubble && window.event.returnValue){// Microsoft
		window.event.cancelBubble = true;
			window.event.returnValue = false;
		}else{ // real browsers
			e.stopPropagation();
			e.preventDefault();
		}		
	}

try{
	
	var form = document.getElementById('contact_form');
	var name, company, email, phone, regex, message;
	var errors = false;// optimistic initial condition
	form.onsubmit = function(e){
	
		name = form.person_name.value;
		company = form.company.value;
		email = form.email.value;
		phone = form.phone.value;
		message = form.message.value;
		
		// Let's go through the fields backwards.
		// That way, if there is more than one invalid input, the topmost will be left with focus at script termination.
		if(message.length < 20){
			errors = true;
			appendErrorMessage(form.message, "Please tell us more about your project.");
		}
		regex = /^(1[-\.\s])?(\([2-9]\d{2}\)|[2-9]\d{2})[-\.\s]?\d{3}[-\.\s]?\d{4}$/;//validates a US phone number
		if(!regex.test(phone)){
			errors = true;
			appendErrorMessage(form.phone, "Invalid phone number.");
		}
		// this lazy email address check does not verify the TLD
		if(email.indexOf('@') == -1){
			errors = true;
			appendErrorMessage(form.email, 'You must provide a valid e-mail address.');
		}		
		if(company.length < 6){
			errors = true;
			appendErrorMessage(form.company, 'Please provide your full company name.');
		}		
		if(name.length < 6 ){
			errors = true;
			appendErrorMessage(form.person_name, 'Please provide your full name.');
		}

		if(errors){
			validationFailed(e);
			return false;
		}
		// but no else clause!
		// because any return value at all will prevent form submission: yes, even return true prevents the submission of the form.
	}
}catch(exception){
	alert("Could not complete your request.  Please call us instead.");
}	