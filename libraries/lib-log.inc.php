<?php // $Revision: 2.12 $

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
/* Check if host has to be ignored                       */
/*********************************************************/

function phpads_logCheckHost()
{
	global $phpAds_config;
	global $HTTP_SERVER_VARS;
	
	if (count($phpAds_config['ignore_hosts']))
	{
		$hosts = "#(".implode ('|',$phpAds_config['ignore_hosts']).")$#i";
		
		if ($hosts != '')
		{
			$hosts = str_replace (".", '\.', $hosts);
			$hosts = str_replace ("*", '[^.]+', $hosts);
			
			if (preg_match($hosts, $HTTP_SERVER_VARS['REMOTE_ADDR']))
				return false;
			
			if (preg_match($hosts, $HTTP_SERVER_VARS['REMOTE_HOST']))
				return false;
		}
	}
	
	return true; //$HTTP_SERVER_VARS['REMOTE_HOST'];
}



/*********************************************************/
/* Log an impression                                     */
/*********************************************************/

function phpAds_logImpression ($userid, $bannerid, $zoneid, $source)
{
	global $HTTP_SERVER_VARS, $phpAds_config, $phpAds_geo;
	
	//decrypt source
	$source = phpAds_decrypt($source);

	// Check if host is on list of hosts to ignore
	if ($host = phpads_logCheckHost())
	{
		$log_source = $phpAds_config['log_source'] ? $source : '';
		
		$log_country = $phpAds_config['geotracking_stats'] && $phpAds_geo && $phpAds_geo['country'] ? $phpAds_geo['country'] : '';
		$log_host    = $phpAds_config['log_hostname'] ? $HTTP_SERVER_VARS['REMOTE_HOST'] : '';
		$log_host    = $phpAds_config['log_iponly'] ? $HTTP_SERVER_VARS['REMOTE_ADDR'] : $log_host;
		
		phpAds_dbQuery(
			"INSERT ".($phpAds_config['insert_delayed'] ? 'DELAYED' : '')." INTO ".$phpAds_config['tbl_adviews'].
			"(userid".
			",bannerid".
			",zoneid".
			",host".
			",source".
			",country)".
			" VALUES ".
			"('".$userid."'".
			",".$bannerid.
			",".$zoneid.
			",'".$log_host."'".
			",'".$source."'".
			",'".$log_country."')"
		);
		
	}
}

/*********************************************************/
/* Log a click                                          */
/*********************************************************/

function phpAds_logClick($userid, $bannerid, $zoneid, $source)
{
	global $HTTP_SERVER_VARS, $phpAds_config, $phpAds_geo;
	
	//decrypt source
	$source = phpAds_decrypt($source);

	// Check if host is on list of hosts to ignore
	if ($host = phpads_logCheckHost())
	{
		$log_source = $phpAds_config['log_source'] ? $source : '';
		
		$log_country = $phpAds_config['geotracking_stats'] && $phpAds_geo && $phpAds_geo['country'] ? $phpAds_geo['country'] : '';
		$log_host    = $phpAds_config['log_hostname'] ? $HTTP_SERVER_VARS['REMOTE_HOST'] : '';
		$log_host    = $phpAds_config['log_iponly'] ? $HTTP_SERVER_VARS['REMOTE_ADDR'] : $log_host;
		
		phpAds_dbQuery(
			"INSERT ".($phpAds_config['insert_delayed'] ? 'DELAYED' : '')." INTO ".$phpAds_config['tbl_adclicks'].
			"(userid".
			",bannerid".
			",zoneid".
			",host".
			",source".
			",country)".
			" VALUES ".
			"('".$userid."'".
			",".$bannerid.
			",".$zoneid.
			",'".$log_host."'".
			",'".$source."'".
			",'".$log_country."')"
		);
	}
}

/*********************************************************/
/* Log a conversion                                      */
/*********************************************************/

function phpAds_logConversion($userid, $trackerid)
{
	global $HTTP_SERVER_VARS, $phpAds_config, $phpAds_geo;
	
	// Check if host is on list of hosts to ignore
	if ($host = phpads_logCheckHost())
	{
		$log_country = $phpAds_config['geotracking_stats'] && $phpAds_geo && $phpAds_geo['country'] ? $phpAds_geo['country'] : '';
		$log_host    = $phpAds_config['log_hostname'] ? $HTTP_SERVER_VARS['REMOTE_HOST'] : '';
		$log_host    = $phpAds_config['log_iponly'] ? $HTTP_SERVER_VARS['REMOTE_ADDR'] : $log_host;
		
		phpAds_dbQuery(
			"INSERT ".($phpAds_config['insert_delayed'] ? 'DELAYED' : '')." INTO ".$phpAds_config['tbl_adconversions'].
			"(userid".
			",trackerid".
			",host".
			",country)".
			" VALUES ".
			"('".$userid."'".
			",".$trackerid.
			",'".$log_host."'".
			",'".$log_country."')"
		);
		
		return phpAds_dbInsertID();
	}
	
}

?>
