<?php // $Revision: 2.1 $

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
require ("lib-zones.inc.php");


// Register input variables
phpAds_registerGlobal ('returnurl');


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency + phpAds_Affiliate);



/*********************************************************/
/* Main code                                             */
/*********************************************************/

if (isset($zoneid) && $zoneid != '')
{
	if (phpAds_isUser(phpAds_Affiliate))
	{
		$result = phpAds_dbQuery("
			SELECT
				affiliateid
			FROM
				".$phpAds_config['tbl_zones']."
			WHERE
				zoneid = '$zoneid'
			") or phpAds_sqlDie();
		$row = phpAds_dbFetchArray($result);
		
		if ($row["affiliateid"] == '' || phpAds_getUserID() != $row["affiliateid"] || !phpAds_isAllowed(phpAds_DeleteZone))
		{
			phpAds_PageHeader("1");
			phpAds_Die ($strAccessDenied, $strNotAdmin);
		}
		else
		{
			$affiliateid = $row["affiliateid"];
		}
	}
	elseif (phpAds_isUser(phpAds_Agency))
	{
		$query = "SELECT z.affiliateid AS affiliateid".
			" FROM ".$phpAds_config['tbl_zones']." AS z".
			",".$phpAds_config['tbl_affiliates']." AS a".
			" WHERE z.affiliateid = a.affiliateid".
			" AND a.agencyid=".phpAds_getUserID();
	
		$res = phpAds_dbQuery($query) or phpAds_sqlDie();
		if (phpAds_dbNumRows($res) == 0)
		{
			phpAds_PageHeader("2");
			phpAds_Die ($strAccessDenied, $strNotAdmin);
		}
	}
	
	
	// Reset append codes which called this zone
	if (phpAds_isUser(phpAds_Admin))
	{
		$query = "SELECT zoneid,append".
			" FROM ".$phpAds_config['tbl_zones'].
			" WHERE appendtype=".phpAds_ZoneAppendZone;
	}
	elseif (phpAds_isUser(phpAds_Agency))
	{
		$query = "SELECT z.zoneid AS zoneid".
			",z.append AS append".
			" FROM ".$phpAds_config['tbl_zones']." AS z".
			",".$phpAds_config['tbl_affiliates']." AS a".
			" WHERE z.affiliateid = a.affiliateid".
			" AND a.agencyid=".phpAds_getUserID().
			" AND appendtype=".phpAds_ZoneAppendZone;
	}
	
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$append = phpAds_ZoneParseAppendCode($row['append']);

		if ($append[0]['zoneid'] == $zoneid)
		{
			phpAds_dbQuery("
					UPDATE
						".$phpAds_config['tbl_zones']."
					SET
						appendtype = ".phpAds_ZoneAppendRaw.",
						append = ''
					WHERE
						zoneid =".$row['zoneid']."
				");
		}
	}
	
	
	// Delete zone
	$res = phpAds_dbQuery("
		DELETE FROM
			".$phpAds_config['tbl_zones']."
		WHERE
			zoneid=$zoneid
		") or phpAds_sqlDie();
}

if (!isset($returnurl) && $returnurl == '')
	$returnurl = 'affiliate-zones.php';

Header("Location: ".$returnurl."?affiliateid=$affiliateid");

?>