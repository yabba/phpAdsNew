<?php

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
	 'action'
	,'trackerids'
	,'clickwindowday'
	,'clickwindowhour'
	,'clickwindowminute'
	,'clickwindows'
	,'clickwindowsecond'
	,'hideinactive'
	,'logids'
	,'submit'
	,'viewwindowday'
	,'viewwindowhour'
	,'viewwindowminute'
	,'viewwindows'
	,'viewwindowsecond'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);

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

/*********************************************************/
/* Process submitted form                                */
/*********************************************************/

if (isset($campaignid) && $campaignid != '')
{
	if (isset($action) && $action == 'set')
	{
		$res = phpAds_dbQuery(
			"DELETE".
			" FROM ".$phpAds_config['tbl_campaigns_trackers'].
			" WHERE campaignid=".$campaignid
		) or phpAds_sqlDie();
			
		if (isset($trackerids) && is_array($trackerids))
		{
			for ($i=0; $i<sizeof($trackerids); $i++)
			{
				$logid = 'n';
				for ($j=0; $j<sizeof($logids); $j++)
				{
					if ($logids[$j] == $trackerids[$i])
					{
						$logid = 'y';
						break;
					}
				}
				
				$clickwindow = $clickwindowday[$i] * (24*60*60) + $clickwindowhour[$i] * (60*60) + $clickwindowminute[$i] * (60) + $clickwindowsecond[$i];
				$viewwindow = $viewwindowday[$i] * (24*60*60) + $viewwindowhour[$i] * (60*60) + $viewwindowminute[$i] * (60) + $viewwindowsecond[$i];
				
				$res = phpAds_dbQuery(
					"INSERT INTO ".$phpAds_config['tbl_campaigns_trackers'].
					" (campaignid, trackerid, logstats, viewwindow, clickwindow)".
					" VALUES (".$campaignid.",".$trackerids[$i].",'".$logid."',".$viewwindow.",".$clickwindow.")"
				) or phpAds_sqlDie();
			}
		}
		
		header ("Location: campaign-trackers.php?clientid=".$clientid."&campaignid=".$campaignid);
		exit;
	}
}



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/


if (!isset($listorder))
{
	if (isset($Session['prefs']['campaign-trackers.php']['listorder']))
		$listorder = $Session['prefs']['campaign-trackers.php']['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['campaign-trackers.php']['orderdirection']))
		$orderdirection = $Session['prefs']['campaign-trackers.php']['orderdirection'];
	else
		$orderdirection = '';
}


// Get other trackers
$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_campaigns'].
	" WHERE clientid=".$clientid.
	phpAds_getCampaignListOrder ($navorder, $navdirection)
);

while ($row = phpAds_dbFetchArray($res))
{
	phpAds_PageContext (
		phpAds_buildName ($row['campaignid'], $row['campaignname']),
		"campaign-trackers.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
		$campaignid == $row['campaignid']
	);
}

if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
{
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	phpAds_PageShortcut($strCampaignHistory, 'stats-campaign-history.php?clientid='.$clientid.'&campaignid='.$campaignid, 'images/icon-statistics.gif');
	
	$extra  = "\t\t\t\t<form action='campaign-modify.php'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='campaignid' value='$campaignid'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='clientid' value='$clientid'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='returnurl' value='campaign-trackers.php'>"."\n";
	$extra .= "\t\t\t\t<br><br>"."\n";
	$extra .= "\t\t\t\t<b>$strModifyCampaign</b><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/icon-move-campaign.gif' align='absmiddle'>&nbsp;$strMoveTo<br>"."\n";
	$extra .= "\t\t\t\t<img src='images/spacer.gif' height='1' width='160' vspace='2'><br>"."\n";
	$extra .= "\t\t\t\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"."\n";
	$extra .= "\t\t\t\t<select name='moveto' style='width: 110;'>"."\n";
	
	if (phpAds_isUser(phpAds_Admin))
	{
		$query = "SELECT clientid,clientname".
		" FROM ".$phpAds_config['tbl_clients'].
			" WHERE clientid!=".$clientid;
	}
	elseif (phpAds_isUser(phpAds_Agency))
	{
		$query = "SELECT clientid,clientname".
			" FROM ".$phpAds_config['tbl_clients'].
			" WHERE clientid!=".$clientid.
			" AND agencyid=".phpAds_getUserID();
	}
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
		$extra .= "\t\t\t\t\t<option value='".$row['clientid']."'>".phpAds_buildName($row['clientid'], $row['clientname'])."</option>\n";
	
	$extra .= "\t\t\t\t</select>&nbsp;\n";
	$extra .= "\t\t\t\t<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif'><br>\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>\n";
	$extra .= "\t\t\t\t<img src='images/icon-recycle.gif' align='absmiddle'>\n";
	$extra .= "\t\t\t\t<a href='campaign-delete.php?clientid=$clientid&campaignid=$campaignid&returnurl=advertiser-campaigns.php'".phpAds_DelConfirm($strConfirmDeleteTracker).">$strDelete</a><br>\n";
	$extra .= "\t\t\t\t</form>\n";

	
	phpAds_PageHeader("4.1.3.5", $extra);
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getParentClientName($campaignid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".phpAds_getCampaignName($campaignid)."</b><br><br><br>";
		phpAds_ShowSections(array("4.1.3.2", "4.1.3.3", "4.1.3.4", "4.1.3.5"));
}

if (isset($campaignid) && $campaignid != '')
{
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE campaignid=".$campaignid
	) or phpAds_sqlDie();
	
	if (phpAds_dbNumRows($res))
	{
		$campaign = phpAds_dbFetchArray($res);
	}
}

$tabindex = 1;


// Header
echo "\t\t\t\t<table width='100%' border='0' align='center' cellspacing='0' cellpadding='0'>\n";
echo "\t\t\t\t<tr height='25'>\n";
echo "\t\t\t\t\t<td height='25' width='40%'>\n";
echo "\t\t\t\t\t\t<b>&nbsp;&nbsp;<a href='campaign-trackers.php?clientid=".$clientid."&campaignid=".$campaignid."&listorder=name'>".$GLOBALS['strName']."</a>";

if (($listorder == "name") || ($listorder == ""))
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo " <a href='campaign-trackers.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=up'>";
		echo "<img src='images/caret-ds.gif' border='0' alt='' title=''>";
	}
	else
	{
		echo " <a href='campaign-trackers.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=down'>";
		echo "<img src='images/caret-u.gif' border='0' alt='' title=''>";
	}
	echo "</a>";
}

echo "</b>\n";
echo "\t\t\t\t\t</td>\n";
echo "\t\t\t\t\t<td width='40'>";
echo "<b><a href='campaign-trackers.php?clientid=".$clientid."&campaignid=".$campaignid."&listorder=id'>".$GLOBALS['strID']."</a>";

if ($listorder == "id")
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo " <a href='campaign-trackers.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=up'>";
		echo "<img src='images/caret-ds.gif' border='0' alt='' title=''>";
	}
	else
	{
		echo " <a href='campaign-trackers.php?clientid=".$clientid."&campaignid=".$campaignid."&orderdirection=down'>";
		echo "<img src='images/caret-u.gif' border='0' alt='' title=''>";
	}
	echo "</a>";
}
echo "</b></td>\n";

echo "\t\t\t\t\t<td width='40'>\n";
echo "\t\t\t\t\t\t<b>".$GLOBALS['strLog']."</b>\n";
echo "\t\t\t\t\t</td>\n";

echo "\t\t\t\t\t<td>\n";
echo "\t\t\t\t\t\t<b>".$GLOBALS['strConversionWindow']."</b>\n";
echo "\t\t\t\t\t</td>\n";

echo "\t\t\t\t</tr>\n";

echo "\t\t\t\t<tr height='1'>\n";
echo "\t\t\t\t\t<td colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>\n";
echo "\t\t\t\t</tr>\n";

$i = 0;
$checkedall = true;

if ( isset($campaignid) && ($campaignid > 0) )
{
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_campaigns_trackers'].
		" WHERE campaignid=".$campaignid
	) or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$campaign_tracker_row[$row['trackerid']] = $row;
	}
}

$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_trackers'].
	" WHERE clientid=".$clientid.
	phpAds_getTrackerListOrder ($listorder, $orderdirection)
) or phpAds_sqlDie();

if (phpAds_dbNumRows($res) == 0)
{
	echo "\t\t\t\t<tr bgcolor='#F6F6F6'>\n";
	echo "\t\t\t\t\t<td colspan='4' height='25'>&nbsp;&nbsp;".$strNoTrackersToLink."</td>\n";
	echo "\t\t\t\t</tr>\n";
}
else
{
	echo "\t\t\t\t<form name='availabletrackers' method='post' action='campaign-trackers.php'>\n";
	echo "\t\t\t\t<input type='hidden' name='campaignid' value='".$GLOBALS['campaignid']."'>\n";
	echo "\t\t\t\t<input type='hidden' name='clientid' value='".$GLOBALS['clientid']."'>\n";
	echo "\t\t\t\t<input type='hidden' name='action' value='set'>\n";
	while ($row = phpAds_dbFetchArray($res))
		$trackers[$row['trackerid']] = $row;

	for (reset($trackers); $tkey = key($trackers); next($trackers))
	{
		$tracker = $trackers[$tkey];
		
		if ($i > 0)
		{
			echo "\t\t\t\t<tr height='1'>\n";
			echo "\t\t\t\t\t<td colspan='4' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>\n";
			echo "\t\t\t\t</tr>\n";
		}
		echo "\t\t\t\t<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">\n";
		
		// Begin row
		echo "\t\t\t\t\t<td height='25'>";

		// Show checkbox
		if (isset($campaign_tracker_row[$tracker['trackerid']]))
			echo "<input id='trk".$tracker['trackerid']."' type='checkbox' name='trackerids[]' value='".$tracker['trackerid']."' checked onclick='phpAds_reviewAll();' tabindex='".($tabindex++)."'>";
		else
		{
			echo "<input id='trk".$tracker['trackerid']."' type='checkbox' name='trackerids[]' value='".$tracker['trackerid']."' onclick='phpAds_reviewAll();' tabindex='".($tabindex++)."'>";
			$checkedall = false;
		}
		
		// Campaign icon
		echo "<img src='images/icon-tracker.gif' align='absmiddle'>";
		
		
		// Name
		if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
		{
			echo "<a href='tracker-edit.php?clientid=".$tracker['clientid']."&trackerid=".$tracker['trackerid']."'>";
			echo phpAds_breakString ($tracker['trackername'], '60')."</a>";
		}
		else
			echo phpAds_breakString ($tracker['trackername'], '60');
		
		echo "</td>\n";
		
		
		// ID
		echo "\t\t\t\t\t<td height='25'>".$tracker['trackerid']."</td>\n";
		
		// Log
		if (isset($campaign_tracker_row[$tracker['trackerid']]) && $campaign_tracker_row[$tracker['trackerid']]['logstats'] == 'y')
			echo "\t\t\t\t\t<td height='25'><input id='logtrk".$tracker['trackerid']."' type='checkbox' name='logids[]' value='".$tracker['trackerid']."' checked onclick='phpAds_reviewAll();' tabindex='".($tabindex++)."'></td>\n";
		else
			echo "\t\t\t\t\t<td height='25'><input id='logtrk".$tracker['trackerid']."' type='checkbox' name='logids[]' value='".$tracker['trackerid']."' onclick='phpAds_reviewAll();' tabindex='".($tabindex++)."'></td>\n";

		$seconds_left = $tracker['clickwindow'];
		if (isset($campaign_tracker_row[$tracker['trackerid']]))
			$seconds_left = $campaign_tracker_row[$tracker['trackerid']]['clickwindow'];
		
		$clickwindowday = floor($seconds_left / (60*60*24));
		$seconds_left = $seconds_left % (60*60*24);
		$clickwindowhour = floor($seconds_left / (60*60));
		$seconds_left = $seconds_left % (60*60);
		$clickwindowminute = floor($seconds_left / (60));
		$seconds_left = $seconds_left % (60);
		$clickwindowsecond = $seconds_left;
		
		// Click Window
		echo "<td nowrap>".$strClick."&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input id='clickwindowdaytrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='clickwindowday[]' value='".$clickwindowday."' onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strDays." &nbsp;&nbsp;";
		echo "<input id='clickwindowhourtrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='clickwindowhour[]' value='".$clickwindowhour."' onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strHours." &nbsp;&nbsp;";
		echo "<input id='clickwindowminutetrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='clickwindowminute[]' value='".$clickwindowminute."' onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strMinutes." &nbsp;&nbsp;";
		echo "<input id='clickwindowsecondtrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='clickwindowsecond[]' value='".$clickwindowsecond."' onBlur=\"phpAds_formLimitBlur('".$tracker['trackerid']."');\" onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strSeconds." &nbsp;&nbsp;";
		echo "</td>";

		echo "\t\t\t\t</tr>\n";

		// Mini Break Line
		echo "\t\t\t\t<tr height='1'>\n";
		echo "\t\t\t\t\t<td".($i%2==0?" bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' height='1' width='100%'></td>\n";
		echo "\t\t\t\t\t<td colspan='3'><img src='images/break-l.gif' height='1' width='100%'></td>\n";
		echo "\t\t\t\t</tr>\n";
		
		echo "<tr height='25'".($i%2==0?" bgcolor='#F6F6F6'":"").">";
		echo "<td>&nbsp;</td>";
		echo "<td>&nbsp;</td>";
		echo "<td>&nbsp;</td>";

		$seconds_left = $tracker['viewwindow'];
		if (isset($campaign_tracker_row[$tracker['trackerid']]))
			$seconds_left = $campaign_tracker_row[$tracker['trackerid']]['viewwindow'];
		
		$viewwindowday = floor($seconds_left / (60*60*24));
		$seconds_left = $seconds_left % (60*60*24);
		$viewwindowhour = floor($seconds_left / (60*60));
		$seconds_left = $seconds_left % (60*60);
		$viewwindowminute = floor($seconds_left / (60));
		$seconds_left = $seconds_left % (60);
		$viewwindowsecond = $seconds_left;

		// View Window
		echo "<td nowrap>".$strView."&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input id='viewwindowdaytrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='viewwindowday[]' value='".$viewwindowday."' onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strDays." &nbsp;&nbsp;";
		echo "<input id='viewwindowhourtrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='viewwindowhour[]' value='".$viewwindowhour."' onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strHours." &nbsp;&nbsp;";
		echo "<input id='viewwindowminutetrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='viewwindowminute[]' value='".$viewwindowminute."' onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strMinutes." &nbsp;&nbsp;";
		echo "<input id='viewwindowsecondtrk".$tracker['trackerid']."' class='flat' type='text' size='3' name='viewwindowsecond[]' value='".$viewwindowsecond."' onBlur=\"phpAds_formLimitBlur('".$tracker['trackerid']."');\" onKeyUp=\"phpAds_formLimitUpdate('".$tracker['trackerid']."');\" tabindex='".($tabindex++)."'> ".$strSeconds." &nbsp;&nbsp;";
		echo "</td>";

		// End row
		echo "</tr>"."\n";
		
		$i++;
	}
}

echo "<tr height='1'><td colspan='4' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>"."\n";
echo "<input type='checkbox' name='checkall' value=''".($checkedall == true ? ' checked' : '')." onclick='phpAds_toggleAll();' tabindex='".($tabindex++)."'>"."\n";
echo "<b>".$strCheckAllNone."</b>"."\n";
echo "</td>\n";
echo "<td>&nbsp;</td>\n";
echo "<td>&nbsp;</td>\n";
echo "<td>&nbsp;</td>\n";
echo "</tr>\n";

echo "<tr height='1'><td colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='25' align='".$phpAds_TextAlignLeft."' nowrap>&nbsp;</td>\n";
echo "<td colspan='2' align='".$phpAds_TextAlignRight."' nowrap>"."\n";

echo "&nbsp;&nbsp;</td></tr>"."\n";
echo "</table>"."\n";
echo "<br><br><br><br>"."\n";

echo "<input type='submit' name='submit' value='$strSaveChanges' tabindex='".($tabindex++)."'>"."\n";
echo "</form>"."\n";

?>
<script language='Javascript'>
<!--
	function phpAds_getAllChecked()
	{
		var allchecked = false;
		
		for (var i=0; i<document.availabletrackers.elements.length; i++)
		{
			if (document.availabletrackers.elements[i].name == 'trackerids[]')
			{
				if (document.availabletrackers.elements[i].checked == false)
				{
					allchecked = true;
				}
			}
		}
		
		return allchecked;
	}
	
	function phpAds_toggleAll()
	{
		var allchecked = phpAds_getAllChecked();
				
		for (var i=0; i<document.availabletrackers.elements.length; i++)
		{
			if (document.availabletrackers.elements[i].name == 'trackerids[]')
			{
				document.availabletrackers.elements[i].checked = allchecked;
			}
		}
		
		phpAds_reviewAll();
	}
	
	function phpAds_reviewAll()
	{
		for (var i=0; i<document.availabletrackers.elements.length; i++)
		{
			var element = document.availabletrackers.elements[i];
			if (element.id.substring(0,3) == 'trk')
			{
				var trkid = element.id.substring(3);
				phpAds_formLimitBlur(trkid);
				phpAds_formLimitUpdate(trkid);
				
				var logelement = document.getElementById('log' + element.id);
				if (logelement) logelement.disabled = !element.checked;
				
				var cwday = document.getElementById('clickwindowday' + element.id);
				if (cwday) cwday.disabled = !element.checked;
				
				var cwhour = document.getElementById('clickwindowhour' + element.id);
				if (cwhour) cwhour.disabled = !element.checked;
				
				var cwminute = document.getElementById('clickwindowminute' + element.id);
				if (cwminute) cwminute.disabled = !element.checked;
				
				var cwsecond = document.getElementById('clickwindowsecond' + element.id);
				if (cwsecond) cwsecond.disabled = !element.checked;
				
				var vwday = document.getElementById('viewwindowday' + element.id);
				if (vwday) vwday.disabled = !element.checked;
				
				var vwhour = document.getElementById('viewwindowhour' + element.id);
				if (vwhour) vwhour.disabled = !element.checked;
				
				var vwminute = document.getElementById('viewwindowminute' + element.id);
				if (vwminute) vwminute.disabled = !element.checked;
				
				var vwsecond = document.getElementById('viewwindowsecond' + element.id);
				if (vwsecond) vwsecond.disabled = !element.checked;
			}
		}
				
		document.availabletrackers.checkall.checked = !phpAds_getAllChecked();
	}

	function phpAds_formLimitBlur (trkid)
	{
		var cwday = document.getElementById('clickwindowdaytrk'+trkid);
		var cwhour = document.getElementById('clickwindowhourtrk'+trkid);
		var cwminute = document.getElementById('clickwindowminutetrk'+trkid);
		var cwsecond = document.getElementById('clickwindowsecondtrk'+trkid);

		var vwday = document.getElementById('viewwindowdaytrk'+trkid);
		var vwhour = document.getElementById('viewwindowhourtrk'+trkid);
		var vwminute = document.getElementById('viewwindowminutetrk'+trkid);
		var vwsecond = document.getElementById('viewwindowsecondtrk'+trkid);
		
		if (cwday.value == '') cwday.value = '0';
		if (cwhour.value == '') cwhour.value = '0';
		if (cwminute.value == '') cwminute.value = '0';
		if (cwsecond.value == '') cwsecond.value = '0';
		
		if (vwday.value == '') vwday.value = '0';
		if (vwhour.value == '') vwhour.value = '0';
		if (vwminute.value == '') vwminute.value = '0';
		if (vwsecond.value == '') vwsecond.value = '0';
		
		phpAds_formLimitUpdate (trkid);
	}
			
	function phpAds_formLimitUpdate (trkid)
	{
		var cwday = document.getElementById('clickwindowdaytrk'+trkid);
		var cwhour = document.getElementById('clickwindowhourtrk'+trkid);
		var cwminute = document.getElementById('clickwindowminutetrk'+trkid);
		var cwsecond = document.getElementById('clickwindowsecondtrk'+trkid);

		var vwday = document.getElementById('viewwindowdaytrk'+trkid);
		var vwhour = document.getElementById('viewwindowhourtrk'+trkid);
		var vwminute = document.getElementById('viewwindowminutetrk'+trkid);
		var vwsecond = document.getElementById('viewwindowsecondtrk'+trkid);
		
		// Set -
		if (cwhour.value == '-' && cwday.value != '-') cwhour.value = '0';
		if (cwminute.value == '-' && cwhour.value != '-') cwminute.value = '0';
		if (cwsecond.value == '-' && cwminute.value != '-') cwsecond.value = '0';
		
		// Set 0
		if (cwday.value == '0') cwday.value = '-';
		if (cwday.value == '-' && cwhour.value == '0') cwhour.value = '-';
		if (cwhour.value == '-' && cwminute.value == '0') cwminute.value = '-';
		if (cwminute.value == '-' && cwsecond.value == '0') cwsecond.value = '-';

		// Set -
		if (vwhour.value == '-' && vwday.value != '-') vwhour.value = '0';
		if (vwminute.value == '-' && vwhour.value != '-') vwminute.value = '0';
		if (vwsecond.value == '-' && vwminute.value != '-') vwsecond.value = '0';
		
		// Set 0
		if (vwday.value == '0') vwday.value = '-';
		if (vwday.value == '-' && vwhour.value == '0') vwhour.value = '-';
		if (vwhour.value == '-' && vwminute.value == '0') vwminute.value = '-';
		if (vwminute.value == '-' && vwsecond.value == '0') vwsecond.value = '-';
	}
	
	phpAds_reviewAll();
//-->
</script>

<?php
/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['campaign-trackers.php']['listorder'] = $listorder;
$Session['prefs']['campaign-trackers.php']['orderdirection'] = $orderdirection;

phpAds_SessionDataStore();


/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>