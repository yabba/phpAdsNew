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



// Include required files
require ("config.php");
require ("lib-storage.inc.php");
require ("lib-zones.inc.php");
require ("lib-statistics.inc.php");
require ("../libraries/lib-priority.inc.php");


// Register input variables
phpAds_registerGlobal ('returnurl');


// Security check
phpAds_checkAccess(phpAds_Admin);



/*********************************************************/
/* Main code                                             */
/*********************************************************/

function phpAds_DeleteCampaign($campaignid)
{
	global $phpAds_config;
	
	// Delete Campaign
	$res = phpAds_dbQuery("DELETE FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE campaignid=".$campaignid
	) or phpAds_sqlDie();
	
	// Delete Campaign/Tracker links
	$res = phpAds_dbQuery("DELETE FROM ".$phpAds_config['tbl_campaigns_trackers'].
		" WHERE campaignid=".$campaignid
	) or phpAds_sqlDie();
	
	// Delete Conversions Logged to this Campaign
	$res = phpAds_dbQuery("DELETE FROM ".$phpAds_config['tbl_conversionlog'].
		" WHERE campaignid=".$campaignid
	) or phpAds_sqlDie();
	
	// Delete any conversion rules to this Campaign
	$res = phpAds_dbQuery("DELETE FROM ".$phpAds_config['tbl_conversionrules'].
		" WHERE campaignid=".$campaignid
	) or phpAds_sqlDie();
	
	
	// Loop through each banner
	$res_banners = phpAds_dbQuery("
		SELECT
			bannerid,
			storagetype,
			filename
		FROM
			".$phpAds_config['tbl_banners']."
		WHERE
			campaignid = '$campaignid'
	") or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res_banners))
	{
		// Cleanup stored images for each banner
		if (($row['storagetype'] == 'web' || $row['storagetype'] == 'sql') && $row['filename'] != '')
			phpAds_ImageDelete ($row['storagetype'], $row['filename']);
		
		
		// Delete Banner ACLs
		phpAds_dbQuery("
			DELETE FROM
				".$phpAds_config['tbl_acls']."
			WHERE
				bannerid = ".$row['bannerid']."
		") or phpAds_sqlDie();
		
		
		// Delete stats for each banner
		phpAds_deleteStatsByBannerID($row['bannerid']);
	}
	
	
	// Delete Banners
	phpAds_dbQuery("
		DELETE FROM
			".$phpAds_config['tbl_banners']."
		WHERE
			campaignid = '$campaignid'
	") or phpAds_sqlDie();
}


if (isset($campaignid) && $campaignid != '')
{
	// Campaign is specified, delete only this campaign
	phpAds_DeleteCampaign($campaignid);
}
elseif (isset($clientid) && $clientid != '')
{
	// No campaign specified, delete all campaigns for this client
	$res_campaigns = phpAds_dbQuery("
		SELECT
			campaignid
		FROM
			".$phpAds_config['tbl_campaigns']."
		WHERE
			clientid = ".$clientid."
	");
	
	while ($row = phpAds_dbFetchArray($res_campaigns))
	{
		phpAds_DeleteCampaign($row['campaignid']);
	}
}


// Rebuild priorities
phpAds_PriorityCalculate ();


// Rebuild cache
if (!defined('LIBVIEWCACHE_INCLUDED')) 
	include (phpAds_path.'/libraries/deliverycache/cache-'.$phpAds_config['delivery_caching'].'.inc.php');

phpAds_cacheDelete();


if (!isset($returnurl) && $returnurl == '')
	$returnurl = 'advertiser-campaigns.php';

header ("Location: ".$returnurl."?clientid=".$clientid);

?>