<?php // $Revision: 2.4 $

/************************************************************************/
/* phpAdsNew 2                                                          */
/* ===========                                                          */
/*                                                                      */
/* Copyright (c) 2000-2002 by the phpAdsNew developers                  */
/* For more information visit: http://www.phpadsnew.com                 */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/


/*********************************************************/
/* Register globals                                      */
/*********************************************************/

function phpAds_registerGlobal ()
{
	global $HTTP_GET_VARS, $HTTP_POST_VARS;
	
	$args = func_get_args();
	while (list(,$key) = each ($args))
	{
		if (isset($HTTP_GET_VARS[$key])) $value = $HTTP_GET_VARS[$key];
		if (isset($HTTP_POST_VARS[$key])) $value = $HTTP_POST_VARS[$key];
		
		if (isset($value))
		{
			if (!ini_get ('magic_quotes_gpc'))
			{
				if (!is_array($value))
					$value = addslashes($value);
				else
					$value = phpAds_slashArray($value);
			}
			
			$GLOBALS[$key] = $value;
			unset($value);
		}
	}
}

// This function will allow the source to be derived from server side parameters, such
// as referer, current location, user agent, domain, etc.

function phpAds_deriveSource ($source, $referer='')
{
	global $HTTP_GET_VARS, $HTTP_SERVER_VARS, $phpAds_config, $matches, $failed;

	$source = trim(urldecode($source));

	if ($source == '{derive}')
	{

		// define table of domains
		$domains_table 	= array(array());
	
		$domains_table[100]['client']	= "Other";
		$domains_table[100]['domain']	= "";
		$domains_table[100]['mnemonic']	= "(other)";
		$domains_table[100]['rule']		= "/^https?:\/\/(.*?)/i";
		$domains_table[100]['modifier']	= $domains_table[100]['mnemonic'] . "/$1";

		
		// Break down the referer...
		if (isset($HTTP_GET_VARS['loc']))
			$referer = $HTTP_GET_VARS['loc'];

		elseif (isset($HTTP_SERVER_VARS['HTTP_REFERER']))
			$referer = $HTTP_SERVER_VARS['HTTP_REFERER'];


		$derived_source = $referer;

		foreach ($domains_table as $key => $value) 
			if (($derived_source = preg_replace($domains_table[$key]['rule'], $domains_table[$key]['modifier'], $referer)) != $referer)
				break;

		return phpAds_encrypt($derived_source);
	}
	
	return phpAds_encrypt($source);
}



/*********************************************************/
/* Recursive add slashes to an array                     */
/*********************************************************/

function phpAds_slashArray ($a)
{
	while (list($k,$v) = each($a))
	{
		if (!is_array($v))
			$a[$k] = addslashes($v);
		else
			$a[$k] = phpAds_slashArray($v);
	}
	
	reset ($a);
	return ($a);
}


/*********************************************************/
/* Store cookies to be set in a cache                    */
/*********************************************************/

function phpAds_getUniqueUserID( $create = true )
{
	global $phpAds_config, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS;
	
	if (isset($HTTP_COOKIE_VARS['phpAds_id']))
		$userid = $HTTP_COOKIE_VARS['phpAds_id'];
	
	if ( !isset($userid))
	{
		if ($create)
	{
		// Create a unique ID.  This is done by combining the web server's address, remote address, and microtime.
		$remote_address = $HTTP_SERVER_VARS['REMOTE_ADDR'];
		$local_address = $phpAds_config['url_prefix'];  //How do I get the IP address of this server?
		//Get the exact time
		list($usec, $sec) = explode(" ", microtime());
		$time = (float) $usec + (float) $sec;
		// Get a random number
		$random = mt_rand(0,999999999);
			$userid = substr(md5($local_address.$time.$remote_address.$random),0,32);  // Need to find a way to generate this...
		}
		else
			$userid = null;
	}
	
	return $userid;
}


/*********************************************************/
/* Store cookies to be set in a cache                    */
/*********************************************************/

function phpAds_setCookie ($name, $value, $expire = 0)
{
	global $phpAds_cookieCache;
	
	if (!isset($phpAds_cookieCache)) $phpAds_cookieCache = array();
	
	$phpAds_cookieCache[] = array ($name, $value, $expire);
}



/*********************************************************/
/* Send all cookies to the browser and clear cache       */
/*********************************************************/

function phpAds_flushCookie ()
{
	global $phpAds_config, $phpAds_cookieCache;
	
	if (isset($phpAds_cookieCache))
	{
		// Send P3P headers
		if ($phpAds_config['p3p_policies'])
		{
			$p3p_header = '';
			
			if ($phpAds_config['p3p_policy_location'] != '')
				$p3p_header .= " policyref=\"".$phpAds_config['p3p_policy_location']."\"";
			
			if ($phpAds_config['p3p_compact_policy'] != '')
				$p3p_header .= " CP=\"".$phpAds_config['p3p_compact_policy']."\"";
			
			if ($p3p_header != '')
				header ("P3P: $p3p_header");
		}
		
		// Get path
		$url_prefix = parse_url($phpAds_config['url_prefix']);
		
		// Set cookies
		while (list($k,$v) = each ($phpAds_cookieCache))
		{
			list ($name, $value, $expire) = $v;
			setcookie ($name, $value, $expire, '/');
		}
		
		// Clear cache
		$phpAds_cookieCache = array();
	}
}

function phpAds_isClickBlocked($bannerid)
{
	global $log, $phpAds_config, $HTTP_COOKIE_VARS;

	$blockClick = false;
	
	if ($log == 'no')
	{
		$blockClick = true;
	}
	elseif ($phpAds_config['block_adclicks'] > 0)
	{
		if (isset($HTTP_COOKIE_VARS['phpAds_blockClick'][$bannerid])
			&& $HTTP_COOKIE_VARS['phpAds_blockClick'][$bannerid] > time())
		{
			$blockClick = true;
		}
	}
	
	return $blockClick;
}

function phpAds_updateClickBlockTime($bannerid)
{
	global $phpAds_config;
	
	if ($phpAds_config['block_adclicks'] > 0)
	{
		phpAds_setCookie ("phpAds_blockClick[".$bannerid."]", time() + $phpAds_config['block_adclicks'], 
						  time() + $phpAds_config['block_adclicks'] + 43200);
	}
}

function phpAds_isConversionBlocked($trackerid)
{
	global $phpAds_config, $HTTP_COOKIE_VARS;
	

	$blockConversion = false;
	
	if ($phpAds_config['block_adconversions'] > 0)
	{
		if (isset($HTTP_COOKIE_VARS['phpAds_blockConversion'][$trackerid])
			&& $HTTP_COOKIE_VARS['phpAds_blockConversion'][$trackerid] > time())
		{
			$blockConversion = true;
		}
	}
	
	return $blockConversion;
}

function phpAds_updateConversionBlockTime($trackerid)
{
	global $phpAds_config;
	
	if ($phpAds_config['block_adconversions'] > 0)
	{
		phpAds_setCookie ("phpAds_blockConversion[".$trackerid."]", time() + $phpAds_config['block_adconversions'], 
						  time() + $phpAds_config['block_adconversions'] + 43200);
	}
}

function phpAds_isViewBlocked($bannerid)
{
	global $phpAds_config, $HTTP_COOKIE_VARS;

	$blockView = false;
	
	if ($phpAds_config['block_adviews'] > 0)
	{
		if (isset($HTTP_COOKIE_VARS['phpAds_blockView'][$bannerid])
			&& $HTTP_COOKIE_VARS['phpAds_blockView'][$bannerid] > time())
		{
			$blockView = true;
		}
	}
	
	return $blockView;
}

function phpAds_updateViewBlockTime($bannerid)
{
	global $phpAds_config;
	
	if ($phpAds_config['block_adviews'] > 0)
	{
		phpAds_setCookie ("phpAds_blockView[".$bannerid."]", time() + $phpAds_config['block_adviews'], 
						  time() + $phpAds_config['block_adviews'] + 43200);
	}
}

function phpAds_updateAdBlockTime($bannerid, $block)
{
	if ( ($block != '') && ($block != '0') )
		phpAds_setCookie ("phpAds_blockAd[".$bannerid."]", time() + $block, time() + $block + 43200);
}

function phpAds_isAdBlocked($bannerid, $block)
{
	global $HTTP_COOKIE_VARS;
	
	$blocked = false;
	
	if ($block > 0)
	{
		if (isset($HTTP_COOKIE_VARS['phpAds_blockAd'][$bannerid])
			&& ($HTTP_COOKIE_VARS['phpAds_blockAd'][$bannerid] > time()) )
				$blocked = true;
	}
	
	return $blocked;
}

function phpAds_isAdCapped($bannerid, $capping, $session_capping = 0)
{
	global $HTTP_COOKIE_VARS;
	
	$capped = false;
	
	if ($capping > 0)
	{
		if (isset($HTTP_COOKIE_VARS['phpAds_capAd'][$bannerid])
			&& ($HTTP_COOKIE_VARS['phpAds_capAd'][$bannerid] >= $capping) )
				$capped = true;
	}
	
	if ($session_capping > 0)
	{
		if (isset($HTTP_COOKIE_VARS['phpAds_sessionCapAd'][$bannerid])
			&& ($HTTP_COOKIE_VARS['phpAds_sessionCapAd'][$bannerid] >= $session_capping) )
				$capped = true;
	
	}
	
	return $capped;
}

function phpAds_updateAdCapping($bannerid, $capping,  $session_capping = 0)
{
	global $HTTP_COOKIE_VARS;
	
	if (($capping != '') && ($capping != '0'))
	{
		if (	isset($HTTP_COOKIE_VARS['phpAds_capAd'])
			&&  isset($HTTP_COOKIE_VARS['phpAds_capAd'][$bannerid]) )
			$newcap = $HTTP_COOKIE_VARS['phpAds_capAd'][$bannerid] + 1;
		else
			$newcap = 1;
		
		phpAds_setCookie ("phpAds_capAd[".$bannerid."]", $newcap, time() + 31536000); // 1 year
	}
	
	if (($session_capping != '') && ($session_capping != '0'))
	{
		if (	isset($HTTP_COOKIE_VARS['phpAds_sessionCapAd'])
			&&  isset($HTTP_COOKIE_VARS['phpAds_sessionCapAd'][$bannerid]) )
			$newcap = $HTTP_COOKIE_VARS['phpAds_sessionCapAd'][$bannerid] + 1;
		else
			$newcap = 1;
		
		phpAds_setCookie ("phpAds_sessionCapAd[".$bannerid."]", $newcap, 0); // session cookie
	}
}

function phpAds_updateGeoTracking($phpAds_geo)
{
	global $phpAds_config, $HTTP_COOKIE_VARS;
	
	if ($phpAds_config['geotracking_type'] != '' && $phpAds_config['geotracking_cookie'])
	{
		if (!isset($HTTP_COOKIE_VARS['phpAds_geoInfo']) && $phpAds_geo)
			phpAds_setCookie ("phpAds_geoInfo", 
				($phpAds_geo['country'] ? $phpAds_geo['country'] : '').'|'.
			   	($phpAds_geo['continent'] ? $phpAds_geo['continent'] : '').'|'.
				($phpAds_geo['region'] ? $phpAds_geo['region'] : ''), 0);
	}
}

function phpAds_getCachedGeoInformation()
{
	global $HTTP_COOKIE_VARS;
	
	$phpAds_geo = null;
	
	if (isset($HTTP_COOKIE_VARS['phpAds_geoInfo']))
	{
		// Use cookie if available
		$phpAds_geoRaw = explode('|', $HTTP_COOKIE_VARS['phpAds_geoInfo']);
		
		if (count($phpAds_geoRaw) == 3)
		{
			$phpAds_geo['country']   = $phpAds_geoRaw[0] != '' ? $phpAds_geoRaw[0] : false;
			$phpAds_geo['continent'] = $phpAds_geoRaw[1] != '' ? $phpAds_geoRaw[1] : false;
			$phpAds_geo['region']    = $phpAds_geoRaw[2] != '' ? $phpAds_geoRaw[2] : false;
		}
	}
	
	return $phpAds_geo;
}


function phpAds_encrypt($string)
{

	global $phpAds_config;
	
	$convert = '';

	if (isset($string) && substr($string,1,4) != 'obfs' && $phpAds_config['obfuscate'])
	{
		for ($i=0; $i < strlen($string); $i++)
		{
			$dec = ord(substr($string,$i,1));
			if (strlen($dec) == 2) $dec = 0 . $dec;
			$dec = 324 - $dec;
			$convert .= $dec;
		}
		$convert = '{obfs:' . $convert . '}';
		return ($convert);
	} else return $string;
}
	
	
	
function phpAds_decrypt($string)
{

	global $phpAds_config;

	$convert = '';
	if (isset($string) && substr($string,1,4) == 'obfs' && $phpAds_config['obfuscate'])
	{
		for ($i=6; $i < strlen($string)-1; $i = $i+3)
		{
			$dec = substr($string,$i,3);
			$dec = 324 - $dec;
			$dec = chr($dec);			
			$convert .= $dec;
		}
		return ($convert);
	}
	else return($string);
}


function phpAds_buildJavascriptVariablesScript($trackerid, $conversionid)
{

	global $phpAds_config;

	$variables = phpAds_fetchJavascriptVariables($trackerid);
	

	$buffer .="
	
		function getQuerystringVariables() {
		
			// declare which querystring variables we need
			this.variablesToGet = new Object();
			
	";
	

	if (isset($variables) && is_array($variables) && sizeof($variables) > 0) {
	
		foreach($variables as $key=>$variable)
			if ($variable['variabletype'] == 'qs')
				$buffer .= "this.variablesToGet['".$variable['name']."'] = 'undefined'";
			
				
		$buffer .="
				//get the url and assume the first ? indicates the start of the querystring
				this.querystring 		= new String(window.location);
				this.querystring_start 	= this.querystring.indexOf('?');
				
				if (querystring_start != -1) {
				
					//get actual querystring without the ?
					this.querystring = querystring.substring(querystring_start+1, querystring.length);
					
					//split querystring into value pairs, check whether that variables is needed,
					// and if so, store it in variablesToGet[]
					for(var i=0; i < this.querystring.split('&').length; i++) {
					
						this.pair 	= this.querystring.split('&')[i];
						this.k 		= this.pair.split('=')[0];
						this.v 		= this.pair.split('=')[1];
						
						if (this.variablesToGet[this.k] == 'undefined')
							this.variablesToGet[this.k] =  this.v;
			
					}
				
				}
			
				return this.variablesToGet;
				
			}
	
		";
		
		
		$buffer .="
		
			function getJavascriptVariables() {
			
				// declare which javascript variables we need
				this.variablesToGet =  new Object();
	
	
		";
		
		foreach($variables as $key=>$variable)
			if ($variable['variabletype'] == 'js')
				$buffer .= "this.variablesToGet['".$variable['name']."'] = 'undefined'";
	
		
		
		$buffer .= "
		
				// iterate through variables and assign value
				for (variable in this.variablesToGet)
				{
					
					try
					{
						if (eval(variable))
							this.variablesToGet[variable] = eval(variable);
						else 
							this.variablesToGet[variable] = 'undefined';
					}
					catch (error)
					{
					
					}
				}
			
					
				return this.variablesToGet;	
			
			}
		
		";
		
		$buffer .= "
			
			var querystringVariables = getQuerystringVariables();
			var javascriptVariables = getJavascriptVariables();
	
		";
		
		$buffer .= "var querystring = '?trackerid=".$trackerid."&conversionid=".$conversionid."'";
	
	
		foreach($variables as $key=>$variable)
		{
			if ($variable['variabletype'] == 'qs')
				$buffer .= "+'&" .$key . "='+querystringVariables['".$variable['name']."']";
			else
				$buffer .= "+'&" .$key . "='+javascriptVariables['".$variable['name']."']";		
		}
	
		$buffer .= ";\n";
	
		//$buffer .= "alert(querystring);";
	
		$buffer .= "\n\n";
		$buffer .= "\tdocument.write (\"<\" + \"script language='JavaScript' type='text/javascript' src='\");\n";
		$buffer .= "\tdocument.write (\"".$phpAds_config['url_prefix']."/adcnvvars.php\"+querystring);\n";
		$buffer .= "\tdocument.write (\"'><\" + \"/script>\");\n";
	
		return $buffer;
		
	} else 
		return false;

}

?>
