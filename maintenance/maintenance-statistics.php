<?php // $Revision: 1.13 $

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

// Process Statistics...
$report .= "==================================================\n";
$report .= "BATCH STATISTICS STARTED\n";
$report .= "==================================================\n\n";
$report .= "--------------------------------------------------\n";

$done = false;

while (!$done)
{
	$time_query =
		"SELECT DATE_FORMAT(DATE_ADD(statslastday, INTERVAL statslasthour HOUR),'%Y%m%d%H%i%s') AS start_timestamp".
		",DATE_FORMAT(DATE_ADD(statslastday, INTERVAL statslasthour+1 HOUR),'%Y%m%d%H%i%s') AS end_timestamp".
		",statslastday as start_day".
		",statslasthour as start_hour".
		",NOW() as exact_time".
		",DATE_FORMAT(NOW(),'%Y%m%d%H%i%s') as exact_timestamp".
		" FROM ".$phpAds_config['tbl_config']
	;
	
	$time_result = phpAds_dbQuery($time_query)
		or $report.= "Could not perform SQL: ".$time_query."\n";

	if ($time_row = phpAds_dbFetchArray($time_result))
	{
		$begin_timestamp = $time_row['start_timestamp'];
		$end_timestamp = $time_row['end_timestamp'];
		$day = $time_row['start_day'];
		$hour = $time_row['start_hour'];
		$exact_timestamp = $time_row['exact_timestamp'];
		$exact_time = $time_row['exact_time'];
		
		$report .= "Checking for statistics...  The current time is ".$exact_time."\n";
		$report .= "\tThe last hour that statistics were compiled was up to ".$hour." on ".$day.".\n\n";

		if ($exact_timestamp >= $end_timestamp)
		{
			$report .= "Processing statistics for hour ".$hour." on ".$day."...\n\n";
		
			if (!phpAds_checkStatsExist($day, $hour, $report))
				phpAds_processStats($begin_timestamp, $end_timestamp, $day, $hour, $report);
			else 
				$report .= "Statistics already exist for hour ".$hour." on ".$day.".  Please delete the statistics if you would like to regenerate.\n";
	
			// Now that everything is done, update the stats generation date/time.
			phpAds_logStatsDate($end_timestamp);
		}
		else 
			$done = true;
	}
	else
		$done = true;
}

$report .= "No more statistics to compile.\n";

// Write the output to the user log.
phpAds_userlogAdd (phpAds_actionBatchStatistics, 0, $report);




function phpAds_processStats($begin_timestamp, $end_timestamp, $day, $hour)
{
	global $report;
	
	// If we are rebuilding a particular hour,
	// then increment back any inventory to campaigns and delete the stats for this hour
	$regen = false; // Will build later...
	if ($regen)
	{
		$report .= "\t REGENERATING!!/n/n";
		phpAds_undoInventory($day, $hour);
		phpAds_deleteCompactStats($day, $hour);
	}
	
	// Count the total views for this hour
	phpAds_countViews($begin_timestamp, $end_timestamp, $day, $hour);
	// Count the total clicks for this hour
	phpAds_countClicks($begin_timestamp, $end_timestamp, $day, $hour);
	// Count the total conversions for this hour
	phpAds_countConversions($begin_timestamp, $end_timestamp, $day, $hour);
	// Decrement the campaigns with our new statistics
	phpAds_decrementCampaigns($day, $hour);
	// Clean up (if user wants...)
	phpAds_deleteVerboseStats($begin_timestamp, $end_timestamp);
}

function phpAds_countViews($begin_timestamp, $end_timestamp, $day, $hour)
{
	global $phpAds_config, $report;
	
	//Process views...
	$num_views = 0;
	$time = time();
	$report .= "\tCounting the verbose views between ".$begin_timestamp." and ".$end_timestamp."...\n";
	$view_query = "SELECT bannerid".
					",zoneid".
					",count(*) as views".
					" FROM ".$phpAds_config['tbl_adviews'].
					" WHERE t_stamp>=".$begin_timestamp.
					" AND t_stamp<".$end_timestamp.
					" GROUP BY bannerid,zoneid";
	$view_result = phpAds_dbQuery($view_query)
		or $report.= "Could not perform SQL: ".$view_query."\n";
	
	while ($view_row = phpAds_dbFetchArray($view_result))
	{
		
	    $stat_query = "INSERT INTO ".$phpAds_config['tbl_adstats'].
	    				" SET day='".$day."'".
	    				",hour=".$hour.
	    				",bannerid=".$view_row['bannerid'].
	    				",zoneid=".$view_row['zoneid'].
	    				",views=".$view_row['views'];
	    $stat_result = phpAds_dbQuery($stat_query)
	    	or $report.= " Could not perform SQL: ".$stat_query."\n";

	    if (phpAds_dbAffectedRows($stat_result) < 1)
	    {
			$stat_query = "UPDATE ".$phpAds_config['tbl_adstats'].
							" SET views=views+".$view_row['views'].
							" WHERE day='".$day."'".
							" AND hour=".$hour.
							" AND bannerid=".$view_row['bannerid'].
							" AND zoneid=".$view_row['zoneid'];
		    $stat_result = phpAds_dbQuery($stat_query)
		    	or $report.= " Could not perform SQL: ".$stat_query."\n";
	    }
	    $num_views += $view_row['views'];
	}
	$report .= "\tCounted ".$num_views." views in ".(time()-$time)." seconds.\n";
}


function phpAds_countClicks($begin_timestamp, $end_timestamp, $day, $hour)
{
	global $phpAds_config, $report;
	
	//Process clicks...
	$num_clicks = 0;
	$time = time();
	$report .= "\tCounting the verbose clicks between ".$begin_timestamp." and ".$end_timestamp."...\n";
	$click_query = "SELECT bannerid".
					",zoneid".
					",count(*) as clicks".
					" FROM ".$phpAds_config['tbl_adclicks'].
					" WHERE t_stamp>=".$begin_timestamp.
					" AND t_stamp<".$end_timestamp.
					" GROUP BY bannerid,zoneid";
	$click_result = phpAds_dbQuery($click_query)
		or $report.= "Could not perform SQL: ".$click_query."\n";
	
	while ($click_row = phpAds_dbFetchArray($click_result))
	{
		$stat_query = "UPDATE ".$phpAds_config['tbl_adstats'].
						" SET clicks=clicks+".$click_row['clicks'].
						" WHERE day='".$day."'".
						" AND hour=".$hour.
						" AND bannerid=".$click_row['bannerid'].
						" AND zoneid=".$click_row['zoneid'];
	    $stat_result = phpAds_dbQuery($stat_query)
	    	or $report.= " Could not perform SQL: ".$stat_query."\n";
	    
	    if (phpAds_dbAffectedRows($stat_result) < 1)
	    {
		    $stat_query = "INSERT INTO ".$phpAds_config['tbl_adstats'].
		    				" SET day='".$day."'".
		    				",hour=".$hour.
		    				",bannerid=".$click_row['bannerid'].
		    				",zoneid=".$click_row['zoneid'].
		    				",clicks=".$click_row['clicks'];
		    $stat_result = phpAds_dbQuery($stat_query)
		    	or $report.= " Could not perform SQL: ".$stat_query."\n";
	    }
	    $num_clicks += $click_row['clicks'];
	}
	$report .= "\tCounted ".$num_clicks." clicks in ".(time()-$time)." seconds.\n";
}

function phpAds_countConversions($begin_timestamp, $end_timestamp, $day, $hour)
{
	global $phpAds_config, $report;
	
	//Process conversions...
	$num_conversions = 0;
	$time = time();
	
	$report .= "\tCounting the verbose conversions between ".$begin_timestamp." and ".$end_timestamp."...\n";

	// Get all of the conversions for this hour...
	$conversion_query =
		"SELECT userid".
		",t_stamp".
		",trackerid".
		",host".
		",country".
		" FROM ".$phpAds_config['tbl_adconversions'].
		" WHERE userid!=''".
		" AND t_stamp>=".$begin_timestamp.
		" AND t_stamp<".$end_timestamp.
		" ORDER BY trackerid"
	;
	
	$res = phpAds_dbQuery($conversion_query) or $report.= "Could not perform SQL: ".$conversion_query."\n";
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$userid = $row['userid'];
		$t_stamp = $row['t_stamp'];
		$trackerid = $row['trackerid'];
		$host = $row['host'];
		$country = $row['country'];
		
		$campaign_query =
			"SELECT *".
			" FROM ".$phpAds_config['tbl_campaigns_trackers'].
			" WHERE trackerid=".$trackerid
		;
		$campaign_res = phpAds_dbQuery($campaign_query)
			or $report.= "Could not perform SQL: ".$campaign_query."\n";
	
		while ($campaign_row = phpAds_dbFetchArray($campaign_res))
		{
			$found = false;
			$action_query =
				"SELECT c.t_stamp AS t_stamp".
				",c.bannerid AS bannerid".
				",c.zoneid AS zoneid".
				",c.host AS host".
				",c.source AS source".
				",c.country AS country".
				" FROM ".$phpAds_config['tbl_adclicks']." AS c".
				",".$phpAds_config['tbl_banners']." AS b".
				" WHERE c.userid='".$userid."'".
				" AND c.bannerid=b.bannerid".
				" AND b.campaignid=".$campaign_row['campaignid'].
				" AND c.t_stamp>= DATE_SUB(".$t_stamp.", INTERVAL ".$campaign_row['clickwindow']." SECOND)".
				" AND c.t_stamp<".$t_stamp.
				" ORDER BY t_stamp DESC".
				" LIMIT 1"
			;
			
			/*
			$action_query =
				"SELECT t_stamp".
				",bannerid".
				",zoneid".
				",host".
				",source".
				",country".
				" FROM ".$phpAds_config['tbl_adclicks'].
				" WHERE userid='".$userid."'".
				" AND t_stamp>= DATE_SUB(".$t_stamp.", INTERVAL ".$campaign_row['clickwindow']." SECOND)".
				" AND t_stamp<".$t_stamp.
				" ORDER BY t_stamp DESC".
				" LIMIT 1"
			;
			*/
			
			$action_res = phpAds_dbQuery($action_query)
				or $report.= "Could not perform SQL: ".$action_query."\n";
			
			if ($action_row = phpAds_dbFetchArray($action_res))
			{
				$found = true;
				$action = 'click';
			}
			else
			{

				$action_query =
					"SELECT v.t_stamp AS t_stamp".
					",v.bannerid AS bannerid".
					",v.zoneid AS zoneid".
					",v.host AS host".
					",v.source AS source".
					",v.country AS country".
					" FROM ".$phpAds_config['tbl_adviews']." AS v".
					",".$phpAds_config['tbl_banners']." AS b".
					" WHERE v.userid='".$userid."'".
					" AND v.bannerid=b.bannerid".
					" AND b.campaignid=".$campaign_row['campaignid'].
					" AND v.t_stamp>= DATE_SUB(".$t_stamp.", INTERVAL ".$campaign_row['viewwindow']." SECOND)".
					" AND v.t_stamp<".$t_stamp.
					" ORDER BY t_stamp DESC".
					" LIMIT 1"
				;

				/*
				$action_query =
					"SELECT t_stamp".
					",bannerid".
					",zoneid".
					",host".
					",source".
					",country".
					" FROM ".$phpAds_config['tbl_adviews'].
					" WHERE userid='".$userid."'".
					" AND t_stamp>= DATE_SUB(".$t_stamp.", INTERVAL ".$campaign_row['viewwindow']." SECOND)".
					" AND t_stamp<".$t_stamp.
					" ORDER BY t_stamp DESC".
					" LIMIT 1"
				;
				*/
				
				$action_res = phpAds_dbQuery($action_query)
					or $report.= "Could not perform SQL: ".$action_query."\n";
				
				if ($action_row = phpAds_dbFetchArray($action_res))
				{
					$found = true;
					$action = 'view';
				}
			}
			if ($found)
			{
				$campaignid = $campaign_row['campaignid'];
				
				$action_t_stamp = $action_row['t_stamp'];
				$action_bannerid = $action_row['bannerid'];
				$action_zoneid = $action_row['zoneid'];
				$action_host = $action_row['host'];
				$action_source = $action_row['source'];
				$action_country = $action_row['country'];
				
				$cnv_logstats = $campaign_row['logstats'];
				$cnv_clickwindow = $campaign_row['clickwindow'];
				$cnv_viewwindow = $campaign_row['viewwindow'];
				
				// Found an item which passed the rules.
				// Now, log this item
				$log_query =
					"INSERT INTO ".$phpAds_config['tbl_conversionlog'].
					" (campaignid".
					",trackerid".
					",userid".
					",t_stamp".
					",host".
					",country".
					",cnv_logstats".
					",cnv_clickwindow".
					",cnv_viewwindow".
					",action".
					",action_bannerid".
					",action_zoneid".
					",action_t_stamp".
					",action_host".
					",action_source".
					",action_country)".
					" VALUES ".
					" (".$campaignid.
					",".$trackerid.
					",'".$userid."'".
					",".$t_stamp.
					",'".$host."'".
					",'".$country."'".
					",'".$cnv_logstats."'".
					",".$cnv_clickwindow.
					",".$cnv_viewwindow.
					",'".$action."'".
					",".$action_bannerid.
					",".$action_zoneid.
					",".$action_t_stamp.
					",'".$action_host."'".
					",'".$action_source."'".
					",'".$action_country."')"
				;
	
				phpAds_dbQuery($log_query)
					or $report.= "Could not perform SQL: ".$log_query."\n";
			
				$conversionlogid = phpAds_dbInsertID();

				$conversion_update_query =
					"UPDATE ".$phpAds_config['tbl_adconversions'].
					" SET conversionlogid=".$conversionlogid.
					" WHERE userid='".$userid."'".
					" AND t_stamp=".$t_stamp
				;
				
				phpAds_dbQuery($conversion_query)
					or $report.= "Could not perform SQL: ".$conversion_query."\n";
				
				$num_conversions++;
			}
		}
	}
	
	// Now, add up all of the conversions that we just logged and put them into adstats.
	//Process conversions...
	$num_conversions = 0;
	$conversion_query = "SELECT action_bannerid".
					",action_zoneid".
					",action_source".
					",count(*) as conversions".
					" FROM ".$phpAds_config['tbl_conversionlog'].
					" WHERE t_stamp>=".$begin_timestamp.
					" AND t_stamp<".$end_timestamp.
					" AND cnv_logstats='y'".
					" GROUP BY action_bannerid,action_zoneid,action_source";
	$conversion_result = phpAds_dbQuery($conversion_query)
		or $report.= "Could not perform SQL: ".$conversion_query."\n";
	
	while ($conversion_row = phpAds_dbFetchArray($conversion_result))
	{
		$stat_query = "UPDATE ".$phpAds_config['tbl_adstats'].
						" SET conversions=conversions+".$conversion_row['conversions'].
						" WHERE day='".$day."'".
						" AND hour=".$hour.
						" AND bannerid=".$conversion_row['action_bannerid'].
						" AND zoneid=".$conversion_row['action_zoneid'];
	    $stat_result = phpAds_dbQuery($stat_query)
	    	or $report.= " Could not perform SQL: ".$stat_query."\n";
	    
	    if (phpAds_dbAffectedRows($stat_result) < 1)
	    {
		    $stat_query = "INSERT INTO ".$phpAds_config['tbl_adstats'].
		    				" SET day='".$day."'".
		    				",hour=".$hour.
		    				",bannerid=".$conversion_row['action_bannerid'].
		    				",zoneid=".$conversion_row['action_zoneid'].
		    				",conversions=".$conversion_row['conversions'];
		    $stat_result = phpAds_dbQuery($stat_query)
		    	or $report.= " Could not perform SQL: ".$stat_query."\n";
	    }
	    $num_conversions += $conversion_row['conversions'];
	}
	$report .= "\tCounted ".$num_conversions." conversions in ".(time()-$time)." seconds.\n\n";
}


function phpAds_decrementCampaigns($day, $hour)
{
	global $phpAds_config, $report;
	
	//Next, Subtract the number of views for a particular banner...
	$report .= "\tDecrementing High Priority Campaigns...\n";
	$time = time();
	$num_views = 0;
	$num_clicks = 0;
	$num_conversions = 0;
	
	// Get campaign information
	$campaign_query = "SELECT".
						" campaignid".
						",clientid".
						",campaignname".
						",active".
						",views".
						",clicks".
						",conversions".
						",UNIX_TIMESTAMP(expire) AS expire_st".
						",UNIX_TIMESTAMP(activate) AS activate_st".
						",UNIX_TIMESTAMP(NOW()) AS current_st".
						" FROM ".$phpAds_config['tbl_campaigns'];
	$campaign_result = phpAds_dbQuery($campaign_query)
		or $report.= "Could not perform SQL: ".$campaign_query."\n";
					
	while ($campaign_row = phpAds_dbFetchArray($campaign_result))
	{
		$views = $campaign_row['views'];
		$clicks = $campaign_row['clicks'];
		$conversions = $campaign_row['conversions'];
		$active = $campaign_row['active'];
		
		if ( ($views > 0) || ($clicks > 0) || ($conversions > 0) )
		{
			$count_query =
				"SELECT".
				" SUM(views) AS sum_views".
				",SUM(clicks) AS sum_clicks".
				",SUM(conversions) AS sum_conversions".
				" FROM ".$phpAds_config['tbl_adstats'].
				",".$phpAds_config['tbl_banners'].
				" WHERE ".$phpAds_config['tbl_banners'].".bannerid=".$phpAds_config['tbl_adstats'].".bannerid".
				" AND ".$phpAds_config['tbl_banners'].".campaignid=".$campaign_row['campaignid'].
				" AND day='".$day."'".
				" AND hour=".$hour
			;
			
			$count_result = phpAds_dbQuery($count_query)
				or $report.= "Could not perform SQL: ".$count_query."\n";
	
			if ($count_row = phpAds_dbFetchArray($count_result))
			{
				if ($views > 0)
				{
					$views -= $count_row['sum_views'];
					if ($views < 1)
					{
						$views = 0;
						$active = 'f';
					}
					$num_views += $count_row['sum_views'];
				}
				if ($clicks > 0)
				{
					$clicks -= $count_row['sum_clicks'];
					if ($clicks < 1)
					{
						$clicks = 0;
						$active = 'f';
					}
					$num_clicks += $count_row['sum_clicks'];
				}
				if ($conversions > 0)
				{
					$conversions -= $count_row['sum_conversions'];
					if ($conversions < 1)
					{
						$conversions = 0;
						$active = 'f';
					}
					$num_conversions += $count_row['sum_conversions'];
				}
			}
		}
	
		// Check time status...
		if ( ($campaign_row['current_st'] < $campaign_row['activate_st']) ||
			 ($campaign_row['current_st'] > $campaign_row['expire_st'] && $campaign_row['expire_st'] != 0) )
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
	
			$update_query =
				"UPDATE ".$phpAds_config['tbl_campaigns'].
				" SET views=".$views.
				",clicks=".$clicks.
				",conversions=".$conversions.
				",active='".$active."'".
				" WHERE campaignid=".$campaign_row['campaignid']
			;
			
			phpAds_dbQuery($update_query)
				or $report.= "Could not perform SQL: ".$update_query."\n";
			
			$report .= "\tChanging campaign ".$campaign_row['campaignid'].":\n";
			$report .= "\t\tViews:  from ".$campaign_row['views']." to ".$views."\n";
			$report .= "\t\tClicks:  from ".$campaign_row['clicks']." to ".$clicks."\n";
			$report .= "\t\tConversions:  from ".$campaign_row['conversions']." to ".$conversions."\n";
			$report .= "\t\tActive Status:  from ".$campaign_row['active']." to ".$active."\n\n";
		}
	}	
	
	$report .= "\tDecremented a total of ".$num_views." views, ".$num_clicks." clicks, and ".$num_conversions." conversions in ".(time()-$time)." seconds.\n\n\n";
}

function phpAds_deleteVerboseStats($begin_timestamp, $end_timestamp)
{
	global $phpAds_config, $report;
	
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
}

function phpAds_checkStatsExist($day, $hour)
{
	global $phpAds_config, $report;
	
	$exists = false;
	$stats_query =
		"SELECT COUNT(*) AS stat_count".
		" FROM ".$phpAds_config['tbl_adstats'].
		" WHERE day=".$day.
		" AND hour=".$hour
	;
	
	$stats_result = phpAds_dbQuery($stats_query)
		or $report.="Could not perform SQL: ".$stats_query."\n";

	if ($stats_row = phpAds_dbFetchArray($stats_result))
	{
		$count = $stats_row['stat_count'];
		if ($count > 0)
			$exists = true;
	}
	
	return $exists;
}

function phpAds_logStatsDate($end_timestamp)
{
	global $phpAds_config;
	
	$time_query =
		"UPDATE ".$phpAds_config['tbl_config'].
		" SET statslastday=".$end_timestamp.
		",statslasthour=HOUR(".$end_timestamp.")"
	;
	
	$time_result = phpAds_dbQuery($time_query);
}
?>