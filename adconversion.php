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



// Figure out our location
define ('phpAds_path', '.');



/*********************************************************/
/* Include required files                                */
/*********************************************************/

require	(phpAds_path."/config.inc.php"); 
require (phpAds_path."/libraries/lib-io.inc.php");
require (phpAds_path."/libraries/lib-db.inc.php");
require (phpAds_path."/libraries/lib-remotehost.inc.php");
require (phpAds_path."/libraries/lib-log.inc.php");
require (phpAds_path."/libraries/lib-cache.inc.php");



/*********************************************************/
/* Register input variables                              */
/*********************************************************/

phpAds_registerGlobal (
	 'trackerid'
	,'block'
	,'capping'
);



/*********************************************************/
/* Main code                                             */
/*********************************************************/

// Determine the cookie ID
$cookieid = phpAds_getCookieID();
// Send the cookie ID
phpAds_setCookie("phpAds_id", $cookieid, time()+365*24*60*60);

if ($phpAds_config['block_adconversions'] == 0 ||
   ($phpAds_config['block_adconversions'] > 0 && 
   (!isset($HTTP_COOKIE_VARS['phpAds_blockConversion'][$trackerid]) ||
   	$HTTP_COOKIE_VARS['phpAds_blockConversion'][$trackerid] <= time())))
{
	if ($phpAds_config['log_adconversions'])
	{
		phpAds_dbConnect();
		phpAds_logConversion($cookieid, $trackerid);
	}
	
	// Send block cookies
	if ($phpAds_config['block_adconversions'] > 0)
		phpAds_setCookie ("phpAds_blockConversion[".$trackerid."]", time() + $phpAds_config['block_adconversions'],
						  time() + $phpAds_config['block_adconversions'] + 43200);
}


if ($phpAds_config['geotracking_type'] != '' && $phpAds_config['geotracking_cookie'])
	if (!isset($HTTP_COOKIE_VARS['phpAds_geoInfo']) && $phpAds_geo)
		phpAds_setCookie ("phpAds_geoInfo", 
			($phpAds_geo['country'] ? $phpAds_geo['country'] : '').'|'.
		   	($phpAds_geo['continent'] ? $phpAds_geo['continent'] : '').'|'.
			($phpAds_geo['region'] ? $phpAds_geo['region'] : ''), 0);


phpAds_flushCookie ();


header ("Content-Type: image/gif");
header ("Content-Length: 43");

// 1 x 1 gif
echo chr(0x47).chr(0x49).chr(0x46).chr(0x38).chr(0x39).chr(0x61).chr(0x01).chr(0x00).
     chr(0x01).chr(0x00).chr(0x80).chr(0x00).chr(0x00).chr(0x04).chr(0x02).chr(0x04).
 	 chr(0x00).chr(0x00).chr(0x00).chr(0x21).chr(0xF9).chr(0x04).chr(0x01).chr(0x00).
     chr(0x00).chr(0x00).chr(0x00).chr(0x2C).chr(0x00).chr(0x00).chr(0x00).chr(0x00).
     chr(0x01).chr(0x00).chr(0x01).chr(0x00).chr(0x00).chr(0x02).chr(0x02).chr(0x44).
     chr(0x01).chr(0x00).chr(0x3B);

?>