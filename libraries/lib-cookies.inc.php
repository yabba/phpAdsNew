<?php // $Revision: 1.1 $

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

?>
