<?php // $Revision: 2.17 $

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
	 'activateDay'
	,'activateMonth'
	,'activateSet'
	,'activateYear'
	,'active_old'
	,'anonymous'
	,'campaignname'
	,'clicks'
	,'conversions'
	,'expire'
	,'expireDay'
	,'expireMonth'
	,'expireSet'
	,'expireYear'
	,'move'
	,'optimise'
	,'priority'
	,'submit'
	,'target_old'
	,'targetviews'
	,'unlimitedclicks'
	,'unlimitedconversions'
	,'unlimitedviews'
	,'views'
	,'weight_old'
	,'weight'
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

if (isset($submit))
{ 
	// If ID is not set, it should be a null-value for the auto_increment
	
	if (empty($campaignid))
	{
		$campaignid = "null";
	}
	
	// set expired
	if ($views == '-')
		$views = 0;
	if ($clicks == '-')
		$clicks = 0;
	if ($conversions == '-')
		$conversions = 0;
	
	// set unlimited
	if (isset($unlimitedviews) && strtolower ($unlimitedviews) == "on")
		$views = -1;
	if (isset($unlimitedclicks) && strtolower ($unlimitedclicks) == "on")
		$clicks = -1;
	if (isset($unlimitedconversions) && strtolower ($unlimitedconversions) == "on")
		$conversions = -1;
	
	
	if ($priority == 'h' || $priority == 'm')
	{
		// set target
		if (isset($targetviews))
		{
			if ($targetviews == '-')
				$targetviews = 0;
			elseif ($targetviews == '')
				$targetviews = 0;
		}
		else
			$targetviews = 0;
		
		$weight = 0;
	}
	else
	{
		// set weight
		if (isset($weight))
		{
			if ($weight == '-')
				$weight = 0;
			elseif ($weight == '')
				$weight = 0;
		}
		else
			$weight = 0;
		
		$targetviews = 0;
	}
	
	if ($expireSet == 't')
	{
		if ($expireDay != '-' && $expireMonth != '-' && $expireYear != '-')
		{
			$expire = $expireYear."-".$expireMonth."-".$expireDay;
		}
		else
			$expire = "0000-00-00";
	}
	else
		$expire = "0000-00-00";
	
	
	if ($activateSet == 't')
	{
		if ($activateDay != '-' && $activateMonth != '-' && $activateYear != '-')
		{
			$activate = $activateYear."-".$activateMonth."-".$activateDay;
		}
		else
			$activate = "0000-00-00";
	}
	else
		$activate = "0000-00-00";
	
	
	$active = "t";
	
	if ($views == 0 || $clicks == 0 || $conversions == 0)
		$active = "f";
	
	if ($activateDay != '-' && $activateMonth != '-' && $activateYear != '-')
		if (time() < mktime(0, 0, 0, $activateMonth, $activateDay, $activateYear))
			$active = "f";
	
	if ($expireDay != '-' && $expireMonth != '-' && $expireYear != '-')
		if (time() > mktime(0, 0, 0, $expireMonth, $expireDay, $expireYear))
			$active = "f";
	
	// Set campaign inactive if weight and target are both null and autotargeting is disabled
	if ($active == 't' && !($targetviews > 0 || $weight > 0 || ($expire != '0000-00-00' && $views > 0)))
		$active = 'f';
	
	if ($optimise != 't')
		$optimise = 'f';
	if ($anonymous != 't')
		$anonymous = 'f';
	
	$new_campaign = $campaignid == 'null';
	
	phpAds_dbQuery(
		"REPLACE INTO ".$phpAds_config['tbl_campaigns'].
		" (campaignid".
		",campaignname".
		",clientid".
		",views".
		",clicks".
		",conversions".
		",expire".
		",activate".
		",active".
		",priority".
		",weight".
		",target".
		",optimise".
		",anonymous)".
		" VALUES".
		" (".$campaignid.
		",'".$campaignname."'".
		",".$clientid.
		",".$views.
		",".$clicks.
		",".$conversions.
		",'".$expire."'".
		",'".$activate."'".
		",'".$active."'".
		",'".$priority."'".
		",".$weight.
		",".$targetviews.
		",'".$optimise."'".
		",'".$anonymous."')"
	) or phpAds_sqlDie();  
	
	// Get ID of campaign
	if ($campaignid == "null")
		$campaignid = phpAds_dbInsertID();
	
	
	// Auto-target campaign if adviews purchased and expiration set
	if ($active == 't' && $expire != '0000-00-00' && $views > 0)
	{
		include (phpAds_path.'/libraries/lib-autotargeting.inc.php');
		
		$targetviews = phpAds_AutoTargetingGetTarget(
			phpAds_AutoTargetingPrepareProfile(),
			$views,
			mktime(0, 0, 0, $expireMonth, $expireDay, $expireYear),
			isset($phpAds_config['autotarget_factor']) ? $phpAds_config['autotarget_factor'] : -1
		);
		
		if (is_array($targetviews))
			list($targetviews, ) = $targetviews;
		
		phpAds_dbQuery("UPDATE ".$phpAds_config['tbl_campaigns'].
			" SET target = ".$targetviews.
			" WHERE campaignid = ".$campaignid
		);
	}
	
	if (isset($move) && $move == 't')
	{
		// We are moving a client to a campaign
		// Update banners
		$res = phpAds_dbQuery(
			"UPDATE ".$phpAds_config['tbl_banners'].
			" SET campaignid=".$campaignid.
			" WHERE campaignid=".$clientid
		) or phpAds_sqlDie();
		
		// Force priority recalculation
		$new_campaign = false;
	}
	
	
	// Update targetstats
	if ($targetviews != $target_old)
	{
		$res = phpAds_dbQuery("
			UPDATE
				".$phpAds_config['tbl_targetstats']."
			SET
				target = '".$targetviews."',
				modified = 1
			WHERE
				campaignid = '".$campaignid."' AND
				day = ".date('Ymd')."
			");

		if (!phpAds_dbAffectedRows($res))
			phpAds_dbQuery("
				INSERT INTO ".$phpAds_config['tbl_targetstats']."
					(day, campaignid, target, modified)
				VALUES
					(".date('Ymd').", '".$campaignid."', '".$targetviews."', 1)
				");
	}
	
	
	// Recalculate priority only when editing a campaign
	// or moving banners into a newly created, and when:
	//
	// - campaing changes status (activated or deactivated) or
	// - the campaign is active and target/weight are changed
	//
	if (!$new_campaign &&
		($active != $active_old ||
		($active == 't' && ($targetviews != $target_old || $weight != $weight_old))))
	{
		include ("../libraries/lib-priority.inc.php");
		phpAds_PriorityCalculate ();
	}
	
	
	// Rebuild cache
	if (!defined('LIBVIEWCACHE_INCLUDED')) 
		include (phpAds_path.'/libraries/deliverycache/cache-'.$phpAds_config['delivery_caching'].'.inc.php');
	
	phpAds_cacheDelete();
	
	
	Header("Location: campaign-banners.php?clientid=".$clientid."&campaignid=".$campaignid);
	exit;
}




/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if ($campaignid != "")
{
	if (isset($Session['prefs']['advertiser-campaigns.php'][$clientid]['listorder']))
		$navorder = $Session['prefs']['advertiser-campaigns.php'][$clientid]['listorder'];
	else
		$navorder = '';
	
	if (isset($Session['prefs']['advertiser-campaigns.php'][$clientid]['orderdirection']))
		$navdirection = $Session['prefs']['advertiser-campaigns.php'][$clientid]['orderdirection'];
	else
		$navdirection = '';
	
	
	// Get other campaigns
	$query = "SELECT campaignid,campaignname".
		" FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE clientid = ".$clientid.
		phpAds_getCampaignListOrder ($navorder, $navdirection);

	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		phpAds_PageContext (
			phpAds_buildName ($row['campaignid'], $row['campaignname']),
			"campaign-edit.php?clientid=".$clientid."&campaignid=".$row['campaignid'],
			$campaignid == $row['campaignid']
		);
	}
	
	phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	phpAds_PageShortcut($strCampaignHistory, 'stats-campaign-history.php?clientid='.$clientid.'&campaignid='.$campaignid, 'images/icon-statistics.gif');
	
	
	
	$extra  = "<form action='campaign-modify.php'>";
	$extra .= "<input type='hidden' name='campaignid' value='$campaignid'>";
	$extra .= "<input type='hidden' name='clientid' value='$clientid'>";
	$extra .= "<input type='hidden' name='returnurl' value='campaign-edit.php'>";
	$extra .= "<br><br>";
	$extra .= "<b>$strModifyCampaign</b><br>";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
	$extra .= "<img src='images/icon-move-campaign.gif' align='absmiddle'>&nbsp;$strMoveTo<br>";
	$extra .= "<img src='images/spacer.gif' height='1' width='160' vspace='2'><br>";
	$extra .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	$extra .= "<select name='moveto' style='width: 110;'>";
	
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
		$extra .= "<option value='".$row['clientid']."'>".phpAds_buildName($row['clientid'], $row['clientname'])."</option>";
	
	$extra .= "</select>&nbsp;<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif'><br>";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
	$extra .= "<img src='images/icon-recycle.gif' align='absmiddle'>&nbsp;<a href='campaign-delete.php?clientid=".$clientid."&campaignid=".$campaignid."&returnurl=advertiser-index.php'".phpAds_DelConfirm($strConfirmDeleteCampaign).">$strDelete</a><br>";
	$extra .= "</form>";
	
	
	
	phpAds_PageHeader("4.1.3.2", $extra);
		echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getParentClientName($campaignid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".phpAds_getCampaignName($campaignid)."</b><br><br><br>";
		phpAds_ShowSections(array("4.1.3.2", "4.1.3.3", "4.1.3.4", "4.1.3.5"));
}
else
{
	if (isset($move) && $move == 't')
	{
		// Convert client to campaign
		
		phpAds_PageHeader("4.1.3.2");
			echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName($clientid);
			echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
			echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".$strUntitled."</b><br><br><br>";
			phpAds_ShowSections(array("4.1.3.2"));
	}
	else
	{
		// New campaign
		
		phpAds_PageHeader("4.1.3.1");
			echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName($clientid);
			echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
			echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;<b>".$strUntitled."</b><br><br><br>";
			phpAds_ShowSections(array("4.1.3.1"));
	}
}

if ($campaignid != "" || (isset($move) && $move == 't'))
{
	// Edit or Convert
	// Fetch exisiting settings
	// Parent setting for converting, campaign settings for editing
	if ($campaignid != "") $ID = $campaignid;
	if (isset($move) && $move == 't')
		if (isset($clientid) && $clientid != "") $ID = $clientid;
	
	$res = phpAds_dbQuery(
		"SELECT *".
		",to_days(expire) as expire_day".
		",to_days(curdate()) as cur_date".
		",UNIX_TIMESTAMP(expire) as timestamp".
		",DATE_FORMAT(expire, '$date_format') as expire_f".
		",dayofmonth(expire) as expire_dayofmonth".
		",month(expire) as expire_month".
		",year(expire) as expire_year".
		",DATE_FORMAT(activate, '$date_format') as activate_f".
		",dayofmonth(activate) as activate_dayofmonth".
		",month(activate) as activate_month".
		",year(activate) as activate_year".
		" FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE campaignid=".$ID
	) or phpAds_sqlDie();
		
	$row = phpAds_dbFetchArray($res);
	
	if ($row['target'] > 0)
	{
		$row['weight'] = '-';
	}
	else
	{
		$row['target'] = '-';
	}
	
	// Set parent when editing an campaign, don't set it
	// when moving an campaign.
	//if ($campaignid != "" && isset($row["parent"]))
	//	$clientid = $row["parent"];
	
	// Set default activation settings
	if (!isset($row["activate_dayofmonth"]))
		$row["activate_dayofmonth"] = 0;
	if (!isset($row["activate_month"]))
		$row["activate_month"] = 0;
	if (!isset($row["activate_year"]))
		$row["activate_year"] = 0;
	if (!isset($row["activate_f"]))
		$row["activate_f"] = "-";
	
	// Set default expiration settings
	if (!isset($row["expire_dayofmonth"]))
		$row["expire_dayofmonth"] = 0;
	if (!isset($row["expire_month"]))
		$row["expire_month"] = 0;
	if (!isset($row["expire_year"]))
		$row["expire_year"] = 0;
	if (!isset($row["expire_f"]))
		$row["expire_f"] = "-";
	
	// Check if timestamp is in the past or future
	if ($row["timestamp"] < time())
	{
		if ($row["timestamp"] > 0)
			$days_left = "0";
		else
			$days_left = -1;
	}
	else
		$days_left=$row["expire_day"] - $row["cur_date"];
}
else
{
	// New campaign
	$res = phpAds_dbQuery(
		"SELECT clientname".
		" FROM ".$phpAds_config['tbl_clients'].
		" WHERE clientid=".$clientid
	);
	
	if ($client = phpAds_dbFetchArray($res))
		$row['campaignname'] = $client['clientname'].' - ';
	else
		$row["campaignname"] = '';
	
	
	$row["campaignname"] .= $strDefault." ".$strCampaign;
	$row["views"] 		= '';
	$row["clicks"] 		= '';
	$row["conversions"] = '';
	$row["active"] 		= '';
	$row["priority"]	= '';
	$row["optimise"]	= '';
	$row["anonymous"]	= '';
	
	$days_left = '';
}



/*********************************************************/
/* Main code                                             */
/*********************************************************/

if (!isset($row['views']) || (isset($row['views']) && $row['views'] == ""))
	$row["views"] = -1;
if (!isset($row['clicks']) || (isset($row['clicks']) && $row['clicks'] == ""))
	$row["clicks"] = -1;
if (!isset($row['conversions']) || (isset($row['conversions']) && $row['conversions'] == ""))
	$row["conversions"] = -1;
if (!isset($row['priority']) || (isset($row['priority']) && $row['priority'] == ""))
	$row["priority"] = 'l';

if ($days_left == "")
	$days_left = -1;

if ($row['active'] == 't' && $row['expire'] != '0000-00-00' && $row['views'] > 0)
	$delivery = 'auto';
elseif ($row['target'] > 0)
	$delivery = 'manual';
else
	$delivery = 'none';


function phpAds_showDateEdit($name, $day=0, $month=0, $year=0, $edit=true)
{
	global $strMonth, $strDontExpire, $strActivateNow, $tabindex;
	
	if ($day == 0 && $month == 0 && $year == 0)
	{
		$day = '-';
		$month = '-';
		$year = '-';
		$set = false;
	}
	else
	{
		$set = true;
	}
	
	if ($name == 'expire')
		$caption = $strDontExpire;
	elseif ($name == 'activate')
		$caption = $strActivateNow;
	
	if ($edit)
	{
		echo "<table><tr><td>";
		echo "<input type='radio' name='".$name."Set' value='f' onclick=\"phpAds_formDateClick('".$name."', false);\"".($set==false?' checked':'')." tabindex='".($tabindex++)."'>";
		echo "&nbsp;$caption";
		echo "</td></tr><tr><td>";
		echo "<input type='radio' name='".$name."Set' value='t' onclick=\"phpAds_formDateClick('".$name."', true);\"".($set==true?' checked':'')." tabindex='".($tabindex++)."'>";
		echo "&nbsp;";
		
		echo "<select name='".$name."Day' onchange=\"phpAds_formDateCheck('".$name."');\" tabindex='".($tabindex++)."'>\n";
		echo "<option value='-'".($day=='-' ? ' selected' : '').">-</option>\n";
		for ($i=1;$i<=31;$i++)
			echo "<option value='$i'".($day==$i ? ' selected' : '').">$i</option>\n";
		echo "</select>&nbsp;\n";
		
		echo "<select name='".$name."Month' onchange=\"phpAds_formDateCheck('".$name."');\" tabindex='".($tabindex++)."'>\n";
		echo "<option value='-'".($month=='-' ? ' selected' : '').">-</option>\n";
		for ($i=1;$i<=12;$i++)
			echo "<option value='$i'".($month==$i ? ' selected' : '').">".$strMonth[$i-1]."</option>\n";
		echo "</select>&nbsp;\n";
		
		if ($year != '-')
			$start = $year < date('Y') ? $year : date('Y');
		else
			$start = date('Y');
		
		echo "<select name='".$name."Year' onchange=\"phpAds_formDateCheck('".$name."');\" tabindex='".($tabindex++)."'>\n";
		echo "<option value='-'".($year=='-' ? ' selected' : '').">-</option>\n";
		for ($i=$start;$i<=($start+4);$i++)
			echo "<option value='$i'".($year==$i ? ' selected' : '').">$i</option>\n";
		echo "</select>\n";
		
		echo "</td></tr></table>";
	}
	else
	{
		if ($set == true)
		{
			echo $day." ".$strMonth[$month-1]." ".$year;
		}
		else
		{
			echo $caption;
		}
	}
}

$tabindex = 1;


echo "<br><br>";
echo "<form name='clientform' method='post' action='campaign-edit.php' onSubmit='return (phpAds_formCheck(this) && phpAds_priorityCheck(this));'>"."\n";
echo "<input type='hidden' name='campaignid' value='".(isset($campaignid) ? $campaignid : '')."'>"."\n";
echo "<input type='hidden' name='clientid' value='".(isset($clientid) ? $clientid : '')."'>"."\n";
echo "<input type='hidden' name='expire' value='".(isset($row["expire"]) ? $row["expire"] : '')."'>"."\n";
echo "<input type='hidden' name='move' value='".(isset($move) ? $move : '')."'>"."\n";
echo "<input type='hidden' name='target_old' value='".(isset($row['target']) ? (int)$row['target'] : 0)."'>"."\n";
echo "<input type='hidden' name='weight_old' value='".(isset($row['weight']) ? (int)$row['weight'] : 0)."'>"."\n";
echo "<input type='hidden' name='active_old' value='".(isset($row['active']) && $row['active'] == 't' ? 't' : 'f')."'>"."\n";
echo "<input type='hidden' name='previousweight' value='".(isset($row["weight"]) ? $row["weight"] : '')."'>"."\n";
echo "<input type='hidden' name='previoustarget' value='".(isset($row["target"]) ? $row["target"] : '')."'>"."\n";
echo "<input type='hidden' name='previousactive' value='".(isset($row["active"]) ? $row["active"] : '')."'>"."\n";
echo "<input type='hidden' name='previousviews' value='".(isset($row["views"]) ? $row["views"] : '')."'>"."\n";
echo "<input type='hidden' name='previousclicks' value='".(isset($row["clicks"]) ? $row["clicks"] : '')."'>"."\n";
echo "<input type='hidden' name='previousconversions' value='".(isset($row["conversions"]) ? $row["conversions"] : '')."'>"."\n";

echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>"."\n";
echo "<tr><td height='25' colspan='3'><b>".$strBasicInformation."</b></td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200'>".$strName."</td>"."\n";
echo "\t"."<td><input onBlur='phpAds_formPriorityUpdate(this.form);' class='flat' type='text' name='campaignname' size='35' style='width:350px;' value='".phpAds_htmlQuotes($row['campaignname'])."' tabindex='".($tabindex++)."'></td>"."\n";
echo "</tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr><td height='25' colspan='3'><b>".$strInventoryDetails."</b></td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

if (isset($row['active']) && $row['active'] == 'f') 
{
	$expire_ts = mktime(0, 0, 0, $row["expire_month"], $row["expire_dayofmonth"], $row["expire_year"]);
	$inactivebecause = array();
	
	if ($row['views'] == 0) $inactivebecause[] =  $strNoMoreViews;
	if ($row['clicks'] == 0) $inactivebecause[] =  $strNoMoreClicks;
	if ($row['conversions'] == 0) $inactivebecause[] =  $strNoMoreConversions;
	if (time() < mktime(0, 0, 0, $row["activate_month"], $row["activate_dayofmonth"], $row["activate_year"])) $inactivebecause[] =  $strBeforeActivate;
	if (time() > $expire_ts && $expire_ts > 0) $inactivebecause[] =  $strAfterExpire;
	if ($row['target'] == 0  && $row['weight'] == 0) $inactivebecause[] =  $strWeightIsNull;
	
	echo "<tr>"."\n";
	echo "\t"."<td width='30' valign='top'>&nbsp;</td>"."\n";
	echo "\t"."<td colspan='2'><div class='errormessage'><img class='errormessage' src='images/info.gif' width='16' height='16' border='0' align='absmiddle'>".$strClientDeactivated." ".join(', ', $inactivebecause)."</div><br></td>"."\n";
	echo "</tr>"."\n";
	echo "<tr><td><img src='images/spacer.gif' height='1' width='100%'></td></tr>"."\n";
}

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200'>".$strViewsPurchased."</td>"."\n";
echo "\t"."<td>"."\n";
echo "\t\t"."&nbsp;&nbsp;<input class='flat' type='text' name='views' size='25' value='".($row["views"] > 0 ? $row["views"] : '-')."' onBlur='phpAds_formPriorityUpdate(this.form);' onKeyUp=\"phpAds_formUnlimitedCheck('unlimitedviews', 'views');\" tabindex='".($tabindex++)."'>&nbsp;"."\n";
echo "\t\t"."<input type='checkbox' name='unlimitedviews'".($row["views"] == -1 ? " CHECKED" : '')." onClick=\"phpAds_formUnlimitedClick('unlimitedviews', 'views');\" tabindex='".($tabindex++)."'>&nbsp;".$strUnlimited."\n";
echo "\t"."</td>"."\n";
echo "</tr>"."\n";
echo "<tr>"."\n";
echo "\t"."<td><img src='images/spacer.gif' height='1' width='100%'></td>"."\n";
echo "\t"."<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td>"."\n";
echo "</tr>";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200'>".$strClicksPurchased."</td>"."\n";
echo "\t"."<td>"."\n";
echo "\t\t"."&nbsp;&nbsp;<input class='flat' type='text' name='clicks' size='25' value='".($row["clicks"] > 0 ? $row["clicks"] : '-')."' onBlur='phpAds_formPriorityUpdate(this.form);' onKeyUp=\"phpAds_formUnlimitedCheck('unlimitedclicks', 'clicks');\" tabindex='".($tabindex++)."'>&nbsp;"."\n";
echo "\t\t"."<input type='checkbox' name='unlimitedclicks'".($row["clicks"] == -1 ? " CHECKED" : '')." onClick=\"phpAds_formUnlimitedClick('unlimitedclicks', 'clicks');\" tabindex='".($tabindex++)."'>&nbsp;".$strUnlimited."\n";
echo "\t"."</td>"."\n";
echo "</tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td><img src='images/spacer.gif' height='1' width='100%'></td>"."\n";
echo "\t"."<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td>"."\n";
echo "</tr>";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200'>".$strConversionsPurchased."</td>"."\n";
echo "\t"."<td>"."\n";
echo "\t\t"."&nbsp;&nbsp;<input class='flat' type='text' name='conversions' size='25' value='".($row["conversions"] > 0 ? $row["conversions"] : '-')."' onBlur='phpAds_formPriorityUpdate(this.form);' onKeyUp=\"phpAds_formUnlimitedCheck('unlimitedconversions', 'conversions');\" tabindex='".($tabindex++)."'>&nbsp;"."\n";
echo "\t\t"."<input type='checkbox' name='unlimitedconversions'".($row["conversions"] == -1 ? " CHECKED" : '')." onClick=\"phpAds_formUnlimitedClick('unlimitedconversions', 'conversions');\" tabindex='".($tabindex++)."'>&nbsp;".$strUnlimited."\n";
echo "\t"."</td>"."\n";
echo "</tr>"."\n";

echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";
echo "<tr><td height='25' colspan='3'><b>".$strContractDetails."</b></td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td><td width='200'>".$strActivationDate."</td>"."\n";
echo "\t"."<td>";
phpAds_showDateEdit('activate', isset($row["activate_dayofmonth"]) ? $row["activate_dayofmonth"] : 0, 
						   	    isset($row["activate_month"]) ? $row["activate_month"] : 0, 
								isset($row["activate_year"]) ? $row["activate_year"] : 0);
echo "</td>"."\n";
echo "</tr>"."\n";
echo "<tr>"."\n";
echo "\t"."<td><img src='images/spacer.gif' height='1' width='100%'></td>"."\n";
echo "\t"."<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td>"."\n";
echo "</tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td><td width='200'>".$strExpirationDate."</td>"."\n";
echo "\t"."<td>";
phpAds_showDateEdit('expire', isset($row["expire_dayofmonth"]) ? $row["expire_dayofmonth"] : 0, 
							  isset($row["expire_month"]) ? $row["expire_month"] : 0, 
							  isset($row["expire_year"]) ? $row["expire_year"] : 0);
echo "</td>"."\n";
echo "</tr>"."\n";

echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";
echo "<tr><td height='25' colspan='3'><b>".$strPriorityInformation."</b></td></tr>"."\n";
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200' valign='top'>".$strPriorityLevel."</td>"."\n";
echo "\t"."<td>"."\n";
echo "\t\t"."<table>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='radio' name='priority' value='h'".($row['priority'] == 'h' ? ' checked' : '')." onClick=\"phpAds_formPriorityRadioClick(this);\" tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strPriorityHigh."</td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='radio' name='priority' value='m'".($row['priority'] == 'm' ? ' checked' : '')." onClick=\"phpAds_formPriorityRadioClick(this);\" tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strPriorityMedium."</td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='radio' name='priority' value='l'".($row['priority'] == 'l' ? ' checked' : '')." onClick=\"phpAds_formPriorityRadioClick(this);\" tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strPriorityLow."</td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."</table>"."\n";
echo "\t"."</td>"."\n";
echo "</tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td><img src='images/spacer.gif' height='1' width='100%'></td>"."\n";
echo "\t"."<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td>"."\n";
echo "</tr>";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200' valign='top'>".$strPriorityTargeting	."</td>"."\n";
echo "\t"."<td>"."\n";
echo "\t\t"."<table>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='radio' name='delivery' value='auto'".($delivery == 'auto' ? ' checked' : '')." onClick=\"phpAds_formDeliveryRadioClick(this);\" tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strPriorityAutoTargeting."</td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='radio' name='delivery' value='manual'".($delivery == 'manual' ? ' checked' : '')." onClick=\"phpAds_formDeliveryRadioClick(this);\" tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strTargetLimitAdViews." <input onBlur='phpAds_formPriorityUpdate(this.form);' class='flat' type='text' name='targetviews' size='7' value='".(isset($row["target"]) ? $row["target"] : '-')."' tabindex='".($tabindex++)."'> ".$strTargetPerDay."</td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='radio' name='delivery' value='none'".($delivery == 'none' ? ' checked' : '')." onClick=\"phpAds_formDeliveryRadioClick(this);\" tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strCampaignWeight.": <input onBlur='phpAds_formPriorityUpdate(this.form);' class='flat' type='text' name='weight' size='7' value='".(isset($row["weight"]) ? $row["weight"] : $phpAds_config['default_campaign_weight'])."' tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."</table>"."\n";
echo "\t"."</td>"."\n";
echo "</tr>"."\n";

echo "<tr>"."\n";
echo "\t"."<td><img src='images/spacer.gif' height='1' width='100%'></td>"."\n";
echo "\t"."<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td>"."\n";
echo "</tr>";

echo "<tr>"."\n";
echo "\t"."<td width='30'>&nbsp;</td>"."\n";
echo "\t"."<td width='200' valign='top'>".$strPriorityOptimisation."</td>"."\n";
echo "\t"."<td>"."\n";
echo "\t\t"."<table>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='checkbox' name='optimise' value='t'".($row['optimise'] == 't' ? ' checked' : '')." tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strOptimise."</td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."<tr>"."\n";
echo "\t\t\t"."<td valign='top'><input type='checkbox' name='anonymous' value='t'".($row['anonymous'] == 't' ? ' checked' : '')." tabindex='".($tabindex++)."'></td>"."\n";
echo "\t\t\t"."<td valign='top'>".$strAnonymous."</td>"."\n";
echo "\t\t"."</tr>"."\n";
echo "\t\t"."</table>"."\n";
echo "\t"."</td>"."\n";
echo "</tr>"."\n";

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
	"SELECT campaignname".
	" FROM ".$phpAds_config['tbl_campaigns'].
	" WHERE clientid=".$clientid
;

if (isset($campaignid) && ($campaignid > 0))
	$query .= " AND campaignid!=".$campaignid;

$res = phpAds_dbQuery($query) or phpAds_sqlDie();

while ($row = phpAds_dbFetchArray($res))
	$unique_names[] = $row['campaignname'];
?>

<script language='JavaScript'>
<!--
	phpAds_formSetRequirements('campaignname', '<?php echo addslashes($strName); ?>', true, 'unique');
	phpAds_formSetRequirements('views', '<?php echo addslashes($strViewsPurchased); ?>', false, 'number+');
	phpAds_formSetRequirements('clicks', '<?php echo addslashes($strClicksPurchased); ?>', false, 'number+');
	phpAds_formSetRequirements('conversions', '<?php echo addslashes($strConversionsPurchased); ?>', false, 'number+');
	phpAds_formSetRequirements('weight', '<?php echo addslashes($strCampaignWeight); ?>', false, 'number+');
	phpAds_formSetRequirements('targetviews', '<?php echo addslashes($strTargetLimitAdviews.' x '.$strTargetPerDay); ?>', false, 'number+');
	
	phpAds_formSetUnique('campaignname', '|<?php echo addslashes(implode('|', $unique_names)); ?>|');


	
	var previous_target = '';
	var previous_weight = '';
	var previous_priority = '';

	function phpAds_priorityCheck(f)
	{
		if (f.delivery[1].checked && !parseInt(f.targetviews.value))
			return confirm ('<?php echo str_replace("\n", '\n', addslashes($strCampaignWarningNoTarget)); ?>');
		
		if (f.delivery[2].checked && !parseInt(f.weight.value))
			return confirm ('<?php echo str_replace("\n", '\n', addslashes($strCampaignWarningNoWeight)); ?>');
		
		return true;
	}
	
	function phpAds_formDateClick (o, value)
	{
		day = eval ("document.clientform." + o + "Day.value");
		month = eval ("document.clientform." + o + "Month.value");
		year = eval ("document.clientform." + o + "Year.value");

		if (value == false)
		{
			eval ("document.clientform." + o + "Day.selectedIndex = 0");
			eval ("document.clientform." + o + "Month.selectedIndex = 0");
			eval ("document.clientform." + o + "Year.selectedIndex = 0");
		}
		
		if (value == true && (day=='-' || month=='-' || year=='-'))
		{
			eval ("document.clientform." + o + "Set[0].checked = true");
		}
		
		if (o == 'expire')
			phpAds_formPriorityUpdate(document.clientform);
	}

	function phpAds_formDateCheck (o)
	{
		day = eval ("document.clientform." + o + "Day.value");
		month = eval ("document.clientform." + o + "Month.value");
		year = eval ("document.clientform." + o + "Year.value");
		
		if (day=='-' || month=='-' || year=='-')
		{
			eval ("document.clientform." + o + "Set[0].checked = true");
		}
		else
		{
			eval ("document.clientform." + o + "Set[1].checked = true");
		}

		if (o == 'expire')
			phpAds_formPriorityUpdate(document.clientform);
	}
	
	function phpAds_formUnlimitedClick (oc,oe)
	{
		e = findObj(oe);
		c = findObj(oc);
		
		if (c.checked == true) 
		{
			e.value = "-";
		} 
		else 
		{
			e.value = "";
			e.focus();
		}
		
		// Update check
		phpAds_formPriorityUpdate(e.form);
	}

	function phpAds_enableRadioButton(field_name, field_value, enabled)
	{
		var radio_group = findObj(field_name);
		for (var i=0; i<radio_group.length; i++)
		{
			if (radio_group[i].value == field_value)
			{
				radio_group[i].disabled = !enabled;
				break;
			}
		}
	}
	function phpAds_enableTextField(field_name, previous_field, enabled)
	{
		var obj = findObj(field_name);
		
		if (enabled)
		{
			if (obj.disabled)
			{
				obj.value = previous_field;
				obj.disabled = !enabled;
			}
		}
		else
		{
			if (!obj.disabled)
			{
				previous_field = obj.value;
				obj.value = '-';
				obj.disabled = true;
			}
		}
		
		return previous_field;
	}
	function phpAds_formPriorityRadioClick(rd)
	{
		phpAds_formPriorityUpdate(rd.form);
	}
	function phpAds_formDeliveryRadioClick(rd)
	{
		phpAds_formPriorityUpdate(rd.form);
		
		if (rd.value == 'm')
		{
			f.targetviews.select();
			f.targetviews.focus();
		}
		else if (rd.value == 'l')
		{
			f.weight.select();
			f.weight.focus();
		}
	}	
	function phpAds_formUnlimitedCheck (oc,oe)
	{
		e = findObj(oe);
		c = findObj(oc);
	
		c.checked = e.value != '-' ? false : true;
		phpAds_formPriorityUpdate(e.form);
	}
	
	function phpAds_formPriorityUpdate (f)
	{
		// Check to see if autotargeting is available...
		// Autotargeting is only available if there is an expiration date and a set number of views.
		var autotarget_available =  ( !(f.expireSet[0].checked == true) &&
									  !( isNaN(f.views.value) || (f.views.value == '') || (f.unlimitedviews.checked == true) )
									);

	    //alert(autotarget_available);
	    							 // If autotargeting is available, do not allow manual targeting, no targeting, or low priority campaigns.
		f.delivery[0].disabled = !autotarget_available;
		f.delivery[1].disabled = autotarget_available;
		f.delivery[2].disabled = autotarget_available;
		// Disable low priority campaigns if autotargeting is available.
		f.priority[2].disabled = autotarget_available;
			
		// Only allow optimisation on medium and high priority campaigns.
		if (!f.priority[0].disabled && f.priority[0].checked)
		{
			f.optimise.disabled = false;
			phpAds_enableRadioButton('delivery', 'auto', autotarget_available);
			phpAds_enableRadioButton('delivery', 'manual', !autotarget_available);
			phpAds_enableRadioButton('delivery', 'none', false);
		}
		if (!f.priority[1].disabled && f.priority[1].checked)
		{
			f.optimise.disabled = false;
			phpAds_enableRadioButton('delivery', 'auto', autotarget_available);
			phpAds_enableRadioButton('delivery', 'manual', !autotarget_available);
			phpAds_enableRadioButton('delivery', 'none', false);
		}
		if (!f.priority[2].disabled && f.priority[2].checked)
		{
			f.optimise.disabled = true;
			phpAds_enableRadioButton('delivery', 'auto', false);
			phpAds_enableRadioButton('delivery', 'manual', false);
			phpAds_enableRadioButton('delivery', 'none', true);
		}

		// Try to set the correct priority radio buttons...
		if ( (f.priority[2].checked && f.priority[2].disabled) ||
			 (f.priority[1].checked && f.priority[1].disabled) ||
			 (f.priority[0].checked && f.priority[0].disabled) )
		{
			if (!f.priority[0].disabled)
				f.priority[0].checked = true;
			else if (!f.priority[2].disabled)
				f.priority[2].checked = true;
			else if (!f.priority[1].disabled)
				f.priority[1].checked = true;
		}
		
		// Try to set the correct delivery radio buttons...
		if ( (f.delivery[2].checked && f.delivery[2].disabled) ||
			 (f.delivery[1].checked && f.delivery[1].disabled) ||
			 (f.delivery[0].checked && f.delivery[0].disabled) )
		{
			if (!f.delivery[0].disabled)
				f.delivery[0].checked = true;
			else if (!f.delivery[2].disabled)
				f.delivery[2].checked = true;
			else if (!f.delivery[1].disabled)
				f.delivery[1].checked = true;
		}
		
		// Only enable target/weight if their radio buttons are checked.
		var len = f.delivery.length;
		for (var i=0; i<f.delivery.length; i++)
		{
			if (!f.delivery[i].disabled && f.delivery[i].checked)
			{
				if (f.delivery[i].value == 'auto')
				{
					previous_target = phpAds_enableTextField('targetviews', previous_target, false);
					previous_weight = phpAds_enableTextField('weight', previous_weight, false);
				}
				else if (f.delivery[i].value == 'manual')
				{
					previous_target = phpAds_enableTextField('targetviews', previous_target, true);
					previous_weight = phpAds_enableTextField('weight', previous_weight, false);
				}
				else if (f.delivery[i].value == 'none')
				{
					previous_target = phpAds_enableTextField('targetviews', previous_target, false);
					previous_weight = phpAds_enableTextField('weight', previous_weight, true);
				}
				break;
			}
		}
	}
	
	// Set default values for priority
	phpAds_formPriorityUpdate(document.clientform);
	
//-->
</script>

<?php



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>