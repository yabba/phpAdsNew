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



// Consolidate the data from the adviews, adclicks and adconversions tables into the adstats table.

// The timeframes that are processed starts with the most recent hour processed from adstats,
//   and ends with the last completed hour in adviews.

$report = "";
$now = time();

// Process Statistics...
$report .= "==================================================\n";
$report .= "BATCH STATISTICS STARTED\n";
$report .= "==================================================\n\n";
$report .= "--------------------------------------------------\n";

// Get the beginning Timestamp
$begin_timestamp = "";
$time_query = "SELECT".
				" DATE_FORMAT(day, '%Y') as year".
				",DATE_FORMAT(day, '%m') as month".
				",DATE_FORMAT(day, '%d') as date".
				",hour".
				" FROM ".$phpAds_config['tbl_adstats'].
				" ORDER BY day DESC".
				",hour DESC".
				" LIMIT 1";
$time_result = phpAds_dbQuery($time_query)
	or $report.= "Could not perform SQL: ".$time_query."\n";

if ($time_row = phpAds_dbFetchArray($time_result))
{
	$time = mktime($time_row['hour']+1, 0, 0, $time_row['month'], $time_row['date'], $time_row['year']);
	$begin_timestamp = date('YmdHis', $time);
}
else
{
	$begin_timestamp = "00000000000000";
}

// Get the ending timestamp
$end_timestamp = "";
$time_query = "SELECT".
				" UNIX_TIMESTAMP(NOW()) as now".
				",CONCAT(DATE_FORMAT(NOW(), '%Y%m%d%H'),'0000') as time";
$time_result = phpAds_dbQuery($time_query)
	or $report.= "Could not perform SQL: ".$time_query."\n";

if ($time_row = phpAds_dbFetchArray($time_result))
{
	$end_timestamp = $time_row['time'];
	$now = $time_row['now'];
}
else
{
	$end_timestamp = "00000000000000";
	$now = time();
}

// FOR DEBUGGING...
$regen=false;
if ($regen)
{
	$begin_timestamp = "20030428150000";
	$end_timestamp = "20030428160000";
	
	$delete_query = "DELETE".
					" FROM ".$phpAds_config['tbl_adstats'].
					" WHERE DATE_FORMAT(DATE_ADD(day, INTERVAL hour HOUR),'%Y%m%d%H%i%s')>=".$begin_timestamp.
					" AND DATE_FORMAT(DATE_ADD(day, INTERVAL hour HOUR),'%Y%m%d%H%i%s')>=".$end_timestamp;
}
//Process views...
$num_views = 0;
$time = time();
$report .= "Counting the verbose views between ".$begin_timestamp." and ".$end_timestamp."...\n";
$view_query = "SELECT".
				" DATE_FORMAT(t_stamp, '%Y%m%d') as day".
				",HOUR(t_stamp) as hour".
				",bannerid".
				",zoneid".
				",source".
				",count(*) as views".
				" FROM ".$phpAds_config['tbl_adviews'].
				" WHERE t_stamp>=".$begin_timestamp.
				" AND t_stamp<".$end_timestamp.
				" GROUP BY day,hour,bannerid,zoneid,source";
$view_result = phpAds_dbQuery($view_query)
	or $report.= "Could not perform SQL: ".$view_query."\n";

while ($view_row = phpAds_dbFetchArray($view_result))
{
	
    $stat_query = "INSERT INTO ".$phpAds_config['tbl_adstats'].
    				" SET day=".$view_row['day'].
    				",hour=".$view_row['hour'].
    				",bannerid=".$view_row['bannerid'].
    				",zoneid=".$view_row['zoneid'].
    				",source='".$view_row['source']."'".
    				",views=".$view_row['views'];
    $stat_result = phpAds_dbQuery($stat_query)
    	or $report.= " Could not perform SQL: ".$stat_query."\n";
    
    if (phpAds_dbAffectedRows($stat_result) < 1)
    {
		$stat_query = "UPDATE ".$phpAds_config['tbl_adstats'].
						" SET views=views+".$view_row['views'].
						" WHERE day=".$view_row['day'].
						" AND hour=".$view_row['hour'].
						" AND bannerid=".$view_row['bannerid'].
						" AND zoneid=".$view_row['zoneid'].
						" AND source='".$view_row['source']."'";
	    $stat_result = phpAds_dbQuery($stat_query)
	    	or $report.= " Could not perform SQL: ".$stat_query."\n";
    }
    $num_views += $view_row['views'];
}
$report .= "Counted ".$num_views." views in ".(time()-$time)." seconds.\n";

//Process clicks...
$num_clicks = 0;
$time = time();
$report .= "Counting the verbose clicks between ".$begin_timestamp." and ".$end_timestamp."...\n";
$click_query = "SELECT".
				" DATE_FORMAT(t_stamp, '%Y%m%d') as day".
				",HOUR(t_stamp) as hour".
				",bannerid".
				",zoneid".
				",source".
				",count(*) as clicks".
				" FROM ".$phpAds_config['tbl_adclicks'].
				" WHERE t_stamp>=".$begin_timestamp.
				" AND t_stamp<".$end_timestamp.
				" GROUP BY day,hour,bannerid,zoneid,source";
$click_result = phpAds_dbQuery($click_query)
	or $report.= "Could not perform SQL: ".$click_query."\n";

while ($click_row = phpAds_dbFetchArray($click_result))
{
	$stat_query = "UPDATE ".$phpAds_config['tbl_adstats'].
					" SET clicks=clicks+".$click_row['clicks'].
					" WHERE day=".$click_row['day'].
					" AND hour=".$click_row['hour'].
					" AND bannerid=".$click_row['bannerid'].
					" AND zoneid=".$click_row['zoneid'].
					" AND source='".$click_row['source']."'";
    $stat_result = phpAds_dbQuery($stat_query)
    	or $report.= " Could not perform SQL: ".$stat_query."\n";
    
    if (phpAds_dbAffectedRows($stat_result) < 1)
    {
	    $stat_query = "INSERT INTO ".$phpAds_config['tbl_adstats'].
	    				" SET day=".$click_row['day'].
	    				",hour=".$click_row['hour'].
	    				",bannerid=".$click_row['bannerid'].
	    				",zoneid=".$click_row['zoneid'].
	    				",source='".$click_row['source']."'".
	    				",clicks=".$click_row['clicks'];
	    $stat_result = phpAds_dbQuery($stat_query)
	    	or $report.= " Could not perform SQL: ".$stat_query."\n";
    }
    $num_clicks += $click_row['clicks'];
}
$report .= "Counted ".$num_clicks." clicks in ".(time()-$time)." seconds.\n\n";


//Next, Subtract the number of views for a particular banner...
$report .= "Decrementing High Priority Campaigns...\n";
$time = time();
$num_views = 0;
$num_clicks = 0;
$num_conversions = 0;

// Get campaign information
$campaign_query = "SELECT".
					" campaignid".
					",active".
					",views".
					",clicks".
					",conversions".
					",UNIX_TIMESTAMP(expire) AS expire_st".
					",UNIX_TIMESTAMP(activate) AS activate_st".
					",clientid".
					",campaignname".
					" FROM ".$phpAds_config['tbl_campaigns'];
$campaign_result = phpAds_dbQuery($campaign_query)
	or $report.= "Could not perform SQL: ".$campaign_query."\n";
				
while ($campaign_row = phpAds_dbFetchArray($campaign_result))
{
	$views = $campaign_row['views'];
	$clicks = $campaign_row['clicks'];
	$conversions = $campaign_row['conversions'];
	$active = $campaign_row['active'];
	
	// Decrement Views...
	if ($views > 0)
	{
		$view_query = "SELECT".
						" COUNT(*) AS views".
						" FROM ".$phpAds_config['tbl_adviews'].
						",".$phpAds_config['tbl_banners'].
						" WHERE ".$phpAds_config['tbl_banners'].".bannerid=".$phpAds_config['tbl_adviews'].".bannerid".
						" AND ".$phpAds_config['tbl_banners'].".campaignid=".$campaign_row['campaignid'].
						" AND t_stamp>=".$begin_timestamp.
						" AND t_stamp<".$end_timestamp;
		$view_result = phpAds_dbQuery($view_query)
			or $report.= "Could not perform SQL: ".$view_query."\n";

		if ($view_row = phpAds_dbFetchArray($view_result))
		{
			$views -= $view_row['views'];
			if ($views < 1)
			{
				$views = 0;
				$active = 'f';
			}
			$num_views += $view_row['views'];
		}
	}
	// Decrement Clicks...
	if ($clicks > 0)
	{
		$click_query = "SELECT".
						" COUNT(*) AS clicks".
						" FROM ".$phpAds_config['tbl_adclicks'].
						",".$phpAds_config['tbl_banners'].
						" WHERE ".$phpAds_config['tbl_banners'].".bannerid=".$phpAds_config['tbl_adclicks'].".bannerid".
						" AND ".$phpAds_config['tbl_banners'].".campaignid=".$campaign_row['campaignid'].
						" AND t_stamp>=".$begin_timestamp.
						" AND t_stamp<".$end_timestamp;
		$click_result = phpAds_dbQuery($click_query)
			or $report.= "Could not perform SQL: ".$click_query."\n";

		if ($click_row = phpAds_dbFetchArray($click_result))
		{
			$clicks -= $click_row['clicks'];
			if ($clicks < 1)
			{
				$clicks = 0;
				$active = 'f';
			}
			$num_clicks += $view_row['clicks'];
		}
	}
	// Decrement Conversions...
	if ($conversions > 0)
	{
		$conversion_query = "SELECT".
						" COUNT(*) AS conversions".
						" FROM ".$phpAds_config['tbl_adconversions'].
						" WHERE campaignid=".$campaign_row['campaignid'].
						" AND t_stamp>=".$begin_timestamp.
						" AND t_stamp<".$end_timestamp;
		$conversion_result = phpAds_dbQuery($conversion_query)
			or $report.= "Could not perform SQL: ".$conversion_query."\n";

		if ($conversion_row = phpAds_dbFetchArray($conversion_result))
		{
			$conversions -= $conversion_row['conversions'];
			if ($conversions < 1)
			{
				$conversions = 0;
				$active = 'f';
			}
			$num_conversions += $view_row['conversions'];
		}
	}

	// Check time status...
	if ( ($now < $campaign_row['activate_st']) ||
		 ($now > $campaign_row['expire_st'] && $campaign_row['expire_st'] != 0) )
	{
		$active = 'f';
	}
	
	// Check to see if we need to log a change in activation status...
	if ($campaign_row['active'] != $active)
	{
		$report.= "Sending an email to the owner of campaign ".$campaign_row['campaignid']."\n";

		if ($active == 'f')
		{
			//Send deactivation emails...
			if (!defined('LIBWARNING_INCLUDED'))
				require(phpAds_path.'/libraries/lib-warnings.inc.php');
			if (!defined('LIBMAIL_INCLUDED'))
				require(phpAds_path.'/libraries/lib-mail.inc.php');
			
			phpAds_deactivateMail($campaign_row);
		}
	}
					
	//Update campaign
	if ( ($views != $campaign_row['views']) ||
		 ($clicks != $campaign_row['clicks']) ||
		 ($conversions != $campaign_row['conversions']) ||
		 ($active != $campaign_row['active']) )
	{

		$update_query = "UPDATE ".$phpAds_config['tbl_campaigns'].
						" SET views=".$views.
						",clicks=".$clicks.
						",conversions=".$conversions.
						",active='".$active."'".
						" WHERE campaignid=".$campaign_row['campaignid'];
		phpAds_dbQuery($update_query)
			or $report.= "Could not perform SQL: ".$update_query."\n";
		
		$report .= "Changing high priority campaign ".$campaign_row['campaignid'].":\n";
		$report .= "   Views:  from ".$campaign_row['views']." to ".$views."\n";
		$report .= "   Clicks:  from ".$campaign_row['clicks']." to ".$clicks."\n";
		$report .= "   Conversions:  from ".$campaign_row['conversions']." to ".$conversions."\n";
		$report .= "   Active Status:  from ".$campaign_row['active']." to ".$active."\n\n";
	}
}	

$report .= "Proccessed a total of ".$num_views." high priority views, ".$num_clicks." high priority clicks, and ".$num_conversions." high priority conversions in ".(time()-$time)." seconds.\n";


if ($phpAds_config['compact_stats'])
{
	$time = time();
	$delete_query = "DELETE".
					" FROM ".$phpAds_config['tbl_adviews'].
					" WHERE t_stamp>=".$begin_timestamp.
					" AND t_stamp<".$end_timestamp;
	phpAds_dbQuery($delete_query)
		or $report.= "Could not perform SQL: ".$delete_query."\n";
	
	$delete_query = "DELETE".
					" FROM ".$phpAds_config['tbl_adclicks'].
					" WHERE t_stamp>=".$begin_timestamp.
					" AND t_stamp<".$end_timestamp;
	phpAds_dbQuery($delete_query)
		or $report.= "Could not perform SQL: ".$delete_query."\n";

	$report .= "Deleted verbose stats in ".(time()-$time)." seconds.\n";
}

phpAds_userlogAdd (phpAds_actionBatchStatistics, 0, $report);
?>