<?php // $Revision: 1.6 $

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
	 'clickwindow'
	,'description'
	,'move'
	,'submit'
	,'trackername'
	,'viewwindow'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);

if (phpAds_isUser(phpAds_Agency))
{
	if (isset($trackerid) && $trackerid != '')
	{
		$query = "SELECT c.clientid".
			" FROM ".$phpAds_config['tbl_clients']." AS c".
			",".$phpAds_config['tbl_trackers']." AS t".
			" WHERE c.clientid=t.clientid".
			" AND c.clientid=".$clientid.
			" AND t.trackerid=".$trackerid.
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

if (isset($submit))
{ 
	// If ID is not set, it should be a null-value for the auto_increment
	
	if (empty($trackerid))
	{
		$trackerid = "null";
	}
	
	$new_tracker = $trackerid == 'null';
	
	// Set window delays
	if (isset($clickwindow))
	{
		$clickwindow_seconds = 0;
		if ($clickwindow['second'] != '-') $clickwindow_seconds += (int)$clickwindow['second'];
		if ($clickwindow['minute'] != '-') $clickwindow_seconds += (int)$clickwindow['minute'] * 60;
		if ($clickwindow['hour'] != '-') 	$clickwindow_seconds += (int)$clickwindow['hour'] * 60*60;
		if ($clickwindow['day'] != '-') 	$clickwindow_seconds += (int)$clickwindow['day'] * 60*60*24;
	}
	else
		$clickwindow_seconds = 0;
	
	if (isset($viewwindow))
	{
		$viewwindow_seconds = 0;
		if ($viewwindow['second'] != '-') $viewwindow_seconds += (int)$viewwindow['second'];
		if ($viewwindow['minute'] != '-') $viewwindow_seconds += (int)$viewwindow['minute'] * 60;
		if ($viewwindow['hour'] != '-') 	$viewwindow_seconds += (int)$viewwindow['hour'] * 60*60;
		if ($viewwindow['day'] != '-') 	$viewwindow_seconds += (int)$viewwindow['day'] * 60*60*24;
	}
	else
		$viewwindow_seconds = 0;
	
	phpAds_dbQuery(
		"REPLACE INTO ".$phpAds_config['tbl_trackers'].
		" (trackerid".
		",trackername".
		",description".
		",clickwindow".
		",viewwindow".
		",clientid)".
		" VALUES".
		" (".$trackerid.
		",'".$trackername."'".
		",'".$description."'".
		",".$clickwindow_seconds.
		",".$viewwindow_seconds.
		",".$clientid.")"
	) or phpAds_sqlDie();
	
	// Get ID of tracker
	if ($trackerid == "null")
		$trackerid = phpAds_dbInsertID();
	
	
	Header("Location: tracker-campaigns.php?clientid=".$clientid."&trackerid=".$trackerid);
	exit;
}




/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if ($trackerid != "")
{
	if (isset($Session['prefs']['advertiser-trackers.php'][$clientid]['listorder']))
		$navorder = $Session['prefs']['advertiser-trackers.php'][$clientid]['listorder'];
	else
		$navorder = '';
	
	if (isset($Session['prefs']['advertiser-trackers.php'][$clientid]['orderdirection']))
		$navdirection = $Session['prefs']['advertiser-trackers.php'][$clientid]['orderdirection'];
	else
		$navdirection = '';
	
	
	// Get other trackers
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_trackers'].
		" WHERE clientid = ".$clientid.
		phpAds_getTrackerListOrder ($navorder, $navdirection)
	) or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		phpAds_PageContext (
			phpAds_buildName ($row['trackerid'], $row['trackername']),
			"tracker-edit.php?clientid=".$clientid."&trackerid=".$row['trackerid'],
			$trackerid == $row['trackerid']
		);
	}
	
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	//phpAds_PageShortcut($strTrackerHistory, 'stats-tracker-history.php?clientid='.$clientid.'&trackerid='.$trackerid, 'images/icon-statistics.gif');
		
	
	$extra  = "\t\t\t\t<form action='tracker-modify.php'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='trackerid' value='$trackerid'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='clientid' value='$clientid'>"."\n";
	$extra .= "\t\t\t\t<input type='hidden' name='returnurl' value='tracker-edit.php'>"."\n";
	$extra .= "\t\t\t\t<br><br>"."\n";
	$extra .= "\t\t\t\t<b>$strModifyTracker</b><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/icon-duplicate-tracker.gif' align='absmiddle'>&nbsp;<a href='tracker-modify.php?clientid=".$clientid."&trackerid=".$trackerid."&duplicate=true&returnurl=tracker-edit.php'>$strDuplicate</a><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>"."\n";
	$extra .= "\t\t\t\t<img src='images/icon-move-tracker.gif' align='absmiddle'>&nbsp;$strMoveTo<br>"."\n";
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
			" AND agencyid=".phpAds_getAgencyID();
	}
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
		$extra .= "\t\t\t\t\t<option value='".$row['clientid']."'>".phpAds_buildName($row['clientid'], $row['clientname'])."</option>\n";
	
	$extra .= "\t\t\t\t</select>&nbsp;\n";
	$extra .= "\t\t\t\t<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif'><br>\n";
	$extra .= "\t\t\t\t<img src='images/break.gif' height='1' width='160' vspace='4'><br>\n";
	$extra .= "\t\t\t\t<img src='images/icon-recycle.gif' align='absmiddle'>\n";
	$extra .= "\t\t\t\t<a href='tracker-delete.php?clientid=$clientid&trackerid=$trackerid&returnurl=advertiser-trackers.php'".phpAds_DelConfirm($strConfirmDeleteTracker).">$strDelete</a><br>\n";
	$extra .= "\t\t\t\t</form>\n";
	
	
	phpAds_PageHeader("4.1.4.2", $extra);
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName(phpAds_getTrackerParentClientID($trackerid));
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-tracker.gif' align='absmiddle'>&nbsp;<b>".phpAds_getTrackerName($trackerid)."</b><br><br><br>";
		phpAds_ShowSections(array("4.1.4.2", "4.1.4.3", "4.1.4.5", "4.1.4.4"));
}
else
{
	if (isset($move) && $move == 't')
	{
		// Convert client to tracker
		
		phpAds_PageHeader("4.1.4.2");
			echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName($clientid);
			echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
			echo "<img src='images/icon-tracker.gif' align='absmiddle'>&nbsp;<b>".$strUntitled."</b><br><br><br>";
			phpAds_ShowSections(array("4.1.4.2"));
	}
	else
	{
		// New tracker
		
		phpAds_PageHeader("4.1.4.1");
			echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName($clientid);
			echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
			echo "<img src='images/icon-tracker.gif' align='absmiddle'>&nbsp;<b>".$strUntitled."</b><br><br><br>";
			phpAds_ShowSections(array("4.1.4.1"));
	}
}

if ($trackerid != "" || (isset($move) && $move == 't'))
{
	// Edit or Convert
	// Fetch exisiting settings
	// Parent setting for converting, tracker settings for editing
	if ($trackerid != "") $ID = $trackerid;
	if (isset($move) && $move == 't')
		if (isset($clientid) && $clientid != "") $ID = $clientid;

	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_trackers'].
		" WHERE trackerid=".$ID
	) or phpAds_sqlDie();
	
	$row = phpAds_dbFetchArray($res);
	
}
else
{
	// New tracker
	$res = phpAds_dbQuery(
		"SELECT clientname".
		" FROM ".$phpAds_config['tbl_clients'].
		" WHERE clientid=".$clientid
	);
	
	if ($client = phpAds_dbFetchArray($res))
		$row['trackername'] = $client['clientname'].' - ';
	else
		$row['trackername'] = '';
	
	
	$row['trackername'] .= $strDefault." ".$strTracker;
	$row['clickwindow'] = $phpAds_config['default_conversion_clickwindow'];
	$row['viewwindow'] = $phpAds_config['default_conversion_viewwindow'];
}

// Parse the number of seconds in the conversion windows into days, hours, minutes, seconds..
$seconds_left = $row['clickwindow'];
$clickwindow['day'] = floor($seconds_left / (60*60*24));
$seconds_left = $seconds_left % (60*60*24);
$clickwindow['hour'] = floor($seconds_left / (60*60));
$seconds_left = $seconds_left % (60*60);
$clickwindow['minute'] = floor($seconds_left / (60));
$seconds_left = $seconds_left % (60);
$clickwindow['second'] = $seconds_left;

$seconds_left = $row['viewwindow'];
$viewwindow['day'] = floor($seconds_left / (60*60*24));
$seconds_left = $seconds_left % (60*60*24);
$viewwindow['hour'] = floor($seconds_left / (60*60));
$seconds_left = $seconds_left % (60*60);
$viewwindow['minute'] = floor($seconds_left / (60));
$seconds_left = $seconds_left % (60);
$viewwindow['second'] = $seconds_left;


/*********************************************************/
/* Main code                                             */
/*********************************************************/

$tabindex = 1;

echo "<br><br>";
echo "<form name='clientform' method='post' action='tracker-edit.php'>"."\n";
echo "<input type='hidden' name='trackerid' value='".(isset($trackerid) ? $trackerid : '')."'>"."\n";
echo "<input type='hidden' name='clientid' value='".(isset($clientid) ? $clientid : '')."'>"."\n";
echo "<input type='hidden' name='move' value='".(isset($move) ? $move : '')."'>"."\n";

echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>"."\n";
echo "<tr><td height='25' colspan='3'><b>".$strBasicInformation."</b></td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200'>".$strName."</td>"."\n";
echo "\t"."<td><input class='flat' type='text' name='trackername' size='35' style='width:350px;' value='".phpAds_htmlQuotes($row['trackername'])."' tabindex='".($tabindex++)."'></td>"."\n";
echo "</tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200'>".$strDescription."</td>"."\n";
echo "\t"."<td><input class='flat' type='text' name='description' size='35' style='width:350px;' value='".phpAds_htmlQuotes($row['description'])."' tabindex='".($tabindex++)."'></td>"."\n";
echo "</tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr><td height='25' colspan='3'><b>".$strDefaultConversionRules."</b></td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr><td width='30'>&nbsp;</td>";
echo "<td width='200'>".$strClickWindow."</td>";
echo "<td valign='top'>";
echo "<input id='clickwindowday' class='flat' type='text' size='3' name='clickwindow[day]' value='".$clickwindow['day']."' onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strDays." &nbsp;&nbsp;";
echo "<input id='clickwindowhour' class='flat' type='text' size='3' name='clickwindow[hour]' value='".$clickwindow['hour']."' onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strHours." &nbsp;&nbsp;";
echo "<input id='clickwindowminute' class='flat' type='text' size='3' name='clickwindow[minute]' value='".$clickwindow['minute']."' onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strMinutes." &nbsp;&nbsp;";
echo "<input id='clickwindowsecond' class='flat' type='text' size='3' name='clickwindow[second]' value='".$clickwindow['second']."' onBlur=\"phpAds_formLimitBlur(this.form);\" onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strSeconds." &nbsp;&nbsp;";
echo "</td></tr>";
echo "<tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";

echo "<tr><td width='30'>&nbsp;</td>";
echo "<td width='200'>".$strViewWindow."</td>";
echo "<td valign='top'>";
echo "<input id='viewwindowday' class='flat' type='text' size='3' name='viewwindow[day]' value='".$viewwindow['day']."' onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strDays." &nbsp;&nbsp;";
echo "<input id='viewwindowhour' class='flat' type='text' size='3' name='viewwindow[hour]' value='".$viewwindow['hour']."' onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strHours." &nbsp;&nbsp;";
echo "<input id='viewwindowminute' class='flat' type='text' size='3' name='viewwindow[minute]' value='".$viewwindow['minute']."' onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strMinutes." &nbsp;&nbsp;";
echo "<input id='viewwindowsecond' class='flat' type='text' size='3' name='viewwindow[second]' value='".$viewwindow['second']."' onBlur=\"phpAds_formLimitBlur(this.form);\" onKeyUp=\"phpAds_formLimitUpdate(this.form);\" tabindex='".($tabindex++)."'> ".$strSeconds." &nbsp;&nbsp;";
echo "</td></tr>";
echo "<tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";

echo "</table>"."\n";

echo "<br><br>"."\n";
echo "<input type='submit' name='submit' value='".$strSaveChanges."' tabindex='".($tabindex++)."'>"."\n";

echo "</form>"."\n";


/*********************************************************/
/* Form requirements                                     */
/*********************************************************/

// Get unique affiliate
$unique_names = array();

$query = 
	"SELECT trackername".
	" FROM ".$phpAds_config['tbl_trackers'].
	" WHERE clientid=".$clientid
;

if (isset($trackerid) && ($trackerid > 0))
	$query .= " AND trackerid!=".$trackerid;

$res = phpAds_dbQuery($query) or phpAds_sqlDie();

while ($row = phpAds_dbFetchArray($res))
	$unique_names[] = $row['trackername'];
?>

<script language='JavaScript'>
<!--
	phpAds_formSetRequirements('trackername', '<?php echo addslashes($strName); ?>', true, 'unique');
	
	phpAds_formSetUnique('trackername', '|<?php echo addslashes(implode('|', $unique_names)); ?>|');
	
	function phpAds_formLimitBlur (f)
	{
		if (f.clickwindowday.value == '') f.clickwindowday.value = '0';
		if (f.clickwindowhour.value == '') f.clickwindowhour.value = '0';
		if (f.clickwindowminute.value == '') f.clickwindowminute.value = '0';
		if (f.clickwindowsecond.value == '') f.clickwindowsecond.value = '0';
		
		if (f.viewwindowday.value == '') f.viewwindowday.value = '0';
		if (f.viewwindowhour.value == '') f.viewwindowhour.value = '0';
		if (f.viewwindowminute.value == '') f.viewwindowminute.value = '0';
		if (f.viewwindowsecond.value == '') f.viewwindowsecond.value = '0';
		
		phpAds_formLimitUpdate (f);
	}
			
	function phpAds_formLimitUpdate (f)
	{
		// Set -
		if (f.clickwindowhour.value == '-' && f.clickwindowday.value != '-') f.clickwindowhour.value = '0';
		if (f.clickwindowminute.value == '-' && f.clickwindowhour.value != '-') f.clickwindowminute.value = '0';
		if (f.clickwindowsecond.value == '-' && f.clickwindowminute.value != '-') f.clickwindowsecond.value = '0';
		
		// Set 0
		if (f.clickwindowday.value == '0') f.clickwindowday.value = '-';
		if (f.clickwindowday.value == '-' && f.clickwindowhour.value == '0') f.clickwindowhour.value = '-';
		if (f.clickwindowhour.value == '-' && f.clickwindowminute.value == '0') f.clickwindowminute.value = '-';
		if (f.clickwindowminute.value == '-' && f.clickwindowsecond.value == '0') f.clickwindowsecond.value = '-';

		// Set -
		if (f.viewwindowhour.value == '-' && f.viewwindowday.value != '-') f.viewwindowhour.value = '0';
		if (f.viewwindowminute.value == '-' && f.viewwindowhour.value != '-') f.viewwindowminute.value = '0';
		if (f.viewwindowsecond.value == '-' && f.viewwindowminute.value != '-') f.viewwindowsecond.value = '0';
		
		// Set 0
		if (f.viewwindowday.value == '0') f.viewwindowday.value = '-';
		if (f.viewwindowday.value == '-' && f.viewwindowhour.value == '0') f.viewwindowhour.value = '-';
		if (f.viewwindowhour.value == '-' && f.viewwindowminute.value == '0') f.viewwindowminute.value = '-';
		if (f.viewwindowminute.value == '-' && f.viewwindowsecond.value == '0') f.viewwindowsecond.value = '-';
	}
	
	phpAds_formLimitUpdate(document.clientform);
	
//-->
</script>

<?php

/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>