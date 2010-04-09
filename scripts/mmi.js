var form_color = "#fffd5b";
var grant_balance = 0.0;
	
/**
 * Checks a form to ensure all of the required fields are filled out.
 *
 * @return	bool	true if all fields are filled out, false with error otherwise
*/
function check_form() {
	var elements = document.getElementsByTagName("INPUT");
	var selects = document.getElementsByTagName("SELECT");
	var texts = document.getElementsByTagName("TEXTAREA");
	
	var ret_value = true;
		
	for ( i=0; i<elements.length; i++ ) {
		if ( elements[i].id == "required" ) {
			elements[i].style.backgroundColor = "#ffffff";
		}
		
		if ( elements[i].id == "required" && elements[i].value == "" ) {
			elements[i].style.backgroundColor = form_color;
			ret_value = false;
		}
	}
	
	for ( i=0; i<texts.length; i++ ) {
		if ( texts[i].id == "required" ) {
			texts[i].style.backgroundColor = "#ffffff";
		}
		
		if ( texts[i].id == "required" && texts[i].value == "" ) {
			texts[i].style.backgroundColor = form_color;
			ret_value = false;
		}
	}
	
	for ( i=0; i<selects.length; i++ ) {
		if ( selects[i].id == "required" ) {
			selects[i].style.backgroundColor = "#ffffff";
		}
		
		if ( selects[i].id == "required" && selects[i].value == "" ) {
			selects[i].style.backgroundColor = form_color;
			ret_value = false;
		}
	}
	
	// Do the date thing
	/*var start_month = document.getElementById('start_month').value;
	var start_day = document.getElementById('start_day').value;
	var start_year  = document.getElementById('start_year').value;
	
	var end_month = document.getElementById('end_month').value;
	var end_day = document.getElementById('end_day').value;
	var end_year  = document.getElementById('end_year').value;

	if ( start_year >= end_year ) {
		if ( (start_month > end_month) || ( start_month == end_month && start_day >= end_day) || ( start_month < end_month ) ) {
			alert("The start date must be less than the end date.");
		}
	}*/
	
	return ret_value;
}

/**
 * Checks two password fields to make sure that they equal each other 
 * and are longer than six characters
 *
 * @param	object	the first password field
 * @param	object	the second password field
 *
 * @return	bool	true if passwords match rules, false with error otherwise
*/
function check_password(id1, id2) {
	var pw1 = document.getElementById(id1);
	var pw2 = document.getElementById(id2);
	
	var ret_value = true;
	
	if ( pw1.value != pw2.value ) {
		pw1.style.backgroundColor = form_color;
		pw2.style.backgroundColor = form_color;
		
		alert("Oops! The two password fields do not match.");
		
		ret_value = false;
	} else if ( (pw1.value != "" && pw2.value != "" ) && (pw1.value == pw2.value) ) {
		if ( pw1.value.length < 6 ) {
			pw1.style.backgroundColor = form_color;
			pw2.style.backgroundColor = form_color;

			alert("Oops! Your new password must be at least 6 characters in length.");

			ret_value = false;
		}
	}
	return ret_value;
}

/**
 * Sets a form action to a form with multiple possible actions.
 *
 * @param	string	the action to set
 *
 * @return	bool	returns true always
*/
function set_action(new_action) {
	var field = document.getElementById("action");
	field.value = new_action;
	
	return false;
}

/**
 * Checks two password fields when the user is registering to make
 * sure the passwords match the rules.
 *
 * @param	object	the first password field
 * @param	object	the second password field
 *
 * @return	bool	true if passwords match rules, false with error otherwise
*/
function check_register_password(id1, id2) {
	var pw1 = document.getElementById(id1);
	var pw2 = document.getElementById(id2);
	
	var ret_value = true;
	
	if ( pw1.value == "" && pw2.value == "" ) {
		ret_value = false;
		
		pw1.style.backgroundColor = form_color;
		pw2.style.backgroundColor = form_color;
		
		alert("Oops! In order to register, you must have a password.");
	} else if ( (pw1.value != "" && pw2.value != "" ) && (pw1.value == pw2.value) ) {
		if ( pw1.value.length < 6 ) {
			pw1.style.backgroundColor = form_color;
			pw2.style.backgroundColor = form_color;
		
			alert("Oops! Your new password must be at least 6 characters in length.");
			
			ret_value = false;
		}
	} else if ( (pw1.value != "" && pw2.value != "" ) && (pw1.value != pw2.value) ) {
		pw1.style.backgroundColor = form_color;
		pw2.style.backgroundColor = form_color;
		
		alert("Oops! Your two passwords do not match. Please make them match before continuing.");
		
		ret_value = false;
	}
	
	return ret_value;
}

/**
 * Sets a value to a form field.
 *
 * @param	object	the field to set the value to
 * @param	string	the value of that field
 *
 * @return	bool	always returns true
*/
function set_auth(id, val) {
	var input = document.getElementById(id);
	
	input.value = val;
	
	return true;
}

/**
 * Confirms that you want to delete an event.
 *
 * @return	bool	true if the user clicks Yes, false otherwise
*/
function confirm_delete() {
	return confirm("Are you sure you want to delete this item? This can not be undone and will delete all Event Information, Evaluation Data, Registration Data and anything else associated with this event.");
}

/**
 * Checks to ensure that a grant can be submitted.
 *
 * @return	bool	true if the user clicks Yes, false otherwise
*/
function check_grant_form() {
	if ( check_form() ) {
		var	initial_amount = parseFloat(document.getElementById("initial-grant-amount").value);
		var	grant_balance = parseFloat(document.getElementById("grant-balance").value);
		var	grant_amount = parseFloat(document.getElementById("grant-amount").value);
	
		if ( (grant_balance + grant_amount) < initial_amount) {
			alert("The new grant amount can not be less than the current balance.");
			return false;
		}
		
		return true;
	} else {
		return false;
	}
}

/* 
	---- Program Tracking functions ----
*/

/**
 * If a No is clicked for a radio button, then set a corresponding
 * field to value 0.
 *
 * @param	object	the radio button
 * @param	object	the field to have a value 0
 *
 * @return	bool	always returns true
*/
function add_zero(field1, field2) {
	var input1 = document.getElementById(field1);
	var input2 = document.getElementById(field2);
	
	if ( input1.value == 0 || input1.value == "" ) {
		input2.value = 0;
	}
	
	return true;
}

/**
 * Sets a field to no value.
 *
 * @param	object	the field to have a null value
*/
function clear_field(field) {
	document.getElementById(field).value = "";

	return true;
}

/**
 * Ensures that all of the fields not filled out by the RD have a 
 * value of 0 set to them.
 *
 * @return	bool	always returns true
*/
function check_program_tracking_form() {
	//var fields = new Array();
	//fields = document.getElementsByTag('INPUT');
	
	//for ( i=0; i<fields.length; i++ ) {
	//	if ( field.type == 'text' && field.value == "" ) {
	//		field.value = 0;
	//	}
	//}
	
	// Ensure the amount of the grant used
	// is not greater than the balance of the grant
	//var grantid = document.getElementById('grant-list');
	//var grant_amount = document.getElementById('grant-amount');
	
	
	
	return true;
}

/**
 * Ensure that the sum for each group of questions does not 
 * exceed the total attendance.
 *
*/
function check_form_attendance() {
	var fields = new Array();
	fields = document.getElementsByTagName('INPUT');
}

/**
 * Copies data from the first part of the request a program form to the second part.
 * Sorry for the hard coded data, it was meant to be.
 *
 * @return	bool	always returns true
*/
function copy_form_data() {
	var fields = new Array();
	fields = document.getElementsByTagName('INPUT');
	
	fields[11].value = fields[1].value;
	fields[12].value = fields[4].value;
	fields[13].value = fields[5].value;
	fields[14].value = fields[6].value;
	fields[15].value = fields[7].value;
	fields[16].value = fields[8].value;
	fields[17].value = fields[2].value;
	fields[18].value = fields[8].value;
	
	return true;
}

function toggle_event_title(new_title_id) {
	var selects = document.getElementsByTagName('SELECT');
	
	for ( i=0; i<selects.length; i++ ) {
		if ( selects[i].name == "event_program_id" ) {
			if ( selects[i].value == new_title_id ) {
				selects[i].value = "";
			} else {
				selects[i].value = new_title_id;
			}
		}
	}
}

/**
 * Toggles all of the checkboxes or not.
 *
 * @return	bool	always returns true
*/
function toggle_chkbx(chkbx_id) {
	chkbxs = document.getElementsByTagName('INPUT');
	
	for ( i=0; i<chkbxs.length; i++ ) {
		if ( chkbxs[i].id == chkbx_id && chkbxs[i].type == "checkbox" ) {
			if ( chkbxs[i].checked == true ) {
				chkbxs[i].checked = false;
			} else {
				chkbxs[i].checked = "checked";
			}
		}
	}
	
	return true;
}

/**
 * Disables a form element.
 *
 * @return	bool	always returns true
*/
function toggle_disable(btn_id) {
	btn = document.getElementById(btn_id);
	
	if ( btn.disabled == true ) {
		btn.disabled = false;
		btn.style.backgroundColor = "#ffffff";
	} else {
		btn.disabled = true;
		btn.style.backgroundColor = "#D4D0C8";
		btn.value = 0;
	}
	
	return true;
}

function fill_zero(status_id, zero_id) {
	var z = document.getElementById(zero_id);
	var s = document.getElementById(status_id);
	
	if (s.value == 0 ) {
		z.value = "0";
		z.disabled = true;
		z.style.backgroundColor = "#d4d0c8";
	} else {
		z.value = "";
		z.disabled = false;
		z.style.backgroundColor = "#ffffff";
	}
}

/**
 * Ensures that a message can be sent.
 *
 * @return	bool	true if the form can be sent, false otherwise
*/
function check_message_form() {
	if ( check_form() ) {
		var vol_list = document.getElementById('volunteer_list');
		var reg_list = document.getElementById('regions');
		var rds = document.getElementById('regional_directors');
		var vols = document.getElementById('volunteers');
		var admins = document.getElementById('administrators');
		
		if ( vol_list.value == "" && reg_list.value == "" && vols.checked == "" && rds.checked == "" && admins.value == "" ) {
			alert('You must choose someone to send the message to.');
			return false;
		}
		
		return true;
	} else {
		return false;
	}
}


/*
	----- The colored rounded gray box -----
*/

var mouse_x = 0;
var mouse_y = 0;

document.onmousemove = capture_mouse_position;

var g_div = null;

function show_div(div_id) {
	var div = document.getElementById(div_id);
	
	if ( g_div != null ) {
		if ( g_div.id != div_id ) {
			hide_div();
		}
	}
	
	div.style.display = 'inline';
	div.style.left = mouse_x + 25;
	div.style.top = mouse_y - 70;
		
	g_div = div;
}

function hide_div() {
	if ( g_div != null ) {
		g_div.style.display = 'none';
		g_div = null;
	}
}

function capture_mouse_position(e) {
	if ( !e ) { e = window.event; }
	
	if ( e.pageX || e.pageY ) {
		mouse_x = e.pageX;
		mouse_y = e.pageY;
	} else if ( e.clientX || e.clientY ) {
		mouse_x = e.clientX + document.body.scrollLeft;
		mouse_y = e.clientY + document.body.scrollTop;
	}
}


function addTag(formid, tag) {
	var formName = document.getElementById(formid);

	if ( tag == 'img' ) {
		var imgsrc = prompt("Enter the location of the image:", '');
	}
	
	openTag = '[' + tag + ']';
	closeTag = '[/' + tag + ']';

	if ( tag == 'url' ) {
		var urlsrc = prompt("Enter in the URL:", '');
		openTag = '[' + tag + '=' + urlsrc + ']';
	}
	
	if ( document.selection ) {
		formName.focus();
		sel = document.selection.createRange();
		
		if ( tag == 'img' ) {
			sel.text = openTag + imgsrc + closeTag;
		} else if ( tag == 'url' ) {
			sel.text = openTag + urlsrc + closeTag;
		} else {
			sel.text = openTag + sel.text + closeTag;
		}
	} else if ( formName.selectionStart || formName.selectionStart == '0' ) {
		var startPos = formName.selectionStart;
		var endPos = formName.selectionEnd;
		cursorPos = startPos + openTag.length;
		
		formName.value = formName.value.substring(0, startPos) + openTag + formName.value.substring(startPos, endPos);
		
		if ( tag == 'img' ) {
			formName.value += imgsrc + closeTag;// + formName.value.substring(endPos, formName.value.length);
		} else if ( tag == 'url' ) {
			formName.value += urlsrc + closeTag;
		} else {
			formName.value += closeTag;// + formName.value.substring(endPos, formName.value.length);
		}
		
		formName.focus();
		formName.setSelectionRange(cursorPos, cursorPos);
	} else {
		formName.value += openTag + closeTag;
	}
}