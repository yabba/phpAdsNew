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
phpAds_registerGlobal ('expand', 'collapse', 'hideinactive', 'listorder', 'orderdirection');


// Security check
phpAds_checkAccess(phpAds_Admin);


/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PrepareHelp();
phpAds_PageHeader("5.5");
phpAds_ShowSections(array("5.1", "5.3", "5.4", "5.2", "5.5"));


/*********************************************************/
/* Get preferences                                       */
/*********************************************************/

if (!isset($hideinactive))
{
	if (isset($Session['prefs']['agency-index.php']['hideinactive']))
		$hideinactive = $Session['prefs']['agency-index.php']['hideinactive'];
	else
		$hideinactive = ($phpAds_config['gui_hide_inactive'] == 't');
}

if (!isset($listorder))
{
	if (isset($Session['prefs']['agency-index.php']['listorder']))
		$listorder = $Session['prefs']['agency-index.php']['listorder'];
	else
		$listorder = '';
}

if (!isset($orderdirection))
{
	if (isset($Session['prefs']['agency-index.php']['orderdirection']))
		$orderdirection = $Session['prefs']['agency-index.php']['orderdirection'];
	else
		$orderdirection = '';
}

if (isset($Session['prefs']['agency-index.php']['nodes']))
	$node_array = explode (",", $Session['prefs']['agency-index.php']['nodes']);
else
	$node_array = array();



/*********************************************************/
/* Main code                                             */
/*********************************************************/

// Get agencys & campaign and build the tree
if (phpAds_isUser(phpAds_Admin))
{
	$res_agencies = phpAds_dbQuery(
		"SELECT agencyid, name".
		" FROM ".$phpAds_config['tbl_agency']
	) or phpAds_sqlDie();
	
}

while ($row_agencies = phpAds_dbFetchArray($res_agencies))
{
	$agencies[$row_agencies['agencyid']] 					= $row_agencies;
	$agencies[$row_agencies['agencyid']]['expand'] 			= 0;
	$agencies[$row_agencies['agencyid']]['count'] 			= 0;
	$agencies[$row_agencies['agencyid']]['hideinactive'] 	= 0;
}



// using same icons and images for agencies as we do for advertisers...
echo "\t\t\t\t<img src='images/icon-advertiser-new.gif' border='0' align='absmiddle'>&nbsp;\n";
echo "\t\t\t\t<a href='agency-edit.php' accesskey='".$keyAddNew."'>".$strAddAgency_Key."</a>&nbsp;&nbsp;\n";
phpAds_ShowBreak();



echo "\t\t\t\t<br><br>\n";
echo "\t\t\t\t<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";

echo "\t\t\t\t<tr height='25'>\n";
echo "\t\t\t\t\t<td height='25' width='40%'>\n";
echo "\t\t\t\t\t\t<b>&nbsp;&nbsp;<a href='agency-index.php?listorder=name'>".$GLOBALS['strName']."</a>";

if (($listorder == "name") || ($listorder == ""))
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo " <a href='agency-index.php?orderdirection=up'>";
		echo "<img src='images/caret-ds.gif' border='0' alt='' title=''>";
	}
	else
	{
		echo " <a href='agency-index.php?orderdirection=down'>";
		echo "<img src='images/caret-u.gif' border='0' alt='' title=''>";
	}
	echo "</a>";
}

echo "</b>\n";
echo "\t\t\t\t\t</td>\n";
echo "\t\t\t\t\t<td height='25'>\n";
echo "\t\t\t\t\t\t<b><a href='agency-index.php?listorder=id'>".$GLOBALS['strID']."</a>";

if ($listorder == "id")
{
	if  (($orderdirection == "") || ($orderdirection == "down"))
	{
		echo " <a href='agency-index.php?orderdirection=up'>";
		echo "<img src='images/caret-ds.gif' border='0' alt='' title=''>";
	}
	else
	{
		echo " <a href='agency-index.php?orderdirection=down'>";
		echo "<img src='images/caret-u.gif' border='0' alt='' title=''>";
	}
	echo "</a>";
}

echo "</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "\t\t\t\t\t</td>\n";
echo "\t\t\t\t\t<td height='25'>&nbsp;</td>\n";
echo "\t\t\t\t\t<td height='25'>&nbsp;</td>\n";
echo "\t\t\t\t\t<td height='25'>&nbsp;</td>\n";
echo "\t\t\t\t</tr>\n";

echo "\t\t\t\t<tr height='1'>\n";
echo "\t\t\t\t\t<td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>\n";
echo "\t\t\t\t</tr>\n";


if (!isset($agencies) || !is_array($agencies) || count($agencies) == 0)
{
	echo "\t\t\t\t<tr height='25' bgcolor='#F6F6F6'>\n";
	echo "\t\t\t\t\t<td height='25' colspan='5'>&nbsp;&nbsp;".$strNoAgencies."</td>\n";
	echo "\t\t\t\t</tr>\n";
	
	echo "\t\t\t\t<tr>\n";
	echo "\t\t\t\t\t<td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>\n";
	echo "\t\t\t\t<tr>\n";
}
else
{
	$i=0;
	for (reset($agencies);$key=key($agencies);next($agencies))
	{
		$agency = $agencies[$key];
		
		echo "\t\t\t\t<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">\n";
		
		// Icon & name
		echo "\t\t\t\t\t<td height='25'>\n";
		echo "\t\t\t\t\t\t<img src='images/spacer.gif' height='16' width='16' align='absmiddle'>\n";
			
		echo "\t\t\t\t\t\t<img src='images/icon-advertiser.gif' align='absmiddle'>\n";
		echo "\t\t\t\t\t\t<a href='agency-edit.php?agencyid=".$agency['agencyid']."'>".$agency['name']."</a>\n";
		echo "\t\t\t\t\t</td>\n";
		
		// ID
		echo "\t\t\t\t\t<td height='25'>".$agency['agencyid']."</td>\n";
		
		echo "\t\t\t\t\t<td height='25'>&nbsp;</td>\n";
		echo "\t\t\t\t\t<td height='25'>&nbsp;</td>\n";
		
		// Delete
		echo "\t\t\t\t\t<td height='25'>";
		echo "<a href='agency-delete.php?agencyid=".$agency['agencyid']."&returnurl=agency-index.php'".phpAds_DelConfirm($strConfirmDeleteAgency)."><img src='images/icon-recycle.gif' border='0' align='absmiddle' alt='$strDelete'>&nbsp;$strDelete</a>&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "</td>\n";

		echo "\t\t\t\t</tr>\n";
		
		echo "\t\t\t\t<tr height='1'>\n";
		echo "\t\t\t\t\t<td colspan='5' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>\n";
		echo "\t\t\t\t</tr>\n";
		$i++;
	}
}

echo "\t\t\t\t<tr>\n";
echo "\t\t\t\t\t<td height='25' colspan='3' align='".$phpAds_TextAlignLeft."' nowrap>";

if ($hideinactive == true)
{
	echo "&nbsp;&nbsp;<img src='images/icon-activate.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='agency-index.php?hideinactive=0'>".$strShowAll."</a>";
	echo "&nbsp;&nbsp;|&nbsp;&nbsp;".$agencieshidden." ".$strInactiveAgenciesHidden;
}
else
{
	echo "&nbsp;&nbsp;<img src='images/icon-hideinactivate.gif' align='absmiddle' border='0'>";
	echo "&nbsp;<a href='agency-index.php?hideinactive=1'>".$strHideInactiveAgencies."</a>";
}

echo "</td>\n";

echo "<td></td>\n";
echo "\t\t\t\t</tr>\n";

echo "\t\t\t\t</table>\n";


// total number of agencies
$res_agencies 		  = phpAds_dbQuery("SELECT count(*) AS count FROM ".$phpAds_config['tbl_agency']) or phpAds_sqlDie();


echo "\t\t\t\t<br><br><br><br>\n";
echo "\t\t\t\t<table width='100%' border='0' align='center' cellspacing='0' cellpadding='0'>\n";
echo "\t\t\t\t<tr>\n";
echo "\t\t\t\t\t<td height='25' colspan='3'>&nbsp;&nbsp;<b>".$strOverall."</b></td>\n";
echo "\t\t\t\t</tr>\n";
echo "\t\t\t\t<tr height='1'>\n";
echo "\t\t\t\t\t<td colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>\n";
echo "\t\t\t\t</tr>\n";

echo "\t\t\t\t<tr>\n";
echo "\t\t\t\t\t<td height='25'>&nbsp;&nbsp;".$strTotalAgencies.": <b>".phpAds_dbResult($res_agencies, 0, "count")."</b></td>\n";
echo "\t\t\t\t\t<td height='25' colspan='2'></td>\n";
echo "\t\t\t\t</tr>\n";

echo "\t\t\t\t<tr height='1'>\n";
echo "\t\t\t\t\t<td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>\n";
echo "\t\t\t\t</tr>\n";

echo "\t\t\t\t</table>\n";
echo "\t\t\t\t<br><br>\n";



/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['agency-index.php']['hideinactive'] 	= $hideinactive;
$Session['prefs']['agency-index.php']['listorder'] 		= $listorder;
$Session['prefs']['agency-index.php']['orderdirection'] = $orderdirection;
$Session['prefs']['agency-index.php']['nodes'] 			= implode (",", $node_array);

phpAds_SessionDataStore();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>