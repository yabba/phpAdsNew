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
require ("lib-statistics.inc.php");
require ("lib-expiration.inc.php");


// Register input variables
phpAds_registerGlobal ('period', 'start', 'limit');


// Security check
phpAds_checkAccess(phpAds_Admin+phpAds_Client);



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (isset($Session['prefs']['stats-advertiser-campaigns.php']['listorder']))
	$navorder = $Session['prefs']['stats-advertiser-campaigns.php']['listorder'];
else
	$navorder = '';

if (isset($Session['prefs']['stats-advertiser-campaigns.php']['orderdirection']))
	$navdirection = $Session['prefs']['stats-advertiser-campaigns.php']['orderdirection'];
else
	$navdirection = '';


if (phpAds_isUser(phpAds_Client))
{
	if (phpAds_getUserID() == phpAds_getCampaignParentClientID ($campaignid))
	{
		$res = phpAds_dbQuery(
			"SELECT *".
			" FROM ".$phpAds_config['tbl_campaigns'].
			" WHERE clientid= ".phpAds_getUserID().
			phpAds_getCampaignListOrder ($navorder, $navdirection)
		) or phpAds_sqlDie();
		
		while ($row = phpAds_dbFetchArray($res))
		{
			phpAds_PageContext (
				phpAds_buildName ($row['campaignid'], $row['campaignname']),
				"stats-campaign-target.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
				$campaignid == $row['campaignid']
			);
		}
		
		phpAds_PageHeader("1.2.3");
			echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".phpAds_getCampaignName($campaignid)."</b><br><br><br>";
			phpAds_ShowSections(array("1.2.1", "1.2.2", "1.2.3"));
	}
	else
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}

if (phpAds_isUser(phpAds_Admin))
{
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE clientid=".$clientid.
		phpAds_getCampaignListOrder ($navorder, $navdirection)
	) or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		phpAds_PageContext (
			phpAds_buildName ($row['campaignid'], $row['campaignname']),
			"stats-campaign-target.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
			$campaignid == $row['campaignid']
		);
	}
	
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	phpAds_PageShortcut($strCampaignProperties, 'campaign-edit.php?clientid='.$clientid.'&campaignid='.$campaignid, 'images/icon-campaign.gif');
	
	phpAds_PageHeader("2.1.2.3");
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getParentClientName($campaignid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".phpAds_getCampaignName($campaignid)."</b><br><br><br>";
		phpAds_ShowSections(array("2.1.2.1", "2.1.2.2", "2.1.2.3"));
}



/*********************************************************/
/* Main code                                             */
/*********************************************************/

	$lib_targetstats_params    = array ('clientid' => $clientid, 'campaignid' => $campaignid);
	$lib_targetstats_where			= "clientid = '".$campaignid."'";
	
	include ("lib-targetstats.inc.php");


/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>