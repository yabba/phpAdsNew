<?php // $Revision: 1.4 $

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
require ("lib-data-statistics.inc.php");

// Register input variables
phpAds_registerGlobal (
	 '_id_'
	,'expand'
	,'campaignshidden'
	,'collapse'
	,'hideinactive'
	,'listorder'
	,'orderdirection'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency + phpAds_Client);


/*********************************************************/
/* Client interface security                             */
/*********************************************************/

if (phpAds_isUser(phpAds_Client))
{
	$clientid = phpAds_getUserID();
}
elseif (phpAds_isUser(phpAds_Agency))
{
	if (isset($clientid) && ($clientid != ''))
	{
		$query = "SELECT clientid".
			" FROM ".$phpAds_config['tbl_clients'].
			" WHERE clientid=".$clientid.
			" AND agencyid=".phpAds_getUserID();

		$res = phpAds_dbQuery($query) or phpAds_sqlDie();
		if (phpAds_dbNumRows($res) == 0)
		{
			phpAds_PageHeader("2");
			phpAds_Die ($strAccessDenied, $strNotAdmin);
		}
	}
}


/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (isset($Session['prefs']['stats-advertiser-campaigns.php']['listorder']) && !isset($listorder))
	$listorder = $Session['prefs']['stats-advertiser-campaigns.php']['listorder'];
//else
	//$listorder = '';
	
if (isset($Session['prefs']['stats-advertiser-campaigns.php']['orderdirection']) && !isset($orderdirection))
	$orderdirection = $Session['prefs']['stats-advertiser-campaigns.php']['orderdirection'];
//else
	//$orderdirection = '';
	
if (isset($Session['prefs']['stats-advertiser-campaigns.php']['results']))
	$array = $Session['prefs']['stats-advertiser-campaigns.php']['results'];
	
if (isset($Session['prefs']['stats-advertiser-campaigns.php']['hide']) && !isset($hideinactive))
	$hideinactive = $Session['prefs']['stats-advertiser-campaigns.php']['hide'];


if (phpAds_isUser(phpAds_Admin))
{
	$query = "SELECT clientid,clientname FROM ".$phpAds_config['tbl_clients'];
}
elseif (phpAds_isUser(phpAds_Agency))
{
	$query = "SELECT clientid,clientname FROM ".$phpAds_config['tbl_clients']." WHERE agencyid=".phpAds_getUserID();
}
elseif (phpAds_isUser(phpAds_Client))
{
	$query = "SELECT clientid,clientname FROM ".$phpAds_config['tbl_clients']." WHERE clientid=".phpAds_getUserID();
}
$res = phpAds_dbQuery($query)
	or phpAds_sqlDie();
	
while ($row = phpAds_dbFetchArray($res))
{
		phpAds_PageContext (
			phpAds_buildName ($row['clientid'], $row['clientname']),
			"stats-advertiser-campaigns.php?clientid=".$row['clientid'],
			$clientid == $row['clientid']
		);
}


if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
{
	
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	
	if (phpAds_isUser(phpAds_Admin)) {
		$extra  = "<br><br><br>";
		$extra .= "<b>$strMaintenance</b><br>";
		$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
		$extra .= "<a href='stats-reset.php?clientid=$clientid'".phpAds_DelConfirm($strConfirmResetClientStats).">";
		$extra .= "<img src='images/".$phpAds_TextDirection."/icon-undo.gif' align='absmiddle' border='0'>&nbsp;$strResetStats</a>";
		$extra .= "<br><br>";
	}	
	
	phpAds_PageHeader("2.1.2", $extra);
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;<b>".phpAds_getClientName($clientid)."</b><br><br><br>";
		phpAds_ShowSections(array("2.1.1", "2.1.2"));
}


if (phpAds_isUser(phpAds_Client))
{
	phpAds_PageHeader("1.2");
	
	if ($phpAds_config['client_welcome'])
	{
		echo "<br><br>";
		// Show welcome message
		if (!empty($phpAds_client_welcome_msg))
			echo $phpAds_client_welcome_msg;
		else
			include('templates/welcome-advertiser.html');
		echo "<br><br>";
	}
	
	phpAds_ShowSections(array("1.1", "1.2"));
}


/*********************************************************/
/* Main code                                             */
/*********************************************************/
if (!isset($array) || $Session['prefs']['stats-affiliate-zones.php']['clientid'] != $clientid)
//if (true)
{
	$res_campaigns = phpAds_dbQuery(
		"SELECT 
			campaignid as id,
			campaignname as name,
			clientid,
			views,
			clicks,
			conversions,
			active,
			anonymous
		FROM "
			.$phpAds_config['tbl_campaigns'].
		" WHERE "
			."clientid=".$clientid
	) or phpAds_sqlDie();

	$campaign_index = 0;
	$array = array();
	while ($row_campaigns = phpAds_dbFetchArray($res_campaigns))
	{
		$array[$campaign_index] 			= $row_campaigns;
		$array[$campaign_index]['expand'] 	= FALSE;
		$array[$campaign_index]['count'] 	= 0;
		$array[$campaign_index]['kind'] 	= 'campaign';

		$campaign_index++;
	}


	// Get the banners for each campaign
	$res_banners = phpAds_dbQuery("
	SELECT 
			b.bannerid as id,
			b.campaignid as campaignid,
			b.alt as alt,
			b.description as description,
			b.active as active,
			b.storagetype as storagetype
		FROM ".
		$phpAds_config['tbl_banners']." AS b,".
		$phpAds_config['tbl_campaigns']." AS c".
		" WHERE b.campaignid=c.campaignid".
		" AND c.clientid=".$clientid
	) or phpAds_sqlDie();

	while ($row_banners = phpAds_dbFetchArray($res_banners))
	{
		foreach ($array as $ckey=>$_array_)
		{
			if ($_array_['id'] == $row_banners['campaignid'])
	{

				$banner_index = isset($array[$ckey]['children']) ?  sizeof($array[$ckey]['children']) : 0;
				
				$array[$ckey]['children'][$banner_index] 					= $row_banners;
				$array[$ckey]['children'][$banner_index]['name'] 			= $array[$ckey]['children'][$banner_index]['id'];
				$array[$ckey]['children'][$banner_index]['clicks'] 			= 0;
				$array[$ckey]['children'][$banner_index]['views'] 			= 0;			
				$array[$ckey]['children'][$banner_index]['conversions'] 	= 0;
				$array[$ckey]['children'][$banner_index]['kind']			= 'banner';
				$array[$ckey]['count']++;
			}
		}
	}


	// Get the adviews/clicks for each banner
	$res_stats = phpAds_dbQuery("
	SELECT
		s.bannerid as bannerid,
		b.campaignid as campaignid,
		sum(s.views) as views,
			sum(s.clicks) as clicks,
			sum(s.conversions) as conversions
	FROM 
		".$phpAds_config['tbl_adstats']." as s,
			".$phpAds_config['tbl_banners']." as b,
			".$phpAds_config['tbl_campaigns']." as c
	WHERE
		b.bannerid = s.bannerid
		AND b.campaignid=c.campaignid
		AND c.clientid=".$clientid."
			
	GROUP BY
		s.bannerid
	") or phpAds_sqlDie();

	while ($row_stats = phpAds_dbFetchArray($res_stats))
	{
		foreach ($array as $ckey=>$campaigns)
	{
	
			if (isset($campaigns['children']))
			{
				foreach($campaigns['children'] as $bkey=>$banners)
				{
					if ($banners['id'] == $row_stats['bannerid'])
					{
						$array[$ckey]['children'][$bkey]['clicks']		= $row_stats['clicks'];
						$array[$ckey]['children'][$bkey]['views']		= $row_stats['views'];
						$array[$ckey]['children'][$bkey]['conversions']	= $row_stats['conversions'];
					}
				}
			}			
		}
	}
}
//-----------------------------------------------------------------------------------------------------------------------
// Handle expanding and collapsing of nodes
//-----------------------------------------------------------------------------------------------------------------------

if (isset($expand) && isset($_id_)) {

	$ids = explode('-', $_id_);
	
	foreach($array as $zkey=>$zone) {
		if ($zone['id'] == $ids[0])	{
			$array[$zkey]['expand'] = TRUE;
		
			if (isset($ids[1]))
				foreach($array[$zkey]['children'] as $ckey=>$campaign)
					if ($array[$zkey]['children'][$ckey]['id'] == $ids[1])
						$array[$zkey]['children'][$ckey]['expand'] = TRUE;
						break;
		}
	}
} else if (isset($collapse) && isset($_id_)) {

	$ids = explode('-', $_id_);

	foreach($array as $zkey=>$zone)	{
		if ($zone['id'] == $ids[0]) {
			if (isset($ids[1]))	{
				foreach($array[$zkey]['children'] as $ckey=>$campaign)
					if ($array[$zkey]['children'][$ckey]['id'] == $ids[1])
						$array[$zkey]['children'][$ckey]['expand'] = FALSE;
			} else 
				$array[$zkey]['expand'] = FALSE;
						
						break;
		}
	}
} else if (isset($expand)) {

	switch($expand)	{
		case 'all' :	foreach($array as $zkey=>$zone)	{
							$array[$zkey]['expand'] = TRUE;

							if (isset($array[$zkey]['children']))
								foreach($array[$zkey]['children'] as $ckey=>$campaign)
									$array[$zkey]['children'][$ckey]['expand'] = TRUE;
						}
						
						break;
						
		case 'none':	foreach($array as $zkey=>$zone)	{
							$array[$zkey]['expand'] = FALSE;

							if (isset($array[$zkey]['children']))
								foreach($array[$zkey]['children'] as $ckey=>$campaign)
									$array[$zkey]['children'][$ckey]['expand'] = FALSE;
	}

						break;

		default:		break;
	}
}

//-----------------------------------------------------------------------------------------------------------------------
// Sort array according to selected column and direction
//-----------------------------------------------------------------------------------------------------------------------
switch ($listorder)
{
	case 'name': 		phpAds_sortArray($array,'name',($orderdirection == 'up' ? TRUE : FALSE));
						break;

	case 'id': 			phpAds_sortArray($array,'id',($orderdirection == 'up' ? TRUE : FALSE));
						break;

	case 'views': 		phpAds_sortArray($array,'views',($orderdirection == 'up' ? TRUE : FALSE));
						break;

	
	case 'clicks': 		phpAds_sortArray($array,'clicks',($orderdirection == 'up' ? TRUE : FALSE));
						break;

		
	case 'CTR': 		phpAds_sortArray($array,'CTR',($orderdirection == 'up' ? TRUE : FALSE));
						break;
					
					
	case 'conversions': phpAds_sortArray($array,'conversions',($orderdirection == 'up' ? TRUE : FALSE));
						break;


	case 'CNVR': 		phpAds_sortArray($array,'CNVR',($orderdirection == 'up' ? TRUE : FALSE));
						break;
						
					
	default:	break;

}
//-----------------------------------------------------------------------------------------------------------------------
// Sort array according to selected column and direction END
//-----------------------------------------------------------------------------------------------------------------------

$totalviews = 0;
$totalclicks = 0;
$totalconversions 	= 0;

if (isset($array) && is_array($array) && sizeof ($array) > 0)
{
	// Calculate statistics for campaigns
	foreach($array as $ckey=>$campaign)
	{
		$campaignviews = 0;
		$campaignclicks = 0;
		$campaignconversions = 0;
		
		if ($hideinactive == true && $campaign['active'] == 'f') 
			isset($campaignshidden) ? $campaignshidden++ : $campaignshidden = 1;
		else
		{
			if (isset($campaign['children']) && sizeof ($campaign['children']) > 0)
		{
				foreach($campaign['children'] as $bkey=>$banner)
			{
					$campaignviews 			+= $banner['views'];
					$campaignclicks 		+= $banner['clicks'];
					$campaignconversions 	+= $banner['conversions'];
			}
		}
		
		$totalviews += $campaignviews;
		$totalclicks += $campaignclicks;
			$totalconversions += $campaignconversions;
	}
	
		$array[$ckey]['views'] = $campaignviews;
		$array[$ckey]['clicks'] = $campaignclicks;
		$array[$ckey]['conversions'] = $campaignconversions;
	}
}

if ($campaignshidden > 0 || $totalviews > 0 || $totalclicks > 0)
{
	echo "<br><br>";
	echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";	
	
//-----------------------------------------------------------------------------------------------------------------------
//Column headers
//-----------------------------------------------------------------------------------------------------------------------
// Column delimiters. Prevents columns from randomly changing width
echo '<tr height="25">';
echo '<td><img src="images/spacer.gif" width="200" height="1" border="0" alt="" title=""></td>';
echo '<td><img src="images/spacer.gif" width="80" height="1" border="0" alt="" title=""></td>';
echo '<td><img src="images/spacer.gif" width="80" height="1" border="0" alt="" title=""></td>';
echo '<td><img src="images/spacer.gif" width="80" height="1" border="0" alt="" title=""></td>';
echo '<td><img src="images/spacer.gif" width="80" height="1" border="0" alt="" title=""></td>';
echo '<td><img src="images/spacer.gif" width="80" height="1" border="0" alt="" title=""></td>';
echo '<td><img src="images/spacer.gif" width="80" height="1" border="0" alt="" title=""></td>';
echo '</tr>';

echo '<tr height="25">';
// Name column
echo '<td height="25"><b>&nbsp;&nbsp;<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&listorder=name">'.$GLOBALS['strName'].'</a>';
if ($listorder == "name" || $listorder == "")
	echo $orderdirection == "up" 
		? ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// ID column
echo '<td height="25"><b><a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&listorder=id">'.$GLOBALS['strID'].'</a>';
if ($listorder == "id")
	echo $orderdirection == "up" 
		? ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Views column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='".$HTTP_SERVER_VARS['PHP_SELF']."?clientid=".$clientid."&listorder=views'>".$GLOBALS['strViews'].'</a>';
if ($listorder == "views")
	echo $orderdirection == "up" 
		? ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Clicks column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='".$HTTP_SERVER_VARS['PHP_SELF']."?clientid=".$clientid."&listorder=clicks'>".$GLOBALS['strClicks'].'</a>';
if ($listorder == "clicks")
	echo $orderdirection == "up" 
		? ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// CTR column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='".$HTTP_SERVER_VARS['PHP_SELF']."?clientid=".$clientid."&listorder=ctr'>".$GLOBALS['strCTRShort'].'</a>';
if ($listorder == "ctr")
	echo $orderdirection == "up" 
		? ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Conversion column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='".$HTTP_SERVER_VARS['PHP_SELF']."?clientid=".$clientid."&listorder=conversions'>".$GLOBALS['strConversions'].'</a>';
if ($listorder == "conversions")
	echo $orderdirection == "up" 
		? ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Sales Ration colum
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='".$HTTP_SERVER_VARS['PHP_SELF']."?clientid=".$clientid."&listorder=CNVR'>".$GLOBALS['strCNVRShort'].'</a>&nbsp;&nbsp;';
if ($listorder == "CNVR")
	echo $orderdirection == "up" 
		? ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="'.$HTTP_SERVER_VARS['PHP_SELF'].'?clientid='.$clientid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
echo "</tr>";
//-----------------------------------------------------------------------------------------------------------------------
//Column headers END
//-----------------------------------------------------------------------------------------------------------------------
		
echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
		
		
	$i = 0;
	rowPresenter($array, $i++, 0, '', true, $clientid,'');
				
	
	// Total
	echo "<tr height='25' ".($i % 2 == 0 ? "bgcolor='#F6F6F6'" : "")."><td height='25'>&nbsp;&nbsp;<b>".$strTotal."</b></td>";
	echo "<td height='25'>&nbsp;</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalviews)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalclicks)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalviews, $totalclicks)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalconversions)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".number_format(($totalclicks ? $totalconversions / $totalclicks * 100 : 0), $phpAds_config['percentage_decimals'], $phpAds_DecimalPoint, $phpAds_ThousandsSeperator)."%&nbsp;&nbsp;</td>";
	echo "</tr>";
	echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	echo "<tr height='25'><td colspan='2' height='25' align='".$phpAds_TextAlignLeft."' nowrap>";
	
	if ($hideinactive == true)
	{
		echo "&nbsp;&nbsp;<img src='images/icon-activate.gif' align='absmiddle' border='0'>";
		echo "&nbsp;<a href='stats-advertiser-campaigns.php?clientid=".$clientid."&hideinactive=0'>".$strShowAll."</a>";
		echo "&nbsp;&nbsp;|&nbsp;&nbsp;".$campaignshidden." ".$strInactiveCampaignsHidden;
	}
	else
	{
		echo "&nbsp;&nbsp;<img src='images/icon-hideinactivate.gif' align='absmiddle' border='0'>";
		echo "&nbsp;<a href='stats-advertiser-campaigns.php?clientid=".$clientid."&hideinactive=1'>".$strHideInactiveCampaigns."</a>";
	}
	
	echo "</td>";
	echo "<td colspan='5' height='25' align='".$phpAds_TextAlignRight."' nowrap>";
	echo "<img src='images/triangle-d.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-advertiser-campaigns.php?clientid=".$clientid."&expand=all' accesskey='".$keyExpandAll."'>".$strExpandAll."</a>";
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
	echo "<img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-advertiser-campaigns.php?clientid=".$clientid."&expand=none' accesskey='".$keyCollapseAll."'>".$strCollapseAll."</a>&nbsp;&nbsp;";
	echo "</td>";
	echo "</tr>";
	
	
	echo "</table>";
	echo "<br><br><br><br>";
}
else
{
	echo "<br><div class='errormessage'><img class='errormessage' src='images/info.gif' width='16' height='16' border='0' align='absmiddle'>";
	echo $strNoStats.'</div>';
}

/*********************************************************/
/* Store preferences                                     */
/*********************************************************/
$Session['prefs']['stats-advertiser-campaigns.php']['listorder'] = $listorder;
$Session['prefs']['stats-advertiser-campaigns.php']['orderdirection'] = $orderdirection;
//$Session['prefs']['stats-advertiser-campaigns.php']['results'] 			= $array;
$Session['prefs']['stats-advertiser-campaigns.php']['hide'] 			= $hideinactive;
$Session['prefs']['stats-affiliate-zones.php']['clientid']				= $clientid;

phpAds_SessionDataStore();

/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>