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
require ("lib-size.inc.php");
require ("lib-zones.inc.php");


// Register input variables
phpAds_registerGlobal (
	 'collapse'
	,'expand'
	,'listorder'
	,'orderdirection'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency + phpAds_Client);

// Check to see if they are switching...
if (isset($distributiontype) && ($distributiontype == 'z') )
{
	Header("Location: stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&bannerid=".$bannerid);
	exit;
}

/*********************************************************/
/* Get preferences                                       */
/*********************************************************/

if (!isset($listorder))
{
	if (isset($Session['prefs']['stats-banner-sources.php']['listorder']))
		$listorder = $Session['prefs']['stats-banner-sources.php']['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['stats-banner-sources.php']['orderdirection']))
		$orderdirection = $Session['prefs']['stats-banner-sources.php']['orderdirection'];
	else
		$orderdirection = '';
}

if (isset($Session['prefs']['stats-banner-sources.php']['nodes']))
	$node_array = explode (",", $Session['prefs']['stats-banner-sources.php']['nodes']);
else
	$node_array = array();

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
			"SELECT campaignid,campaignname".
			" FROM ".$phpAds_config['tbl_campaigns'].
			" WHERE clientid=".phpAds_getUserID().
			" AND campaignid=".$campaignid.
			phpAds_getCampaignListOrder ($navorder, $navdirection)
		) or phpAds_sqlDie();
		
		while ($row = phpAds_dbFetchArray($res))
		{
			phpAds_PageContext (
				phpAds_buildName ($row['campaignid'], $row['campaignname']),
				"stats-banner-sources.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
				$campaignid == $row['campaignid']
			);
		}
		
		$sections[] = "1.2.2.1";
		if (phpAds_isAllowed(phpAds_ModifyBanner)) $sections[] = "1.2.2.2";
		$sections[] = "1.2.2.4";
		
		phpAds_PageHeader("1.2.2.4");
			echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;".phpAds_getCampaignName($campaignid);
			echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
			echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>&nbsp;<b>".phpAds_getBannerName($bannerid)."</b><br><br>";
			echo phpAds_buildBannerCode($bannerid)."<br><br><br><br>";
			phpAds_ShowSections($sections);
	}
	else
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}

if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
{
	if (phpAds_isUser(phpAds_Admin))
	{
		$query = "SELECT campaignid,campaignname".
		" FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE clientid = ".$clientid.
			phpAds_getCampaignListOrder ($navorder, $navdirection);
	}
	elseif (phpAds_isUser(phpAds_Agency))
	{
		$query = "SELECT campaignid,campaignname".
			" FROM ".$phpAds_config['tbl_campaigns'].
			" WHERE clientid=".$clientid.
			" AND agencyid=".phpAds_getUserID().
			phpAds_getCampaignListOrder ($navorder, $navdirection);
	}
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		phpAds_PageContext (
			phpAds_buildName ($row['campaignid'], $row['campaignname']),
			"stats-banner-sources.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
			$campaignid == $row['campaignid']
		);
	}
	
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	phpAds_PageShortcut($strCampaignProperties, 'campaign-edit.php?clientid='.$clientid.'&campaignid='.$campaignid, 'images/icon-campaign.gif');
	
	phpAds_PageHeader("2.1.2.2.2");
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getParentClientName($campaignid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;".phpAds_getCampaignName($campaignid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>&nbsp;<b>".phpAds_getBannerName($bannerid)."</b><br><br>";
		echo phpAds_buildBannerCode($bannerid)."<br><br><br><br>";
		phpAds_ShowSections(array("2.1.2.2.1", "2.1.2.2.2"));
}


/*********************************************************/
/* Main code                                             */
/*********************************************************/

$manual['clicks'] = 0;
$manual['conversions'] = 0;
$manual['views'] = 0;
$totalclicks = 0;
$totalconversions = 0;
$totalviews = 0;

// Check to see if this campaign is anonymous
$anonymous = false;
$res_campaign = phpAds_dbQuery(
	"SELECT anonymous".
	" FROM ".$phpAds_config['tbl_campaigns'].
	" WHERE campaignid=".$campaignid
);

if ($row_campaign = phpAds_dbFetchArray($res_campaign))
{
	$anonymous = ($row_campaign['anonymous'] == 't');
}

$sources = array();

// Get the adviews/clicks for each campaign
$res_stats = phpAds_dbQuery(
	"SELECT ".
	//" source".
	"sum(views) as views".
	",sum(clicks) as clicks".
	",sum(conversions) as conversions".
	" FROM ".$phpAds_config['tbl_adstats'].
	" WHERE bannerid=".$bannerid
	//." GROUP BY source"
) or phpAds_sqlDie();

while ($row_stats = phpAds_dbFetchArray($res_stats))
{
	$source = $row_stats['source'];
	if (strlen($source) > 0)
	{
		$sources = phpAds_buildSourceArray($sources, $source, '', $row_stats);
	}
	else
	{
		$manual['clicks'] += $row_stats['clicks'];
		$manual['conversions'] += $row_stats['conversions'];
		$manual['views'] += $row_stats['views'];
	}
	
	$totalclicks += $row_stats['clicks'];
	$totalconversions += $row_stats['conversions'];
	$totalviews += $row_stats['views'];
}

// Add ID found in expand to expanded nodes
if (isset($expand) && ($expand != ''))
{
	if ($expand == 'none')
	{
		$node_array = array();
	}
	elseif ($expand != 'all')
	{
		$node_array[] = $expand;
	}
}

$arrlen = sizeof($node_array);
for ($i=0; $i<$arrlen; $i++)
{
	if (isset($collapse) && ($collapse == $node_array[$i]) )
	{
		unset ($node_array[$i]);
	}
	elseif (strlen($node_array[$i]) == 0)
	{
		unset ($node_array[$i]);
	}
	else
	{
		$sources['expand'][$node_array[$i]] = 1;
	}
}

if ($expand == 'all')
	$sources['expand'] = 'all';

echo "\t\t\t\t<form action='".$HTTP_SERVER_VARS['PHP_SELF']."' method='post'>\n";
echo "\t\t\t\t<input type='hidden' name='clientid' value='".$clientid."'>\n";
echo "\t\t\t\t<input type='hidden' name='campaignid' value='".$campaignid."'>\n";
echo "\t\t\t\t<input type='hidden' name='bannerid' value='".$bannerid."'>\n";
echo "\t\t\t\t".$strDistributionBy." <select name='distributiontype' onChange='this.form.submit();' accesskey='".$keyList."' tabindex='".($tabindex++)."'>\n";
echo "\t\t\t\t\t<option value='z'>".$strZone."</option>\n";
echo "\t\t\t\t\t<option value='s' selected>".$strSource."</option>\n";
echo "\t\t\t\t</select>\n";

phpAds_ShowBreak();
echo "\t\t\t\t</form>\n";


if ($totalviews > 0 || $totalclicks > 0)
{
	echo "\t\t\t\t<br><br>\n";
	echo "\t\t\t\t<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";	
	
	echo "\t\t\t\t<tr height='25'>\n";
	echo "\t\t\t\t\t<td height='25' width='40%'><b>&nbsp;&nbsp;<a href='stats-banner-sources.php?clientid=".$clientid."&campaignid=".$campaignid."&listorder=name'>".$GLOBALS['strName']."</a>";
	
	if (($listorder == "name") || ($listorder == ""))
	{
		if  (($orderdirection == "") || ($orderdirection == "down"))
		{
			echo " <a href='stats-banner-sources.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=up'>";
			echo "<img src='images/caret-ds.gif' border='0' alt='' title=''>";
		}
		else
		{
			echo " <a href='stats-banner-sources.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=down'>";
			echo "<img src='images/caret-u.gif' border='0' alt='' title=''>";
		}
		echo "</a>";
	}
	
	echo "</b></td>\n";
	
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strViews']."</b></td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strClicks']."</b></td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strCTRShort']."</b></td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strConversions']."</b></td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strCNVRShort']."</b>&nbsp;&nbsp;</td>\n";
	echo "\t\t\t\t</tr>\n";
	
	echo "\t\t\t\t<tr height='1'><td colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";
	
	
	$cnt=0;

	for ($i=0; $i<sizeof($sources); $i++)
	{
		if (is_array($sources[$i]))
		{
			phpAds_printSourceRow($sources[$i], $sources['expand'], "&nbsp;&nbsp;");
			echo "\t\t\t\t<tr height='1'><td colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";
			$cnt++;
		}
	}
	
	if ( ($manual['views'] > 0) || ($manual['clicks'] > 0) || ($manual['conversions'] > 0) )
	{
		echo "\t\t\t\t<tr height='25' ".($cnt%2==0?"bgcolor='#F6F6F6'":"").">\n";
		echo "\t\t\t\t\t<td>&nbsp;&nbsp;".$strUnknown."</td>";
		
		echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($manual['views'])."</td>\n";
		echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($manual['clicks'])."</td>\n";
		echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($manual['views'], $manual['clicks'])."</td>\n";
		echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($manual['conversions'])."</td>\n";
		echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($manual['clicks'], $manual['conversions'])."&nbsp;&nbsp;</td>\n";
		echo "\t\t\t\t</tr>\n";
		
		echo "\t\t\t\t<tr height='1'><td colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";
	}
	
	
	// Total
	echo "\t\t\t\t<tr height='25'>\n";
	echo "\t\t\t\t\t<td height='25'>&nbsp;&nbsp;<b>".$strTotal."</b></td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalviews)."</td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalclicks)."</td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalviews, $totalclicks)."</td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalconversions)."</td>\n";
	echo "\t\t\t\t\t<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalclicks, $totalconversions)."&nbsp;&nbsp;</td>\n";
	echo "\t\t\t\t</tr>\n";
	
	// Break
	echo "\t\t\t\t<tr height='1'><td colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";

	// Expand / Collapse
	echo "\t\t\t\t<tr>\n";
	echo "\t\t\t\t\t<td colspan='6' align='".$phpAds_TextAlignRight."' nowrap>";
	echo "<img src='images/triangle-d.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-banner-sources.php?clientid=".$clientid."&campaignid=".$campaignid."&expand=all' accesskey='".$keyExpandAll."'>".$strExpandAll."</a>";
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
	echo "<img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-banner-sources.php?clientid=".$clientid."&campaignid=".$campaignid."&expand=none' accesskey='".$keyCollapseAll."'>".$strCollapseAll."</a>";
	echo "</td>\n";
	echo "\t\t\t\t</tr>";

	echo "\t\t\t\t</table>\n";
	echo "\t\t\t\t<br><br>\n";
}
else
{
	echo "\t\t\t\t<br><div class='errormessage'><img class='errormessage' src='images/info.gif' width='16' height='16' border='0' align='absmiddle'>";
	echo $strNoStats."</div>\n";
}

/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['stats-banner-sources.php']['listorder'] = $listorder;
$Session['prefs']['stats-banner-sources.php']['orderdirection'] = $orderdirection;
$Session['prefs']['stats-banner-sources.php']['nodes'] = implode (",", $node_array);

phpAds_SessionDataStore();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

function phpAds_printSourceRow($source_row, $expand_arr, $begin_str)
{
	global
		 $anonymous
		,$campaignid
		,$clientid
		,$cnt
		,$phpAds_TextDirection
		,$phpAds_TextAlignRight
	;
	
	$expand = ( $expand_arr == 'all' || ( isset($expand_arr[$source_row['path']]) && ($expand_arr[$source_row['path']] == 1) ) );
	$children_present = ( isset($source_row['children']) && is_array($source_row['children']) & (sizeof($source_row['children']) > 0) );

	echo "\t\t\t\t<tr height='25' ".($cnt%2==0?"bgcolor='#F6F6F6'":"").">\n";
	echo "\t\t\t\t\t<td>";
	echo $begin_str;
	
	if ($children_present && !$anonymous)
	{
		if ($expand)
			echo "<a href='stats-banner-sources.php?clientid=".$clientid."&campaignid=".$campaignid."&collapse=".$source_row['path']."'><img src='images/triangle-d.gif' align='absmiddle' border='0'></a>&nbsp;";
		else
			echo "<a href='stats-banner-sources.php?clientid=".$clientid."&campaignid=".$campaignid."&expand=".$source_row['path']."'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>&nbsp;";
	}
	else
		echo "<img src='images/spacer.gif' align='absmiddle' width='16' height='16' border='0'>";

	if ($anonymous)
		echo "(hidden source #".($cnt+1).")";
	else
		echo $source_row['name'];

	echo "</td>\n";	
	echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($source_row['views'])."</td>\n";
	echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($source_row['clicks'])."</td>\n";
	echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($source_row['views'], $source_row['clicks'])."</td>";
	echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($source_row['conversions'])."</td>\n";
	echo "\t\t\t\t\t<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($source_row['clicks'], $source_row['conversions'])."&nbsp;&nbsp;</td>";
	echo "\t\t\t\t</tr>\n";
	
	if ($expand && $children_present && !$anonymous)
	{
		$child_source_row = $source_row['children'];
		for ($i=0; $i<sizeof($child_source_row); $i++)
		{
			echo "\t\t\t\t<tr height='1'".($cnt%2==0?" bgcolor='#F6F6F6'":"")."><td><img src='images/spacer.gif' width='100%' height='1' border='0'></td><td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";
			phpAds_printSourceRow($child_source_row[$i], $expand_arr, $begin_str."&nbsp;&nbsp;&nbsp;&nbsp;");
		}
	}
}

function phpAds_buildSourceArray($sources, $source, $path, $row_stats)
{
	// Set the master array if there is not already one.
	if (!isset($sources) || !is_array($sources) )
		$sources = array();

	// First, get the name of this branch of the source.
	$len = strpos($source, '/');
	if ($len > -1)
	{
		$name = substr($source, 0, $len);
	}
	else
	{
		$name = $source;
	}
	
	// Next, see if there is already a branch present in the sources array
	$index = -1;
	for ($i=0; $i<sizeof($sources); $i++)
	{
		if ($sources[$i]['name'] == $name)
		{
			$index = $i;
			break;
		}
	}
	
	// If this branch is not present, add the default information
	if ($index == -1)
	{
		$source_arr['name'] = $name;
		if (strlen($path) > 0)
			$source_arr['path'] = $path.'/'.$source_arr['name'];
		else
			$source_arr['path'] = $source_arr['name'];

		$source_arr['clicks'] = 0;
		$source_arr['conversions'] = 0;
		$source_arr['views'] = 0;
		$source_arr['children'] = array();
	}
	// ...Otherwise, grab this specific branch of the source array
	else 
	{
		$source_arr = $sources[$index];
	}
	
	// Increment the stats for this branch
	$source_arr['views'] += $row_stats['views'];
	$source_arr['clicks'] += $row_stats['clicks'];
	$source_arr['conversions'] += $row_stats['conversions'];

	// If there are children, recursively populate the children array
	if ($len > -1)
	{
		$source_arr['children'] = phpAds_buildSourceArray($source_arr['children'], substr($source, $len+1), $source_arr['path'], $row_stats);
	}
	
	if ($index == -1)
	{
		$sources[] = $source_arr;
	}
	else 
	{
		$sources[$index] = $source_arr;
	}
	
	return $sources;
}
?>