<?php // $Revision: 2.2 $

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


// Register input variables
phpAds_registerGlobal ('moveto', 'returnurl');


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);


/*********************************************************/
/* Main code                                             */
/*********************************************************/

if (isset($campaignid) && $campaignid != '')
{
	if (isset($moveto) && $moveto != '')
	{
		if (phpAds_isUser(phpAds_Agency))
		{
			$query = "SELECT c.clientid".
				" FROM ".$phpAds_config['tbl_clients']." AS c".
				",".$phpAds_config['tbl_campaigns']." AS m".
				" WHERE c.clientid=m.clientid".
				" AND c.clientid=".$clientid.
				" AND m.campaignid=".$campaignid.
				" AND agencyid=".phpAds_getUserID();
			$res = phpAds_dbQuery($query) or phpAds_sqlDie();
			if (phpAds_dbNumRows($res) == 0)
			{
				phpAds_PageHeader("2");
				phpAds_Die ($strAccessDenied, $strNotAdmin);
			}
			$query = "SELECT c.clientid".
				" FROM ".$phpAds_config['tbl_clients']." AS c".
				" WHERE c.clientid=".$moveto.
				" AND agencyid=".phpAds_getUserID();
			$res = phpAds_dbQuery($query) or phpAds_sqlDie();
			if (phpAds_dbNumRows($res) == 0)
			{
				phpAds_PageHeader("2");
				phpAds_Die ($strAccessDenied, $strNotAdmin);
			}
		}
		
		// Delete any campaign-tracker links
		$res = phpAds_dbQuery(
			"DELETE FROM ".$phpAds_config['tbl_campaigns_trackers'].
			" WHERE campaignid=".$campaignid
		) or phpAds_sqlDie();

		// Move the campaign
		$res = phpAds_dbQuery(
			"UPDATE ".$phpAds_config['tbl_campaigns'].
			" SET clientid=".$moveto.
			" WHERE campaignid=".$campaignid
		) or phpAds_sqlDie();
		
		// Rebuild cache
		if (!defined('LIBVIEWCACHE_INCLUDED')) 
			include (phpAds_path.'/libraries/deliverycache/cache-'.$phpAds_config['delivery_caching'].'.inc.php');
		
		phpAds_cacheDelete();
	}
}

Header ("Location: ".$returnurl."?clientid=".$moveto."&campaignid=".$campaignid);

?>