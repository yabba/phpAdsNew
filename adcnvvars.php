<?php

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
require_once (phpAds_path."/libraries/lib-io.inc.php");
require (phpAds_path."/libraries/lib-db.inc.php");

global $phpAds_config;

/*********************************************************/
/* Register input variables                              */
/*********************************************************/

$variables = array();

foreach($HTTP_GET_VARS as $key=>$value)
	$variables[$key] = $value;
	
if (sizeof($variables) > 2 && isset($variables['trackerid']) && isset($variables['conversionid']))
{
	
	$conversionid = $variables['conversionid'];
	$trackerid = $variables['trackerid'];
	unset($variables['conversionid']);
	unset($variables['trackerid']);
	
	phpAds_dbConnect() or die("didnt connect");
	
	foreach($variables as $variableid=>$value)
	{

		$value = ($value != 'undefined' ? "'".$value."'" : 'NULL' );

		$query = ("INSERT ".($phpAds_config['insert_delayed'] ? 'DELAYED' : '')." INTO ".$phpAds_config['tbl_variablevalues']."
				(variableid,
				 value,
				 conversionsid)
			VALUES
				(".$variableid.",
				".$value.",
				'".$conversionid."')"
	 	);
		
		phpAds_dbQuery($query);

	}
}

?>