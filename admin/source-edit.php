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

require_once("config.php");

// Register Input Variables
phpAds_registerGlobal (
	 'collapse'
	,'expand'
	,'listorder'
	,'orderdirection'
	,'parent'
	,'source_del'
	,'source_new'
	,'source_old'
	,'submit'
);

// Verify Permissions
if (phpAds_isUser(phpAds_Admin))
{
	$pageID = "source-edit.php";
	
	// Check if Posting Information
	if (isset($submit))
	{
		// Make sure that this process finishes...
		@set_time_limit (300);
		@ignore_user_abort(1);
		if ( isset($source_old) & isset($source_new))
		{
			for ($i=0; $i<sizeof($source_old); $i++)
			{
				if ($source_old[$i] != $source_new[$i])
				{
					phpAds_changeSourcePart($source_old[$i], $source_new[$i], $parent);
				}
			}
		}
		if ( isset($source_del) )
		{
			for ($i=0; $i<sizeof($source_del); $i++)
			{
				phpAds_removeSourcePart($source_del[$i], $parent);
			}
		}
		
		echo "<a href='".$pageID."?parent=".$parent.">Continue.</a><br>";
		exit;
	}
	else
	{
		// Get Data
		//require_once("source-edit-businesslayer.inc.php");
		$source_arr = phpAds_getPageData($pageID);
		
		// Display Data
		//require_once("source-edit-display.inc.php");
		phpAds_displayData($source_arr, $pageID);
		
		// Store Preferences
		phpAds_setPref($page, 'parent', $parent);
		phpAds_SessionDataStore();
	}
}

function phpAds_removeSourcePart($source_part, $parent)
{
	global $phpAds_config;
	
	$source_old = (strlen($parent) > 0) ? $parent.'/'.$source_part : $source_part;
	$len_source_old = strlen($source_old);

	echo "Removing Part: ".$source_old."...<br>\n";
	$prev_source = null;
	$views = 0;
	$query = "SELECT *".
		" FROM ".$phpAds_config['tbl_adstats'].
		" WHERE source='".$source_old."'".
		" OR source LIKE '".$source_old."/%'".
		" ORDER BY source";
	$res = phpAds_dbQuery($query);
	while ($row = phpAds_dbFetchArray($res))
	{
		$source_new = $parent;
		$source_new .= substr($row['source'], $len_source_old);
		
		if (substr($source_new, 0, 1) == '/')
			$source_new = substr($source_new, 1);

		if ($prev_source != $row['source'])
		{
			echo "Total Views Processed: ".$views.".<br>\n";
			echo "Processing: ".$source_new."...";
			$prev_source = $row['source'];
			$views = 0;
		}
		else
		{
			$views += $row['views'];
			echo ". ";
		}

		$query1 = "UPDATE ".$phpAds_config['tbl_adstats'].
			" SET views=views+".$row['views'].
			",clicks=clicks+".$row['clicks'].
			",conversions=conversions+".$row['conversions'].
			" WHERE bannerid=".$row['bannerid'].
			" AND zoneid=".$row['zoneid'].
			" AND source='".addslashes($source_new)."'".
			" AND hour=".$row['hour'].
			" AND day='".$row['day']."'"
		;
		
		$res1 = phpAds_dbQuery($query1)
	    	or phpAds_sqlDie();

	    if (phpAds_dbAffectedRows($res1) < 1)
	    {
	    	$query1 = "INSERT INTO ".$phpAds_config['tbl_adstats'].
				" SET views=".$row['views'].
				",clicks=".$row['clicks'].
				",conversions=".$row['conversions'].
				",bannerid=".$row['bannerid'].
				",zoneid=".$row['zoneid'].
				",source='".addslashes($source_new)."'".
				",hour=".$row['hour'].
				",day='".$row['day']."'"
	    	;
	    	$res1 = phpAds_dbQuery($query1)
	    		or phpAds_sqlDie();
    	}
	    
		$query1 = "DELETE FROM ".$phpAds_config['tbl_adstats'].
			" WHERE bannerid=".$row['bannerid'].
			" AND zoneid=".$row['zoneid'].
			" AND source='".addslashes($row['source'])."'".
			" AND hour=".$row['hour'].
			" AND day='".$row['day']."'"
		;
		
		$res1 = phpAds_dbQuery($query1)
	    	or phpAds_sqlDie();
	}
	echo "Total Views Processed: ".$views.".<br>\n";
}

function phpAds_changeSourcePart($source_part_old, $source_part_new, $parent)
{
	$unknown_source = '3284729384723984701euijswhfjdncfr9283yrfhe';
	global $phpAds_config;
	
	$source_old = (strlen($parent) > 0) ? $parent.'/'.$source_part_old : $source_part_old;
	$len_source_old = strlen($source_old);

	echo "Converting: ".$source_old."...<br>\n";
	$prev_source = $unknown_source;
	$views = 0;
	$query = "SELECT *".
		" FROM ".$phpAds_config['tbl_adstats'].
		" WHERE source='".$source_old."'".
		" OR source LIKE '".$source_old."/%'".
		" ORDER BY source";
	$res = phpAds_dbQuery($query);
	while ($row = phpAds_dbFetchArray($res))
	{
		$source_new = (strlen($parent) > 0) ? $parent.'/'.$source_part_new : $source_part_new;
		$source_new .= substr($row['source'], $len_source_old);
		
		if ($prev_source != $row['source'])
		{
			if ($prev_source != $unknown_source)
				echo "Total Views Processed: ".$views.".<br>\n";
			echo "Processing: ".$source_new."...";
			$prev_source = $row['source'];
			$views = 0;
			
		    // Update the adviews table...
			$query1 = "UPDATE ".$phpAds_config['tbl_adviews'].
				" SET source='".addslashes($source_new)."'".
				" WHERE source='".addslashes($row['source'])."'"
			;
			$res1 = phpAds_dbQuery($query1)
		    	or phpAds_sqlDie();
			echo "v ";
	
		    // Update the adclicks table...
			$query1 = "UPDATE ".$phpAds_config['tbl_adclicks'].
				" SET source='".addslashes($source_new)."'".
				" WHERE source='".addslashes($row['source'])."'"
			;
			
			$res1 = phpAds_dbQuery($query1)
		    	or phpAds_sqlDie();
			echo "c ";
	
		    // Update the conversion log table...
			$query1 = "UPDATE ".$phpAds_config['tbl_conversionlog'].
				" SET action_source='".addslashes($source_new)."'".
				" WHERE action_source='".addslashes($row['source'])."'"
			;
			
			$res1 = phpAds_dbQuery($query1)
		    	or phpAds_sqlDie();
			echo "n ";
		}
		else
		{
			$views += $row['views'];
			echo ". ";
		}

		$query1 = "UPDATE ".$phpAds_config['tbl_adstats'].
			" SET views=views+".$row['views'].
			",clicks=clicks+".$row['clicks'].
			",conversions=conversions+".$row['conversions'].
			" WHERE bannerid=".$row['bannerid'].
			" AND zoneid=".$row['zoneid'].
			" AND source='".addslashes($source_new)."'".
			" AND hour=".$row['hour'].
			" AND day='".$row['day']."'"
		;
		
		$res1 = phpAds_dbQuery($query1)
	    	or phpAds_sqlDie();

	    if (phpAds_dbAffectedRows($res1) < 1)
	    {
	    	$query1 = "INSERT INTO ".$phpAds_config['tbl_adstats'].
				" SET views=".$row['views'].
				",clicks=".$row['clicks'].
				",conversions=".$row['conversions'].
				",bannerid=".$row['bannerid'].
				",zoneid=".$row['zoneid'].
				",source='".addslashes($source_new)."'".
				",hour=".$row['hour'].
				",day='".$row['day']."'"
	    	;
	    	$res1 = phpAds_dbQuery($query1)
	    		or phpAds_sqlDie();
    	}
		
	    $query1 = "DELETE FROM ".$phpAds_config['tbl_adstats'].
			" WHERE bannerid=".$row['bannerid'].
			" AND zoneid=".$row['zoneid'].
			" AND source='".addslashes($row['source'])."'".
			" AND hour=".$row['hour'].
			" AND day='".$row['day']."'"
		;
		
		$res1 = phpAds_dbQuery($query1)
	    	or phpAds_sqlDie();
	}
	echo "Total Views Processed: ".$views.".<br>\n";
}

function phpAds_getPageData($pageID)
{
	global $parent;
	
	// GET PREFERENCES
	require_once("lib-prefs.inc.php");
	if (!isset($parent))
		$parent = phpAds_getPref($pageID, 'parent');
	
	// GET DATA
	require_once('lib-data-sources.inc.php');
	$source_arr = phpAds_getSources('SOURCES', $parent);
	
	return $source_arr;
}

function phpAds_displayData($source_arr, $pageID)
{
	global $parent;
	
	echo "<form action='".$pageID."' method='post'>\n";
	
	$parent_arr = explode('/', $parent);
	
	echo "<h1><a href='".$pageID."?parent='>Top</a> : ";
	$tmp_parent = "";
	for ($i=0; $i<sizeof($parent_arr); $i++)
	{
		if ($i != 0) echo ' / ';

		$tmp_parent = strlen($tmp_parent) > 0 ? $tmp_parent.'/'.$parent_arr[$i] : $parent_arr[$i];
		echo "<a href='".$pageID."?parent=".urlencode($tmp_parent)."'>".$parent_arr[$i]."</a>";
	}
	echo "</h1>\n";

	echo "<table cellspacing='0' cellpadding='0' border='1'>\n";
	echo "<tr class='data_header_row'>\n";
	echo "\t<td class='data_header_cell'>Views</td>\n";
	echo "\t<td class='data_header_cell'>Source</td>\n";
	echo "\t<td class='data_header_cell'>Modified Source</td>\n";
	echo "\t<td class='data_header_cell'>Remove this part</td>\n";
	echo "</tr>\n";
	
	for ($i=0; $i<sizeof($source_arr); $i++)
	{
		if (is_array($source_arr[$i]))
		{
			$source_part = $source_arr[$i]['source_part'];
			$sum_views = $source_arr[$i]['sum_views'];
			echo "<tr class='data_row".($cnt%2==0?"_alt":"")."'>\n";
			echo "\t<td class='data_cell'>".$sum_views."</td>\n";
			echo "\t<td class='data_cell'>";
			echo "<a href='".$pageID."?parent=".urlencode(strlen($parent) > 0 ? $parent.'/'.$source_part : $source_part)."'>";
			echo $source_part;
			echo "</td>\n";
			echo "<td>";
			echo "<input type='hidden' name='source_old[]' value='".$source_part."'>";
			echo "<input type='text' class='flat' size='26' name='source_new[]' value='".$source_part."' style='width:250px;'>";
			echo "</td>\n";
			echo "<td>";
			echo "<input type='checkbox' name='source_del[]' value='".$source_part."'>";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
	
	echo "</table>\n";
	echo "<input type='hidden' name='parent' value='".$parent."'>\n";
	echo "<input type='submit' name='submit' value='Update Fields'>\n";
	echo "</form>\n";
	
	phpAds_PageFooter();
}

function phpAds_displayDataRow($data_arr, $level = 0)
{
	global
		 $cnt
		,$page_ID
		,$phpAds_TextDirection
	;
	$show_children = false;
	
	echo "<tr class='data_row".($cnt%2==0?"_alt":"").">\n";
	
	// Change this to a SPAN??
	if ($level > 1)
		echo "<img src='images/spacer.gif' width='".($level*15)."' height='1' border='0'>";

	
	if (isset($data_arr['children']) && is_array($data_arr['children']) && (sizeof($data_arr['children']) > 0) )
	{
		if (in_array($data_arr['name'], $expand_arr))
		{
			echo "<a href='".$page_ID."?collapse=".$data_arr['name']."'><img src='images/triangle-d.gif' align='absmiddle' border='0'></a>";
			$show_children = true;
		}
		else
			echo "<a href='".$page_ID."?expand=".$data_arr['name']."'><img src='images/".$phpAds_TextDirection."/triangle-l.gif' align='absmiddle' border='0'></a>";
	}

	if ($level == 0)
		echo "<span class='data_row_top'>".$data_arr['name']."</span></td>\n";
	else
		echo $data_arr['name']."</td>\n";

	echo "</tr>\n";
	
	if ($show_children)
	{
		for ($i=0; $i<sizeof($data_arr['children']); $i++)
		{
			phpAds_displayStatsRow($data_arr['children'][$i], $expand_arr, $level+1);
		}
	}
	
	if ($level == 0)
		$cnt++;
}	

function phpAds_printTableBreak($num_columns, $offset=0, $bgcolor='')
{
	
	echo "\t\t\t\t<tr height='1'";

	if ($offset > 0)
	{
		if (strlen($bgcolor) > 0)
		{
			$bgcolor = " bgcolor='".$bgcolor."'";
		}

		echo $bgcolor."><td";
		
		if ($offset > 1)
		{
			echo " colspan='".$offset."'";
		}
		
		echo "><img src='images/spacer.gif' width='100%' height='1' border='0'></td>";
	}
	
	echo "<td colspan='".($num_columns-$offset)." bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";
}

?>
