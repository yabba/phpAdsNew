<?php // $Revision: 1.8 $

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

// Register input variables
phpAds_registerGlobal (
	 'collapse'
	,'expand'
	,'hideinactive'
	,'listorder'
	,'orderdirection'
	,'period'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);


// Set default values
if (!isset($period))
	$period = '';

$tabindex = 1;



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (phpAds_isUser(phpAds_Admin)) {
	$extra  = "<br><br><br>";
	$extra .= "<b>$strMaintenance</b><br>";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
	$extra .= "<a href='stats-reset.php?all=true'".phpAds_DelConfirm($strConfirmResetStats).">";
	$extra .= "<img src='images/".$phpAds_TextDirection."/icon-undo.gif' align='absmiddle' border='0'>&nbsp;$strResetStats</a>";
	$extra .= "<br><br>";
}
phpAds_PageHeader("2.1", $extra);
phpAds_ShowSections(array("2.1", "2.4", "2.2", "2.5"));



/*********************************************************/
/* Get preferences                                       */
/*********************************************************/

if (!isset($hideinactive))
{
	if (isset($Session['prefs']['stats-global-advertiser.php']['hideinactive']))
		$hideinactive = $Session['prefs']['stats-global-advertiser.php']['hideinactive'];
	else
		$hideinactive = ($phpAds_config['gui_hide_inactive'] == 't');
}

if (!isset($listorder))
{
	if (isset($Session['prefs']['stats-global-advertiser.php']['listorder']))
		$listorder = $Session['prefs']['stats-global-advertiser.php']['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['stats-global-advertiser.php']['orderdirection']))
		$orderdirection = $Session['prefs']['stats-global-advertiser.php']['orderdirection'];
	else
		$orderdirection = '';
}

if (isset($Session['prefs']['stats-global-advertiser.php']['nodes']))
	$node_array = explode (",", $Session['prefs']['stats-global-advertiser.php']['nodes']);
else
	$node_array = array();

/*********************************************************/
/* Main code                                             */
/*********************************************************/

// Get clients & campaign and build the tree
if (phpAds_isUser(phpAds_Admin))
{
	$res_clients = phpAds_dbQuery(
		"SELECT clientid,clientname".
		" FROM ".$phpAds_config['tbl_clients'].
		phpAds_getClientListOrder ($listorder, $orderdirection)
	) or phpAds_sqlDie();
	
	$res_campaigns = phpAds_dbQuery(
		"SELECT campaignid,campaignname,clientid,views,clicks,conversions,active".
		" FROM ".$phpAds_config['tbl_campaigns'].
		phpAds_getCampaignListOrder ($listorder, $orderdirection)
	) or phpAds_sqlDie();
}
elseif (phpAds_isUser(phpAds_Agency))
{
	$res_clients = phpAds_dbQuery(
		"SELECT clientid,clientname".
		" FROM ".$phpAds_config['tbl_clients'].
		" WHERE agencyid=".phpAds_getUserID().
		phpAds_getClientListOrder ($listorder, $orderdirection)
	) or phpAds_sqlDie();
	
	$res_campaigns = phpAds_dbQuery(
		"SELECT m.campaignid as campaignid".
		",m.campaignname as campaignname".
		",m.clientid as clientid".
		",m.views as views".
		",m.clicks as clicks".
		",m.conversions as conversions".
		",m.active as active".
		" FROM ".$phpAds_config['tbl_campaigns']." AS m".
		",".$phpAds_config['tbl_clients']." AS c".
		" WHERE m.clientid=c.clientid".
		" AND c.agencyid=".phpAds_getUserID().
		phpAds_getCampaignListOrder ($listorder, $orderdirection)
	) or phpAds_sqlDie();
}

while ($row_clients = phpAds_dbFetchArray($res_clients))
{
	$clients[$row_clients['clientid']] = $row_clients;
	$clients[$row_clients['clientid']]['expand'] = 0;
	$clients[$row_clients['clientid']]['count'] = 0;
	$clients[$row_clients['clientid']]['hideinactive'] = 0;
}

while ($row_campaigns = phpAds_dbFetchArray($res_campaigns))
{
		$campaigns[$row_campaigns['campaignid']] = $row_campaigns;
		$campaigns[$row_campaigns['campaignid']]['expand'] = 0;
		$campaigns[$row_campaigns['campaignid']]['count'] = 0;
}

switch ($period)
{
	case 't':	$timestamp	= mktime(0, 0, 0, date('m'), date('d'), date('Y'));
				$limit 		= " AND day >= ".date('Ymd', $timestamp);
				break;
			
	case 'w':	$timestamp	= mktime(0, 0, 0, date('m'), date('d') - 6, date('Y'));
				$limit 		= " AND day >= ".date('Ymd', $timestamp);
				break;
			
	case 'm':	$timestamp	= mktime(0, 0, 0, date('m'), 1, date('Y'));
				$limit 		= " AND day >= ".date('Ymd', $timestamp);
				break;
			
	default:	$limit = '';
				$period = '';
				break;
}

// Get the banners for each campaign
if (phpAds_isUser(phpAds_Admin))
{
	$query = "SELECT bannerid".
		",campaignid".
		",alt".
		",description".
		",active".
		",storagetype".
		" FROM ".$phpAds_config['tbl_banners'].
		phpAds_getBannerListOrder ($listorder, $orderdirection);
	
}
elseif (phpAds_isUser(phpAds_Agency))
{
	$query = "SELECT b.bannerid AS bannerid".
		",b.campaignid AS campaignid".
		",b.alt AS alt".
		",b.description AS description".
		",b.active AS active".
		",b.storagetype AS storagetype".
		" FROM ".$phpAds_config['tbl_banners']." AS b".
		",".$phpAds_config['tbl_campaigns']." AS m".
		",".$phpAds_config['tbl_clients']." AS c".
		" WHERE b.campaignid=m.campaignid".
		" AND m.clientid=c.clientid".
		" AND c.agencyid=".phpAds_getUserID().
		phpAds_getBannerListOrder ($listorder, $orderdirection);
}

$res_banners = phpAds_dbQuery($query)
	or phpAds_sqlDie();

while ($row_banners = phpAds_dbFetchArray($res_banners))
{

	if (isset($clients[$row_banners['campaignid']]))
	{
		$clients[$row_banners['campaignid']]['count']++;
	}
	
	if (isset($campaigns[$row_banners['campaignid']]))
	{
		$banners[$row_banners['bannerid']] = $row_banners;
		$banners[$row_banners['bannerid']]['clicks'] = 0;
		$banners[$row_banners['bannerid']]['views'] = 0;
		$campaigns[$row_banners['campaignid']]['count']++;
	}
	
	
	$res_stats = phpAds_dbQuery(
		"SELECT".
		" sum(views) as views".
		",sum(clicks) as clicks".
		",sum(conversions) as conversions".
		" FROM ".$phpAds_config['tbl_adstats'].
		" WHERE bannerid=".$row_banners['bannerid'].
		$limit
	) or phpAds_sqlDie();
	
	if ($row_stats = phpAds_dbFetchArray($res_stats))
	{
		$banners[$row_banners['bannerid']]['clicks'] = $row_stats['clicks'];
		$banners[$row_banners['bannerid']]['conversions'] = $row_stats['conversions'];
		$banners[$row_banners['bannerid']]['views'] = $row_stats['views'];
	}
	
}

// Add ID found in expand to expanded nodes
if (isset($expand) && $expand != '')
{
	switch ($expand)
	{
		case 'all' :	$node_array   = array();
						if (isset($clients)) while (list($key,) = each($clients)) $node_array[] = $key;
						if (isset($campaigns)) while (list($key,) = each($campaigns)) $node_array[] = $key;
						break;
						
		case 'none':	$node_array   = array();
						break;
						
		default:		$node_array[] = $expand;
						break;
	}
}


$node_array_size = sizeof($node_array);
for ($i=0; $i < $node_array_size;$i++)
{
	if (isset($collapse) && $collapse == $node_array[$i])
		unset ($node_array[$i]);
	else
	{
		if (isset($clients[$node_array[$i]]))
			$clients[$node_array[$i]]['expand'] = 1;
		if (isset($campaigns[$node_array[$i]]))
			$campaigns[$node_array[$i]]['expand'] = 1;
	}
}


// Build Tree
$clientshidden = 0;

if (isset($banners) && is_array($banners) && count($banners) > 0)
{
	// Add banner to campaigns
	reset ($banners);
	while (list ($bkey, $banner) = each ($banners))
		if ($hideinactive == false || $banner['active'] == 't')
			$campaigns[$banner['campaignid']]['banners'][$bkey] = $banner;
	
	unset ($banners);
}

if (isset($campaigns) && is_array($campaigns) && count($campaigns) > 0)
{
	reset ($campaigns);
	while (list ($ckey, $campaign) = each ($campaigns))
	{
		if (!isset($campaign['banners']))
			$campaign['banners'] = array();
		
		if ($hideinactive == false || $campaign['active'] == 't' && 
		   (count($campaign['banners']) != 0 || count($campaign['banners']) == $campaign['count']))
			$clients[$campaign['clientid']]['campaigns'][$ckey] = $campaign;
		else
			$clients[$campaign['clientid']]['hideinactive']++;
	}
	
	unset ($campaigns);
}

if (isset($clients) && is_array($clients) && count($clients) > 0)
{
	reset ($clients);
	while (list ($key, $client) = each ($clients))
	{
		if (!isset($client['campaigns']))
			$client['campaigns'] = array();
		
		if (count($client['campaigns']) == 0 && $client['hideinactive'] > 0)
		{
			$clientshidden++;
			unset($clients[$key]);
		}
	}
}



$totalclicks = 0;
$totalconversions = 0;
$totalviews = 0;

if (isset($clients) && is_array($clients) && count($clients) > 0)
{
	// Calculate statistics for clients
	for (reset($clients);$key=key($clients);next($clients))
	{
		$clientclicks = 0;
		$clientconversions = 0;
		$clientviews = 0;
		
		if (isset($clients[$key]['campaigns']) && sizeof ($clients[$key]['campaigns']) > 0)
		{
			$campaigns = $clients[$key]['campaigns'];
			
			// Calculate statistics for campaigns
			for (reset($campaigns);$ckey=key($campaigns);next($campaigns))
			{
				$campaignclicks = 0;
				$campaignconversions = 0;
				$campaignviews = 0;
				
				if (isset($campaigns[$ckey]['banners']) && sizeof ($campaigns[$ckey]['banners']) > 0)
				{
					$banners = $campaigns[$ckey]['banners'];
					for (reset($banners);$bkey=key($banners);next($banners))
					{
						$campaignclicks += $banners[$bkey]['clicks'];
						$campaignconversions += $banners[$bkey]['conversions'];
						$campaignviews += $banners[$bkey]['views'];
					}
				}
				
				$clientclicks += $campaignclicks;
				$clientconversions += $campaignconversions;
				$clientviews += $campaignviews;
				
				$clients[$key]['campaigns'][$ckey]['clicks'] = $campaignclicks;
				$clients[$key]['campaigns'][$ckey]['conversions'] = $campaignconversions;
				$clients[$key]['campaigns'][$ckey]['views'] = $campaignviews;
			}
		}
		
		$totalclicks += $clientclicks;
		$totalviews += $clientconversions;
		$totalviews += $clientviews;
		
		$clients[$key]['clicks'] = $clientclicks;
		$clients[$key]['conversions'] = $clientconversions;
		$clients[$key]['views'] = $clientviews;
	}
	
	unset ($campaigns);
	unset ($banners);
}



echo "\t\t\t\t<form action='".$HTTP_SERVER_VARS['PHP_SELF']."'>\n";

echo "\t\t\t\t<select name='period' onChange='this.form.submit();' accesskey='".$keyList."' tabindex='".($tabindex++)."'>\n";
echo "\t\t\t\t\t<option value=''".($period == '' ? ' selected' : '').">".$strCollectedAll."</option>\n";
echo "\t\t\t\t\t<option value='t'".($period == 't' ? ' selected' : '').">".$strCollectedToday."</option>\n";
echo "\t\t\t\t\t<option value='w'".($period == 'w' ? ' selected' : '').">".$strCollected7Days."</option>\n";
echo "\t\t\t\t\t<option value='m'".($period == 'm' ? ' selected' : '').">".$strCollectedMonth."</option>\n";
echo "\t\t\t\t</select>\n";

phpAds_ShowBreak();
echo "\t\t\t\t</form>\n";


if ($clientshidden > 0 || $totalviews > 0 || $totalclicks > 0 || $totalconversions > 0)
{
	echo "<br><br>";
	echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
	
	echo "<tr height='25'>";
	echo '<td height="25" width="30%"><b>&nbsp;&nbsp;<a href="stats-global-advertiser.php?listorder=name">'.$GLOBALS['strName'].'</a>';
	if (($listorder == "name") || ($listorder == ""))
	{
		if  (($orderdirection == "") || ($orderdirection == "down"))
		{
			echo ' <a href="stats-global-advertiser.php?orderdirection=up">';
			echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
		}
		else
		{
			echo ' <a href="stats-global-advertiser.php?orderdirection=down">';
			echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
		}
		echo '</a>';
	}
	echo "</b></td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'><b><a href='stats-global-advertiser.php?listorder=id'>".$GLOBALS['strID']."</a>";
	if ($listorder == "id")
	{
		if  (($orderdirection == "") || ($orderdirection == "down"))
		{
			echo ' <a href="stats-global-advertiser.php?orderdirection=up">';
			echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
		}
		else
		{
			echo ' <a href="stats-global-advertiser.php?orderdirection=down">';
			echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
		}
		echo '</a>';
	}
	echo '</b></td>';
	echo "<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strViews']."</b></td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strClicks']."</b></td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strCTRShort']."</b></td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strConversions']."</b></td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'><b>".$GLOBALS['strCNVRShort']."</b></td>";
	echo "</tr>";
	
	echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	
	$i=0;
	for (reset($clients);$key=key($clients);next($clients))
	{
		$client = $clients[$key];
		
		echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
		
		// Icon & name
		echo "<td height='25'>";
		if (isset($client['campaigns']))
		{
			if ($client['expand'] == '1')
				echo "&nbsp;<a href='stats-global-advertiser.php?period=".$period."&amp;collapse=".$client['clientid']."'><img src='images/triangle-d.gif' align='absmiddle' border='0'></a>&nbsp;";
			else
				echo "&nbsp;<a href='stats-global-advertiser.php?period=".$period."&amp;expand=".$client['clientid']."'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>&nbsp;";
		}
		else
			echo "&nbsp;<img src='images/spacer.gif' height='16' width='16'>&nbsp;";
			
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;";
		echo "<a href='stats-advertiser-history.php?clientid=".$client['clientid']."'>".$client['clientname']."</a>";
		echo "</td>";
		
		// ID
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".$client['clientid']."</td>";
		
		// Views
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($client['views'])."</td>";
		
		// Clicks
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($client['clicks'])."</td>";
		
		// CTR
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($client['views'], $client['clicks'])."</td>";
		
		// Conversions
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($client['conversions'])."</td>";
		
		// CNVR
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($client['clicks'], $client['conversions'])."</td>";
		
		
		
		if (isset($client['campaigns']) && sizeof ($client['campaigns']) > 0 && $client['expand'] == '1')
		{
			$campaigns = $client['campaigns'];
			
			for (reset($campaigns);$ckey=key($campaigns);next($campaigns))
			{
				// Divider
				echo "<tr height='1'>";
				echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
				echo "<td colspan='7' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
				echo "</tr>";
				
				// Icon & name
				echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>";
				echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				
				if (isset($campaigns[$ckey]['banners']))
				{
					if ($campaigns[$ckey]['expand'] == '1')
						echo "<a href='stats-global-advertiser.php?period=".$period."&amp;collapse=".$campaigns[$ckey]['campaignid']."'><img src='images/triangle-d.gif' align='absmiddle' border='0'></a>&nbsp;";
					else
						echo "<a href='stats-global-advertiser.php?period=".$period."&amp;expand=".$campaigns[$ckey]['campaignid']."'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>&nbsp;";
				}
				else
					echo "<img src='images/spacer.gif' height='16' width='16' align='absmiddle'>&nbsp;";
				
				
				if ($campaigns[$ckey]['active'] == 't')
					echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;";
				else
					echo "<img src='images/icon-campaign-d.gif' align='absmiddle'>&nbsp;";
				
				echo "<a href='stats-campaign-history.php?clientid=".$client['clientid']."&campaignid=".$campaigns[$ckey]['campaignid']."'>".$campaigns[$ckey]['campaignname']."</td>";
				echo "</td>";
				
				// ID
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".$campaigns[$ckey]['campaignid']."</td>";
				
				// Views
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($campaigns[$ckey]['views'])."</td>";
				
				// Clicks
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($campaigns[$ckey]['clicks'])."</td>";
				
				// CTR
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($campaigns[$ckey]['views'], $campaigns[$ckey]['clicks'])."</td>";
				
				// Conversions
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($campaigns[$ckey]['conversions'])."</td>";
				
				// CNVR
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($campaigns[$ckey]['clicks'], $campaigns[$ckey]['conversions'])."</td>";
				
				
				
				if ($campaigns[$ckey]['expand'] == '1' && isset($campaigns[$ckey]['banners']))
				{
					$banners = $campaigns[$ckey]['banners'];
					for (reset($banners);$bkey=key($banners);next($banners))
					{
						$name = $strUntitled;
						if (isset($banners[$bkey]['alt']) && $banners[$bkey]['alt'] != '') $name = $banners[$bkey]['alt'];
						if (isset($banners[$bkey]['description']) && $banners[$bkey]['description'] != '') $name = $banners[$bkey]['description'];
						
						$name = phpAds_breakString ($name, '30');
						
						// Divider
						echo "<tr height='1'>";
						echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
						echo "<td colspan='6' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
						echo "</tr>";
						
						// Icon & name
						echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
						echo "<td height='25'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						
						if ($banners[$bkey]['active'] == 't' && $campaigns[$ckey]['active'] == 't')
						{
							if ($banners[$bkey]['storagetype'] == 'html')
								echo "<img src='images/icon-banner-html.gif' align='absmiddle'>";
							elseif ($banners[$bkey]['storagetype'] == 'txt')
								echo "<img src='images/icon-banner-text.gif' align='absmiddle'>";
							elseif ($banners[$bkey]['storagetype'] == 'url')
								echo "<img src='images/icon-banner-url.gif' align='absmiddle'>";
							else
								echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>";
						}
						else
						{
							if ($banners[$bkey]['storagetype'] == 'html')
								echo "<img src='images/icon-banner-html-d.gif' align='absmiddle'>";
							elseif ($banners[$bkey]['storagetype'] == 'txt')
								echo "<img src='images/icon-banner-text-d.gif' align='absmiddle'>";
							elseif ($banners[$bkey]['storagetype'] == 'url')
								echo "<img src='images/icon-banner-url-d.gif' align='absmiddle'>";
							else
								echo "<img src='images/icon-banner-stored-d.gif' align='absmiddle'>";
						}
						
						echo "&nbsp;<a href='stats-banner-history.php?clientid=".$client['clientid']."&campaignid=".$campaigns[$ckey]['campaignid']."&bannerid=".$banners[$bkey]['bannerid']."'>".$name."</a></td>";
						
						// ID
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>".$banners[$bkey]['bannerid']."</td>";
						
						// Views
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($banners[$bkey]['views'])."</td>";
						
						// Clicks
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($banners[$bkey]['clicks'])."</td>";
						
						// CTR
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($banners[$bkey]['views'], $banners[$bkey]['clicks'])."</td>";

						// Conversions
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($banners[$bkey]['conversions'])."</td>";
						
						// CNVR
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($banners[$bkey]['clicks'], $banners[$bkey]['conversions'])."</td>";
					}
				}
			}
		}
		

		echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
		$i++;
	}
	
	// Total
	echo "<tr height='25' ".($i % 2 == 0 ? "bgcolor='#F6F6F6'" : "")."><td height='25'>&nbsp;&nbsp;<b>".$strTotal."</b></td>";
	echo "<td height='25'>&nbsp;</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalviews)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalclicks)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalviews, $totalclicks)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($totalconversions)."</td>";
	echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($totalclicks, $totalconversions)."</td>";
	echo "</tr>";
	echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	
	echo "<tr><td height='25' colspan='4' align='".$phpAds_TextAlignLeft."' nowrap>";
	
	if ($hideinactive == true)
	{
		echo "&nbsp;&nbsp;<img src='images/icon-activate.gif' align='absmiddle' border='0'>";
		echo "&nbsp;<a href='stats-global-advertiser.php?period=".$period."&amp;hideinactive=0'>".$strShowAll."</a>";
		echo "&nbsp;&nbsp;|&nbsp;&nbsp;".$clientshidden." ".$strInactiveAdvertisersHidden;
	}
	else
	{
		echo "&nbsp;&nbsp;<img src='images/icon-hideinactivate.gif' align='absmiddle' border='0'>";
		echo "&nbsp;<a href='stats-global-advertiser.php?period=".$period."&amp;hideinactive=1'>".$strHideInactiveAdvertisers."</a>";
	}
	
	echo "</td><td height='25' colspan='3' align='".$phpAds_TextAlignRight."' nowrap>";
	echo "<img src='images/triangle-d.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-global-advertiser.php?period=".$period."&amp;expand=all' accesskey='".$keyExpandAll."'>".$strExpandAll."</a>";
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
	echo "<img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='stats-global-advertiser.php?period=".$period."&amp;expand=none' accesskey='".$keyCollapseAll."'>".$strCollapseAll."</a>&nbsp;&nbsp;";
	echo "</td></tr>";
	
	
	/*
	
	// Spacer
	echo "<tr><td colspan='5' height='40'>&nbsp;</td></tr>";
	
	
	
	// Stats today
	$adviews  = (int)phpAds_totalViews("", "day");
	$adclicks = (int)phpAds_totalClicks("", "day");
	$ctr = phpAds_buildCTR($adviews, $adclicks);
		echo "<tr><td height='25' colspan='2'>&nbsp;&nbsp;<b>".$strToday."</b></td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($adviews)."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($adclicks)."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".$ctr."&nbsp;&nbsp;</td></tr>";
		echo "<tr height='1'><td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	
	// Stats this week
	$adviews  = (int)phpAds_totalViews("", "week");
	$adclicks = (int)phpAds_totalClicks("", "week");
	$ctr = phpAds_buildCTR($adviews, $adclicks);
		echo "<tr><td height='25' colspan='2'>&nbsp;&nbsp;".$strLast7Days."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($adviews)."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($adclicks)."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".$ctr."&nbsp;&nbsp;</td></tr>";
		echo "<tr height='1'><td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	
	// Stats this month
	$adviews  = (int)phpAds_totalViews("", "month");
	$adclicks = (int)phpAds_totalClicks("", "month");
	$ctr = phpAds_buildCTR($adviews, $adclicks);
		echo "<tr><td height='25' colspan='2'>&nbsp;&nbsp;".$strThisMonth."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($adviews)."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($adclicks)."</td>";
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>".$ctr."&nbsp;&nbsp;</td></tr>";
		echo "<tr height='1'><td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	*/
	
	echo "</table>";
	echo "<br><br>";
}
else
{
	echo "<br><br><div class='errormessage'><img class='errormessage' src='images/info.gif' width='16' height='16' border='0' align='absmiddle'>";
	echo $strNoStats.'</div>';
}



/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['stats-global-advertiser.php']['hideinactive'] = $hideinactive;
$Session['prefs']['stats-global-advertiser.php']['listorder'] = $listorder;
$Session['prefs']['stats-global-advertiser.php']['orderdirection'] = $orderdirection;
$Session['prefs']['stats-global-advertiser.php']['nodes'] = implode (",", $node_array);

phpAds_SessionDataStore();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();



?>