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
require ("lib-banner.inc.php");


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);



/*********************************************************/
/* Main code                                             */
/*********************************************************/

if (phpAds_isUser(phpAds_Admin))
{
	$query = "SELECT *".
		" FROM ".$phpAds_config['tbl_banners'];
}
elseif (phpAds_isUser(phpAds_Agency))
{
	$query = "SELECT *".
		" FROM ".$phpAds_config['tbl_banners']." AS b".
		",".$phpAds_config['tbl_campaigns']." AS m".
		",".$phpAds_config['tbl_clients']." AS c".
		" WHERE b.campaignid=m.campaignid".
		" AND m.clientid=c.clientid".
		" AND c.agencyid=".phpAds_getUserID();
}
$res = phpAds_dbQuery($query);

while ($current = phpAds_dbFetchArray($res))
{
	// Rebuild filename
	if ($current['storagetype'] == 'sql')
		$current['imageurl'] = "{url_prefix}/adimage.php?filename=".$current['filename']."&amp;contenttype=".$current['contenttype'];
	
	if ($current['storagetype'] == 'web')
		$current['imageurl'] = '{image_url_prefix}/'.$current['filename'];
	
	
	// Add slashes to status to prevent javascript errors
	// NOTE: not needed in banner-edit because of magic_quotes_gpc
	$current['status'] = addslashes($current['status']);
	
	
	// Rebuild cache
	$current['htmltemplate'] = stripslashes($current['htmltemplate']);
	$current['htmlcache']    = addslashes(phpAds_getBannerCache($current));
	
	phpAds_dbQuery(
		"UPDATE ".$phpAds_config['tbl_banners'].
		" SET htmlcache='".$current['htmlcache']."'".
		",imageurl='".$current['imageurl']."'".
		" WHERE bannerid=".$current['bannerid']
	);
}

Header("Location: maintenance-banners.php");

?>