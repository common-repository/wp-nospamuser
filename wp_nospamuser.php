<?php
/*
Plugin Name: NoSpamUser
Version: 0.7.2
Plugin URI: http://danielgilbert.de/nospamuser/
Description: Prevents known Spam Users from registering on your blog.
Author: Daniel Gilbert
Author URI: http://danielgilbert.de

Copyright (C) 2008  Daniel Gilbert

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/
add_action('register_form','InsertPrivacyStatement');
add_action('register_post','check_user');

//======================================
//Descriotion: Insert Privacy Statement
Function InsertPrivacyStatement() {
	
	//Define Global Vars
	
	$test = '<p style="font-weight:normal; background-color:#ffffe0; padding: 12px 12px 12px 12px; display:block; Border: 1px solid #E6DB55;"; >Your Email and Username will be verified by stopforumspam.com. If you are not confidential with this procedure, please contact the admin.</p>';

	echo $test;
}

//======================================
//Description: Action Filter.
Function check_user(){

	//Define Global Vars
	global $user_login;
	global $user_email;
	global $update;

	$ip = $_SERVER['REMOTE_ADDR'];
		if	(CheckUserAgainstSpam($user_login,$user_email) == true )  {
		
				//Simply die and do nothing at all
				//Please "uncomment" the following line by removing the two // if you wish to receive a notification
				//SendEmailNotification($user_login,$user_email,$ip);
				die(ap_errortmp());
		
					}
	return $user_login;			
	
}

//======================================
// Description: Contact stopforumspam.com and compare Users
Function ContactSFS($username, $email) {
	$check = "";

	$check_url = "http://www.stopforumspam.com/api.php?username=" . urlencode($username) . "&email=" . urlencode($email);

	$check = file_get_contents($check_url);
	return $check;
}

//======================================
// Description: 
Function CheckUserAgainstSpam($username, $email) {
	$result = false;
	
	if (($username == "") or ($email == "")) {

	 	$result = false;
	
	} else {
		
		//Username and Email are fine, so lets check
		$check_new_user = ContactSFS($username, $email);
		
		//Run through XML
		$xml = xmlize($check_new_user);

		if (($xml["response"]["#"]["appears"][0]["#"] == "yes") or ($xml["response"]["#"]["appears"][1]["#"] == "yes")) {
				$result = true;
		}
		
	}
	return $result;
}

//======================================
//Description: Send Notification Mail
Function SendEmailNotification($username,$email,$ip){

$sendermail = get_option('admin_email');
$header 		= "From: $sendermail";
$recipient  = get_option('admin_email');

$message 		= "A potential Spammer tried to register on your blog.\n\n";
$message 	 .= "Username: $username\n";
$message	 .= "EMail: $email\n";
$message	 .= "IP: $ip\n\n";
$message	 .= "This attempt was successfully blocked!";

	//Check, if a plugin already uses the wp-mail func
	if (function_exists('wp_mail')) {
		wp_mail ($recipient, get_bloginfo('name').' - Spammer Alert',$message,$header);
	} else {
		mail ($recipient, get_bloginfo('name').' - Spammer Alert', $message,$header);
	}
}

//======================================
// Description: The Error Template
Function ap_errortmp(){
	$temp = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
	$temp.= "<html xmlns=\"http://www.w3.org/1999/xhtml\" dir=\"".get_bloginfo('text_direction')."\" lang=\"en-EN\">";
	$temp.= '<head>';
	$temp.= '<title>'.get_bloginfo('name').' SPAMMER ALERT!</title>';
	$temp.= "<link rel='stylesheet' href='".get_bloginfo('siteurl')."/wp-admin/css/login.css' type='text/css' media='all' />";
	$temp.= "<link rel='stylesheet' href='".get_bloginfo('siteurl')."/wp-admin/css/colors-fresh.css' type='text/css' media='all' />";
	$temp.= '</head>';
	$temp.= '<body>';
	$temp.= '<div id="login"><h1><a href="'.apply_filters('login_headerurl', 'http://wordpress.org/').'" title="'.apply_filters('login_headertitle', __('Powered by WordPress')).'">'.get_bloginfo('name').'</a></h1>';
	$temp.= '<div id="login_error"><strong>ERROR:</strong> If you are not a spammer, this username, email or ip was used by one of them, so you cannot register here. Please contact the admin! (Or simply go back and use another name ;) )</div>';
	$temp.= '';
	$temp.= '';
	$temp.= '</div>';
	$temp.= '</body>';
	
	echo $temp;
}	
	/* xmlize() is by Hans Anderson, www.hansanderson.com/contact/
 *
 * Ye Ole "Feel Free To Use it However" License [PHP, BSD, GPL].
 * some code in xml_depth is based on code written by other PHPers
 * as well as one Perl script.  Poor programming practice and organization
 * on my part is to blame for the credit these people aren't receiving.
 * None of the code was copyrighted, though.
 *
 * This is a stable release, 1.0.  I don't foresee any changes, but you
 * might check http://www.hansanderson.com/php/xml/ to see
 *
 * usage: $xml = xmlize($xml_data);
 *
 * See the function traverse_xmlize() for information about the
 * structure of the array, it's much easier to explain by showing you.
 * Be aware that the array is very complex.  I use xmlize all the time,
 * but still need to use traverse_xmlize or print_r() quite often to
 * show me the structure!
 *
 */

function xmlize($data, $WHITE=1) {

    $data = trim($data);
    $vals = $index = $array = array();
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $WHITE);
    if ( !xml_parse_into_struct($parser, $data, $vals, $index) )
    {
	die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($parser)),
                    xml_get_current_line_number($parser)));

    }
    xml_parser_free($parser);

    $i = 0; 

    $tagname = $vals[$i]['tag'];
    if ( isset ($vals[$i]['attributes'] ) )
    {
        $array[$tagname]['@'] = $vals[$i]['attributes'];
    } else {
        $array[$tagname]['@'] = array();
    }

    $array[$tagname]["#"] = xml_depth($vals, $i);

    return $array;
}

/* 
 *
 * You don't need to do anything with this function, it's called by
 * xmlize.  It's a recursive function, calling itself as it goes deeper
 * into the xml levels.  If you make any improvements, please let me know.
 *
 *
 */

function xml_depth($vals, &$i) { 
    $children = array(); 

    if ( isset($vals[$i]['value']) )
    {
        array_push($children, $vals[$i]['value']);
    }

    while (++$i < count($vals)) { 

        switch ($vals[$i]['type']) { 

           case 'open': 

                if ( isset ( $vals[$i]['tag'] ) )
                {
                    $tagname = $vals[$i]['tag'];
                } else {
                    $tagname = '';
                }

                if ( isset ( $children[$tagname] ) )
                {
                    $size = sizeof($children[$tagname]);
                } else {
                    $size = 0;
                }

                if ( isset ( $vals[$i]['attributes'] ) ) {
                    $children[$tagname][$size]['@'] = $vals[$i]["attributes"];
                }

                $children[$tagname][$size]['#'] = xml_depth($vals, $i);

            break; 


            case 'cdata':
                array_push($children, $vals[$i]['value']); 
            break; 

            case 'complete': 
                $tagname = $vals[$i]['tag'];

                if( isset ($children[$tagname]) )
                {
                    $size = sizeof($children[$tagname]);
                } else {
                    $size = 0;
                }

                if( isset ( $vals[$i]['value'] ) )
                {
                    $children[$tagname][$size]["#"] = $vals[$i]['value'];
                } else {
                    $children[$tagname][$size]["#"] = '';
                }

                if ( isset ($vals[$i]['attributes']) ) {
                    $children[$tagname][$size]['@']
                                             = $vals[$i]['attributes'];
                }			

            break; 

            case 'close':
                return $children; 
            break;
        } 

    } 

	return $children;

}


/* function by acebone@f2s.com, a HUGE help!
 *
 * this helps you understand the structure of the array xmlize() outputs
 *
 * usage:
 * traverse_xmlize($xml, 'xml_');
 * print '<pre>' . implode("", $traverse_array . '</pre>';
 *
 *
 */ 

function traverse_xmlize($array, $arrName = "array", $level = 0) {

    foreach($array as $key=>$val)
    {
        if ( is_array($val) )
        {
            traverse_xmlize($val, $arrName . "[" . $key . "]", $level + 1);
        } else {
            $GLOBALS['traverse_array'][] = '$' . $arrName . '[' . $key . '] = "' . $val . "\"\n";
        }
    }

    return 1;

}