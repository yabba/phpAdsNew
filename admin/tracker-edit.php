<?php // $Revision: 1.2 $

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
	 'trackername'
	,'description'
	,'move'
	,'submit'
);


// Security check
phpAds_checkAccess(phpAds_Admin);



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
	
	phpAds_dbQuery(
		"REPLACE INTO ".$phpAds_config['tbl_trackers'].
		" (trackerid".
		",trackername".
		",description".
		",clientid)".
		" VALUES".
		" (".$trackerid.
		",'".$trackername."'".
		",'".$description."'".
		",".$clientid.")"
	) or phpAds_sqlDie();
	
	// Get ID of tracker
	if ($trackerid == "null")
		$trackerid = phpAds_dbInsertID();
	
	if (isset($move) && $move == 't')
	{
		// We are moving a client to a tracker
		// Update banners
		$res = phpAds_dbQuery(
			"UPDATE ".$phpAds_config['tbl_banners'].
			" SET trackerid=".$trackerid.
			" WHERE trackerid=".$clientid
		) or phpAds_sqlDie();
		
		// Force priority recalculation
		$new_tracker = false;
	}
	
	Header("Location: tracker-include.php?clientid=".$clientid."&trackerid=".$trackerid);
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
	
	
	
	$extra  = "<form action='tracker-modify.php'>";
	$extra .= "<input type='hidden' name='trackerid' value='$trackerid'>";
	$extra .= "<input type='hidden' name='clientid' value='$clientid'>";
	$extra .= "<input type='hidden' name='returnurl' value='tracker-edit.php'>";
	$extra .= "<br><br>";
	$extra .= "<b>$strModifyTracker</b><br>";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
	$extra .= "<img src='images/icon-move-tracker.gif' align='absmiddle'>&nbsp;$strMoveTo<br>";
	$extra .= "<img src='images/spacer.gif' height='1' width='160' vspace='2'><br>";
	$extra .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$extra .= "<select name='moveto' style='width: 110;'>";
	
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_clients'].
		" WHERE clientid!=".phpAds_getTrackerParentClientID($trackerid)
	) or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
		$extra .= "<option value='".$row['clientid']."'>".phpAds_buildName($row['clientid'], $row['clientname'])."</option>";
	
	$extra .= "</select>&nbsp;<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif'><br>";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
	$extra .= "<img src='images/icon-recycle.gif' align='absmiddle'>&nbsp;<a href='tracker-delete.php?clientid=".$clientid."&trackerid=".$trackerid."&returnurl=advertiser-index.php'".phpAds_DelConfirm($strConfirmDeleteTracker).">$strDelete</a><br>";
	$extra .= "</form>";
	
	
	
	phpAds_PageHeader("4.1.4.2", $extra);
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName(phpAds_getTrackerParentClientID($trackerid));
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-tracker.gif' align='absmiddle'>&nbsp;<b>".phpAds_getTrackerName($trackerid)."</b><br><br><br>";
		phpAds_ShowSections(array("4.1.4.2", "4.1.4.3", "4.1.4.4"));
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
		$row["trackername"] = '';
	
	
	$row["trackername"] .= $strDefault;
}



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

echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
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
//-->
</script>

<?php

/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>