<?php // $Revision: 1.1 $

/************************************************************************/
/* phpAdsNew 2                                                          */
/* ===========                                                          */
/*                                                                      */
/* Copyright (c) 2001 by the phpAdsNew developers                       */
/* http://sourceforge.net/projects/phpadsnew                            */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/



// Include required files
require ("config.php");
require ("lib-storage.inc.php");
require ("lib-zones.inc.php");


// Security check
phpAds_checkAccess(phpAds_Admin);



/*********************************************************/
/* Main code                                             */
/*********************************************************/

if (isset($bannerid) && $bannerid != '')
{
	if (isset($moveto) && $moveto != '')
	{
		// Move the banner
		$res = phpAds_dbQuery("UPDATE ".$phpAds_config['tbl_banners']." SET clientid = '".$moveto."' WHERE bannerid = '".$bannerid."'") or phpAds_sqlDie();
		
		// Rebuild zone cache
		if ($phpAds_config['zone_cache'])
			phpAds_RebuildZoneCache ();
		
		Header ("Location: ".$returnurl."?campaignid=".$moveto."&bannerid=".$bannerid);
	}
	elseif (isset($duplicate) && $duplicate == 'true')
	{
		// Duplicate the banner
	}
}

?>