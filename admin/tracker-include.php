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
	,'campaignids'
	,'hideinactive'
	,'submit'
);


// Security check
phpAds_checkAccess(phpAds_Admin);



/*********************************************************/
/* Affiliate interface security                          */
/*********************************************************/

if (phpAds_isUser(phpAds_Client))
{
	$result = phpAds_dbQuery(
		"SELECT clientid".
		" FROM ".$phpAds_config['tbl_trackers'].
		" WHERE trackerid=".$trackerid
	) or phpAds_sqlDie();
	
	$row = phpAds_dbFetchArray($result);
	
	if ($row['clientid'] == '' || phpAds_getUserID() != $row['clientid'] || !phpAds_isAllowed(phpAds_LinkCampaigns))
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
	else
	{
		$clientid = $row['clientid'];
	}
}



/*********************************************************/
/* Process submitted form                                */
/*********************************************************/

if (isset($trackerid) && $trackerid != '')
{
	if (isset($action) && $action == 'set')
	{
		$res = phpAds_dbQuery(
			"DELETE".
			" FROM ".$phpAds_config['tbl_campaigns_trackers'].
			" WHERE trackerid=".$trackerid
		) or phpAds_sqlDie();
			
		if (isset($campaignids) && is_array($campaignids))
		{
			for ($i=0; $i<sizeof($campaignids); $i++)
			{
				$res = phpAds_dbQuery(
					"INSERT INTO ".$phpAds_config['tbl_campaigns_trackers'].
					" (campaignid, trackerid)".
					" VALUES (".$campaignids[$i].",".$trackerid.")"
				) or phpAds_sqlDie();
			}
		}
		
		header ("Location: tracker-invocation.php?clientid=".$clientid."&trackerid=".$trackerid);
		exit;
	}
}



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (!isset($hideinactive))
{
	if (isset($Session['prefs']['tracker-include.php']['hideinactive']))
		$hideinactive = $Session['prefs']['tracker-include.php']['hideinactive'];
	else
		$hideinactive = ($phpAds_config['gui_hide_inactive'] == 't');
}

if (!isset($listorder))
{
	if (isset($Session['prefs']['tracker-include.php']['listorder']))
		$listorder = $Session['prefs']['tracker-include.php']['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['tracker-include.php']['orderdirection']))
		$orderdirection = $Session['prefs']['tracker-include.php']['orderdirection'];
	else
		$orderdirection = '';
}


// Get other trackers
$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_trackers'].
	" WHERE clientid=".$clientid.
	phpAds_getTrackerListOrder ($navorder, $navdirection)
);

while ($row = phpAds_dbFetchArray($res))
{
	phpAds_PageContext (
		phpAds_buildName ($row['trackerid'], $row['trackername']),
		"tracker-include.php?clientid=".$clientid."&trackerid=".$row['trackerid'],
		$trackerid == $row['trackerid']
	);
}

if (phpAds_isUser(phpAds_Admin))
{
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	//phpAds_PageShortcut($strTrackerHistory, 'stats-tracker-history.php?clientid='.$clientid.'&trackerid='.$trackerid, 'images/icon-statistics.gif');
	
	
	$extra  = "\t\t\t\t<form action='tracker-modify.php'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='trackerid' value='$trackerid'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='clientid' value='$clientid'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='returnurl' value='tracker-include.php'>"."\n";
	$extra .= "\t\t\t\t<br><br>"."\n";
	$extra .= "\t\t\t\t<b>$strModifyTracker</b><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/icon-duplicate-tracker.gif' align='absmiddle'>&nbsp;<a href='tracker-modify.php?clientid=".$clientid."&trackerid=".$trackerid."&duplicate=true&returnurl=tracker-include.php'>$strDuplicate</a><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/icon-move-tracker.gif' align='absmiddle'>&nbsp;$strMoveTo<br>"."\n";
	$extra .= "\t\t\t\t<img src='images/spacer.gif' height='1' width='160' vspace='2'><br>"."\n";
	$extra .= "\t\t\t\t&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"."\n";
	$extra .= "\t\t\t\t<select name='moveto' style='width: 110;'>"."\n";
	
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_clients'].
		" WHERE clientid != ".$clientid
	) or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
		$extra .= "\t\t\t\t\t<option value='".$row['clientid']."'>".phpAds_buildName($row['clientid'], $row['clientname'])."</option>\n";
	
	$extra .= "\t\t\t\t</select>&nbsp;\n";
	$extra .= "\t\t\t\t<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif'><br>\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>\n";
	$extra .= "\t\t\t\t<img src='images/icon-recycle.gif' align='absmiddle'>\n";
	$extra .= "\t\t\t\t<a href='tracker-delete.php?clientid=$clientid&trackerid=$trackerid&returnurl=advertiser-trackers.php'".phpAds_DelConfirm($strConfirmDeleteTracker).">$strDelete</a><br>\n";
	$extra .= "\t\t\t\t</form>\n";

	
	phpAds_PageHeader("4.1.4.3", $extra);
		echo "\t\t\t\t<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName($clientid)."\n";
		echo "\t\t\t\t<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>\n";
		echo "\t\t\t\t<img src='images/icon-tracker.gif' align='absmiddle'>\n";
		echo "\t\t\t\t<b>".phpAds_getTrackerName($trackerid)."</b><br><br><br>\n";
		phpAds_ShowSections(array("4.1.4.2", "4.1.4.3", "4.1.4.4"));
}

if (isset($trackerid) && $trackerid != '')
{
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_trackers'].
		" WHERE trackerid=".$trackerid
	) or phpAds_sqlDie();
	
	if (phpAds_dbNumRows($res))
	{
		$tracker = phpAds_dbFetchArray($res);
	}
}

$tabindex = 1;


// Header
echo "\t\t\t\t<table width='100%' border='0' align='center' cellspacing='0' cellpadding='0'>\n";
echo "\t\t\t\t<tr height='25'>\n";
echo "\t\t\t\t\t<td height='25' width='40%'>\n";
echo "\t\t\t\t\t\t<b>&nbsp;&nbsp;<a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&listorder=name'>".$GLOBALS['strName']."</a>";

if (($listorder == "name") || ($listorder == ""))
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo " <a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&orderdirection=up'>";
		echo "<img src='images/caret-ds.gif' border='0' alt='' title=''>";
	}
	else
	{
		echo " <a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&orderdirection=down'>";
		echo "<img src='images/caret-u.gif' border='0' alt='' title=''>";
	}
	echo "</a>";
}

echo "</b>\n";
echo "\t\t\t\t\t</td>\n";
echo "\t\t\t\t\t<td height='25'>\n";
echo "\t\t\t\t\t\t<b><a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&listorder=id'>".$GLOBALS['strID']."</a>";

if ($listorder == "id")
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo " <a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&orderdirection=up'>";
		echo "<img src='images/caret-ds.gif' border='0' alt='' title=''>";
	}
	else
	{
		echo " <a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&orderdirection=down'>";
		echo "<img src='images/caret-u.gif' border='0' alt='' title=''>";
	}
	echo "</a>";
}

echo "</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "\t\t\t\t\t</td>\n";
echo "\t\t\t\t\t<td height='25'>&nbsp;</td>\n";
echo "\t\t\t\t</tr>\n";

echo "\t\t\t\t<tr height='1'>\n";
echo "\t\t\t\t\t<td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>\n";
echo "\t\t\t\t</tr>\n";

$i = 0;
$checkedall = true;
$campaignshidden = 0;

if ( isset($trackerid) && ($trackerid > 0) )
{
	$res = phpAds_dbQuery(
		"SELECT campaignid".
		" FROM ".$phpAds_config['tbl_campaigns_trackers'].
		" WHERE trackerid=".$trackerid
	) or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$campaignids[$row['campaignid']] = true;
	}
}

$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_campaigns'].
	" WHERE clientid=".$clientid.
	phpAds_getCampaignListOrder ($listorder, $orderdirection)
) or phpAds_sqlDie();

if (phpAds_dbNumRows($res) == 0)
{
	echo "\t\t\t\t<tr bgcolor='#F6F6F6'>\n";
	echo "\t\t\t\t\t<td colspan='3' height='25'>&nbsp;&nbsp;".$strNoCampaignsToLink."</td>\n";
	echo "\t\t\t\t</tr>\n";
}
else
{
	echo "\t\t\t\t<form name='availablecampaigns' method='post' action='tracker-include.php'>\n";
	echo "\t\t\t\t<input type='hidden' name='trackerid' value='".$GLOBALS['trackerid']."'>\n";
	echo "\t\t\t\t<input type='hidden' name='clientid' value='".$GLOBALS['clientid']."'>\n";
	echo "\t\t\t\t<input type='hidden' name='action' value='set'>\n";
	while ($row = phpAds_dbFetchArray($res))
		$campaigns[$row['campaignid']] = $row;

	for (reset($campaigns); $ckey = key($campaigns); next($campaigns))
	{
		$campaign = $campaigns[$ckey];
		
		if ($campaign['active'] == 't' || $hideinactive != '1')
		{
			$linkedrow = (isset($campaignids[$campaign['campaignid']]) && $campaignids[$campaign['campaignid']] == true);
			
			if ($i > 0)
			{
				echo "\t\t\t\t<tr height='1'>\n";
				echo "\t\t\t\t\t<td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>\n";
				echo "\t\t\t\t</tr>\n";
			}
			echo "\t\t\t\t<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">\n";
			
			// Begin row
			echo "\t\t\t\t\t<td height='25'>";

			// Show checkbox
			if (isset($campaignids[$campaign['campaignid']]) && $campaignids[$campaign['campaignid']] == true)
				echo "\t\t\t\t\t\t&nbsp;&nbsp;<input type='checkbox' name='campaignids[]' value='".$campaign['campaignid']."' checked onclick='reviewall();' tabindex='".($tabindex++)."'>\n";
			else
			{
				echo "\t\t\t\t\t\t&nbsp;&nbsp;<input type='checkbox' name='campaignids[]' value='".$campaign['campaignid']."' onclick='reviewall();' tabindex='".($tabindex++)."'>\n";
				$checkedall = false;
			}
			
			// Campaign icon
			if ($campaign['active'] == 't')
				echo "\t\t\t\t\t\t&nbsp;&nbsp;<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;\n";
			else
				echo "\t\t\t\t\t\t&nbsp;&nbsp;<img src='images/icon-campaign-d.gif' align='absmiddle'>&nbsp;\n";
			
			
			// Name
			if (phpAds_isUser(phpAds_Admin))
			{
				echo "<a href='campaign-edit.php?clientid=".$campaign['clientid']."&campaignid=".$campaign['campaignid']."'>";
				echo phpAds_breakString ($campaign['campaignname'], '60')."</a>";
			}
			else
				echo phpAds_breakString ($campaign['campaignname'], '60');
			
			echo "</td>"."\n";
			
			
			// ID
			echo "<td height='25'>".$campaign['campaignid']."</td>"."\n";
			
			// Edit
			echo "<td height='25'>";
			echo "</td>"."\n";
			
			// End row
			echo "</tr>"."\n";
			
			$i++;
		}
		else
			$campaignshidden++;
	}
}

echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>"."\n";
echo "&nbsp;&nbsp;<input type='checkbox' name='checkall' value=''".($checkedall == true ? ' checked' : '')." onclick='toggleall();' tabindex='".($tabindex++)."'>"."\n";
echo "&nbsp;&nbsp;<b>".$strCheckAllNone."</b>"."\n";
echo "</td><td>&nbsp;</td><td>&nbsp;</td></tr>"."\n";

echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='25' align='".$phpAds_TextAlignLeft."' nowrap>"."\n";

if ($hideinactive == true)
{
	echo "&nbsp;&nbsp;<img src='images/icon-activate.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&hideinactive=0'>".$strShowAll."</a>";
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;".$campaignshidden." ".$strInactiveCampaignsHidden;
}
else
{
	echo "&nbsp;&nbsp;<img src='images/icon-hideinactivate.gif' align='absmiddle' border='0'>"."\n";
	echo "&nbsp;<a href='tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid."&hideinactive=1'>".$strHideInactiveCampaigns."</a>"."\n";
}




echo "</td><td colspan='2' align='".$phpAds_TextAlignRight."' nowrap>"."\n";

echo "&nbsp;&nbsp;</td></tr>"."\n";
echo "</table>"."\n";
echo "<br><br><br><br>"."\n";

echo "<input type='submit' name='submit' value='$strSaveChanges' tabindex='".($tabindex++)."'>"."\n";
echo "</form>"."\n";

?>
<script language='Javascript'>
<!--
	function toggleall()
	{
		allchecked = false;
		
		for (var i=0; i<document.availablecampaigns.elements.length; i++)
		{
			if (document.availablecampaigns.elements[i].name == 'campaignids[]')
			{
				if (document.availablecampaigns.elements[i].checked == false)
				{
					allchecked = true;
				}
			}
		}
		
		for (var i=0; i<document.availablecampaigns.elements.length; i++)
		{
			if (document.availablecampaigns.elements[i].name == 'campaignids[]')
			{
				document.availablecampaigns.elements[i].checked = allchecked;
			}
		}
	}
	
	function reviewall()
	{
		allchecked = true;
		
		for (var i=0; i<document.availablecampaigns.elements.length; i++)
		{
			if (document.availablecampaigns.elements[i].name == 'campaignids[]')
			{
				if (document.availablecampaigns.elements[i].checked == false)
				{
					allchecked = false;
				}
			}
		}
		
				
		document.availablecampaigns.checkall.checked = allchecked;
	}
//-->
</script>

<?php
/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['tracker-include.php']['hideinactive'] = $hideinactive;
$Session['prefs']['tracker-include.php']['listorder'] = $listorder;
$Session['prefs']['tracker-include.php']['orderdirection'] = $orderdirection;

phpAds_SessionDataStore();


/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>