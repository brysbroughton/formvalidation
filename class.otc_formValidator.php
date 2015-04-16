<?php
//uncomment these lines to debug
//ini_set('display_errors','On');
//error_reporting(E_ALL | E_STRICT);
/**
 * otc_formValidator - PHP form submission validator
 * @package otc_formValidator
 * @author Brys Broughton
 * @copyright 2014 Brys Broughton
 */
/*
 * all validation parameters (ostensible)
    required attr
    types
      date type
      alpha type
      alphaNumeric type
      number type
      email type
      phone number type
    maxlength

 */
 /*Example Input to the constructor:
    $validation_params = array(
        "event_name" => array("type" => "alpha", "required" => "true", "maxlength" => 8, "pretty_name" => "Event Name"),
        "event_type" => array("required" => "true"),//no info needed for drop-down unless required
        "event_date" => array("type"=>"date"),
        "num_attendies" => array("type" => "number"),
        "tel_number" => array("type" => "phoneNumber", "required" => "false"),
        "submitter_email" => array("type" => "email"),
        "color_pref" => array("type" => "array"),
        "color_fav" => '',//no info needed for radio buttons unless required
        "additional_comments" => array("type" => "alphaNumeric", "maxlength" => 2)
    );

  */
 /*
  
  use otc_formValidator -> memo() to get error messages when validation fails, or the success message when it passes
  
  */
ini_set("filter.default", "special_chars");//sanitizing inputs
 
class otc_formValidator {
    
    var $rules;//granular associative array of element rules
    var $passed = true;//boolean
    var $messages = array("status" => "validator initialized.",
                          "errors"  => array()
                          );
    
    function __construct ( $input_rules = array() ){
        $this->rules = $input_rules;//rules array must be passed to constructor, or to valid()
    }
    
    function valid ($inputRules = NULL) {//returns true or false
    //Assumes that all post values map to input rules 1-1
    
        if (is_null($inputRules)) {//allows valid to be called with or without passing the rules parameter
            $inputRules = $this->rules;
        } else {
            $this->rules = $inputRules;
        }

        foreach ($inputRules as $name => $parameters) {//($_POST as $name => $val) {

            //required
            if ((!IsSet($_POST[$name])) || ($_POST[$name] == '')) {
                if (IsSet($parameters['required'])) {
                    array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name)." is required");
                    $this->passed = false;
                }
                continue;//Don't evaluate anything else about this element, go to next in loop
            }

            $val = $_POST[$name];
        
            //maxlength
            if (IsSet($parameters['maxlength'])) {
                $submit_len = strlen($val);
                $max_len = $parameters['maxlength'];
                if ($submit_len > $max_len) {
                    array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name)." can only be ".$max_len." characters, compared to ".$submit_len." submitted.");
                    $this->passed = false;
                }
            }
            
            //type
            if (IsSet($parameters["type"])) {
                switch(strtolower($parameters["type"])){
                    case "date":
                        $val = Trim($val);
                        if (!preg_match('/^$|^(?:\d{4}[-\/.\ ]\d{1,2}[-\/.\ ]\d{1,2}[\ ]*)|^(?:\d{1,2}[-\/.\ ]\d{1,2}[-\/.\ ]\d{4}[\ ]*)|^(?:\d{2}[-][A-Z]{3}[-]\d{4}[\ ]*)$/i', $val)){
                            array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name).": Please enter a valid date.");
                            $this->passed = false;
                        }
                        break;
                    case "email":
                        if(($val == '')||((filter_var($val, FILTER_VALIDATE_EMAIL)))){
                            //pass 
                        }else{
                            array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name).": Please enter a valid email address.");
                            $this->passed = false;
                        }
                        break;
                    case "numeric"://synonym for a friendly front-end
                    case "number":
                        if (!preg_match('/[0-9]+|^$/', $val)) {
                            array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name).": Value must be a number.");
                            $this->passed = false;
                        }
                        break;
                    case "phonenumber":
                        if (!(preg_match('/^$|^\(?(\d{3})\)?[\.\-\/\ ]?(\d{3})[\.\-\/]?(\d{4})$/i', $val))) {
                            array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name).": Please enter a valid phone number.");
                            $this->passed = false;
                        }
                        break;
                    case "alpha":
                        if (!(preg_match("/^[a-z\s.,-\/\\\:_'#!]*$/i", $val))) {
                            array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name).": Please enter only letters and the special characters :/.'-_#! .");
                            $this->passed = false;
                        }
                        break;
                    case "alphanumeric":
                        if (!preg_match("/^[a-z0-9\s.,:\/\\\-_%\$\(\)'#!?;=&]*$/i", $val)) {
                            array_push($this->messages['errors'], ($parameters['pretty_name'] ? $parameters['pretty_name'] : $name).": Please enter only letters, numbers, and the special characters :/.'-_;#!?$ .");
                            $this->passed = false;
                        }
                        break;
                    default:
                        array_push($this->messages['errors'], "Server Error: ".($parameters['pretty_name'] ? $parameters['pretty_name'] : $name)." has unsupported type {$parameters['type']}\nPlease contact <a href=\"\">Web Services</a>");
                        $this->passed = false;
                }
            }
            
        }
            
        if($this->passed) {
            array_push($this->messages['errors'], "Form validated successfully");
            $this->messages["status"] = "Passed";
        } else {
            $this->messages["status"] = "Failed";
        }

        return $this->passed;
    }
    
    function memo () {//provides html messages
        $html = '<h3>'.$this->messages["status"].'</h3><ul>';
        foreach ($this->messages["errors"] as $errmsg) {
            $html.='<li>'.$errmsg.'</li>';
        }
        $html.='</ul>';
        return $html;
    }
    
    function printPOST ($formData) {

        $html = '<table><thead><tr><th>data</th><th>submitted value</th></tr></thead><tbody>';
        foreach ($formData as $name => $val) {
            $html .= '<tr><th>'.(IsSet($this->rules[$name]['pretty_name']) ? $this->rules[$name]['pretty_name'] : $name).'</th><td>';
            
            if (is_array($val)) {
                foreach($val as $CSV){//val is array of comma-separated values
                    $html .= $CSV.', ';
                }
                $html = rtrim($html, ', ').'</td></tr>';//remove trailing comma, add closing tags
            } else {
                $html .= $val.'</td></tr>';
            }
        }
        $html .= '</tbody></table>';
        return $html;
    }

    function recPrint ($granule) {

        foreach ($granule as $element => $properties) {
            $html = '<tr>';
            $html .= '<td>'.$element.'</td>';
            if (gettype($properties) == "array") {
                $html .= $this->recPrint($properties);
            } else {
                $html .= '<td>'.$properties.'</td>';
            }
            $html .= '</tr>';
        }
        return $html;
    }

}
    
function translate_params_jvalidate($params) {
/* Takes the granular array of rules and switches them 
 * to the format expected by the jQuery validation library
 * The only change is to the type parameter
 * The resulting array is converted to a json string and returned
 */
    $new_params = array();
    
    //switching type:datatype to datatype:true
    foreach ($params as $el_name => $rules_array) {

        $new_rules = array();
        
        foreach ($rules_array as $property => $value) {
            
            if ($property == 'type') {
                $new_rules[$value] = "true";
            } else {
                $new_rules[$property] = $value;
            }
            
        }
        
        //jvalidate doesn't use this field
        unset($new_rules['pretty_name']);
        
        $new_params[$el_name] = $new_rules;
        
    }
    
    //encode in json and remove extra quotes that php function adds
    //note that string type attributes will break here
    return str_replace('"', '', json_encode($new_params));

}



?>