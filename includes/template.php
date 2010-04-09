<?php

/*
	file: template.php
	function: Abstract interaction with template code.
	flmod: 04.01.04 07:04 PM
	flmod-who: Vic Cherubini <vic@openglforums.com>
*/

/*
	CHANGELOG
	12.01.03
		* original file and class inception
	04.01.04
		* added comments
		* changed $template_code to $_template_code
		* made code in parse() more readable
	04.02.04
		* added class variable $_template_name
		* changed set_template() to something a little different
		* added $append_template_name argument in parse
		* added class variables $comment_start and $commend_end
*/

class template {
	var $_tokens = array();	// variables/values array
	var $_template_code;		// code sent to the class
	var $_template_name;
	
	// hard coded stuff is bad, mmmkay
	var $comment_start = '<!-- ';
	var $comment_end = ' -->';
	
	// default constructor
	function template() {
		unset($this->_tokens, $this->_template_code, $this->_template_name);
	}
	
	// sets the variables to be parsed
	// $variables *must* be an array
	// if $clear is true, then $_tokens is reset
	// else, the new variables are appended to $_tokens
	function set_vars($variables, $clear = false) {
		if ( empty($variables) || !is_array($variables) ) {
			return false;
		}
		
		if ( $clear == true ) {
			$this->_tokens = $variables;
		} else {
			while ( $key = key($variables) ) {
				$this->_tokens[$key] = $variables[$key];
				next($variables);
			}
		}
		
		return true;
	}
	
	// sets the template code
	// notice, we don't care where the code came from
	// it could come from a file, the database, or within the
	// PHP itself.
	function set_template($code_array) {
		// key value pair array, key is template name, value is code
		if ( empty($code_array) || !is_array($code_array) ) {
			return false;
		}
		
		$key1 = key($code_array);
		next($code_array);
		$key2 = key($code_array);
		reset($code_array);
		$this->_template_name = $code_array[$key1];
		$this->_template_code = $code_array[$key2];
		
		unset($code_array);
		
		return true;
	}
	
	// replace all variables in the $_template_code variable with their values from $_tokens
	function parse($append_template_name = false) {
		// if $append_template_name == true, show the name of the template in a comment
		// this should possibly be at a different level, but it looks good here
		
		$results = array();
		preg_match_all("/\{(\w+)\}/i", $this->_template_code, $results, PREG_SET_ORDER);
	
		for ( $i=0; $i<count($results); $i++ ) {
			$var = $results[$i][0];			// this is the string {VARIABLE}
			$trim_var = $results[$i][1]; 	// this is the string variable (the key)
			
			//$this->_template_code = preg_replace('/' . $var . '/', $this->_tokens[$trim_var], $this->_template_code);
			$this->_template_code = str_replace($var, $this->_tokens[$trim_var], $this->_template_code);
		}
		
		// added this 
		if ( $append_template_name == true ) {
			$final_code = ($this->comment_start . $this->_template_name . $this->comment_end) . $this->_template_code;
		} else {
			$final_code = $this->_template_code;
		}
		return $final_code;
	}
}

?>