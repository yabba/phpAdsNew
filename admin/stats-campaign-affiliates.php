<?php // $Revision: 2.3 $

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
	,'distributiontype'
	,'expand'
	,'listorder'
	,'orderdirection'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency + phpAds_Client);

if (phpAds_isUser(phpAds_Agency))
{
	if (isset($campaignid) && $campaignid != '')
	{
		$query = "SELECT c.clientid".
			" FROM ".$phpAds_config['tbl_clients']." AS c".
			",".$phpAds_config['tbl_campaigns']." AS m".
			" WHERE c.clientid=m.clientid".
			" AND c.clientid=".$clientid.
			" AND m.campaignid=".$campaignid.
			" AND agencyid=".phpAds_getUserID();
	}
	else
	{
		$query = "SELECT c.clientid".
			" FROM ".$phpAds_config['tbl_clients']." AS c".
			" WHERE c.clientid=".$clientid.
			" AND agencyid=".phpAds_getUserID();
	}
	$res = phpAds_dbQuery($query) or phpAds_sqlDie();
	if (phpAds_dbNumRows($res) == 0)
	{
		phpAds_PageHeader("2");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}
elseif (phpAds_isUser(phpAds_Client))
{
	$clientid = phpAds_getUserID();
	if (isset($campaignid) && $campaignid != '')
	{
		$query = "SELECT clientid ".
			" FROM ".$phpAds_config['tbl_campaigns'].
			" WHERE clientid=".$clientid.
			" AND campaignid=".$campaignid;
	}
	else
	{
		$query = "SELECT clientid".
			"FROM ".$phpAds_config['tbl_campaigns'].
			" WHERE clientid=".$clientid;
	}
	$res = phpAds_dbQuery($query) or phpAds_sqlDie();
	if (phpAds_dbNumRows($res) == 0)
	{
		phpAds_PageHeader("2");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}


// Check to see if they are switching...
if (isset($distributiontype) && ($distributiontype == 's') )
{
	Header("Location: stats-campaign-sources.php?clientid=".$clientid."&campaignid=".$campaignid);
	exit;
}



/*********************************************************/
/* Get preferences                                       */
/*********************************************************/

if (!isset($listorder))
{
	if (isset($Session['prefs']['stats-campaign-affiliates.php']['listorder']))
		$listorder = $Session['prefs']['stats-campaign-affiliates.php']['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['stats-campaign-affiliates.php']['orderdirection']))
		$orderdirection = $Session['prefs']['stats-campaign-affiliates.php']['orderdirection'];
	else
		$orderdirection = '';
}

if (isset($Session['prefs']['stats-campaign-affiliates.php']['nodes']))
	$node_array = explode (",", $Session['prefs']['stats-campaign-affiliates.php']['nodes']);
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
		$res = phpAds_dbQuery("SELECT campaignid,campaignname".
			" FROM ".$phpAds_config['tbl_campaigns'].
			" WHERE clientid=".$clientid.
			phpAds_getCampaignListOrder ($navorder, $navdirection)
		) or phpAds_sqlDie();
		
		while ($row = phpAds_dbFetchArray($res))
		{
			phpAds_PageContext (
				phpAds_buildName ($row['campaignid'], $row['campaignname']),
				"stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
				$campaignid == $row['campaignid']
			);
		}
		
		phpAds_PageHeader("1.2.3");
			echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".phpAds_getCampaignName($campaignid)."</b><br><br><br>";
			phpAds_ShowSections(array("1.2.1", "1.2.2", "1.2.3", "1.2.4"));
	}
	else
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}
elseif (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
{
	if (phpAds_isUser(phpAds_Admin))
	{
		$query = "SELECT campaignid,campaignname".
		" FROM ".$phpAds_config['tbl_campaigns'].
			" WHERE clientid=".$clientid.
			phpAds_getCampaignListOrder ($navorder, $navdirection);
	}
	elseif (phpAds_isUser(phpAds_Agency))
	{
		$query = "SELECT m.campaignid AS campaignid".
			",m.campaignname AS campaignname".
			" FROM ".$phpAds_config['tbl_campaigns']." AS m".
			",".$phpAds_config['tbl_clients']." AS c".
			" WHERE m.clientid=c.clientid".
			" AND m.clientid=".$clientid.
			" AND c.agencyid=".phpAds_getUserID().
			phpAds_getCampaignListOrder ($navorder, $navdirection);
	}
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		phpAds_PageContext (
			phpAds_buildName ($row['campaignid'], $row['campaignname']),
			"stats-campaign-banners.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
			$campaignid == $row['campaignid']
		);
	}
	
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	phpAds_PageShortcut($strCampaignProperties, 'campaign-edit.php?clientid='.$clientid.'&campaignid='.$campaignid, 'images/icon-campaign.gif');
	
	phpAds_PageHeader("2.1.2.3");
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getParentClientName($campaignid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".phpAds_getCampaignName($campaignid)."</b><br><br><br>";
		phpAds_ShowSections(array("2.1.2.1", "2.1.2.2", "2.1.2.3", "2.1.2.4", "2.1.2.5"));
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

// Get affiliates and build the tree
if (phpAds_isUser(phpAds_Admin))
{
	$query = "SELECT affiliateid".
		",name".
		" FROM ".$phpAds_config['tbl_affiliates'].
		phpAds_getAffiliateListOrder($listorder, $orderdirection);
}
elseif (phpAds_isUser(phpAds_Agency))
{
	$query = "SELECT affiliateid".
		",name".
		" FROM ".$phpAds_config['tbl_affiliates'].
		" WHERE agencyid=".phpAds_getUserID().
		phpAds_getAffiliateListOrder($listorder, $orderdirection);
}
elseif (phpAds_isUser(phpAds_Client))
{
	$query = "SELECT affiliateid".
		",name".
		" FROM ".$phpAds_config['tbl_affiliates'].
		" WHERE agencyid=".phpAds_getAgencyID().
		phpAds_getAffiliateListOrder($listorder, $orderdirection);
}
$res_affiliates = phpAds_dbQuery($query)
	or phpAds_sqlDie();

while ($row_affiliates = phpAds_dbFetchArray($res_affiliates))
{
	$affiliates[$row_affiliates['affiliateid']] = $row_affiliates;
	$affiliates[$row_affiliates['affiliateid']]['expand'] = 0;
	$affiliates[$row_affiliates['affiliateid']]['count'] = 0;
}

// Get the zones for each affiliate
if (phpAds_isUser(phpAds_Admin))
{
	$query = "SELECT zoneid".
		",affiliateid".
		",zonename".
		",delivery".
		",what".
		" FROM ".$phpAds_config['tbl_zones'].
		phpAds_getZoneListOrder ($listorder, $orderdirection);
}
elseif (phpAds_isUser(phpAds_Agency))
{
	$query = "SELECT z.zoneid AS zoneid".
		",z.affiliateid AS affiliateid".
		",z.zonename AS zonename".
		",z.delivery AS delivery".
		",z.what AS what".
		" FROM ".$phpAds_config['tbl_zones']." AS z".
		",".$phpAds_config['tbl_affiliates']." AS a".
		" WHERE z.affiliateid=a.affiliateid".
		" AND a.agencyid=".phpAds_getAgencyID().
		phpAds_getZoneListOrder ($listorder, $orderdirection);
}
elseif (phpAds_isUser(phpAds_Client))
{
	$query = "SELECT z.zoneid AS zoneid".
		",z.affiliateid AS affiliateid".
		",z.zonename AS zonename".
		",z.delivery AS delivery".
		",z.what AS what".
		" FROM ".$phpAds_config['tbl_zones']." AS z".
		",".$phpAds_config['tbl_affiliates']." AS a".
		" WHERE z.affiliateid=a.affiliateid".
		" AND a.agencyid=".phpAds_getAgencyID().
		phpAds_getZoneListOrder ($listorder, $orderdirection);
}
$res_zones = phpAds_dbQuery($query)
	or phpAds_sqlDie();

while ($row_zones = phpAds_dbFetchArray($res_zones))
{
	if (isset($affiliates[$row_zones['affiliateid']]))
	{
		$zones[$row_zones['zoneid']] = $row_zones;
		$affiliates[$row_zones['affiliateid']]['count']++;
		
		$zones[$row_zones['zoneid']]['clicks'] = 0;
		$zones[$row_zones['zoneid']]['conversions'] = 0;
		$zones[$row_zones['zoneid']]['views'] = 0;
	}
}

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

// Get the views/clicks/conversions for each campaign
$res_stats = phpAds_dbQuery(
	"SELECT".
	" zoneid".
	",sum(views) as views".
	",sum(clicks) as clicks".
	",sum(conversions) as conversions".
	" FROM ".$phpAds_config['tbl_adstats']." AS s".
	",".$phpAds_config['tbl_banners']." AS b".
	" WHERE b.bannerid=s.bannerid".
	" AND b.campaignid=".$campaignid.
	" GROUP BY zoneid"
) or phpAds_sqlDie();

while ($row_stats = phpAds_dbFetchArray($res_stats))
{
	if (isset($zones[$row_stats['zoneid']]))
	{
		$zones[$row_stats['zoneid']]['clicks'] = $row_stats['clicks'];
		$zones[$row_stats['zoneid']]['conversions'] = $row_stats['conversions'];
		$zones[$row_stats['zoneid']]['views'] = $row_stats['views'];
	}
	else
	{
		$manual['clicks'] += $row_stats['clicks'];
		$manual['conversions'] += $row_stats['conversions'];
		$manual['views'] += $row_stats['views'];
	}
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
		if (isset($affiliates[$node_array[$i]]))
			$affiliates[$node_array[$i]]['expand'] = 1;
	}
}

// Build Tree
if (isset($zones) && is_array($zones) && count($zones) > 0)
{
	// Add zone to affiliate
	for (reset($zones);$zkey=key($zones);next($zones))
		$affiliates[$zones[$zkey]['affiliateid']]['zones'][$zkey] = $zones[$zkey];
	
	unset ($zones);
}

if (isset($affiliates) && is_array($affiliates) && count($affiliates) > 0)
{
	// Calculate statistics for affiliates
	for (reset($affiliates);$key=key($affiliates);next($affiliates))
	{
		$affiliatesclicks = 0;
		$affiliatesconversions = 0;
		$affiliatesviews = 0;
		
		if (isset($affiliates[$key]['zones']) && sizeof ($affiliates[$key]['zones']) > 0)
		{
			$zones = $affiliates[$key]['zones'];
			
			// Calculate statistics for zones
			for (reset($zones);$zkey=key($zones);next($zones))
			{
				$affiliatesclicks += $zones[$zkey]['clicks'];
				$affiliatesconversions += $zones[$zkey]['conversions'];
				$affiliatesviews += $zones[$zkey]['views'];
			}
		}
		
		$totalclicks += $affiliatesclicks;
		$totalconversions += $affiliatesconversions;
		$totalviews += $affiliatesviews;
		
		$affiliates[$key]['clicks'] = $affiliatesclicks;
		$affiliates[$key]['conversions'] = $affiliatesconversions;
		$affiliates[$key]['views'] = $affiliatesviews;
	}
	
	unset ($zones);
}

$totalclicks += $manual['clicks'];
$totalconversions += $manual['conversions'];
$totalviews += $manual['views'];

/*
// dropdown menu to select either zone or source
echo "\t\t\t\t<form action='".$HTTP_SERVER_VARS['PHP_SELF']."'>\n";
echo "\t\t\t\t<input type='hidden' name='clientid' value='".$clientid."'>\n";
echo "\t\t\t\t<input type='hidden' name='campaignid' value='".$campaignid."'>\n";
echo "\t\t\t\t".$strDistributionBy." <select name='distributiontype' onChange='this.form.submit();' accesskey='".$keyList."' tabindex='".($tabindex++)."'>\n";
echo "\t\t\t\t\t<option value='z' selected>".$strZone."</option>\n";
echo "\t\t\t\t\t<option value='s'>".$strSource."</option>\n";
echo "\t\t\t\t</select>\n";

phpAds_ShowBreak();
echo "\t\t\t\t</form>\n";
*/

if ($totalviews > 0 || $totalclicks > 0 || $totalconversions > 0)
{
	echo "<br><br>";
	echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";	
	
	echo "<tr height='25'>";
	echo "<td height='25' width='40%'>\n";
	
	echo "<b>&nbsp;&nbsp;<a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&listorder=name'>".$GLOBALS['strName']."</a>";

	if (($listorder == "name") || ($listorder == ""))
	{
		if  (($orderdirection == "") || ($orderdirection == "down"))
		{
			echo " <a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=up'><img src='images/caret-ds.gif' border='0' alt='' title=''></a>";
		}
		else
		{
			echo " <a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=down'><img src='images/caret-u.gif' border='0' alt='' title=''></a>";
		}
	}
	
	echo "</b>";
	echo '</td>';
	
	echo "<td height='25'><b><a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&listorder=id'>".$GLOBALS['strID']."</a>";
	
	if ($listorder == "id")
	{
		if  (($orderdirection == "") || ($orderdirection == "down"))
		{
			echo " <a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=up'>";
			echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
		}
		else
		{
			echo " <a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=down'>";
			echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
		}
		echo '</a>';
	}
	
	echo '</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	
	echo "<td align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strViews']."</b></td>";
	echo "<td align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strClicks']."</b></td>";
	echo "<td align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strCTRShort']."</b></td>";
	echo "<td align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strConversions']."</b></td>";
	echo "<td align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strCNVRShort']."</b>&nbsp;&nbsp;</td>";
	echo "</tr>";
	
	echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	
	
	$cnt=0;

	if (isset($affiliates) && is_array($affiliates) && count($affiliates))
	{
		for (reset($affiliates);$key=key($affiliates);next($affiliates))
		{
			$affiliate = $affiliates[$key];
			
			if ($affiliate['views'] || $affiliate['clicks'] || $affiliate['conversions'])
			{
				echo "<tr height='25' ".($cnt%2==0?"bgcolor='#F6F6F6'":"").">";
				
				// Icon & name
				echo "<td height='25'>";
				if (isset($affiliate['zones']) && !$anonymous)
				{
					if ($affiliate['expand'] == '1')
						echo "&nbsp;<a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&collapse=".$affiliate['affiliateid']."'><img src='images/triangle-d.gif' align='absmiddle' border='0'></a>&nbsp;";
					else
						echo "&nbsp;<a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&expand=".$affiliate['affiliateid']."'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>&nbsp;";
				}
				else
					echo "&nbsp;<img src='images/spacer.gif' height='16' width='16'>&nbsp;";
					
				echo "<img src='images/icon-affiliate.gif' align='absmiddle'>&nbsp;";
				if ($anonymous)
				{
					echo "(hidden publisher #".($cnt+1).")";
				}
				else 
				{
					echo "<a href='stats-affiliate-history.php?affiliateid=".$affiliate['affiliateid']."'>".$affiliate['name']."</a>";
				}
				echo "</td>";
				
				echo "<td height='25'>";
				
				if ($anonymous)
					echo "&nbsp;";
				else 
					echo $affiliate['affiliateid'];

				echo "</td>";
				echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($affiliate['views'])."</td>";
				echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($affiliate['clicks'])."</td>";
				echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($affiliate['views'], $affiliate['clicks'])."</td>";
				echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($affiliate['conversions'])."</td>";
				echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($affiliate['clicks'], $affiliate['conversions'])."&nbsp;&nbsp;</td>";
				echo "</tr>";
				
				
				
				if (isset($affiliate['zones'])
					&& (sizeof ($affiliate['zones']) > 0)
					&& ( ($affiliate['expand'] == '1') || ($expand == 'all') )
					&& !$anonymous)
				{
					$zones = $affiliate['zones'];
					
					for (reset($zones);$zkey=key($zones);next($zones))
					{
						if ($zones[$zkey]['views'] || $zones[$zkey]['clicks'] || $zones[$zkey]['conversions'])
						{
							// Divider
							echo "<tr height='1'>";
							echo "<td ".($cnt%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
							echo "<td colspan='6' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
							echo "</tr>";
							
							// Icon & name
							echo "<tr height='25' ".($cnt%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>";
							echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
							echo "<img src='images/spacer.gif' height='16' width='16' align='absmiddle'>&nbsp;";
							
							if ($zones[$zkey]['what'] != '')
							{
								if ($zones[$zkey]['delivery'] == phpAds_ZoneBanner)
									echo "<img src='images/icon-zone.gif' align='absmiddle'>&nbsp;";
								elseif ($zones[$zkey]['delivery'] == phpAds_ZoneInterstitial)
									echo "<img src='images/icon-interstitial.gif' align='absmiddle'>&nbsp;";
								elseif ($zones[$zkey]['delivery'] == phpAds_ZonePopup)
									echo "<img src='images/icon-popup.gif' align='absmiddle'>&nbsp;";
								elseif ($zones[$zkey]['delivery'] == phpAds_ZoneText)
									echo "<img src='images/icon-textzone.gif' align='absmiddle'>&nbsp;";
							}
							else
							{
								if ($zones[$zkey]['delivery'] == phpAds_ZoneBanner)
									echo "<img src='images/icon-zone-d.gif' align='absmiddle'>&nbsp;";
								elseif ($zones[$zkey]['delivery'] == phpAds_ZoneInterstitial)
									echo "<img src='images/icon-interstitial-d.gif' align='absmiddle'>&nbsp;";
								elseif ($zones[$zkey]['delivery'] == phpAds_ZonePopup)
									echo "<img src='images/icon-popup-d.gif' align='absmiddle'>&nbsp;";
								elseif ($zones[$zkey]['delivery'] == phpAds_ZoneText)
									echo "<img src='images/icon-textzone-d.gif' align='absmiddle'>&nbsp;";
							}
							
							//echo "<img src='images/icon-zone.gif' align='absmiddle'>&nbsp;";
							
							echo "<a href='stats-zone-history.php?affiliateid=".$affiliate['affiliateid']."&zoneid=".$zones[$zkey]['zoneid']."'>".$zones[$zkey]['zonename']."</td>";
							echo "</td>";
							
							echo "<td>".$zones[$zkey]['zoneid']."</td>";
							echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($zones[$zkey]['views'])."</td>";
							echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($zones[$zkey]['clicks'])."</td>";
							echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($zones[$zkey]['views'], $zones[$zkey]['clicks'])."</td>";
							echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($zones[$zkey]['conversions'])."</td>";
							echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($zones[$zkey]['clicks'], $zones[$zkey]['conversions'])."&nbsp;&nbsp;</td>";
							echo "</tr>";
						}
					}
				}
				
				echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
				$cnt++;
			}
		}
	}
	
	if ($manual['views'] || $manual['clicks'])
	{
		echo "<tr height='25' ".($cnt%2==0?"bgcolor='#F6F6F6'":"").">";
		echo "<td>&nbsp;&nbsp;".$strUnknown."</td>";
		
		echo "<td>-</td>";
		echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($manual['views'])."</td>";
		echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($manual['clicks'])."</td>";
		echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($manual['views'], $manual['clicks'])."</td>";
		echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($manual['conversions'])."</td>";
		echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($manual['clicks'], $manual['conversions'])."&nbsp;&nbsp;</td>";
		echo "</tr>";
		
		echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	}
	
	
	// Total
	echo "<tr height='25'><td height='25'>&nbsp;&nbsp;<b>".$strTotal."</b></td>";
	echo "<td>&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalviews)."</td>";
	echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalclicks)."</td>";
	echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalviews, $totalclicks)."&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalconversions)."</td>";
	echo "<td align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalclicks, $totalconversions)."&nbsp;&nbsp;</td>";
	echo "</tr>";
	
	// Break
	echo "\t\t\t\t<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";

	// Expand / Collapse
	echo "\t\t\t\t<tr>\n";
	echo "\t\t\t\t\t<td colspan='7' align='".$phpAds_TextAlignRight."' nowrap>";
	echo "<img src='images/triangle-d.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&expand=all' accesskey='".$keyExpandAll."'>".$strExpandAll."</a>";
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
	echo "<img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-campaign-affiliates.php?clientid=".$clientid."&campaignid=".$campaignid."&expand=none' accesskey='".$keyCollapseAll."'>".$strCollapseAll."</a>";
	echo "</td>\n";
	echo "\t\t\t\t</tr>";

	echo "</table>";
	echo "<br><br>";
}
else
{
	echo "<br><div class='errormessage'><img class='errormessage' src='images/info.gif' width='16' height='16' border='0' align='absmiddle'>";
	echo $strNoStats.'</div>';
}



/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['stats-campaign-affiliates.php']['listorder'] = $listorder;
$Session['prefs']['stats-campaign-affiliates.php']['orderdirection'] = $orderdirection;
$Session['prefs']['stats-campaign-affiliates.php']['nodes'] = implode (",", $node_array);

phpAds_SessionDataStore();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>