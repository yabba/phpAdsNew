<?php // $Revision: 1.1 $

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
	,'listorder'
	,'orderdirection'
);


// Security check
phpAds_checkAccess(phpAds_Admin);



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (isset($Session['prefs']['advertiser-index.php']['listorder']))
	$navorder = $Session['prefs']['advertiser-index.php']['listorder'];
else
	$navorder = '';

if (isset($Session['prefs']['advertiser-index.php']['orderdirection']))
	$navdirection = $Session['prefs']['advertiser-index.php']['orderdirection'];
else
	$navdirection = '';


// Get other clients
$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_clients'].
	phpAds_getClientListOrder ($navorder, $navdirection)
) or phpAds_sqlDie();

while ($row = phpAds_dbFetchArray($res))
{
	phpAds_PageContext (
		phpAds_buildName ($row['clientid'], $row['clientname']),
		"advertiser-trackers.php?clientid=".$row['clientid'],
		$clientid == $row['clientid']
	);
}

phpAds_PageShortcut($strClientHistory, 'stats-advertiser-history.php?clientid='.$clientid, 'images/icon-statistics.gif');

phpAds_PageHeader("4.1.4");
	echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;<b>".phpAds_getClientName($clientid)."</b><br><br><br>";
	phpAds_ShowSections(array("4.1.2", "4.1.3", "4.1.4"));



/*********************************************************/
/* Get preferences                                       */
/*********************************************************/

if (!isset($listorder))
{
	if (isset($Session['prefs']['advertiser-trackers.php'][$clientid]['listorder']))
		$listorder = $Session['prefs']['advertiser-trackers.php'][$clientid]['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['advertiser-trackers.php'][$clientid]['orderdirection']))
		$orderdirection = $Session['prefs']['advertiser-trackers.php'][$clientid]['orderdirection'];
	else
		$orderdirection = '';
}

if (isset($Session['prefs']['advertiser-trackers.php'][$clientid]['nodes']))
	$node_array = explode (",", $Session['prefs']['advertiser-trackers.php'][$clientid]['nodes']);
else
	$node_array = array();



/*********************************************************/
/* Main code                                             */
/*********************************************************/

// Get clients & tracker and build the tree
$res_trackers = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_trackers'].
	" WHERE clientid=".$clientid.
	phpAds_getTrackerListOrder ($listorder, $orderdirection)
) or phpAds_sqlDie();

while ($row_trackers = phpAds_dbFetchArray($res_trackers))
{
	$trackers[$row_trackers['clientid']] = $row_trackers;
	$trackers[$row_trackers['clientid']]['expand'] = 0;
	$trackers[$row_trackers['clientid']]['count'] = 0;
}


// Get the markers for each tracker
$res_markers = phpAds_dbQuery(
	"SELECT".
	" markerid".
	",trackerid".
	",description".
	" FROM ".$phpAds_config['tbl_markers'].
	phpAds_getMarkerListOrder ($listorder, $orderdirection)
) or phpAds_sqlDie();

while ($row_markers = phpAds_dbFetchArray($res_markers))
{
	if (isset($trackers[$row_markers['trackerid']]))
	{
		$markers[$row_markers['markerid']] = $row_markers;
		$trackers[$row_markers['trackerid']]['count']++;
	}
}



// Add ID found in expand to expanded nodes
if (isset($expand) && $expand != '')
{
	switch ($expand)
	{
		case 'all' :	$node_array   = array();
						if (isset($trackers)) while (list($key,) = each($trackers)) $node_array[] = $key;
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
		if (isset($trackers[$node_array[$i]]))
			$trackers[$node_array[$i]]['expand'] = 1;
	}
}


// Build Tree
if (isset($markers) && is_array($markers) && count($markers) > 0)
{
	// Add marker to trackers
	reset ($markers);
	while (list ($bkey, $marker) = each ($markers))
		$trackers[$marker['clientid']]['markers'][$bkey] = $marker;
	
	unset ($markers);
}

if (isset($trackers) && is_array($trackers) && count($trackers) > 0)
{
	reset ($trackers);
	while (list ($key, $tracker) = each ($trackers))
	{
		if (!isset($tracker['markers']))
			$tracker['markers'] = array();
	}
}


echo "<img src='images/icon-tracker-new.gif' border='0' align='absmiddle'>&nbsp;";
echo "<a href='tracker-edit.php?clientid=".$clientid."' accesskey='".$keyAddNew."'>".$strAddTracker_Key."</a>&nbsp;&nbsp;";
phpAds_ShowBreak();



echo "<br><br>";
echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";	

echo "<tr height='25'>";
echo "<td height='25' width='40%'><b>&nbsp;&nbsp;<a href='advertiser-trackers.php?clientid=".$clientid."&listorder=name'>".$GLOBALS['strName']."</a>";

if (($listorder == "name") || ($listorder == ""))
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo ' <a href="advertiser-trackers.php?clientid='.$clientid.'&orderdirection=up">';
		echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
	}
	else
	{
		echo ' <a href="advertiser-trackers.php?clientid='.$clientid.'&orderdirection=down">';
		echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
	}
	echo '</a>';
}

echo '</b></td>';
echo '<td height="25"><b><a href="advertiser-trackers.php?clientid='.$clientid.'&listorder=id">'.$GLOBALS['strID'].'</a>';

if ($listorder == "id")
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo ' <a href="advertiser-trackers.php?clientid='.$clientid.'&orderdirection=up">';
		echo '<img src="images/caret-ds.gif" border="0" alt="" title="">';
	}
	else
	{
		echo ' <a href="advertiser-trackers.php?clientid='.$clientid.'&orderdirection=down">';
		echo '<img src="images/caret-u.gif" border="0" alt="" title="">';
	}
	echo '</a>';
}

echo '</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
echo "<td height='25'>&nbsp;</td>";
echo "<td height='25'>&nbsp;</td>";
echo "<td height='25'>&nbsp;</td>";
echo "</tr>";

echo "<tr height='1'><td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";


if (!isset($trackers) || !is_array($trackers) || count($trackers) == 0)
{
	echo "<tr height='25' bgcolor='#F6F6F6'><td height='25' colspan='5'>";
	echo "&nbsp;&nbsp;".$strNoTrackers;
	echo "</td></tr>";
	
	echo "<td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>";
}
else
{
	$i=0;
	
	
	for (reset($trackers);$ckey=key($trackers);next($trackers))
	{
		// Icon & name
		echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>";
		echo "&nbsp;";
		
		if (isset($trackers[$ckey]['markers']))
		{
			if ($trackers[$ckey]['expand'] == '1')
				echo "<a href='advertiser-trackers.php?clientid=".$clientid."&collapse=".$trackers[$ckey]['clientid']."'><img src='images/triangle-d.gif' align='absmiddle' border='0'></a>&nbsp;";
			else
				echo "<a href='advertiser-trackers.php?clientid=".$clientid."&expand=".$trackers[$ckey]['clientid']."'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>&nbsp;";
		}
		else
			echo "<img src='images/spacer.gif' height='16' width='16' align='absmiddle'>&nbsp;";
		
		
		if ($trackers[$ckey]['active'] == 't')
			echo "<img src='images/icon-tracker.gif' align='absmiddle'>&nbsp;";
		else
			echo "<img src='images/icon-tracker-d.gif' align='absmiddle'>&nbsp;";
		
		echo "<a href='tracker-edit.php?clientid=".$clientid."&trackerid=".$trackers[$ckey]['trackerid']."'>".$trackers[$ckey]['trackername']."</td>";
		echo "</td>";
		
		// ID
		echo "<td height='25'>".$trackers[$ckey]['clientid']."</td>";
		
		// Button 1
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>";
		if ($trackers[$ckey]['expand'] == '1' || !isset($trackers[$ckey]['markers']))
			echo "<a href='marker-edit.php?clientid=".$clientid."&trackerid=".$trackers[$ckey]['trackerid']."'><img src='images/icon-marker-new.gif' border='0' align='absmiddle' alt='$strCreate'>&nbsp;$strCreate</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		else
			echo "&nbsp;";
		echo "</td>";
		
		// Button 2
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>";
		echo "<a href='tracker-markers.php?clientid=".$clientid."&trackerid=".$trackers[$ckey]['trackerid']."'><img src='images/icon-overview.gif' border='0' align='absmiddle' alt='$strOverview'>&nbsp;$strOverview</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "</td>";
		
		// Button 3
		echo "<td height='25' align='".$phpAds_TextAlignRight."'>";
		echo "<a href='tracker-delete.php?clientid=".$clientid."&trackerid=".$trackers[$ckey]['trackerid']."&returnurl=advertiser-trackers.php'".phpAds_DelConfirm($strConfirmDeleteTracker)."><img src='images/icon-recycle.gif' border='0' align='absmiddle' alt='$strDelete'>&nbsp;$strDelete</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "</td></tr>";
		
		if ($trackers[$ckey]['expand'] == '1' && isset($trackers[$ckey]['markers']))
		{
			$markers = $trackers[$ckey]['markers'];
			for (reset($markers);$bkey=key($markers);next($markers))
			{
				$name = $strUntitled;
				if (isset($markers[$bkey]['alt']) && $markers[$bkey]['alt'] != '') $name = $markers[$bkey]['alt'];
				if (isset($markers[$bkey]['description']) && $markers[$bkey]['description'] != '') $name = $markers[$bkey]['description'];
				
				$name = phpAds_breakString ($name, '30');
				
				// Divider
				echo "<tr height='1'>";
				echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
				echo "<td colspan='4' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
				echo "</tr>";
				
				// Icon & name
				echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
				echo "<td height='25'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				
				if ($markers[$bkey]['active'] == 't' && $trackers[$ckey]['active'] == 't')
				{
					if ($markers[$bkey]['storagetype'] == 'html')
						echo "<img src='images/icon-marker-html.gif' align='absmiddle'>";
					elseif ($markers[$bkey]['storagetype'] == 'txt')
						echo "<img src='images/icon-marker-text.gif' align='absmiddle'>";
					elseif ($markers[$bkey]['storagetype'] == 'url')
						echo "<img src='images/icon-marker-url.gif' align='absmiddle'>";
					else
						echo "<img src='images/icon-marker-stored.gif' align='absmiddle'>";
				}
				else
				{
					if ($markers[$bkey]['storagetype'] == 'html')
						echo "<img src='images/icon-marker-html-d.gif' align='absmiddle'>";
					elseif ($markers[$bkey]['storagetype'] == 'txt')
						echo "<img src='images/icon-marker-text-d.gif' align='absmiddle'>";
					elseif ($markers[$bkey]['storagetype'] == 'url')
						echo "<img src='images/icon-marker-url-d.gif' align='absmiddle'>";
					else
						echo "<img src='images/icon-marker-stored-d.gif' align='absmiddle'>";
				}
				
				echo "&nbsp;<a href='marker-edit.php?clientid=".$clientid."&trackerid=".$trackers[$ckey]['clientid']."&markerid=".$markers[$bkey]['markerid']."'>".$name."</a></td>";
				
				// ID
				echo "<td height='25'>".$markers[$bkey]['markerid']."</td>";
				
				// Empty
				echo "<td>&nbsp;</td>";
				
				// Button 2
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>";
				echo "<a href='marker-acl.php?clientid=".$clientid."&trackerid=".$trackers[$ckey]['clientid']."&markerid=".$markers[$bkey]['markerid']."'><img src='images/icon-acl.gif' border='0' align='absmiddle' alt='$strACL'>&nbsp;$strACL</a>&nbsp;&nbsp;&nbsp;&nbsp;";
				echo "</td>";
				
				// Button 3
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>";
				echo "<a href='marker-delete.php?clientid=".$clientid."&trackerid=".$trackers[$ckey]['clientid']."&markerid=".$markers[$bkey]['markerid']."&returnurl=advertiser-trackers.php'".phpAds_DelConfirm($strConfirmDeleteMarker)."><img src='images/icon-recycle.gif' border='0' align='absmiddle' alt='$strDelete'>&nbsp;$strDelete</a>&nbsp;&nbsp;&nbsp;&nbsp;";
				echo "</td></tr>";
			}
		}
		
		if ($phpAds_config['gui_show_tracker_info'])
		{
			echo "<tr height='1'>";
			echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
			echo "<td colspan='4' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
			echo "</tr>";
			
			echo "<tr ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td colspan='1'>&nbsp;</td><td colspan='4'>";
			echo "<table width='100%' cellpadding='0' cellspacing='0' border='0'>";
			
			echo "<tr height='25'><td width='50%'>".$strViewsPurchased.": ".($trackers[$ckey]['views'] >= 0 ? $trackers[$ckey]['views'] : $strUnlimited)."</td>";
			echo "<td width='50%'>".$strClicksPurchased.": ".($trackers[$ckey]['clicks'] >= 0 ? $trackers[$ckey]['clicks'] : $strUnlimited)."</td></tr>";
			
			echo "<tr height='15'><td width='50%'>".$strActivationDate.": ".($trackers[$ckey]['activate'] != '0000-00-00' ? $trackers[$ckey]['activate_f'] : '-')."</td>";
			echo "<td width='50%'>".$strExpirationDate.": ".($trackers[$ckey]['expire'] != '0000-00-00' ? $trackers[$ckey]['expire_f'] : '-')."</td></tr>";
			
			echo "<tr height='25'><td width='50%'>".$strPriority.": ".($trackers[$ckey]['target'] > 0 ? $strHigh : $strLow)."</td>";
			
			if ($trackers[$ckey]['target'] > 0)
				echo "<td width='50%'>".$strtrackerTarget.": ".$trackers[$ckey]['target']."</td></tr>";
			else
				echo "<td width='50%'>".$strWeight.": ".$trackers[$ckey]['weight']."</td></tr>";
			
			echo "</table><br></td></tr>";
		}
		
		echo "<tr height='1'><td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
		$i++;
	}
}

echo "<tr height='25'><td colspan='2' height='25' nowrap>";

echo "</td>";
echo "<td colspan='3' height='25' align='".$phpAds_TextAlignRight."' nowrap>";
echo "<img src='images/triangle-d.gif' align='absmiddle' border='0'>";
echo "&nbsp;<a href='advertiser-trackers.php?clientid=".$clientid."&expand=all' accesskey='".$keyExpandAll."'>".$strExpandAll."</a>";
echo "&nbsp;&nbsp;|&nbsp;&nbsp;";
echo "<img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'>";
echo "&nbsp;<a href='advertiser-trackers.php?clientid=".$clientid."&expand=none' accesskey='".$keyCollapseAll."'>".$strCollapseAll."</a>&nbsp;&nbsp;";
echo "</td>";
echo "</tr>";

if (isset($trackers) && count($trackers))
{
	echo "<tr height='1'><td colspan='5' bgcolor='#888888'><img src='images/break-el.gif' height='1' width='100%'></td></tr>";
	echo "<tr height='25'>";
	echo "<td colspan='5' height='25' align='".$phpAds_TextAlignRight."'>";
	echo "<img src='images/icon-recycle.gif' border='0' align='absmiddle'>&nbsp;<a href='tracker-delete.php?clientid=".$clientid."&returnurl=advertiser-trackers.php'".phpAds_DelConfirm($strConfirmDeleteAllTrackers).">$strDeleteAllTrackers</a>&nbsp;&nbsp;";
	echo "</td>";
	echo "</tr>";
}

echo "</table>";
echo "<br><br>";



/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['advertiser-trackers.php'][$clientid]['listorder'] = $listorder;
$Session['prefs']['advertiser-trackers.php'][$clientid]['orderdirection'] = $orderdirection;
$Session['prefs']['advertiser-trackers.php'][$clientid]['nodes'] = implode (",", $node_array);

phpAds_SessionDataStore();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>