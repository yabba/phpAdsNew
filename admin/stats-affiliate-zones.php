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
require ("lib-statistics.inc.php");
require ("lib-data-statistics.inc.php");
require ("lib-size.inc.php");
require ("lib-zones.inc.php");


// Register input variables
phpAds_registerGlobal ('expand', 'collapse', '_id_', 'listorder', 'orderdirection', 'screen', 'hideinactive', 'campaignshidden');


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency + phpAds_Affiliate);

/*********************************************************/
/* Affiliate interface security                          */
/*********************************************************/

if (phpAds_isUser(phpAds_Affiliate))
{
	$affiliateid = phpAds_getUserID();
}
elseif (phpAds_isUser(phpAds_Agency))
{
	if (isset($affiliateid) && ($affiliateid != ''))
	{
		$query = "SELECT affiliateid".
			" FROM ".$phpAds_config['tbl_affiliates'].
			" WHERE affiliateid=".$affiliateid.
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
/* Get preferences                                       */
/*********************************************************/

if (!isset($listorder))
{
	if (isset($Session['prefs']['stats-affiliate-zones.php']['listorder']))
		$listorder = $Session['prefs']['stats-affiliate-zones.php']['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['stats-affiliate-zones.php']['orderdirection']))
		$orderdirection = $Session['prefs']['stats-affiliate-zones.php']['orderdirection'];
	else
		$orderdirection = '';
}

if (isset($Session['prefs']['stats-affiliate-zones.php']['nodes']))
	//$node_array = explode (",", $Session['prefs']['stats-affiliate-zones.php']['nodes']);
	$node_array = $Session['prefs']['stats-affiliate-zones.php']['nodes'];
else
	$node_array = array();

if (isset($Session['prefs']['stats-affiliate-zones.php']['results']))
	$array = $Session['prefs']['stats-affiliate-zones.php']['results'];

if (isset($Session['prefs']['stats-affiliate-zones.php']['hide'])&& !isset($hideinactive))
	$hideinactive = $Session['prefs']['stats-affiliate-zones.php']['hide'];


if (isset($Session['prefs']['stats-affiliate-zones.php']['screen']) && !isset($screen))
	$screen = $Session['prefs']['stats-affiliate-zones.php']['screen'];


/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
{
	if (phpAds_isUser(phpAds_Admin))
	{
		$query = "SELECT affiliateid, name".
			" FROM ".$phpAds_config['tbl_affiliates'];
	}
	elseif (phpAds_isUser(phpAds_Agency))
	{
		$query = "SELECT affiliateid, name".
			" FROM ".$phpAds_config['tbl_affiliates'].
			" WHERE agencyid=".phpAds_getUserID();
	}
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		phpAds_PageContext (
			phpAds_buildAffiliateName ($row['affiliateid'], $row['name']),
			"stats-affiliate-zones.php?affiliateid=".$row['affiliateid'],
			$affiliateid == $row['affiliateid']
		);
	}
	
	phpAds_PageShortcut($strAffiliateProperties, 'affiliate-edit.php?affiliateid='.$affiliateid, 'images/icon-affiliate.gif');
	
	phpAds_PageHeader("2.4.2");
		echo "<img src='images/icon-affiliate.gif' align='absmiddle'>&nbsp;<b>".phpAds_getAffiliateName($affiliateid)."</b><br><br><br>";
		phpAds_ShowSections(array("2.4.1", "2.4.2"));
}
else
{
	phpAds_PageHeader("1.1");
	
	if ($phpAds_config['client_welcome'])
	{
		echo "<br><br>";
		// Show welcome message
		if (!empty($phpAds_client_welcome_msg))
			echo $phpAds_client_welcome_msg;
		else
			include('templates/welcome-publisher.html');
		echo "<br><br>";
	}
	
	phpAds_ShowSections(array("1.1", "1.2"));
}


/*********************************************************/
/*														 */
/* Main code                                             */
/*														 */
/*********************************************************/


//-----------------------------------------------------------------------------------------------------------------------
// Build array containing all information
//-----------------------------------------------------------------------------------------------------------------------

// if array is build dont query the DB again
if (!isset($array) || $Session['prefs']['stats-affiliate-zones.php']['affiliateid'] != $affiliateid)
{
	// Get the zones for this particular affiliate
	$result_zones = phpAds_dbQuery("
	SELECT 
			zoneid as id,
			affiliateid, 
			zonename as name, 
			what, 
			delivery
	FROM 
		".$phpAds_config['tbl_zones']."
	WHERE
		affiliateid = '".$affiliateid."'
			
	") or phpAds_sqlDie();


	$zones_index = 0;
	$array = array();

	while ($row_zones = phpAds_dbFetchArray($result_zones))
	{

		// Get id of zone we're working on
		$zoneid								= $row_zones['id'];

		$array[$zones_index]				= $row_zones;
		$array[$zones_index]['views'] 		= 0;
		$array[$zones_index]['clicks']		= 0;
		$array[$zones_index]['conversions']	= 0;
		$array[$zones_index]['CTR']			= 0;
		$array[$zones_index]['CNVR']		= 0;
		$array[$zones_index]['kind']		= 'zone';

		// Get the adviews/clicks for each banner
		$result_banners = phpAds_dbQuery("
	SELECT
						zoneid,
						bannerid,
						sum(views) as views,
						sum(clicks) as clicks,
						sum(conversions) as conversions
	FROM 
						".$phpAds_config['tbl_adstats']."
	WHERE
						zoneid = ".$zoneid." 
	GROUP BY
		zoneid, bannerid
	") or phpAds_sqlDie();

		while ($row_banners = phpAds_dbFetchArray($result_banners))
		{ 
			if (isset($array[$zones_index]))
	{

				$campaigns_index = sizeof($array[$zones_index]['children']);

				$result_campaigns = phpAds_dbQuery("
					SELECT
						b.bannerid AS bannerid,
						b.campaignid,
						c.campaignid AS campaignid,
						c.campaignname AS campaignname,
						c.active AS active, 
						c.anonymous AS anonymous
					FROM 
						".$phpAds_config['tbl_banners']."	AS b,
						".$phpAds_config['tbl_campaigns']." AS c
					WHERE
						b.bannerid = ".$row_banners['bannerid']." AND
						c.campaignid = b.campaignid
				") or phpAds_sqlDie();
				
				$row_campaigns = phpAds_dbFetchArray($result_campaigns);
					
				if (isset($array[$zones_index]['children']))
					foreach ($array[$zones_index]['children'] as $mykey=>$myarray)
	{
						if ($myarray['id'] == $row_campaigns['campaignid']) {
							$campaigns_index = $mykey;
							break;
						}
	}

				$array[$zones_index]['children'][$campaigns_index]['id']										= $row_campaigns['campaignid'];
				$array[$zones_index]['children'][$campaigns_index]['name']										= $row_campaigns['campaignname'];
				$array[$zones_index]['children'][$campaigns_index]['active']									= $row_campaigns['active'];
				$array[$zones_index]['children'][$campaigns_index]['anonymous']									= $row_campaigns['anonymous'];
				$array[$zones_index]['children'][$campaigns_index]['kind']										= 'campaign';

				$banners_index = sizeof($array[$zones_index]['children'][$campaigns_index]['children']);
				
				$array[$zones_index]['children'][$campaigns_index]['children'][$banners_index]['id']			= $row_banners['bannerid'];
				$array[$zones_index]['children'][$campaigns_index]['children'][$banners_index]['clicks'] 		= $row_banners['clicks'];
				$array[$zones_index]['children'][$campaigns_index]['children'][$banners_index]['views'] 		= $row_banners['views'];
				$array[$zones_index]['children'][$campaigns_index]['children'][$banners_index]['conversions']	= $row_banners['conversions'];
				$array[$zones_index]['children'][$campaigns_index]['children'][$banners_index]['anonymous'] 	= $row_campaigns['anonymous'];
				$array[$zones_index]['children'][$campaigns_index]['children'][$banners_index]['kind'] 			= 'banner';				
			}
		}
		
		
		$zones_index++;
		
	}
}
//-----------------------------------------------------------------------------------------------------------------------
// Build array containing all information END
//-----------------------------------------------------------------------------------------------------------------------

//-----------------------------------------------------------------------------------------------------------------------
//Calculate views, clicks and conversions
//-----------------------------------------------------------------------------------------------------------------------
	if (isset($array) && is_array($array) && count($array) > 0)
	{

	$totalviews = 0;
	$totalclicks = 0;
		$totalconversions 	= 0;
		$totalCTR			= 0;
		$totalCNVR			= 0;
	
		foreach ($array as $zkey=>$_zones_)
	{
			$array[$zkey]['clicks'] 		= 0;
			$array[$zkey]['views'] 			= 0;	
			$array[$zkey]['conversions']	= 0;
			$array[$zkey]['CTR']			= 0;
			$array[$zkey]['CNVR']			= 0;
		
			if (isset($array[$zkey]['children']) && sizeof($array[$zkey]['children']) > 0)
			{
				foreach ($array[$zkey]['children'] as $ckey=>$_campaigns_)
		{
					$array[$zkey]['children'][$ckey]['views']			= 0;
					$array[$zkey]['children'][$ckey]['clicks']			= 0;
					$array[$zkey]['children'][$ckey]['conversions']		= 0;
					$array[$zkey]['children'][$ckey]['CTR']				= 0;
					$array[$zkey]['children'][$ckey]['CNVR']			= 0;
			
					if (isset($array[$zkey]['children'][$ckey]['children']) && sizeof ($array[$zkey]['children'][$ckey]['children']) > 0)
			{
	
						foreach ($array[$zkey]['children'][$ckey]['children'] as $bkey=>$_banners_)
						{
							if ($hideinactive == true && $array[$zkey]['children'][$ckey]['active'] == 'f') {
								$campaignshidden++;
							}
							else 
							{
								$array[$zkey]['children'][$ckey]['views'] 			+= $array[$zkey]['children'][$ckey]['children'][$bkey]['views'];
								$array[$zkey]['children'][$ckey]['clicks'] 			+= $array[$zkey]['children'][$ckey]['children'][$bkey]['clicks'];
								$array[$zkey]['children'][$ckey]['conversions'] 	+= $array[$zkey]['children'][$ckey]['children'][$bkey]['conversions'];
								$array[$zkey]['children'][$ckey]['CTR'] 			+= $array[$zkey]['children'][$ckey]['children'][$bkey]['CTR'];
								$array[$zkey]['children'][$ckey]['CNVR'] 			+= $array[$zkey]['children'][$ckey]['children'][$bkey]['CNVR'];
			}
		}
		
						$array[$zkey]['views'] 			+= $array[$zkey]['children'][$ckey]['views'];
						$array[$zkey]['clicks'] 		+= $array[$zkey]['children'][$ckey]['clicks'];
						$array[$zkey]['conversions']	+= $array[$zkey]['children'][$ckey]['conversions'];
						$array[$zkey]['CTR']			+= $array[$zkey]['children'][$ckey]['CTR'];
						$array[$zkey]['CNVR']			+= $array[$zkey]['children'][$ckey]['CNVR'];
		
	}
				}
			}		
			//totals
	
			$totalviews  		+= $array[$zkey]['views'];
			$totalclicks 		+= $array[$zkey]['clicks'];
			$totalconversions 	+= $array[$zkey]['conversions'];
			$totalCTR		 	+= $array[$zkey]['CTR'];
			$totalCNVR		 	+= $array[$zkey]['CNVR'];
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
// Handle expanding and collapsing of nodes END
//-----------------------------------------------------------------------------------------------------------------------

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
				
		
//-----------------------------------------------------------------------------------------------------------------------
// Start output
//-----------------------------------------------------------------------------------------------------------------------

// Form to select Overall or By Zone
echo "<form action='".$HTTP_SERVER_VARS['PHP_SELF']."' method='post'>";
echo "<select name='screen' onChange='this.form.submit();' accesskey='".$keyList."' tabindex='".($tabindex++)."'>";
echo "<option value='overall'"	.($screen == 'overall' ? ' selected' : '') . ">" . $strOverall  . "</option>";
echo "<option value='zones'"	.($screen == 'zones' ? ' selected' : '') . ">" . $strByZone . "</option>";
echo "</select>";
echo "&nbsp;&nbsp;";
echo "<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif' border='0' name='submit'>&nbsp;";
echo "<input type='hidden' name='affiliateid' value='".$affiliateid."'>";
phpAds_ShowBreak();
echo "</form>";
	
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
echo '<td height="25"><b>&nbsp;&nbsp;<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&listorder=name">'.$GLOBALS['strName'].'</a>';
if ($listorder == "name" || $listorder == "")
	echo $orderdirection == "up" 
		? ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// ID column
echo '<td height="25"><b><a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&listorder=id">'.$GLOBALS['strID'].'</a>';
if ($listorder == "id")
	echo $orderdirection == "up" 
		? ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Views column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='stats-affiliate-zones.php?affiliateid=".$affiliateid."&listorder=views'>".$GLOBALS['strViews'].'</a>';
if ($listorder == "views")
	echo $orderdirection == "up" 
		? ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Clicks column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='stats-affiliate-zones.php?affiliateid=".$affiliateid."&listorder=clicks'>".$GLOBALS['strClicks'].'</a>';
if ($listorder == "clicks")
	echo $orderdirection == "up" 
		? ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// CTR column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='stats-affiliate-zones.php?affiliateid=".$affiliateid."&listorder=ctr'>".$GLOBALS['strCTRShort'].'</a>';
if ($listorder == "ctr")
	echo $orderdirection == "up" 
		? ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Conversion column
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='stats-affiliate-zones.php?affiliateid=".$affiliateid."&listorder=conversions'>".$GLOBALS['strConversions'].'</a>';
if ($listorder == "conversions")
	echo $orderdirection == "up" 
		? ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
// Sales Ration colum
echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='stats-affiliate-zones.php?affiliateid=".$affiliateid."&listorder=CNVR'>".$GLOBALS['strCNVRShort'].'</a>&nbsp;&nbsp;';
if ($listorder == "CNVR")
	echo $orderdirection == "up" 
		? ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=down"><img src="images/caret-u.gif" border="0" alt="" title=""></a>')
		: ('<a href="stats-affiliate-zones.php?affiliateid='.$affiliateid.'&orderdirection=up"><img src="images/caret-ds.gif" border="0" alt="" title=""></a>');
echo '</b></td>';
echo "</tr>";
//-----------------------------------------------------------------------------------------------------------------------
//Column headers END
//-----------------------------------------------------------------------------------------------------------------------


echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
if ($screen == 'zones')
{	
	$i=0;

	if (!isset($array) || !is_array($array) || count($array) == 0)
	{
		echo "<tr height='25' bgcolor='#F6F6F6'><td height='25' colspan='7'>";
		echo "&nbsp;&nbsp;".$strNoZones;
		echo "</td></tr>";
		echo "<td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>";
	}
	
	else rowPresenter ($array, 0,'',0,false, $affiliateid);

} else {

	$i=0;
	foreach($array as $zone)
		rowPresenter ($zone['children'], $i,'',$zone['id'],false, $affiliateid);

	

}

	// Total
	echo "<tr height='25'><td height='25'>&nbsp;&nbsp;<b>".$strTotal."</b></td>";
	echo "<td height='25'>&nbsp;</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalviews)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalclicks)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalviews, $totalclicks)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalconversions)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalclicks, $totalconversions)."&nbsp;&nbsp;</td>";
	echo "</tr>";
	
	echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";


	echo "<tr>";
	echo "<td>";

	// Hide/show inactive	
	if ($hideinactive == true)
	{
		echo "&nbsp;&nbsp;<img src='images/icon-activate.gif' align='absmiddle' border='0'>";
		echo "&nbsp;<a href='".$HTTP_SERVER_VARS['PHP_SELF']."?hideinactive=0'>".$strShowAll."</a>";
		echo "&nbsp;&nbsp;|&nbsp;&nbsp;".$campaignshidden." ".$strInactiveCampaignsHidden;
	}
	else
	{
		echo "&nbsp;&nbsp;<img src='images/icon-hideinactivate.gif' align='absmiddle' border='0'>";
		echo "&nbsp;<a href='".$HTTP_SERVER_VARS['PHP_SELF']."?hideinactive=1'>".$strHideInactiveCampaigns."</a>";
	}
	
	echo "</td>";
	echo "<td colspan='6' height='25' align='".$phpAds_TextAlignRight."' nowrap>";
	echo "<img src='images/triangle-d.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-affiliate-zones.php?expand=all'>".$strExpandAll."</a>";
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
	echo "<img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-affiliate-zones.php?expand=none'>".$strCollapseAll."</a>&nbsp;&nbsp;";
	echo "</td>";
	echo "</tr>";

echo "</table>";
echo "<br><br>";

/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['stats-affiliate-zones.php']['listorder'] = $listorder;
$Session['prefs']['stats-affiliate-zones.php']['orderdirection'] = $orderdirection;
//$Session['prefs']['stats-affiliate-zones.php']['nodes'] 			= implode (",", $node_array);
$Session['prefs']['stats-affiliate-zones.php']['nodes'] 			= $node_array;
//$Session['prefs']['stats-affiliate-zones.php']['results'] 			= $array;
$Session['prefs']['stats-affiliate-zones.php']['screen'] 			= $screen;
$Session['prefs']['stats-affiliate-zones.php']['hide'] 				= $hideinactive;
$Session['prefs']['stats-affiliate-zones.php']['affiliateid']		= $affiliateid;

phpAds_SessionDataStore();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();
?>