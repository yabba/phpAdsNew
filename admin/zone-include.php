<?php // $Revision: 2.8 $

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
require ("lib-zones.inc.php");


// Register input variables
phpAds_registerGlobal (
	 'action'
	,'hideinactive'
	,'showbanners'
	,'showcampaigns'
	,'submit'
	,'what'
	,'zonetype'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency + phpAds_Affiliate);



/*********************************************************/
/* Affiliate interface security                          */
/*********************************************************/

if (phpAds_isUser(phpAds_Admin))
{
	$query = "SELECT a.agencyid AS agencyid".
		" FROM ".$phpAds_config['tbl_affiliates']." AS a".
		",".$phpAds_config['tbl_zones']." AS z".
		" WHERE z.affiliateid=".$affiliateid.
		" AND z.zoneid=".$zoneid.
		" AND z.affiliateid=a.affiliateid";
	
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
		
	if ($row = phpAds_dbFetchArray($res))
	{
		$agencyid = $row['agencyid'];
	}
	else
	{
		phpAds_PageHeader("2");
		phpAds_Die ($strAccessDenied, $strParametersWrong);
	}
}
elseif (phpAds_isUser(phpAds_Agency))
{
	$agencyid = phpAds_getUserID();
	
	$query = "SELECT z.zoneid as zoneid".
		" FROM ".$phpAds_config['tbl_affiliates']." AS a".
		",".$phpAds_config['tbl_zones']." AS z".
		" WHERE z.affiliateid=".$affiliateid.
		" AND z.zoneid=".$zoneid.
		" AND z.affiliateid=a.affiliateid".
		" AND a.agencyid=".$agencyid;
	
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
		
	if (phpAds_dbNumRows($res) == 0)
	{
		phpAds_PageHeader("2");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}
elseif (phpAds_isUser(phpAds_Affiliate))
{
	if (phpAds_isAllowed(phpAds_LinkBanners))
	{
		$affiliateid = phpAds_getUserID();
		
		$query = "SELECT a.agencyid".
			" FROM ".$phpAds_config['tbl_zones']." AS z".
			",".$phpAds_config['tbl_affiliates']." AS a".
			" WHERE z.affiliateid=a.affiliateid".
			" AND z.zoneid=".$zoneid.
			" AND a.affiliateid=".$affiliateid;
			
		$result = phpAds_dbQuery($query)
			or phpAds_sqlDie();
	
	$row = phpAds_dbFetchArray($result);
	
		if (phpAds_dbNumRows($res) == 0)
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
	else
	{
			$agencyid = $row["agencyid"];
		}
	}
	else
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}



/*********************************************************/
/* Process submitted form                                */
/*********************************************************/

if (isset($zoneid) && $zoneid != '')
{
	if (isset($action) && $action == 'toggle')
	{
		// Update zonetype
		$result = phpAds_dbQuery(
			"SELECT zonetype".
			" FROM ".$phpAds_config['tbl_zones'].
			" WHERE zoneid=".$zoneid
		) or phpAds_sqlDie();
		
		if ($row = phpAds_dbFetchArray($result))
		{
			if ($row['zonetype'] != $zonetype)
			{
				$res = phpAds_dbQuery(
					"UPDATE ".$phpAds_config['tbl_zones'].
					" SET zonetype=".$zonetype.
					",what=''".
					" WHERE zoneid=".$zoneid
				) or phpAds_sqlDie();
			}
		}
		
		if ($zonetype == phpAds_ZoneBanners)
		{
			if (isset($bannerid) && $bannerid != '')
			{
				phpAds_ToggleBannerInZone ($bannerid, $zoneid);
			}
		}
		
		if ($zonetype == phpAds_ZoneCampaign)
		{
			if (isset($campaignid) && $campaignid != '')
			{
				phpAds_ToggleCampaignInZone ($campaignid, $zoneid);
			}
		}
		
		header ("Location: zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&clientid=".$clientid."&campaignid=".$campaignid);
		exit;
	}
	
	if (isset($action) && $action == 'set')
	{
		if (!isset($what)) $what = '';
		
		if ($zonetype == phpAds_ZoneBanners)
		{
			if (isset($bannerid) && is_array($bannerid))
			{
				for ($i=0;$i<sizeof($bannerid);$i++)
					$bannerid[$i] = 'bannerid:'.$bannerid[$i];
				
				$what .= implode (',', $bannerid);
			}
		}
		
		if ($zonetype == phpAds_ZoneCampaign)
		{
			if (isset($campaignid) && is_array($campaignid))
			{
				for ($i=0;$i<sizeof($campaignid);$i++)
					$campaignid[$i] = 'campaignid:'.$campaignid[$i];
				
				$what .= implode (',', $campaignid);
			}
		}
		
		if (isset($zonetype))
		{
			$res = phpAds_dbQuery("
				UPDATE
					".$phpAds_config['tbl_zones']."
				SET
					what = '$what',
					zonetype = $zonetype
				WHERE
					zoneid=$zoneid
			") or phpAds_sqlDie();
		}
		
		// Rebuild Cache
		if (!defined('LIBVIEWCACHE_INCLUDED'))  include (phpAds_path.'/libraries/deliverycache/cache-'.$phpAds_config['delivery_caching'].'.inc.php');
		
		phpAds_cacheDelete('what=zone:'.$zoneid);
		
		header ("Location: zone-probability.php?affiliateid=".$affiliateid."&zoneid=".$zoneid);
		exit;
	}
}



/*********************************************************/
/* Get preferences                                       */
/*********************************************************/

if (!isset($hideinactive))
{
	if (isset($Session['prefs']['zone-include.php']['hideinactive']))
		$hideinactive = $Session['prefs']['zone-include.php']['hideinactive'];
	else
		$hideinactive = ($phpAds_config['gui_hide_inactive'] == 't');
}

if (!isset($showbanners))
{
	if (isset($Session['prefs']['zone-include.php']['showbanners']))
		$showbanners = $Session['prefs']['zone-include.php']['showbanners'];
	else
		$showbanners = ($phpAds_config['gui_show_matching'] == 't');
}

if (!isset($showcampaigns))
{
	if (isset($Session['prefs']['zone-include.php']['showcampaigns']))
		$showcampaigns = $Session['prefs']['zone-include.php']['showcampaigns'];
	else
		$showcampaigns = ($phpAds_config['gui_show_parents'] == 't');
}



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (isset($Session['prefs']['affiliate-zones.php']['listorder']))
	$navorder = $Session['prefs']['affiliate-zones.php']['listorder'];
else
	$navorder = '';

if (isset($Session['prefs']['affiliate-zones.php']['orderdirection']))
	$navdirection = $Session['prefs']['affiliate-zones.php']['orderdirection'];
else
	$navdirection = '';


// Get other zones
$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_zones'].
	" WHERE affiliateid=".$affiliateid.
	phpAds_getZoneListOrder ($navorder, $navdirection)
);

while ($row = phpAds_dbFetchArray($res))
{
	phpAds_PageContext (
		phpAds_buildZoneName ($row['zoneid'], $row['zonename']),
		"zone-include.php?affiliateid=".$affiliateid."&zoneid=".$row['zoneid'],
		$zoneid == $row['zoneid']
	);
}

if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
{
	phpAds_PageShortcut($strAffiliateProperties, 'affiliate-edit.php?affiliateid='.$affiliateid, 'images/icon-affiliate.gif');
	phpAds_PageShortcut($strZoneHistory, 'stats-zone-history.php?affiliateid='.$affiliateid.'&zoneid='.$zoneid, 'images/icon-statistics.gif');
	
	
	$extra  = "<form action='zone-modify.php'>"."\n";
	$extra .= "<input type='hidden' name='zoneid' value='$zoneid'>"."\n";
	$extra .= "<input type='hidden' name='affiliateid' value='$affiliateid'>"."\n";
	$extra .= "<input type='hidden' name='returnurl' value='zone-include.php'>"."\n";
	$extra .= "<br><br>"."\n";
	$extra .= "<b>$strModifyZone</b><br>"."\n";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>"."\n";
	$extra .= "<img src='images/icon-duplicate-zone.gif' align='absmiddle'>&nbsp;<a href='zone-modify.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&duplicate=true&returnurl=zone-include.php'>$strDuplicate</a><br>"."\n";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>"."\n";
	$extra .= "<img src='images/icon-move-zone.gif' align='absmiddle'>&nbsp;$strMoveTo<br>"."\n";
	$extra .= "<img src='images/spacer.gif' height='1' width='160' vspace='2'><br>"."\n";
	$extra .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"."\n";
	$extra .= "<select name='moveto' style='width: 110;'>"."\n";
	
	$query = "SELECT affiliateid,name".
		" FROM ".$phpAds_config['tbl_affiliates'].
		" WHERE affiliateid != ".$affiliateid.
		" AND agencyid=".$agencyid;

	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
		$extra .= "\t"."<option value='".$row['affiliateid']."'>".phpAds_buildAffiliateName($row['affiliateid'], $row['name'])."</option>"."\n";
	
	$extra .= "</select>"."\n";
	$extra .= "&nbsp;<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif'><br>";
	$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
	$extra .= "<img src='images/icon-recycle.gif' align='absmiddle'>&nbsp;<a href='zone-delete.php?affiliateid=$affiliateid&zoneid=$zoneid&returnurl=affiliate-zones.php'".phpAds_DelConfirm($strConfirmDeleteZone).">$strDelete</a><br>"."\n";
	$extra .= "</form>"."\n";
	
	
	phpAds_PageHeader("4.2.3.3", $extra);
		echo "<img src='images/icon-affiliate.gif' align='absmiddle'>&nbsp;".phpAds_getAffiliateName($affiliateid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-zone.gif' align='absmiddle'>&nbsp;<b>".phpAds_getZoneName($zoneid)."</b><br><br><br>"."\n";
		phpAds_ShowSections(array("4.2.3.2", "4.2.3.6", "4.2.3.3", "4.2.3.4", "4.2.3.5"));
}
else
{
	if (phpAds_isAllowed(phpAds_EditZone)) $sections[] = "2.1.2";
	if (phpAds_isAllowed(phpAds_EditZone)) $sections[] = "2.1.6";
	$sections[] = "2.1.3";
	$sections[] = "2.1.4";
	$sections[] = "2.1.5";
	
	phpAds_PageHeader("2.1.3");
		echo "<img src='images/icon-affiliate.gif' align='absmiddle'>&nbsp;".phpAds_getAffiliateName($affiliateid);
		echo "&nbsp;<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>&nbsp;";
		echo "<img src='images/icon-zone.gif' align='absmiddle'>&nbsp;<b>".phpAds_getZoneName($zoneid)."</b><br><br><br>"."\n";
		phpAds_ShowSections($sections);
}




/*********************************************************/
/* Main code                                             */
/*********************************************************/

function phpAds_showZoneCampaign ($width, $height, $what, $delivery)
{
	global
		 $affiliateid
		,$agencyid
		,$hideinactive
		,$phpAds_config
		,$phpAds_TextAlignRight
		,$phpAds_TextAlignLeft
		,$showbanners
		,$strCheckAllNone
		,$strDescription
		,$strEdit
		,$strHideMatchingBanners
		,$strHideInactiveCampaigns
		,$strID
		,$strInactiveCampaignsHidden
		,$strMatchingBanners
		,$strNoCampaignsToLink
		,$strName
		,$strSaveChanges
		,$strSelectCampaignToLink
		,$strShowAll
		,$strShowBanner
		,$strShowMatchingBanners
		,$strUntitled
		,$tabindex
		,$zoneid
	;
	
	$what_array = explode(",",$what);
	for ($k=0; $k < count($what_array); $k++)
	{
		if (substr($what_array[$k],0,11)=="campaignid:")
		{
			$campaignid = substr($what_array[$k],11);
			$campaignids[$campaignid] = true;
		}
	}
	
	// Fetch all campaigns for the agency
	$query = "SELECT m.campaignid AS campaignid".
		",m.campaignname as campaignname".
		",m.clientid as clientid".
		",m.active as active".
		" FROM ".$phpAds_config['tbl_campaigns']." AS m".
		",".$phpAds_config['tbl_clients']." AS c".
		" WHERE m.clientid=c.clientid".
		" AND c.agencyid=".$agencyid;
	
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
		$campaigns[$row['campaignid']] = $row;
	
	$compact = (phpAds_dbNumRows($res) > $phpAds_config['gui_link_compact_limit']);
	
	
	// Fetch all banners which can be linked
	$query = "SELECT b.bannerid AS bannerid".
		",b.campaignid AS campaignid".
		",b.alt AS alt".
		",b.description AS description".
		",b.active AS active".
		",b.storagetype AS storagetype".
		",b.contenttype AS contenttype".
		",b.width AS width".
		",b.height AS height".
		" FROM ".$phpAds_config['tbl_banners']." AS b".
		",".$phpAds_config['tbl_campaigns']." AS m".
		",".$phpAds_config['tbl_clients']." AS c".
		" WHERE b.campaignid=m.campaignid".
		" AND m.clientid=c.clientid".
		" AND c.agencyid=".$agencyid;
	
	if ($delivery != phpAds_ZoneText)
	{
		if ($width != -1 && $height != -1)
			$query .= " AND width = $width AND height = $height AND contenttype != 'txt'";
		elseif ($width != -1)
			$query .= " AND width = $width AND contenttype != 'txt'";
		elseif ($height != -1)
			$query .= " AND height = $height AND contenttype != 'txt'";
		else
			$query .= " AND contenttype != 'txt'";
	}
	else
	{
		$query .= " AND contenttype = 'txt'";
	}
	
	$query .= " ORDER BY bannerid";
	
	$res = phpAds_dbQuery($query) or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$campaigns[$row['campaignid']]['banners'][$row['bannerid']] = $row;
	}
	
	
	
	if (!$compact)
	{
		echo "<form name='zonetypeselection' method='post' action='zone-include.php'>"."\n";
		echo "<input type='hidden' name='zoneid' value='".$GLOBALS['zoneid']."'>"."\n";
		echo "<input type='hidden' name='affiliateid' value='".$GLOBALS['affiliateid']."'>"."\n";
		echo "<input type='hidden' name='zonetype' value='".phpAds_ZoneCampaign."'>"."\n";
		echo "<input type='hidden' name='action' value='set'>"."\n";
	}
	else
	{
		echo "<br>".$strSelectCampaignToLink."<br><br>"."\n";
		echo "<table cellpadding='0' cellspacing='0' border='0'><tr>"."\n";
		
		echo "<form name='zonetypeselection' method='get' action='zone-include.php'>"."\n";
		echo "<input type='hidden' name='zoneid' value='".$GLOBALS['zoneid']."'>"."\n";
		echo "<input type='hidden' name='affiliateid' value='".$GLOBALS['affiliateid']."'>"."\n";
		echo "<input type='hidden' name='zonetype' value='".phpAds_ZoneCampaign."'>"."\n";
		
		echo "<td><img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;"."\n";
		echo "<select name='clientid' onChange='this.form.submit();' tabindex='".($tabindex++)."'>"."\n";
		
		if (!isset($GLOBALS['clientid']) || $GLOBALS['clientid'] == '')
			echo "<option value='' selected></option>"."\n";
		
		// Fetch all campaigns
		$query = "SELECT clientid,clientname".
			" FROM ".$phpAds_config['tbl_clients'].
			" WHERE agencyid=".$agencyid;
		
		$res = phpAds_dbQuery($query)
			or phpAds_sqlDie();
		
		while ($row = phpAds_dbFetchArray($res))
		{
			if (isset($GLOBALS['clientid']) && $GLOBALS['clientid'] == $row['clientid'])
				echo "<option value='".$row['clientid']."' selected>[id".$row['clientid']."] ".$row['clientname']."</option>"."\n";
			else
				echo "<option value='".$row['clientid']."'>[id".$row['clientid']."] ".$row['clientname']."</option>"."\n";
		}
		
		echo "</select>"."\n";
		echo "</td></form>"."\n";
		
		if (isset($GLOBALS['clientid']) && $GLOBALS['clientid'] != '')
		{
			echo "<form name='zonetypeselection' method='get' action='zone-include.php'>"."\n";
			echo "<input type='hidden' name='zoneid' value='".$GLOBALS['zoneid']."'>"."\n";
			echo "<input type='hidden' name='affiliateid' value='".$GLOBALS['affiliateid']."'>"."\n";
			echo "<input type='hidden' name='clientid' value='".$GLOBALS['clientid']."'>"."\n";
			echo "<input type='hidden' name='zonetype' value='".phpAds_ZoneCampaign."'>"."\n";
			echo "<input type='hidden' name='action' value='toggle'>"."\n";
			echo "<td>&nbsp;&nbsp;<img src='images/caret-r.gif' align='absmiddle'>&nbsp;&nbsp;"."\n";
			echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;"."\n";
			echo "<select name='campaignid' tabindex='".($tabindex++)."'>"."\n";
			
			// Fetch all campaigns
			$res = phpAds_dbQuery(
				"SELECT campaignid,campaignname,active".
				" FROM ".$phpAds_config['tbl_campaigns'].
				" WHERE	clientid = ".$GLOBALS['clientid']
			) or phpAds_sqlDie();
			
			while ($row = phpAds_dbFetchArray($res))
			{
				if (!isset($campaignids[$row['campaignid']]) || $$campaignids[$row['campaignid']] != true)
					echo "<option value='".$row['campaignid']."'>[id".$row['campaignid']."] ".$row['campaignname']." (".(isset($campaigns[$row['campaignid']]['banners']) ? count($campaigns[$row['campaignid']]['banners']) : 0).")</option>"."\n";
			}
			
			echo "</select>"."\n";
			echo "&nbsp;<input type='image' src='images/".$GLOBALS['phpAds_TextDirection']."/go_blue.gif' border='0' tabindex='".($tabindex++)."'>"."\n";
			echo "</td></form>"."\n";
		}
		
		echo "</tr></table>"."\n";
		echo "<br><br>"."\n";
	}
	
	
	// Header
	echo "<table width='100%' border='0' align='center' cellspacing='0' cellpadding='0'>"."\n";
	echo "<tr height='25'>"."\n";
	echo "<td height='25' width='40%'><b>&nbsp;&nbsp;$strName</b></td>"."\n";
	echo "<td height='25'><b>$strID&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b></td>"."\n";
	echo "<td height='25'>&nbsp;</td>"."\n";
	echo "</tr>"."\n";
	
	echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
	
	$i = 0;
	$checkedall = true;
	$inactivehidden = 0;
	
	if (!$compact && phpAds_dbNumRows($res) == 0)
	{
		echo "<tr bgcolor='#F6F6F6'><td colspan='3' height='25'>&nbsp;&nbsp;".$strNoCampaignsToLink."</td></tr>"."\n";
	}
	else
	{
		for (reset($campaigns); $ckey = key($campaigns); next($campaigns))
		{
			$campaign = $campaigns[$ckey];
			
			$linkedrow = (isset($campaignids[$campaign['campaignid']]) && $campaignids[$campaign['campaignid']] == true);
			
			if ($compact)
				$showrow = $linkedrow;
			else
				$showrow = ($hideinactive == false || $hideinactive == true && ($campaign['active'] == 't' || $linkedrow));
			
			if (!$compact && !$showrow) $inactivehidden++;
			
			if ($showrow)
			{
				if ($i > 0) echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>"."\n";
				echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">"."\n";
				
				// Begin row
				echo "<td height='25'>";
				echo "&nbsp;&nbsp;"."\n";
				
				if (!$compact)
				{
					// Show checkbox
					if (isset($campaignids[$campaign['campaignid']]) && $campaignids[$campaign['campaignid']] == true)
						echo "<input type='checkbox' name='campaignid[]' value='".$campaign['campaignid']."' checked onclick='reviewall();' tabindex='".($tabindex++)."'>"."\n";
					else
					{
						echo "<input type='checkbox' name='campaignid[]' value='".$campaign['campaignid']."' onclick='reviewall();' tabindex='".($tabindex++)."'>"."\n";
						$checkedall = false;
					}
				}
				else
				{
					echo "<a href='zone-include.php?affiliateid=".$GLOBALS['affiliateid']."&zoneid=".$GLOBALS['zoneid']."&campaignid=".$campaign['campaignid']."&zonetype=".phpAds_ZoneCampaign."&action=toggle'>"."\n";
					echo "<img src='images/caret-l.gif' border='0' align='absmiddle'></a>"."\n";
				}
				
				// Space
				echo "&nbsp;&nbsp;";
				
				
				// Banner icon
				if ($campaign['active'] == 't')
					echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;"."\n";
				else
					echo "<img src='images/icon-campaign-d.gif' align='absmiddle'>&nbsp;"."\n";
				
				
				// Name
				if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
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
				if ($showbanners)
					echo "&nbsp;";
				else
					echo str_replace ('{count}', isset($campaign['banners']) ? count($campaign['banners']) : 0, $strMatchingBanners);
				echo "</td>"."\n";
				
				// End row
				echo "</tr>"."\n";
				
				
				if ($showbanners && isset($campaign['banners']))
				{
					reset ($campaign['banners']);
					while (list ($bannerid, $banner) = each ($campaign['banners']))
					{
						$name = $strUntitled;
						if (isset($banner['alt']) && $banner['alt'] != '') $name = $banner['alt'];
						if (isset($banner['description']) && $banner['description'] != '') $name = $banner['description'];
						
						$name = phpAds_breakString ($name, '60');
						
						
						echo "<tr height='1'>"."\n";
						echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>"."\n";
						echo "<td colspan='3' bgcolor='#888888'><img src='images/break-el.gif' height='1' width='100%'></td>"."\n";
						echo "</tr>"."\n";
						
						echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>"."\n";
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"."\n";
						if (!$compact) echo "&nbsp;&nbsp;";
						
						// Banner icon
						if ($campaign['active'] == 't' && $banner['active'] == 't')
						{
							if ($banner['storagetype'] == 'html')
								echo "<img src='images/icon-banner-html.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'url')
								echo "<img src='images/icon-banner-url.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'txt')
								echo "<img src='images/icon-banner-text.gif' align='absmiddle'>&nbsp;"."\n";
							else
								echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>&nbsp;"."\n";
						}
						else
						{
							if ($banner['storagetype'] == 'html')
								echo "<img src='images/icon-banner-html-d.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'url')
								echo "<img src='images/icon-banner-url-d.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'txt')
								echo "<img src='images/icon-banner-text-d.gif' align='absmiddle'>&nbsp;"."\n";
							else
								echo "<img src='images/icon-banner-stored-d.gif' align='absmiddle'>&nbsp;"."\n";
						}
						
						
						// Name
						if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
						{
							echo "<a href='banner-edit.php?clientid=".$campaign['clientid']."&campaignid=".$campaign['campaignid']."&bannerid=".$banner['bannerid']."'>";
							echo $name."</a>"."\n";
						}
						else
							echo $name;
						
						echo "</td>"."\n";
						
						
						// ID
						echo "<td height='25'>".$banner['bannerid']."</td>"."\n";
						
						// Show banner
						if ($banner['contenttype'] == 'txt')
						{
							$width	= 300;
							$height = 200;
						}
						else
						{
							$width  = $banner['width'] + 64;
							$height = $banner['bannertext'] ? $banner['height'] + 90 : $banner['height'] + 64;
						}
						
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>"."\n";
						echo "<a href='banner-htmlpreview.php?bannerid=$bannerid' target='_new' ";
						echo "onClick=\"return openWindow('banner-htmlpreview.php?bannerid=".$banner['bannerid']."', '', 'status=no,scrollbars=no,resizable=no,width=".$width.",height=".$height."');\">"."\n";
						echo "<img src='images/icon-zoom.gif' align='absmiddle' border='0'>&nbsp;".$strShowBanner."</a>&nbsp;&nbsp;"."\n";
						echo "</td>"."\n";
					}
				}
				
				
				$i++;
			}
		}
	}
	
	if (!$compact)
	{
		echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>"."\n";
		echo "<tr ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>"."\n";
		echo "&nbsp;&nbsp;<input type='checkbox' name='checkall' value=''".($checkedall == true ? ' checked' : '')." onclick='toggleall();' tabindex='".($tabindex++)."'>"."\n";
		echo "&nbsp;&nbsp;<b>".$strCheckAllNone."</b>"."\n";
		echo "</td><td>&nbsp;</td><td>&nbsp;</td></tr>"."\n";
	}
	
	echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
	echo "<tr><td height='25' align='".$phpAds_TextAlignLeft."' nowrap>"."\n";
	
	if (!$compact)
	{
		if ($hideinactive == true)
		{
			echo "&nbsp;&nbsp;<img src='images/icon-activate.gif' align='absmiddle' border='0'>"."\n";
			echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneCampaign."&hideinactive=0'>".$strShowAll."</a>"."\n";
			echo "&nbsp;&nbsp;|&nbsp;&nbsp;".$inactivehidden." ".$strInactiveCampaignsHidden;
		}
		else
		{
			echo "&nbsp;&nbsp;<img src='images/icon-hideinactivate.gif' align='absmiddle' border='0'>"."\n";
			echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneCampaign."&hideinactive=1'>".$strHideInactiveCampaigns."</a>"."\n";
		}
	}
	
	echo "</td><td colspan='2' align='".$phpAds_TextAlignRight."' nowrap>"."\n";
	
	if ($showbanners == true)
	{
		echo "&nbsp;&nbsp;<img src='images/icon-banner-stored-d.gif' align='absmiddle' border='0'>"."\n";
		echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneCampaign."&showbanners=0'>".$strHideMatchingBanners."</a>"."\n";
	}
	else
	{
		echo "&nbsp;&nbsp;<img src='images/icon-banner-stored.gif' align='absmiddle' border='0'>"."\n";
		echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneCampaign."&showbanners=1'>".$strShowMatchingBanners."</a>"."\n";
	}
	
	echo "&nbsp;&nbsp;</td></tr>"."\n";
	echo "</table>"."\n";
	echo "<br><br><br><br>"."\n";
	
	if (!$compact)
	{
		echo "<input type='submit' name='submit' value='$strSaveChanges' tabindex='".($tabindex++)."'>"."\n";
		echo "</form>"."\n";
	}
}



function phpAds_showZoneBanners ($width, $height, $what, $zonetype, $delivery)
{
	global
		 $phpAds_config
		,$showcampaigns
		,$hideinactive
		,$affiliateid
		,$agencyid
		,$zoneid
		,$strName
		,$strID
		,$strUntitled
		,$strDescription
		,$phpAds_TextAlignRight
		,$phpAds_TextAlignLeft
		,$strEdit
		,$strCheckAllNone
		,$strShowBanner
		,$strNoBannersToLink
		,$strSaveChanges
		,$strSelectBannerToLink
		,$strInactiveBannersHidden
		,$strShowParentCampaigns
		,$strHideParentCampaigns
		,$strHideInactiveBanners
		,$strShowAll
		,$tabindex
	;
	
	if ($zonetype == phpAds_ZoneBanners)
	{
		// Determine selected banners
		$what_array = explode(",",$what);
		for ($k=0; $k < count($what_array); $k++)
		{
			if (substr($what_array[$k],0,9)=="bannerid:")
			{
				$bannerid = substr($what_array[$k],9);
				$bannerids[$bannerid] = true;
			}
		}
	}
	elseif ($zonetype == phpAds_ZoneCampaign)
	{
		// Determine selected campaigns
		$campaignids = array();
		$what_array = explode(",",$what);
		for ($k=0; $k < count($what_array); $k++)
		{
			if (substr($what_array[$k],0,11)=="campaignid:")
			{
				$campaignid = substr($what_array[$k],11);
				$campaignids[] = "campaignid=".$campaignid;
			}
		}
		
		// Determine banners owned by selected campaigns
		if (count($campaignids))
		{
			$res = phpAds_dbQuery(
				"SELECT bannerid".
				" FROM ".$phpAds_config['tbl_banners'].
				" WHERE ".implode (" OR ", $campaignids)
			);
			
			while ($row = phpAds_dbFetchArray($res))
				$bannerids[$row['bannerid']] = true;
		}
		else
			$bannerids = array();
	}
	else
	{
		$bannerids = array();
	}
	
	// Fetch all campaigns
	$query = "SELECT m.campaignid AS campaignid".
		",m.clientid AS clientid".
		",m.campaignname AS campaignname".
		",m.active AS active".
		" FROM ".$phpAds_config['tbl_campaigns']." AS m".
		",".$phpAds_config['tbl_clients']." AS c".
		" WHERE m.clientid=c.clientid".
		" AND c.agencyid=".$agencyid;
	
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	while ($row = phpAds_dbFetchArray($res))
		$campaigns[$row['campaignid']] = $row;
	
	
	// Fetch all banners which can be linked
	$query = "SELECT b.bannerid AS bannerid".
		",b.campaignid AS campaignid".
		",b.alt AS alt".
		",b.description AS description".
		",b.active AS active".
		",b.storagetype AS storagetype".
		",b.contenttype AS contenttype".
		",b.width AS width".
		",b.height AS height".
		" FROM ".$phpAds_config['tbl_banners']." AS b".
		",".$phpAds_config['tbl_campaigns']." AS m".
		",".$phpAds_config['tbl_clients']." AS c".
		" WHERE b.campaignid=m.campaignid".
		" AND m.clientid=c.clientid".
		" AND c.agencyid=".$agencyid;
	
	if ($delivery != phpAds_ZoneText)
	{
		if ($width != -1 && $height != -1)
			$query .= " AND width = $width AND height = $height AND contenttype != 'txt'";
		elseif ($width != -1)
			$query .= " AND width = $width AND contenttype != 'txt'";
		elseif ($height != -1)
			$query .= " AND height = $height AND contenttype != 'txt'";
		else
			$query .= " AND contenttype != 'txt'";
	}
	else
	{
		$query .= " AND contenttype = 'txt'";
	}
	
	$query .= " ORDER BY bannerid";
	
	$res = phpAds_dbQuery($query);
	$compact = (phpAds_dbNumRows($res) > $phpAds_config['gui_link_compact_limit']);
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$campaigns[$row['campaignid']]['banners'][$row['bannerid']] = $row;
	}
	
	$inactivehidden = 0;
	
	
	
	if (!$compact)
	{
		echo "<form name='zonetypeselection' method='post' action='zone-include.php'>"."\n";
		echo "<input type='hidden' name='zoneid' value='".$GLOBALS['zoneid']."'>"."\n";
		echo "<input type='hidden' name='affiliateid' value='".$GLOBALS['affiliateid']."'>"."\n";
		echo "<input type='hidden' name='zonetype' value='".phpAds_ZoneBanners."'>"."\n";
		echo "<input type='hidden' name='action' value='set'>"."\n";
	}
	else
	{
		echo "<br>".$strSelectBannerToLink."<br><br>"."\n";
		echo "<table cellpadding='0' cellspacing='0' border='0'><tr>"."\n";
		
		echo "<form name='zonetypeselection' method='get' action='zone-include.php'>"."\n";
		echo "<input type='hidden' name='zoneid' value='".$GLOBALS['zoneid']."'>"."\n";
		echo "<input type='hidden' name='affiliateid' value='".$GLOBALS['affiliateid']."'>"."\n";
		echo "<input type='hidden' name='zonetype' value='".phpAds_ZoneBanners."'>"."\n";
		
		echo "<td><img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;"."\n";
		echo "<select name='clientid' onChange='this.form.submit();' tabindex='".($tabindex++)."'>"."\n";
		
		if (!isset($GLOBALS['clientid']) || $GLOBALS['clientid'] == '')
			echo "<option value='' selected></option>"."\n";
		
		// Fetch all advertisers
		$query = "SELECT clientid,clientname".
			" FROM ".$phpAds_config['tbl_clients'].
			" WHERE agencyid=".$agencyid;

		$res = phpAds_dbQuery($query)
			or phpAds_sqlDie();
		
		while ($row = phpAds_dbFetchArray($res))
		{
			if (isset($GLOBALS['clientid']) && $GLOBALS['clientid'] == $row['clientid'])
				echo "<option value='".$row['clientid']."' selected>[id".$row['clientid']."] ".$row['clientname']."</option>"."\n";
			else
				echo "<option value='".$row['clientid']."'>[id".$row['clientid']."] ".$row['clientname']."</option>"."\n";
		}
		
		echo "</select>"."\n";
		echo "</td></form>"."\n";
		
		if (isset($GLOBALS['clientid']) && $GLOBALS['clientid'] != '')
		{
			echo "<form name='zonetypeselection' method='get' action='zone-include.php'>"."\n";
			echo "<input type='hidden' name='zoneid' value='".$GLOBALS['zoneid']."'>"."\n";
			echo "<input type='hidden' name='affiliateid' value='".$GLOBALS['affiliateid']."'>"."\n";
			echo "<input type='hidden' name='clientid' value='".$GLOBALS['clientid']."'>"."\n";
			echo "<input type='hidden' name='zonetype' value='".phpAds_ZoneBanners."'>"."\n";
			echo "<td>&nbsp;&nbsp;<img src='images/caret-r.gif' align='absmiddle'>&nbsp;&nbsp;"."\n";
			echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;"."\n";
			echo "<select name='campaignid' onChange='this.form.submit();' tabindex='".($tabindex++)."'>"."\n";
			
			if (!isset($GLOBALS['campaignid']) || $GLOBALS['campaignid'] == '')
				echo "<option value='' selected></option>"."\n";
			
			// Fetch all campaigns
			$res = phpAds_dbQuery(
				"SELECT campaignid,campaignname".
				" FROM ".$phpAds_config['tbl_campaigns'].
				" WHERE clientid=".$GLOBALS['clientid']
			) or phpAds_sqlDie();
			
			while ($row = phpAds_dbFetchArray($res))
			{
				if (isset($GLOBALS['campaignid']) && $GLOBALS['campaignid'] == $row['campaignid'])
					echo "<option value='".$row['campaignid']."' selected>[id".$row['campaignid']."] ".$row['campaignname']."</option>";
				else
					echo "<option value='".$row['campaignid']."'>[id".$row['campaignid']."] ".$row['campaignname']."</option>";
			}
			
			echo "</select>"."\n";
			echo "</td></form>"."\n";
			
			if (isset($GLOBALS['campaignid']) && $GLOBALS['campaignid'] != '')
			{
				echo "<form name='zonetypeselection' method='get' action='zone-include.php'>"."\n";
				echo "<input type='hidden' name='zoneid' value='".$GLOBALS['zoneid']."'>"."\n";
				echo "<input type='hidden' name='affiliateid' value='".$GLOBALS['affiliateid']."'>"."\n";
				echo "<input type='hidden' name='clientid' value='".$GLOBALS['clientid']."'>"."\n";
				echo "<input type='hidden' name='campaignid' value='".$GLOBALS['campaignid']."'>"."\n";
				echo "<input type='hidden' name='zonetype' value='".phpAds_ZoneBanners."'>"."\n";
				echo "<input type='hidden' name='action' value='toggle'>"."\n";
				echo "<td>&nbsp;&nbsp;<img src='images/caret-r.gif' align='absmiddle'>&nbsp;&nbsp;"."\n";
				echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>&nbsp;"."\n";
				echo "<select name='bannerid' tabindex='".($tabindex++)."'>"."\n";
				
				// Fetch all banners which can be linked
				$query =
					"SELECT".
					" bannerid".
					",campaignid".
					",alt".
					",description".
					",active".
					",storagetype".
					" FROM ".$phpAds_config['tbl_banners'].
					" WHERE campaignid=".$GLOBALS['campaignid']
				;
				
				if ($delivery != phpAds_ZoneText)
				{
					if ($width != -1 && $height != -1)
						$query .= " AND width = $width AND height = $height";
					elseif ($width != -1)
						$query .= " AND width = $width";
					elseif ($height != -1)
						$query .= " AND height = $height";
				}
				else
				{
					$query .= " WHERE contenttype = 'txt'";
				}
				
				$query .= " ORDER BY bannerid";
				
				$res = phpAds_dbQuery($query) or phpAds_sqlDie();
				
				while ($row = phpAds_dbFetchArray($res))
				{
					if (!isset($bannerids[$row['bannerid']]) || $bannerids[$row['bannerid']] != true)
					{
						$name = $strUntitled;
						if (isset($row['alt']) && $row['alt'] != '') $name = $row['alt'];
						if (isset($row['description']) && $row['description'] != '') $name = $row['description'];
						
						echo "<option value='".$row['bannerid']."'>[id".$row['bannerid']."] ".$name."</option>"."\n";
					}
				}
				
				echo "</select>"."\n";
				echo "&nbsp;<input type='image' src='images/".$GLOBALS['phpAds_TextDirection']."/go_blue.gif' border='0' tabindex='".($tabindex++)."'>"."\n";
				echo "</td></form>"."\n";
			}
		}
		
		echo "</tr></table>"."\n";
		echo "<br><br>"."\n";
	}
	
	// Header
	echo "<table width='100%' border='0' align='center' cellspacing='0' cellpadding='0'>"."\n";
	echo "<tr height='25'>"."\n";
	echo "<td height='25' width='40%'><b>&nbsp;&nbsp;$strName</b></td>"."\n";
	echo "<td height='25'><b>$strID&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b></td>"."\n";
	echo "<td height='25'>&nbsp;</td>"."\n";
	echo "</tr>"."\n";
	
	echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
	
	$i = 0;
	$checkedall = true;
	
	if (!$compact && phpAds_dbNumRows($res) == 0)
	{
		echo "<tr bgcolor='#F6F6F6'><td colspan='3' height='25'>&nbsp;&nbsp;".$strNoBannersToLink."</td></tr>"."\n";
	}
	else
	{
		for (reset($campaigns); $ckey = key($campaigns); next($campaigns))
		{
			$campaign = $campaigns[$ckey];
			
			if (isset($campaign['banners']) && is_array($campaign['banners']) && count($campaign['banners']))
			{
				$banners = $campaign['banners'];
				
				$activebanners = 0;
				for (reset($banners); $bkey = key($banners); next($banners))
				{
					$banner = $banners[$bkey];
					
					$linkedrow = (isset($bannerids[$banner['bannerid']]) && $bannerids[$banner['bannerid']] == true);
					
					if ($compact)
						$showrow = $linkedrow;
					else
						$showrow = ($hideinactive == false || $hideinactive == true && ($banner['active'] == 't' && $campaign['active'] == 't' || $linkedrow));
					
					if ($showrow) $activebanners++;
				}
				
				
				if ($showcampaigns && $activebanners)
				{
					if ($i > 0) echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>"."\n";
					echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">"."\n";
					
					// Begin row
					echo "<td height='25'>"."\n";
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"."\n";
					if (!$compact) echo "&nbsp;&nbsp;"."\n";
					
					// Banner icon
					if ($campaign['active'] == 't')
						echo "<img src='images/icon-campaign.gif' align='absmiddle'>&nbsp;"."\n";
					else
						echo "<img src='images/icon-campaign-d.gif' align='absmiddle'>&nbsp;"."\n";
					
					
					// Name
					if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
					{
						echo "<a href='campaign-edit.php?clientid=".$campaign['clientid']."&campaignid=".$campaign['campaignid']."'>"."\n";
						echo phpAds_breakString ($campaign['campaignname'], '60')."</a>"."\n";
					}
					else
						echo phpAds_breakString ($campaign['campaignname'], '60');
					
					echo "</td>"."\n";
					
					
					// ID
					echo "<td height='25'>".$campaign['campaignid']."</td>"."\n";
					echo "<td>&nbsp;</td></tr>"."\n";
				}
				
				for (reset($banners); $bkey = key($banners); next($banners))
				{
					$banner = $banners[$bkey];
					
					$linkedrow = (isset($bannerids[$banner['bannerid']]) && $bannerids[$banner['bannerid']] == true);
					
					if ($compact)
						$showrow = $linkedrow;
					else
						$showrow = ($hideinactive == false || $hideinactive == true && ($banner['active'] == 't' && $campaign['active'] == 't' || $linkedrow));
					
					if (!$compact && !$showrow) $inactivehidden++;
					
					if ($showrow)
					{
						$name = $strUntitled;
						if (isset($banner['alt']) && $banner['alt'] != '') $name = $banner['alt'];
						if (isset($banner['description']) && $banner['description'] != '') $name = $banner['description'];
						
						$name = phpAds_breakString ($name, '60');
						
						
						if (!$showcampaigns)
						{
							if ($i > 0) echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>"."\n";
						}
						else
						{
							echo "<tr height='1'>"."\n";
							echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>"."\n";
							echo "<td colspan='3' bgcolor='#888888'><img src='images/break-el.gif' height='1' width='100%'></td>"."\n";
							echo "</tr>"."\n";
						}
						
						
						echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">"."\n";
						
						// Begin row
						echo "<td height='25'>"."\n";
						echo "&nbsp;&nbsp;"."\n";
						
						// Show checkbox
						if (!$compact)
						{
							if (isset($bannerids[$banner['bannerid']]) && $bannerids[$banner['bannerid']] == true)
								echo "<input type='checkbox' name='bannerid[]' value='".$banner['bannerid']."' checked onclick='reviewall();' tabindex='".($tabindex++)."'>"."\n"; 
							else
							{
								echo "<input type='checkbox' name='bannerid[]' value='".$banner['bannerid']."' onclick='reviewall();' tabindex='".($tabindex++)."'>"."\n";
								$checkedall = false;
							}
						}
						else
						{
							echo "<a href='zone-include.php?affiliateid=".$GLOBALS['affiliateid']."&zoneid=".$GLOBALS['zoneid']."&bannerid=".$banner['bannerid']."&zonetype=".phpAds_ZoneBanners."&action=toggle'>"."\n";
							echo "<img src='images/caret-l.gif' border='0' align='absmiddle'></a>"."\n";
						}
						
						// Space
						echo "&nbsp;&nbsp;"."\n";
						if ($showcampaigns) echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"."\n";
						
						// Banner icon
						if ($campaign['active'] == 't' && $banner['active'] == 't')
						{
							if ($banner['storagetype'] == 'html')
								echo "<img src='images/icon-banner-html.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'url')
								echo "<img src='images/icon-banner-url.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'txt')
								echo "<img src='images/icon-banner-text.gif' align='absmiddle'>&nbsp;"."\n";
							else
								echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>&nbsp;"."\n";
						}
						else
						{
							if ($banner['storagetype'] == 'html')
								echo "<img src='images/icon-banner-html-d.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'url')
								echo "<img src='images/icon-banner-url-d.gif' align='absmiddle'>&nbsp;"."\n";
							elseif ($banner['storagetype'] == 'txt')
								echo "<img src='images/icon-banner-text-d.gif' align='absmiddle'>&nbsp;"."\n";
							else
								echo "<img src='images/icon-banner-stored-d.gif' align='absmiddle'>&nbsp;"."\n";
						}
						
						// Name
						if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
						{
							echo "<a href='banner-edit.php?clientid=".$campaign['clientid']."&campaignid=".$campaign['campaignid']."&bannerid=".$banner['bannerid']."'>"."\n";
							echo $name."</a></td>"."\n";
						}
						else
							echo $name;
						
						// ID
						echo "<td height='25'>".$banner['bannerid']."</td>"."\n";
						
						// Show banner
						if ($banner['contenttype'] == 'txt')
						{
							$width	= 300;
							$height = 200;
						}
						else
						{
							$width  = $banner['width'] + 64;
							$height = $banner['bannertext'] ? $banner['height'] + 90 : $banner['height'] + 64;
						}
						
						echo "<td height='25' align='".$phpAds_TextAlignRight."'>"."\n";
						echo "<a href='banner-htmlpreview.php?bannerid=".$banner['bannerid']."' target='_new' ";
						echo "onClick=\"return openWindow('banner-htmlpreview.php?bannerid=".$banner['bannerid']."', '', 'status=no,scrollbars=no,resizable=no,width=".$width.",height=".$height."');\">"."\n";
						echo "<img src='images/icon-zoom.gif' align='absmiddle' border='0'>&nbsp;".$strShowBanner."</a>&nbsp;&nbsp;"."\n";
						echo "</td>"."\n";
						
						// End row
						echo "</tr>"."\n";
						
						
						if (!$showcampaigns) $i++;
					}
				}
				
				if ($showcampaigns && $activebanners) $i++;
			}
		}
	}
	
	if (!$compact)
	{
		echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>"."\n";
		echo "<tr ".($i%2==0?"bgcolor='#F6F6F6'":"")."><td height='25'>"."\n";
		echo "&nbsp;&nbsp;<input type='checkbox' name='checkall' value=''".($checkedall == true ? ' checked' : '')." onclick='toggleall();' tabindex='".($tabindex++)."'>"."\n";
		echo "&nbsp;&nbsp;<b>".$strCheckAllNone."</b>"."\n";
		echo "</td><td>&nbsp;</td><td>&nbsp;</td></tr>"."\n";
	}
	
	echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>"."\n";
	echo "<tr><td height='25' align='".$phpAds_TextAlignLeft."' nowrap>"."\n";
	
	if (!$compact)
	{
		if ($hideinactive == true)
		{
			echo "&nbsp;&nbsp;<img src='images/icon-activate.gif' align='absmiddle' border='0'>"."\n";
			echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneBanners."&hideinactive=0'>".$strShowAll."</a>"."\n";
			echo "&nbsp;&nbsp;|&nbsp;&nbsp;".$inactivehidden." ".$strInactiveBannersHidden;
		}
		else
		{
			echo "&nbsp;&nbsp;<img src='images/icon-hideinactivate.gif' align='absmiddle' border='0'>"."\n";
			echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneBanners."&hideinactive=1'>".$strHideInactiveBanners."</a>"."\n";
		}
	}
	
	echo "</td><td colspan='2' align='".$phpAds_TextAlignRight."' nowrap>"."\n";
	
	if ($showcampaigns == true)
	{
		echo "&nbsp;&nbsp;<img src='images/icon-campaign-d.gif' align='absmiddle' border='0'>"."\n";
		echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneBanners."&showcampaigns=0'>".$strHideParentCampaigns."</a>"."\n";
	}
	else
	{
		echo "&nbsp;&nbsp;<img src='images/icon-campaign.gif' align='absmiddle' border='0'>"."\n";
		echo "&nbsp;<a href='zone-include.php?affiliateid=".$affiliateid."&zoneid=".$zoneid."&zonetype=".phpAds_ZoneBanners."&showcampaigns=1'>".$strShowParentCampaigns."</a>"."\n";
	}
	
	echo "&nbsp;&nbsp;</td></tr>"."\n";
	
	
	echo "</table>"."\n";
	
	echo "<br><br>"."\n";
	echo "<br><br>"."\n";
	
	if (!$compact)
	{
		echo "<input type='submit' name='submit' value='$strSaveChanges' tabindex='".($tabindex++)."'>"."\n";
		echo "</form>"."\n";
	}
}



/*********************************************************/
/* Main code                                             */
/*********************************************************/

?>

<script language='Javascript'>
<!--
	function toggleall()
	{
		allchecked = false;
		
		for (var i=0; i<document.zonetypeselection.elements.length; i++)
		{
			if (document.zonetypeselection.elements[i].name == 'bannerid[]' ||
				document.zonetypeselection.elements[i].name == 'campaignid[]')
			{
				if (document.zonetypeselection.elements[i].checked == false)
				{
					allchecked = true;
				}
			}
		}
		
		for (var i=0; i<document.zonetypeselection.elements.length; i++)
		{
			if (document.zonetypeselection.elements[i].name == 'bannerid[]' ||
				document.zonetypeselection.elements[i].name == 'campaignid[]')
			{
				document.zonetypeselection.elements[i].checked = allchecked;
			}
		}
	}
	
	function reviewall()
	{
		allchecked = true;
		
		for (var i=0; i<document.zonetypeselection.elements.length; i++)
		{
			if (document.zonetypeselection.elements[i].name == 'bannerid[]' ||
				document.zonetypeselection.elements[i].name == 'campaignid[]')
			{
				if (document.zonetypeselection.elements[i].checked == false)
				{
					allchecked = false;
				}
			}
		}
		
				
		document.zonetypeselection.checkall.checked = allchecked;
	}	
//-->
</script>

<?php

if (isset($zoneid) && $zoneid != '')
{
	$res = phpAds_dbQuery(
		"SELECT *".
		" FROM ".$phpAds_config['tbl_zones'].
		" WHERE zoneid=".$zoneid
	) or phpAds_sqlDie();
	
	if (phpAds_dbNumRows($res))
	{
		$zone = phpAds_dbFetchArray($res);
	}
}

// Set the default zonetype
if (!isset($zonetype) || $zonetype == '')
	$zonetype = $zone['zonetype'];

$tabindex = 1;


echo "<form name='zonetypes' method='post' action='zone-include.php'>"."\n";
echo "<input type='hidden' name='zoneid' value='".$zoneid."'>"."\n";
echo "<input type='hidden' name='affiliateid' value='".$affiliateid."'>"."\n";

echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>"."\n";
echo "<tr><td height='25' colspan='3'><b>".$strSelectZoneType."</b></td></tr>"."\n";
echo "<tr><td height='25'>"."\n";

echo "<select name='zonetype' onChange='this.form.submit();' accesskey='".$keyList."' tabindex='".($tabindex++)."'>"."\n";
	echo "<option value='".phpAds_ZoneCampaign."'".(($zonetype == phpAds_ZoneCampaign) ? " selected" : "").">".$strCampaignSelection."</option>"."\n";
	echo "<option value='".phpAds_ZoneBanners."'".(($zonetype == phpAds_ZoneBanners) ? " selected" : "").">".$strBannerSelection."</option>"."\n";
	echo "<option value='".phpAds_ZoneRaw."'".(($zonetype == phpAds_ZoneRaw) ? " selected" : "").">".$strRawQueryString."</option>"."\n";
echo "</select>"."\n";
echo "&nbsp;<input type='image' src='images/".$phpAds_TextDirection."/go_blue.gif' border='0'>"."\n";

echo "</td></tr>"."\n";
echo "</table>"."\n";
phpAds_ShowBreak();
echo "</form>"."\n";
echo "<br>"."\n";



if ($zonetype == phpAds_ZoneCampaign)
{
	phpAds_showZoneCampaign($zone["width"], $zone["height"], $zone["what"], $zone['delivery']);
}

if ($zonetype == phpAds_ZoneBanners)
{
	phpAds_showZoneBanners($zone["width"], $zone["height"], $zone["what"], $zone["zonetype"], $zone['delivery']);
}

if ($zonetype == phpAds_ZoneRaw)
{
	echo "<form name='zonetypeselection' method='post' action='zone-include.php'>"."\n";
	echo "<input type='hidden' name='zoneid' value='".$zoneid."'>"."\n";
	echo "<input type='hidden' name='affiliateid' value='".$affiliateid."'>"."\n";
	echo "<input type='hidden' name='zonetype' value='$zonetype'>"."\n";
	echo "<input type='hidden' name='action' value='set'>"."\n";
	
	echo "<textarea cols='50' rows='16' name='what' style='width:600px;' tabindex='".($tabindex++)."'>".(isset($zone['what']) ? $zone['what'] : '')."</textarea>"."\n";
	
	echo "<br><br>"."\n";
	echo "<br><br>"."\n";
	
	echo "<input type='submit' name='submit' value='$strSaveChanges' tabindex='".($tabindex++)."'>"."\n";
	echo "</form>"."\n";
}



/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['zone-include.php']['hideinactive'] = $hideinactive;
$Session['prefs']['zone-include.php']['showbanners'] = $showbanners;
$Session['prefs']['zone-include.php']['showcampaigns'] = $showcampaigns;

phpAds_SessionDataStore();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>

