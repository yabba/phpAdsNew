<?php // $Revision: 1.2 $

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
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);

if (phpAds_isUser(phpAds_Agency))
{
	$res = phpAds_dbQuery(
		"SELECT clientid".
		" FROM ".$phpAds_config['tbl_clients']." AS c".
		",".$phpAds_config['tbl_trackers']." AS t".
		" WHERE t.trackerid=c.trackterid".
		" AND t.trackerid=".$trackerid.
		" AND c.agencyid=".phpAds_getUserID()
	) or phpAds_sqlDie();
	
	if (phpAds_dbNumRows($res) == 0)
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}


/*********************************************************/
/* Main code                                             */
/*********************************************************/

function phpAds_DeleteTracker($trackerid)
{
	global $phpAds_config;
	
	// Delete Campaign
	$res = phpAds_dbQuery("DELETE FROM ".$phpAds_config['tbl_trackers'].
		" WHERE trackerid=".$trackerid
	) or phpAds_sqlDie();
	
	// Delete Campaign/Tracker links
	$res = phpAds_dbQuery("DELETE FROM ".$phpAds_config['tbl_campaigns_trackers'].
		" WHERE trackerid=".$trackerid
	) or phpAds_sqlDie();
	
	// Delete Conversions Logged to this Tracker
	$res = phpAds_dbQuery("DELETE FROM ".$phpAds_config['tbl_conversionlog'].
		" WHERE trackerid=".$trackerid
	) or phpAds_sqlDie();
	
	// Delete stats for each banner
	phpAds_deleteStatsByTrackerID($trackerid);
}


if (isset($trackerid) && $trackerid != '')
{
	// Campaign is specified, delete only this campaign
	phpAds_DeleteTracker($trackerid);
}
elseif (isset($clientid) && $clientid != '')
{
	// No campaign specified, delete all trackers for this client
	$res_trackers = phpAds_dbQuery("
		SELECT
			trackerid
		FROM
			".$phpAds_config['tbl_trackers']."
		WHERE
			clientid = ".$clientid."
	");
	
	while ($row = phpAds_dbFetchArray($res_trackers))
	{
		phpAds_DeleteTracker($row['trackerid']);
	}
}


if (!isset($returnurl) && $returnurl == '')
	$returnurl = 'advertiser-trackers.php';

header ("Location: ".$returnurl."?clientid=".$clientid);

?>