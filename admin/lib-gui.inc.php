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



// Define defaults
$phpAds_Message     = '';
$phpAds_NavID	    = '';
$phpAds_GUIDone     = false;
$phpAds_showHelp    = false;
$phpAds_helpDefault = '';
$phpAds_context		= array();
$phpAds_shortcuts	= array();

define ("phpAds_Login", 0);
define ("phpAds_Error", -1);



/*********************************************************/
/* Add breadcrumb context to left menubar                */
/*********************************************************/

function phpAds_PageContext ($name, $link, $selected)
{
	global $phpAds_context;
	
	$phpAds_context[] = array (
		'name' => $name,
		'link' => $link,
		'selected' => $selected
	);
}



/*********************************************************/
/* Add shortcuts to left menubar                         */
/*********************************************************/

function phpAds_PageShortcut ($name, $link, $icon)
{
	global $phpAds_shortcuts;
	
	$phpAds_shortcuts[] = array (
		'name' => $name,
		'link' => $link,
		'icon' => $icon
	);
}



/*********************************************************/
/* Show page header                                      */
/*********************************************************/

function phpAds_PageHeader($ID, $extra="")
{
	global $phpAds_config;
	global $phpAds_version_readable, $phpAds_productname;
	global $phpAds_TextDirection, $phpAds_TextAlignRight, $phpAds_TextAlignLeft;
	
	global $phpAds_Message, $phpAds_GUIDone, $phpAds_NavID;
	global $phpAds_context, $phpAds_shortcuts;
	global $phpAds_nav, $pages, $phpAds_showHelp;
	
	global $phpAds_CharSet;
	global $strLogout, $strNavigation, $strShortcuts;
	global $strAuthentification, $strSearch, $strHelp;
	
	global $keyHome, $keyUp, $keyNextItem, $keyPreviousItem, $keySearch;
	
	
	$phpAds_GUIDone = true;
	$phpAds_NavID   = $ID;
	
	$mozbar = '';
	
	// Travel navigation
	if ($ID != phpAds_Login && $ID != phpAds_Error)
	{
		// Prepare Navigation
		if (phpAds_isUser(phpAds_Admin))
			$pages	= $phpAds_nav['admin'];
		elseif (phpAds_isUser(phpAds_Agency))
			$pages	= $phpAds_nav['agency'];
		elseif (phpAds_isUser(phpAds_Client))
			$pages  = $phpAds_nav['client'];
		else
			$pages  = $phpAds_nav['affiliate'];
		
		// Build sidebar
		$sections = explode(".", $ID);
		$sectionID = "";
		
		$sidebar  = "\t\t\t\t<table width='160' cellpadding='0' cellspacing='0' border='0'>\n";
		$sidebar .= "\t\t\t\t<tr>\n";
		$sidebar .= "\t\t\t\t\t<td colspan='2' class='nav'><b>$strNavigation</b></td>\n";
		$sidebar .= "\t\t\t\t</tr>\n";
		$sidebar .= "\t\t\t\t<tr>\n";
		$sidebar .= "\t\t\t\t\t<td colspan='2'><img src='images/break.gif' height='1' width='160' vspace='4'></td>\n";
		$sidebar .= "\t\t\t\t</tr>\n";
		
		for ($i=0; $i<count($sections)-1; $i++)
		{
			$sectionID .= $sections[$i];
			list($filename, $title) = each($pages["$sectionID"]);
			$sectionID .= ".";
			
			if ($i==0)
			{
				$sidebar .= "\t\t\t\t<tr>\n";
				$sidebar .= "\t\t\t\t\t<td width='20' valign='top'><img src='images/caret-t.gif' width='11' height='7'>&nbsp;</td>\n";
				$sidebar .= "\t\t\t\t\t<td width='140'><a href='$filename'>$title</a></td>\n";
				$sidebar .= "\t\t\t\t</tr>\n";
				$sidebar .= "\t\t\t\t<tr>\n";
				$sidebar .= "\t\t\t\t\t<td colspan='2'><img src='images/break.gif' height='1' width='160' vspace='4'></td>\n";
				$sidebar .= "\t\t\t\t</tr>\n";
				
				$mozbar  .= "\t\t<link REL='top' HREF='$filename' TITLE='$title'>\n";
			}
			else
			{
				$sidebar .= "\t\t\t\t<tr>\n";
				$sidebar .= "\t\t\t\t\t<td width='20' valign='top'><img src='images/caret-u.gif' width='11' height='7'>&nbsp;</td>\n";
				$sidebar .= "\t\t\t\t\t<td width='140'><a href='$filename'".($i == count($sections) - 2 ? " accesskey='".$keyUp."'" : "").">$title</a></td>\n";
				$sidebar .= "\t\t\t\t</tr>\n";
			}
			
			if ($i == count($sections) - 2)
				$mozbar  .= "\t\t<link REL='up' HREF='$filename' TITLE='$title'>\n";
		}
		
		if (isset($pages["$ID"]) && is_array($pages["$ID"]))
		{
			list($filename, $title) = each($pages["$ID"]);
			$sidebar .= "\t\t\t\t<tr>\n";
			$sidebar .= "\t\t\t\t\t<td width='20'valign='top'><img src='images/caret-u.gif' width='11' height='7'>&nbsp;</td>\n";
			$sidebar .= "\t\t\t\t\t<td width='140' class='nav'>$title</td>\n";
			$sidebar .= "\t\t\t\t</tr>\n";
			$sidebar .= "\t\t\t\t<tr>\n";
			$sidebar .= "\t\t\t\t\t<td colspan='2'><img src='images/break.gif' height='1' width='160' vspace='4'></td>\n";
			$sidebar .= "\t\t\t\t</tr>";
			
			$pagetitle  = isset($phpAds_config['name']) && $phpAds_config['name'] != '' ? $phpAds_config['name'] : $phpAds_productname;
			$pagetitle .= ' - '.$title;
		}
		else
		{
			$pagetitle = isset($phpAds_config['name']) && $phpAds_config['name'] != '' ? $phpAds_config['name'] : $phpAds_productname;
		}
		
		
		// Build Context
		if (count($phpAds_context))
		{
			$sidebar .= "\t\t\t\t<tr>\n";
			$sidebar .= "\t\t\t\t\t<td width='20'>&nbsp;</td>\n";
			$sidebar .= "\t\t\t\t\t<td width='140'>\n";
			$sidebar .= "\t\t\t\t\t\t<table width='140' cellpadding='0' cellspacing='0' border='0'>\n";
			
			$selectedcontext = '';
			
			for ($ci=0; $ci < count($phpAds_context); $ci++)
				if ($phpAds_context[$ci]['selected'])
					$selectedcontext = $ci;
			
			for ($ci=0; $ci < count($phpAds_context); $ci++)
			{
				$ac = '';
				if ($ci == $selectedcontext - 1) $ac = $keyPreviousItem;
				if ($ci == $selectedcontext + 1) $ac = $keyNextItem;
				
				if ($phpAds_context[$ci]['selected'])
				{
					$sidebar .= "\t\t\t\t\t\t<tr>\n";
					$sidebar .= "\t\t\t\t\t\t\t<td width='20' valign='top'><img src='images/box-1.gif'>&nbsp;</td>\n";
				}
				else
				{
					$sidebar .= "\t\t\t\t\t\t<tr>\n";
					$sidebar .= "\t\t\t\t\t\t\t<td width='20' valign='top'><img src='images/box-0.gif'>&nbsp;</td>\n";
				}
								
				$sidebar .= "\t\t\t\t\t\t\t<td width='120'><a href='".$phpAds_context[$ci]['link']."'".($ac != '' ? " accesskey='".$ac."'" : "").">";
				$sidebar .= $phpAds_context[$ci]['name']."</a></td>\n";
				$sidebar .= "\t\t\t\t\t\t</tr>\n";
			}
			
			$sidebar .= "\t\t\t\t\t\t</table>\n";
			$sidebar .= "\t\t\t\t\t</td>\n";
			$sidebar .= "\t\t\t\t</tr>\n";
			$sidebar .= "\t\t\t\t<tr>\n";
			$sidebar .= "\t\t\t\t\t<td colspan='2'><img src='images/break.gif' height='1' width='160' vspace='4'></td>\n";
			$sidebar .= "\t\t\t\t</tr>\n";
		}
		
		$sidebar .= "\t\t\t\t</table>\n";
		
		
		// Include custom HTML for the sidebar
		if ($extra != '') $sidebar .= $extra;
		
		
		// Include shortcuts
		if (count($phpAds_shortcuts))
		{
			$sidebar .= "\t\t\t\t<br><br><br>\n";
			$sidebar .= "\t\t\t\t<table width='160' cellpadding='0' cellspacing='0' border='0'>\n";
			$sidebar .= "\t\t\t\t<tr>\n";
			$sidebar .= "\t\t\t\t\t<td colspan='2' class='nav'><b>$strShortcuts</b></td>\n";
			$sidebar .= "\t\t\t\t</tr>\n";
			
			for ($si=0; $si<count($phpAds_shortcuts); $si++)
			{
				$sidebar .= "\t\t\t\t<tr>\n";
				$sidebar .= "\t\t\t\t\t<td colspan='2'><img src='images/break.gif' height='1' width='160' vspace='4'></td>\n";
				$sidebar .= "\t\t\t\t</tr>\n";
				$sidebar .= "\t\t\t\t<tr>\n";
				$sidebar .= "\t\t\t\t\t<td width='20' valign='top'><img src='".$phpAds_shortcuts[$si]['icon']."' align='absmiddle'>&nbsp;</td>\n";
				$sidebar .= "\t\t\t\t\t<td width='140'><a href='".$phpAds_shortcuts[$si]['link']."'>".$phpAds_shortcuts[$si]['name']."</a></td>\n";
				$sidebar .= "\t\t\t\t</tr>\n";
				
				$mozbar  .= "\t\t<link REL='bookmark' HREF='".$phpAds_shortcuts[$si]['link']."' TITLE='".$phpAds_shortcuts[$si]['name']."'>\n";
			}
			
			$sidebar .= "\t\t\t\t<tr>\n";
			$sidebar .= "\t\t\t\t\t<td colspan='2'><img src='images/break.gif' height='1' width='160' vspace='4'></td>\n";
			$sidebar .= "\t\t\t\t</tr>\n";
			$sidebar .= "\t\t\t\t</table>\n";
		}
		
		
		// Build Tabbar
		$currentsection = $sections[0];
		$tabbar = '';
		
		
		// Prepare Navigation
		if (phpAds_isUser(phpAds_Admin))
			$pages	= $phpAds_nav['admin'];
		elseif (phpAds_isUser(phpAds_Agency))
			$pages  = $phpAds_nav['agency'];
		elseif (phpAds_isUser(phpAds_Client))
			$pages  = $phpAds_nav['client'];
		elseif (phpAds_isUser(phpAds_Affiliate))
			$pages  = $phpAds_nav['affiliate'];
		else
			$pages  = array();
		
		
		$i = 0;
		$lastselected = false;
		
		for (reset($pages);$key=key($pages);next($pages))
		{
			if (strpos($key, ".") == 0)
			{
				list($filename, $title) = each($pages[$key]);
				
				
				if ($i > 0)
				{
					if ($lastselected)
						$tabbar .= "\t\t\t\t\t<td><img src='images/".$phpAds_TextDirection."/tab-d.gif' width='10' height='24'></td>\n";
					else
						$tabbar .= "\t\t\t\t\t<td><img src='images/".$phpAds_TextDirection."/tab-dd.gif' width='10' height='24'></td>\n";
				}
				
				if ($key == $currentsection)
				{
					$tabbar .= "\t\t\t\t\t<td bgcolor='#FFFFFF' valign='middle' nowrap>&nbsp;&nbsp;<a class='tab-s' href='$filename' accesskey='".$keyHome."'>$title</a></td>\n";
					$lastselected = true;
				}
				else
				{
					$tabbar .= "\t\t\t\t\t<td bgcolor='#0066CC' valign='middle' nowrap>&nbsp;&nbsp;<a class='tab-u' href='$filename'>$title</a></td>\n";
					$lastselected = false;
				}
			}
			
			$i++;
		}
		
		if ($lastselected)
			$tabbar .= "\t\t\t\t\t<td><img src='images/".$phpAds_TextDirection."/tab-ew.gif' width='10' height='24'></td>\n";
		else
			$tabbar .= "\t\t\t\t\t<td><img src='images/".$phpAds_TextDirection."/tab-eb.gif' width='10' height='24'></td>\n";
		
		
		
		if (phpAds_isLoggedIn() && ( phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency) ) && !defined('phpAds_installing'))
		{
			$searchbar  = "\t\t<table cellpadding='0' cellspacing='0' border='0' bgcolor='#0066CC' height='24'>\n";
			$searchbar .= "\t\t<form name='search' action='admin-search.php' target='SearchWindow' onSubmit=\"search_window(document.search.keyword.value,'".$phpAds_config['url_prefix']."/admin/admin-search.php'); return false;\">\n";
			$searchbar .= "\t\t<tr height='24'>\n";
			$searchbar .= "\t\t\t<td height='24'><img src='images/".$phpAds_TextDirection."/tab-sb.gif' height='24' width='10'></td>\n";
			$searchbar .= "\t\t\t<td class='tab-u'>".$strSearch.":</td>\n";
			$searchbar .= "\t\t\t<td>&nbsp;&nbsp;<input type='text' name='keyword' size='15' class='search' accesskey='".$keySearch."'>&nbsp;&nbsp;</td>\n";
			$searchbar .= "\t\t\t<td><a href=\"javascript:search_window(document.search.keyword.value,'".$phpAds_config['url_prefix']."/admin/admin-search.php');\"><img src='images/".$phpAds_TextDirection."/go.gif' border='0'></a></td>\n";
			$searchbar .= "\t\t\t<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
			$searchbar .= "\t\t</tr>\n";
			$searchbar .= "\t\t</form>\n";
			$searchbar .= "\t\t</table>\n";
		}
		else
			$searchbar = "\t\t&nbsp;\n";
	}
	else
	{
		$sidebar   = "\t\t\t\t&nbsp;\n";
		$searchbar = "\t\t&nbsp;\n";
		$pagetitle = isset($phpAds_config['name']) && $phpAds_config['name'] != '' ? $phpAds_config['name'] : $phpAds_productname;
		
		if ($ID == phpAds_Login)
			$tabbar    = "\t\t\t\t\t<td bgcolor='#FFFFFF' valign='middle' nowrap>&nbsp;&nbsp;<a class='tab-s' href='index.php'>$strAuthentification</a></td>\n";
		
		if ($ID == phpAds_Error)
			$tabbar    = "\t\t\t\t\t<td bgcolor='#FFFFFF' valign='middle' nowrap>&nbsp;&nbsp;<a class='tab-s' href='index.php'>Error</a></td>\n";
		
		$tabbar   .= "\t\t\t\t\t<td><img src='images/".$phpAds_TextDirection."/tab-ew.gif' width='10' height='24'></td>\n";
	}
	
	
	// Use gzip content compression
	if (isset($phpAds_config['content_gzip_compression']) && $phpAds_config['content_gzip_compression'])
		ob_start("ob_gzhandler");
	
	// Send header with charset info
	header ("Content-Type: text/html".(isset($phpAds_CharSet) && $phpAds_CharSet != "" ? "; charset=".$phpAds_CharSet : ""));
	
	
	// Head
	echo "<html".($phpAds_TextDirection != 'ltr' ? " dir='".$phpAds_TextDirection."'" : '').">\n";
	echo "\t<head>\n";
	echo "\t\t<title>".$pagetitle."</title>\n";
	echo "\t\t<meta name='generator' content='".$phpAds_productname." ".$phpAds_version_readable." - http://www.phpadsnew.com'>\n";
	echo "\t\t<meta name='robots' content='noindex, nofollow'>\n\n";
	echo "\t\t<link rel='stylesheet' href='images/".$phpAds_TextDirection."/interface.css'>\n";
	echo "\t\t<script language='JavaScript' src='js-gui.js'></script>\n";
	if (isset($phpAds_config['language'])) echo "\t\t<script language='JavaScript' src='js-form.php?language=".$phpAds_config['language']."'></script>\n";
	if ($phpAds_showHelp) echo "\t\t<script language='JavaScript' src='js-help.js'></script>\n";
	
	// Show Moz site bar
	echo $mozbar;
	echo "\t</head>\n\n\n";
	
	echo "<body bgcolor='#FFFFFF' background='images/".$phpAds_TextDirection."/background.gif' text='#000000' leftmargin='0' ";
	echo "topmargin='0' marginwidth='0' marginheight='0' onLoad='initAccessKey();'".($phpAds_showHelp ? " onResize='resizeHelp();' onScroll='resizeHelp();'" : '').">\n";
	
	// Header
	if (isset($phpAds_config['my_header']) && $phpAds_config['my_header'] != '')
	{
		include ($phpAds_config['my_header']);
	}
	
	
	// Branding
 	echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
	echo "<tr>\n";
	
	if (isset($phpAds_config['name']) && $phpAds_config['name'] != '')
	{
		echo "\t<td height='48' bgcolor='#000063' valign='middle'>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;<img src='images/logo-s.gif' width='36' height='34' align='absmiddle'>";
		echo "<span class='phpAdsNew'>".$phpAds_config['name']."</span>";
	}
	else
	{
		echo "\t<td height='48' bgcolor='#000063' valign='bottom'>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;<img src='images/logo.gif' width='163' height='34'>";
	}
	
	echo "</td>\n";
	echo "\t<td bgcolor='#000063' valign='top' align='".$phpAds_TextAlignRight."'>\n";
	echo $searchbar;
	echo "\t</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	
	
	// Spacer
	echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
	echo "<tr>\n";
	echo "\t<td colspan='2' height='6' bgcolor='#000063'><img src='images/spacer.gif' height='1' width='1'></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	
	
	// Tabbar
	echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
	echo "<tr>\n";
	echo "\t<td height='24' width='181' bgcolor='#000063'>&nbsp;</td>\n";
	echo "\t<td height='24' bgcolor='#000063'>\n";
	echo "\t\t<table border='0' cellspacing='0' cellpadding='0' width='100%'>\n";
	echo "\t\t<tr>\n";
	echo "\t\t\t<td>\n";
	echo "\t\t\t\t<table border='0' cellspacing='0' cellpadding='0' width='1'>\n";
	echo "\t\t\t\t<tr>\n";
	echo $tabbar;
	echo "\t\t\t\t</tr>\n";
	echo "\t\t\t\t</table>\n";
	echo "\t\t\t</td>\n";
	echo "\t\t\t<td align='".$phpAds_TextAlignRight."' valign='middle' nowrap>\n";
	
	if ($ID != "" && phpAds_isLoggedIn() && !defined('phpAds_installing'))
	{
		if (phpAds_isUser(phpAds_Admin))
		{
			echo "\t\t\t\t<a class='tab-n' href='../misc/documentation/user-guide.pdf' target='_blank'";
			echo "onClick=\"openWindow('../misc/documentation/user-guide.pdf','',";
			echo "'status=yes,menubar=yes,scrollbars=yes,resizable=yes,width=700,height=500'); return false;\">$strHelp</a> \n";
			echo "\t\t\t\t<a href='../misc/documentation/user-guide.pdf' target='_blank'";
			echo "onClick=\"openWindow('../misc/documentation/user-guide.pdf','',";
			echo "'status=yes,menubar=yes,scrollbars=yes,resizable=yes,width=700,height=500'); return false;\">";
			echo "<img src='images/help.gif' width='16' height='16' align='absmiddle' border='0'></a>";
			echo "&nbsp;&nbsp;&nbsp;\n";
		}
		
		echo "\t\t\t\t<a class='tab-n' href='logout.php'>$strLogout</a> \n";
		echo "\t\t\t\t<a href='logout.php'><img src='images/logout.gif' width='16' height='16' align='absmiddle' border='0'></a>";
	}
	
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
	echo "\t\t\t</td>\n";
	echo "\t\t</tr>\n";
	echo "\t\t</table>\n";
	echo "\t</td>\n";
	echo "</tr>\n";
	echo "</table>\n\n";
	
	// Sidebar
	echo "<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
	echo "<tr>\n";
	echo "\t<td valign='top'>\n";
	echo "\t\t<table width='181' border='0' cellspacing='0' cellpadding='0'>\n";
	
	// Blue square
    echo "\t\t<tr valign='top'>\n";
    echo "\t\t\t<td colspan='2' width='20' height='48' bgcolor='#000063' valign='bottom'>&nbsp;</td>\n";
    echo "\t\t</tr>\n";
	
	// Gradient
    echo "\t\t<tr valign='top'>\n";
    echo "\t\t\t<td width='20' height='24'><img src='images/grad-1.gif' width='20' height='20'></td>\n";
	echo "\t\t\t<td height='24'><img src='images/grad-1.gif' width='160' height='20'></td>\n";
	echo "\t\t</tr>\n";
	
	// Navigation
	echo "\t\t<tr>\n";
	echo "\t\t\t<td width='20'>&nbsp;</td>\n";
    echo "\t\t\t<td class='nav'>\n";
    echo $sidebar;
    echo "\t\t\t</td>\n";
    echo "\t\t</tr>\n";
    echo "\t\t</table>\n";
    echo "\t</td>\n";
	
	
	// Main contents
	echo "\t<td valign='top' width='100%'>\n";
	echo "\t\t<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
    echo "\t\t<tr>\n";
    echo "\t\t\t<td width='40' height='20'>&nbsp;</td>\n";
    echo "\t\t\t<td height='20'>&nbsp;</td>\n";
    echo "\t\t</tr>\n";
    echo "\t\t<tr>\n";
    echo "\t\t\t<td width='20'>&nbsp;</td>\n";
    echo "\t\t\t<td>\n";
}



/*********************************************************/
/* Show page footer                                      */
/*********************************************************/

function phpAds_PageFooter()
{
	global $phpAds_config, $HTTP_SERVER_VARS;
	global $Session, $phpAds_showHelp, $phpAds_helpDefault, $strMaintenanceNotActive;
	global $phpAds_TextDirection, $phpAds_TextAlignLeft, $phpAds_TextAlignRight;
	
	echo "\t\t\t</td>\n";
	echo "\t\t\t<td width='40'>&nbsp;</td>\n";
	echo "\t\t</tr>\n";
	
	// Spacer
	echo "\t\t<tr>\n";
	echo "\t\t\t<td width='40' height='20'>&nbsp;</td>\n";
	echo "\t\t\t<td height='20'>&nbsp;</td>\n";
	echo "\t\t</tr>\n";
	
	// Footer
	if (isset($phpAds_config['my_footer']) && $phpAds_config['my_footer'] != '')
	{
		echo "\t\t<tr>\n";
		echo "\t\t\t<td width='40' height='20'>&nbsp;</td>\n";
		echo "\t\t\t<td height='20'>";
		include ($phpAds_config['my_footer']);
		echo "</td>\n";
		echo "\t\t</tr>\n";
	}
	
	echo "\t\t</table>\n";
	echo "\t</td>\n";
	echo "</tr>\n";
	echo "</table>\n";
	
	if ($phpAds_showHelp) 
	{
		echo "<div id='helpLayer' name='helpLayer' style='position:absolute; left:".($phpAds_TextDirection != 'ltr' ? '0' : '181')."; top:-10; width:10px; height:10px; z-index:1; overflow: hidden; visibility: hidden;'>\n";
		echo "<img id='helpIcon' src='images/help-book.gif' align='absmiddle'>\n";
		echo "<span id='helpContents' name='helpContents'>".$phpAds_helpDefault."</span>\n";
		echo "</div>\n";
		echo "<br><br><br><br><br><br>\n";
	}
	
	echo "\n\n";
	
	
	if (!ereg("/(index|maintenance-updates|install|upgrade)\.php$", $HTTP_SERVER_VARS['PHP_SELF']))
	{
		// Add Product Update redirector
		if (phpAds_isUser(phpAds_Admin) &&
			function_exists('xml_parser_create') &&
			!isset($Session['update_check']))
		{
			echo "<script language='JavaScript' src='maintenance-updates-js.php'></script>\n";
		}
		
		// Check if the maintenance script is running
		if (phpAds_isUser(phpAds_Admin))
		{
			if ($phpAds_config['maintenance_timestamp'] < time() - (60 * 60 * 24))
			{
				if ($phpAds_config['maintenance_timestamp'] > 0)
				{
					// The maintenance script hasn't run in the 
					// last 24 hours, warn the user
					echo "<script language='JavaScript'>\n";
					echo "<!--//\n";
					echo "\talert('".$strMaintenanceNotActive."');\n";
					echo "//-->\n";
					echo "</script>\n";
				}
				
				// Update the timestamp to make sure the warning 
				// is shown only once every 24 hours
				$res = phpAds_dbQuery (
					"UPDATE ".$phpAds_config['tbl_config'].
					" SET maintenance_timestamp = '".time()."'"
				);
			}
		}
	}
	
	echo "</body>\n";
	echo "</html>\n";
}



/*********************************************************/
/* Show section navigation                               */
/*********************************************************/

function phpAds_ShowSections($sections)
{
	global $phpAds_nav, $phpAds_NavID;
	global $phpAds_TextDirection, $phpAds_TextAlignRight, $phpAds_TextAlignLeft;
	
	echo "\t\t\t</td>\n";
	echo "\t\t</tr>\n";
	echo "\t\t</table>\n";
	
	
	echo "\t\t<table border='0' cellpadding='0' cellspacing='0' width='100%' background='images/".$phpAds_TextDirection."/stab-bg.gif'>\n";
	echo "\t\t<tr height='24'>\n";
	echo "\t\t\t<td width='40'><img src='images/".$phpAds_TextDirection."/stab-bg.gif' width='40' height='24'></td>\n";
	echo "\t\t\t<td width='600' align='".$phpAds_TextAlignLeft."'>\n";
	
	echo "\t\t\t\t<table border='0' cellpadding='0' cellspacing='0'>\n";
	echo "\t\t\t\t<tr height='24'>\n";
	
	// Prepare Navigation
	if (phpAds_isUser(phpAds_Admin))
		$pages	= $phpAds_nav['admin'];
	elseif (phpAds_isUser(phpAds_Agency))
		$pages  = $phpAds_nav['agency'];
	elseif (phpAds_isUser(phpAds_Client))
		$pages  = $phpAds_nav['client'];
	else
		$pages  = $phpAds_nav['affiliate'];
	
	echo "\t\t\t\t\t<td></td>\n";
	
	for ($i=0; $i<count($sections);$i++)
	{
		list($sectionUrl, $sectionStr) = each($pages["$sections[$i]"]);
		$selected = ($phpAds_NavID == $sections[$i]);
		
		if ($selected)
		{
			echo "\t\t\t\t\t<td background='images/".$phpAds_TextDirection."/stab-sb.gif' valign='middle' nowrap>";
			
			if ($i > 0) 
				echo "<img src='images/".$phpAds_TextDirection."/stab-mus.gif' align='absmiddle'>";
			else
				echo "<img src='images/".$phpAds_TextDirection."/stab-bs.gif' align='absmiddle'>";
			echo "</td>\n";			
			echo "\t\t\t\t\t<td background='images/".$phpAds_TextDirection."/stab-sb.gif' valign='middle' nowrap>";
			echo "&nbsp;&nbsp;<a class='tab-s' href='".$sectionUrl."' accesskey='".($i+1)."'>".$sectionStr."</a></td>\n";
		}
		else
		{
			echo "\t\t\t\t\t<td background='images/".$phpAds_TextDirection."/stab-ub.gif' valign='middle' nowrap>";
			
			if ($i > 0) 
				if ($previousselected) 
					echo "<img src='images/".$phpAds_TextDirection."/stab-msu.gif' align='absmiddle'>";
				else
					echo "<img src='images/".$phpAds_TextDirection."/stab-muu.gif' align='absmiddle'>";
			else
				echo "<img src='images/".$phpAds_TextDirection."/stab-bu.gif' align='absmiddle'>";

			echo "</td>\n";
			echo "\t\t\t\t\t<td background='images/".$phpAds_TextDirection."/stab-ub.gif' valign='middle' nowrap>";
			echo "&nbsp;&nbsp;<a class='tab-g' href='".$sectionUrl."' accesskey='".($i+1)."'>".$sectionStr."</a></td>\n";
		}
		
		$previousselected = $selected;
	}
	
	if ($previousselected)
		echo "\t\t\t\t\t<td><img src='images/".$phpAds_TextDirection."/stab-es.gif'></td>\n";
	else
		echo "\t\t\t\t\t<td><img src='images/".$phpAds_TextDirection."/stab-eu.gif'></td>\n";
	
	echo "\t\t\t\t</tr>\n";
	echo "\t\t\t\t</table>\n";
	
	echo "\t\t\t</td>\n";
	echo "\t\t\t<td>&nbsp;</td>\n";
	echo "\t\t</tr>\n";
	echo "\t\t</table>\n";
	echo "\t\t<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
	echo "\t\t<tr>\n";
	echo "\t\t\t<td width='40'>&nbsp;</td>\n";
	echo "\t\t\t<td>\n";
	echo "\t\t\t\t<br>\n";
}



/*********************************************************/
/* Show a light gray line break                          */
/*********************************************************/

function phpAds_ShowBreak()
{
	echo "\t\t\t</td>\n";
	echo "\t\t\t<td width='40'>&nbsp;</td>\n";
	echo "\t\t</tr>\n";
	echo "\t\t</table>\n";
	echo "\t\t<img src='images/break-el.gif' height='1' width='100%' vspace='5'>\n";
	echo "\t\t<table width='100%' border='0' cellspacing='0' cellpadding='0'>\n";
	echo "\t\t<tr>\n";
	echo "\t\t\t<td width='40'>&nbsp;</td>\n";
	echo "\t\t\t<td>\n";
}



/*********************************************************/
/* Show a the last SQL error and die                     */
/*********************************************************/

function phpAds_sqlDie()
{
	global $phpAds_dbmsname, $phpAds_version_readable, $phpAds_version, $phpAds_productname;
    global $phpAds_last_query, $HTTP_SERVER_VARS;
	
	
	$error = phpAds_dbError();
	$corrupt = false;
	
	if ($phpAds_dbmsname == 'MySQL')
	{
		$errornumber = phpAds_dbErrorNo();
		
		if ($errornumber == 1027 || $errornumber == 1039)
			$corrupt = true;
		
		if ($errornumber == 1016 || $errornumber == 1030)
		{
			// Probably corrupted table, do additional check
			eregi ("[0-9]+", $error, $matches);
			
			if ($matches[0] == 126 || $matches[0] == 127 ||
				$matches[0] == 132 || $matches[0] == 134 ||
				$matches[0] == 135 || $matches[0] == 136 ||
				$matches[0] == 141 || $matches[0] == 144 ||
				$matches[0] == 145)
			{
				$corrupt = true;
			}
		}
	}
	
	if ($corrupt)
	{
		$title    = $GLOBALS['strErrorDBSerious'];
		$message  = $GLOBALS['strErrorDBNoDataSerious'];
		
		if (phpAds_isLoggedIn() && phpAds_isUser(phpAds_Admin))
			$message .= " (".$error.").<br><br>".$GLOBALS['strErrorDBCorrupt'];
		else
			$message .= ".<br>".$GLOBALS['strErrorDBContact'];
	}
	else
	{
		$title    = $GLOBALS['strErrorDBPlain'];
		$message  = $GLOBALS['strErrorDBNoDataPlain'];
		
		if (phpAds_isLoggedIn() && phpAds_isUser(phpAds_Admin))
		{
			$message .= $GLOBALS['strErrorDBSubmitBug'];
			
			$last_query = $phpAds_last_query;
			
			$message .= "<br><br><table cellpadding='0' cellspacing='0' border='0'>";
			$message .= "<tr><td valign='top' nowrap><b>Version:</b>&nbsp;&nbsp;&nbsp;</td><td>".$phpAds_productname." ".$phpAds_version_readable." (".$phpAds_version.")</td></tr>";
			$message .= "<tr><td>&nbsp;</td><td>PHP ".phpversion()." / ".$phpAds_dbmsname." ".phpAds_dbResult(phpAds_dbQuery('SELECT VERSION()'), 0, 0)."</td></tr>";
			$message .= "<tr><td valign='top' nowrap><b>Page:</b></td><td>".$HTTP_SERVER_VARS['PHP_SELF']."</td></tr>";
			$message .= "<tr><td valign='top' nowrap><b>Error:</b></td><td>".$error."</td></tr>";
			$message .= "<tr><td valign='top' nowrap><b>Query:</b></td><td>".$last_query."</td></tr>";
			$message .= "</table>";
		}
	}
	
	phpAds_Die ($title, $message);
}



/*********************************************************/
/* Display a custom error message and die                */
/*********************************************************/

function phpAds_Die($title="Error", $message="Unknown error")
{
	global $phpAds_GUIDone, $phpAds_TextDirection, $phpAds_config;
	
	// Header
	if ($phpAds_GUIDone == false)
	{
		if (!isset($phpAds_TextDirection)) 
			$phpAds_TextDirection = 'ltr';
		
		phpAds_PageHeader(phpAds_Error);
	}
	
	// Message
	echo "<br>";
	echo "<div class='errormessage'><img class='errormessage' src='images/errormessage.gif' align='absmiddle'>";
	echo "<span class='tab-r'>".$title."</span><br><br>".$message."</div><br><br>";
	
	// Die
	phpAds_PageFooter();
	exit;
}



/*********************************************************/
/* Show a confirm message for delete / reset actions	 */
/*********************************************************/

function phpAds_DelConfirm($msg)
{
	global $phpAds_config;
	
	if (phpAds_isUser(phpAds_Admin))
	{
		if ($phpAds_config['admin_novice'])
			$str = " onClick=\"return confirm('".$msg."')\"";
		else
			$str = "";
	}
	else
		$str = " onClick=\"return confirm('".$msg."')\"";
	
	return $str;
}



/*********************************************************/
/* Load the function need for the help system            */
/*********************************************************/

function phpAds_PrepareHelp($default='')
{
	global $phpAds_showHelp, $phpAds_helpDefault;
	
	$phpAds_helpDefault = $default;
	$phpAds_showHelp = true;
}

//================================================================================
//	statsPresenter Class
//================================================================================
/*
|
|
|
|
|
|
|
*/


function rowPresenter ($array, $i=0, $level=0, $parent='', $isClient=false, $id=0)
{

	global $HTTP_SERVER_VARS, $phpAds_TextAlignRight, $phpAds_TextDirection, $hideinactive,$i;

	if (is_array($array))
	{
	
		foreach($array as $array)
		{
		
			if ($array['kind'] == 'campaign' && $array['active'] == 'f' && $hideinactive == '1')			
				continue;

			// Define kind of row and id
			$kind 		= $array['kind'];
			$thisID		= $array['id'];
	
			// Inserts divider if NOT top level (level > 0)
			if ($level > 0)
				echo "<tr ".($i%2==0?"bgcolor='#F6F6F6'":"")."height='1'><td><img src='images/spacer.gif' width='1' height='1'></td><td colspan='6' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td></tr>";
			
			// Sets background color of the row
			echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
	
			// Indents as necesseary
			echo "<td height='25'>";
			echo "<img src='images/spacer.gif' height='16' width='". 4 ."'>";
			echo "<img src='images/spacer.gif' height='16' width='". ($level*20)  ."'>";
			
			// expanding arrows 
			if (isset($array['children']) && ($array['anonymous'] == 'f' || (!phpAds_isUser(phpAds_Affiliate) && !phpAds_isUser(phpAds_Client))))
			{
				if (isset($array['expand']) && $array['expand'] == '1')
					echo "<a href='".$HTTP_SERVER_VARS['PHP_SELF']."?_id_=".($parent !='' ? $parent . "-" : '') . $thisID."&collapse=1&".($isClient ? 'clientid='.$id : 'affiliateid='.$id )."'><img src='images/triangle-d.gif' align='absmiddle' align='absmiddle' border='0'></a>";
				else
					echo "<a href='".$HTTP_SERVER_VARS['PHP_SELF']."?_id_=".($parent !='' ? $parent . "-" : '') . $thisID."&expand=1&".($isClient ? 'clientid='.$id : 'affiliateid='.$id )."'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>";
			}
			else
				echo "<img src='images/spacer.gif' height='16' width='". 16 ."' align='absmiddle'>";
	
			echo "<img src='images/spacer.gif' height='16' width='". 4  ."'>";
	
	
			// specific zone stuff
			if ($kind == 'zone') {
				// icon
				if ($array['delivery'] == phpAds_ZoneBanner)
					echo "<img src='images/icon-zone.gif' align='absmiddle'>";
				elseif ($array['delivery'] == phpAds_ZoneInterstitial)
					echo "<img src='images/icon-interstitial.gif' align='absmiddle'>";
				elseif ($array['delivery'] == phpAds_ZonePopup)
					echo "<img src='images/icon-popup.gif' align='absmiddle'>";
				elseif ($array['delivery'] == phpAds_ZoneText)
					echo "<img src='images/icon-textzone.gif' align='absmiddle'>";
					
				// spacer between icon and name
				echo "<img src='images/spacer.gif' height='16' width='". 4 ."' align='absmiddle'>";
					
				// name and info
				echo "<a href='stats-zone-history.php?affiliateid=".$array['affiliateid']."&zoneid=".$array['id']."'>".$array['name']."</a>";
				echo "</td>";
				echo "<td height='25'>".$array['id']."</td>";
					
					
			} 
			else if ($kind == 'campaign')
			{
				// check whether the campaign is active
				if ($array['active'] == 't')
					echo "<img src='images/icon-campaign.gif' align='absmiddle'>";
				else
					echo "<img src='images/icon-campaign-d.gif' align='absmiddle'>";

				// spacer between icon and name
				echo "<img src='images/spacer.gif' height='16' width='". 4 ."' align='absmiddle'>";
					
				// get campaign name
				$name = '';
				if (isset($array['alt']) && $array['alt'] != '') $name = $array['alt'];
				if (isset($array['name']) && $array['name'] != '') $name = $array['name'];
				
				// check whether we should show the name and id of this banner	
				if ($array['anonymous'] == 't' && (phpAds_isUser(phpAds_Affiliate) || phpAds_isUser(phpAds_Client)))
				{
					echo "<a href='#'>".$GLOBALS['strHiddenCampaign']."</a></td>";						
					echo "<td height='25'></td>";

				}
				else	
				{
			
					echo 
						($isClient
							? "<a href='stats-campaign-history.php?clientid=".$id."&campaignid=".$array['id']."'>".$name."</a>"
							: "<a href='stats-campaign-affiliates.php?clientid=".$id."&campaignid=".$array['id']."'>".$name."</a>"
						);
				
					echo "</td><td height='25'>".$array['id']."</td>";
				}
			} 
			else if ($kind == 'banner') 
			{
			
				if (ereg ('bannerid:'.$array['id'], $array['what']))
					echo "<img src='images/icon-zone-linked.gif' align='absmiddle'>";
				else
					echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>";
	
				// spacer between icon and name
				echo "<img src='images/spacer.gif' height='16' width='". 4 ."' align='absmiddle'>";

				if ($isClient)
				{					
					echo "<a href='stats-banner-history.php?clientid=".$id."&bannerid=".$array['id']."&campaignid=".phpAds_getBannerParentClientID($array['id'])."'>". ($array['anonymous'] == 't' ? "(Hidden Banner)" : phpAds_getBannerName($array['id'], 30, false)) . "</td>";				
				}
				else
				{
					$thiszone = explode('-',$parent);
					echo "<a href='stats-linkedbanner-history.php?affiliateid=".$id."&zoneid=".$thiszone[0]."&bannerid=".$array['id']."'>". ($array['anonymous'] == 't' ? "(Hidden Banner)" : phpAds_getBannerName($array['id'], 30, false)) . "</td>";
				}

					

				echo "</td>";
				echo "<td height='25'>".$array['id']."</td>";
			}
			
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['views'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['clicks'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($array['views'], $array['clicks'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['conversions'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($array['clicks'], $array['conversions'])."&nbsp;&nbsp;</td>";
			echo "</tr>";
			
			if ($array['expand'] == TRUE && ($array['anonymous'] != 't' || (!phpAds_isUser(phpAds_Affiliate) && !phpAds_isUser(phpAds_Client)))  && is_array($array['children'])) 
				rowPresenter($array['children'], $i, $level+1, ($parent !='' ? $parent . "-" : '') . $thisID, $isClient, $id);
	
			if ($level == 0)
				echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
						
			if ($level==0) $i++;

		}



	}

}

/*
function zoneRow ($array, $i=0, $level=0) {

	global $phpAds_TextAlignRight, $phpAds_TextDirection;

	if (is_array($array))
	{
		foreach($array as $array)
		{
			// sets background color of the row
			echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
			echo "<td height='25'><img src='images/spacer.gif' height='16' width='". $level*25 ."'>";

			// expanding arrows
			if (isset($array['children']))
			{
				if (isset($array['expand']) && $array['expand'] == '1')
					echo "<a href='stats-affiliate-zones.php?_zone_=".$array['id']."&collapse=1'><img src='images/triangle-d.gif' align='absmiddle' align='absmiddle' border='0'></a>";
				else
					echo "<a href='stats-affiliate-zones.php?_zone_=".$array['id']."&expand=1'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>";
			}
			else
				echo "<img src='images/spacer.gif' height='16' width='". ($level+1)*16 ."' align='absmiddle'>";
	
			echo "&nbsp;";

			// icon
			if ($array['delivery'] == phpAds_ZoneBanner)
				echo "<img src='images/icon-zone.gif' align='absmiddle'>";
			elseif ($array['delivery'] == phpAds_ZoneInterstitial)
				echo "<img src='images/icon-interstitial.gif' align='absmiddle'>";
			elseif ($array['delivery'] == phpAds_ZonePopup)
				echo "<img src='images/icon-popup.gif' align='absmiddle'>";
			elseif ($array['delivery'] == phpAds_ZoneText)
				echo "<img src='images/icon-textzone.gif' align='absmiddle'>";


			echo "&nbsp;";
			
			// name and info
			echo "<a href='stats-zone-history.php?affiliateid=".$array['affiliateid']."&zoneid=".$array['id']."'>".$array['name']."</a>";
			echo "</td>";
			
			echo "<td height='25'>".$array['id']."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['views'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['clicks'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($array['views'], $array['clicks'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['conversions'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($array['clicks'], $array['conversions'])."&nbsp;&nbsp;</td>";
			echo "</tr>";
			
			if ($array['expand'] == TRUE && is_array($array['children'])) campaignRow($array['children'], $i, 1);

			if ($level == 0)
				echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
			
		}

	}

}

function campaignRow ($array, $i=0, $level=0) {

	global $phpAds_TextAlignRight, $phpAds_TextDirection, $hideinactive;

	if (is_array($array))
	{
				
		foreach ($array as $array)
		{
				
			if ($array['active'] == 't' || $hideinactive == '0')
			{
				
				
				$name = phpAds_breakString ($name, '30');
		
				// Divider
				if ($level == 1) {
					echo "<tr height='1'>";
					echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
					echo "<td colspan='6' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
					echo "</tr>";
				}
						
				// spacer
				echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
				echo "<td height='25'><img src='images/spacer.gif' height='16' width='" . $level*25 ."'>";
						
				echo "&nbsp;";
				// Expanding / Collapsing arrows
				if (isset($array['children'])  && $array['anonymous'] == 'f')
				{
					if (isset($array['expand']) && $array['expand'] == '1')
						echo "<a href='stats-affiliate-zones.php?_zone_=".$zone['id']."&_campaign_=".$array['id']."&collapse=1'><img src='images/triangle-d.gif' align='absmiddle' align='absmiddle' border='0'></a>";
					else
						echo "<a href='stats-affiliate-zones.php?_zone_=".$zone['id']."&_campaign_=".$array['id']."&expand=1'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>";
				}
				else
					echo "<img src='images/spacer.gif' height='16' width='". ($level+1)*16 ."' align='absmiddle'>";
	
				echo "&nbsp;";
	
						
				// check whether the campaign is active				
				if ($array['active'] == 't')
					echo "<img src='images/icon-campaign.gif' align='absmiddle'>";
				else
					echo "<img src='images/icon-campaign-d.gif' align='absmiddle'>";
	
				echo "&nbsp;";
					
				// check whether we should show the name and id of this banner	
				if ($array['anonymous'] == 'f') {
					echo "<a href='stats-banner-history.php?clientid=".$clientid."&campaignid=".$array['clientid']."&bannerid=".$array['id']."'>".$name."</a></td>";
					echo "<td height='25'>".$array['id']."</td>";					
				}
				else	
				{
					echo "<a href='#'>Hidden Banner</a></td>";						
					echo "<td height='25'></td>";
				}
				
				// echo the columns and corresponding values
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['views'])."</td>";
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['clicks'])."</td>";
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['CTR'])."</td>";
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['conversions'])."</td>";
				echo "<td height='25' align='".$phpAds_TextAlignRight."'>".number_format(phpAds_formatNumber($array['CNVR']), $phpAds_config['percentage_decimals'], $phpAds_DecimalPoint, $phpAds_ThousandsSeperator)."%&nbsp;&nbsp;</td>";
				
				if ($array['expand'] == TRUE && $array['anonymous'] == 'f' && is_array($array['children'])) bannerRow($array['children'], $i, 2);
	
				if ($level == 0)
					echo "<tr height='1'><td colspan='7' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";

			}			
			
		}
	}
}


function bannerRow ($array, $i=0, $level=0) {

	global $phpAds_TextAlignRight, $phpAds_TextDirection;

	if (is_array($array)){
	
		foreach ($array as $array)
		{
			// Divider
			echo "<tr height='1'>";
			echo "<td ".($i%2==0?"bgcolor='#F6F6F6'":"")."><img src='images/spacer.gif' width='1' height='1'></td>";
			echo "<td colspan='7' bgcolor='#888888'><img src='images/break-l.gif' height='1' width='100%'></td>";
			echo "</tr>";

			// Icon & name
			echo "<tr height='25' ".($i%2==0?"bgcolor='#F6F6F6'":"").">";
			echo "<td height='25'><img src='images/spacer.gif' height='16' width='" . $level*25 . "'>";


			echo "<img src='images/spacer.gif' height='16' width='". 16 ."' align='absmiddle'>";

			if (ereg ('bannerid:'.$array['id'], $zone['what']))
				echo "<img src='images/icon-zone-linked.gif' align='absmiddle'>";
			else
				echo "<img src='images/icon-banner-stored.gif' align='absmiddle'>";
	
			echo "&nbsp;";

			echo "<a href='stats-linkedbanner-history.php?affiliateid=".$zone['affiliateid']."&zoneid=".$zone['id']."&bannerid=".$array['id']."'>". ($array['anonymous'] == 't' ? "(Hidden Banner)" : phpAds_getBannerName($array['id'], 30, false)) . "</td>";
			echo "</td>";
	
	
			echo "<td height='25'>" . ($array['anonymous'] == 'f' || phpAds_isUser(phpAds_Admin) ? $array['id'] : "") . "</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['views'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['clicks'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_buildCTR($array['views'], $array['clicks'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".phpAds_formatNumber($array['conversions'])."</td>";
			echo "<td height='25' align='".$phpAds_TextAlignRight."'>".number_format(($array['clicks'] ? $array['conversions'] / $array['clicks'] * 100 : 0), $phpAds_config['percentage_decimals'], $phpAds_DecimalPoint, $phpAds_ThousandsSeperator)."%&nbsp;&nbsp;</td>";
			echo "</tr>";
		}
	}
}
*/


?>