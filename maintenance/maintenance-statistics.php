<?php // $Revision: 1.7 $

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

// Get the beginning Timestamp
$begin_timestamp = "";

$time_query =
	"SELECT DATE_FORMAT(DATE_ADD(statslastday, INTERVAL statslasthour HOUR)),'%Y%m%d%H%i%s') AS start_timestamp".
	",DATE_FORMAT(DATE_ADD(statslastday, INTERVAL statslasthour+1 HOUR)),'%Y%m%d%H%i%s') AS end_timestamp".
	",statslastday as start_day".
	",statslasthour as start_hour".
	" FROM ".$phpAds_config['tbl_config'].
	" WHERE DATE_ADD(statslastday, INTERVAL statslasthour+1 HOUR) < NOW()"
;

$time_result = phpAds_dbQuery($time_query)
	or $report.= "Could not perform SQL: ".$time_query."\n";

if ($time_row = phpAds_dbFetchArray($time_result))
{
	$begin_timestamp = $time_row['start_timestamp'];
	$end_timestamp = $time_row['end_timestamp'];
	$day = $time_row['start_day'];
	$hour = $time_row['start_hour'];

	$report .= "Processing statistics for hour ".$hour." on ".$day."...\n\n";
	
	// If we are rebuilding a particular hour,
	// then increment back any inventory to campaigns and delete the stats for this hour
	$regen = false; // Will build later...
	if ($regen)
	{
		$report .= " REGENERATING!!/n/n";
		phpAds_undoInventory($day, $hour, $report);
		phpAds_deleteCompactStats($day, $hour, $report);
	}
	
	// Count the total views for this hour
	phpAds_countViews($begin_timestamp, $end_timestamp, $day, $hour, $report);
	// Count the total clicks for this hour
	phpAds_countClicks($begin_timestamp, $end_timestamp, $day, $hour, $report);
	// Count the total conversions for this hour
	phpAds_countConversions($begin_timestamp, $end_timestamp, $day, $hour, $report);
	// Decrement the campaigns with our new statistics
	phpAds_decrementCampaigns($day, $hour, $report);
	// Clean up (if user wants...)
	phpAds_deleteVerboseStats($begin_timestamp, $end_timestamp, $report);
	
}

function phpAds_countViews($begin_timestamp, $end_timestamp, $day, $hour, $report)
{
	//Process views...
	$num_views = 0;
	$time = time();
	$report .= "Counting the verbose views between ".$begin_timestamp." and ".$end_timestamp."...\n";
	$view_query = "SELECT bannerid".
					",zoneid".
					",source".
					",count(*) as views".
					" FROM ".$phpAds_config['tbl_adviews'].
					" WHERE t_stamp>=".$begin_timestamp.
					" AND t_stamp<".$end_timestamp.
					" GROUP BY bannerid,zoneid,source";
	$view_result = phpAds_dbQuery($view_query)
		or $report.= "Could not perform SQL: ".$view_query."\n";
	
	while ($view_row = phpAds_dbFetchArray($view_result))
	{
		
	    $stat_query = "INSERT INTO ".$phpAds_config['tbl_adstats'].
	    				" SET day=".$day.
	    				",hour=".$hour.
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
							" WHERE day=".$day.
							" AND hour=".$hour.
							" AND bannerid=".$view_row['bannerid'].
							" AND zoneid=".$view_row['zoneid'].
							" AND source='".$view_row['source']."'";
		    $stat_result = phpAds_dbQuery($stat_query)
		    	or $report.= " Could not perform SQL: ".$stat_query."\n";
	    }
	    $num_views += $view_row['views'];
	}
	$report .= "Counted ".$num_views." views in ".(time()-$time)." seconds.\n";
}


function phpAds_countClicks($begin_timestamp, $end_timestamp, $day, $hour, $report)
{
	//Process clicks...
	$num_clicks = 0;
	$time = time();
	$report .= "Counting the verbose clicks between ".$begin_timestamp." and ".$end_timestamp."...\n";
	$click_query = "SELECT bannerid".
					",zoneid".
					",source".
					",count(*) as clicks".
					" FROM ".$phpAds_config['tbl_adclicks'].
					" WHERE t_stamp>=".$begin_timestamp.
					" AND t_stamp<".$end_timestamp.
					" GROUP BY bannerid,zoneid,source";
	$click_result = phpAds_dbQuery($click_query)
		or $report.= "Could not perform SQL: ".$click_query."\n";
	
	while ($click_row = phpAds_dbFetchArray($click_result))
	{
		$stat_query = "UPDATE ".$phpAds_config['tbl_adstats'].
						" SET clicks=clicks+".$click_row['clicks'].
						" WHERE day=".$day.
						" AND hour=".$hour.
						" AND bannerid=".$click_row['bannerid'].
						" AND zoneid=".$click_row['zoneid'].
						" AND source='".$click_row['source']."'";
	    $stat_result = phpAds_dbQuery($stat_query)
	    	or $report.= " Could not perform SQL: ".$stat_query."\n";
	    
	    if (phpAds_dbAffectedRows($stat_result) < 1)
	    {
		    $stat_query = "INSERT INTO ".$phpAds_config['tbl_adstats'].
		    				" SET day=".$day.
		    				",hour=".$hour.
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
}

function phpAds_countConversions($begin_timestamp, $end_timestamp, $day, $hour, $report)
{
	global $phpAds_config;
	
	//Process conversions...
	$num_conversions = 0;
	$time = time();
	unset($trackercampaignrules);
	
	$report .= "Counting the verbose conversions between ".$begin_timestamp." and ".$end_timestamp."...\n";

	$conversion_query =
		"SELECT cookieid".
		",t_stamp".
		",trackerid".
		",host".
		",country".
		" FROM ".$phpAds_config['tbl_adconversions'].
		" WHERE cookieid!=''".
		" AND t_stamp>=".$begin_timestamp.
		" AND t_stamp<".$end_timestamp.
		" ORDER BY trackerid"
	;
	
	$res = phpAds_dbQuery($conversion_query) or $report.= "Could not perform SQL: ".$conversion_query."\n";
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$cookieid = $row['cookieid'];
		$t_stamp = $row['t_stamp'];
		$trackerid = $row['trackerid'];
		$host = $row['host'];
		$country = $row['country'];
		
		if (!isset($trackercampaignrules[$trackerid]))
		{
			$rule_query = 
				"SELECT ".$phpAds_config['tbl_conversionrules'].".campaignid as campaignid".
				",".$phpAds_config['tbl_conversionrules'].".conversiontype as conversiontype".
				",".$phpAds_config['tbl_conversionrules'].".action as action".
				",".$phpAds_config['tbl_conversionrules'].".delay_seconds as delay_seconds".
				" FROM ".$phpAds_config['tbl_conversionrules'].
				",".$phpAds_config['tbl_campaigns_trackers'].
				" WHERE ".$phpAds_config['tbl_campaigns_trackers'].".trackerid=".$trackerid.
				" AND ".$phpAds_config['tbl_campaigns_trackers'].".campaignid=".$phpAds_config['tbl_conversionrules'].".campaignid".
				" ORDER BY campaignid,conversiontype,action";
			;
	
			$res2 = phpAds_dbQuery($rule_query) or $report.= "Could not perform SQL: ".$rule_query."\n";
	
			unset($campaignrules);
			while ($row2 = phpAds_dbFetchArray($res2))
			{
				$campaignid = $row2['campaignid'];
				$campaignrules[$campaignid][] = $row2;
			}
			
			$trackercampaignrules[$trackerid] = $campaignrules;
		}
		
		// Get the rules for this specific campaign
		$campaignrules = $trackercampaignrules[$trackerid];

		for ($i=0; $i<sizeof($campaignrules) i++)
		{
			// Go through each rule and see if it fits.
			$action = $campaignrules[$i]['action'];
			$campaignid = $campaignrules[$i]['campaignid'];
			$conversiontype = $campaignrules[$i]['conversiontype'];
			$delay_seconds = $campaignrules[$i]['delay_seconds'];
			
			if ($action == 'view')
			{
				$action_query =
					"SELECT t_stamp".
					",bannerid".
					",zoneid".
					",host".
					",source".
					",country".
					" FROM ".$phpAds_config['tbl_adviews'].
					" WHERE cookieid=".$cookieid.
					" AND t_stamp>= DATE_SUB(".$t_stamp.", INTERVAL ".$delay_seconds." SECOND)".
					" AND t_stamp<".$t_stamp.
					" ORDER BY t_stamp DESC"
				;
			}
			elseif ($action == 'click')
			{
				$action_query =
					"SELECT t_stamp".
					",bannerid".
					",zoneid".
					",host".
					",source".
					",country".
					" FROM ".$phpAds_config['tbl_adclicks'].
					" WHERE cookieid=".$cookieid.
					" AND t_stamp>= DATE_SUB(".$t_stamp.", INTERVAL ".$delay_seconds." SECOND)".
					" AND t_stamp<".$t_stamp.
					" ORDER BY t_stamp DESC"
				;
			}
			
			if ( ($action == 'view') || ($action == 'click') )
			{
				$res3 = phpAds_dbQuery($action_query) or $report.= "Could not perform SQL: ".$action_query."\n";
				
				if ($row3 = phpAds_dbFetchArray($res3))
				{
					$action_t_stamp = $row3['t_stamp'];
					$action_bannerid = $row3['bannerid'];
					$action_zoneid = $row3['zoneid'];
					$action_host = $row3['host'];
					$action_source = $row3['source'];
					$action_country = $row3['country'];
					// Found an item which passed the rules.
					// Now, log this item
					$log_query =
						"INSERT INTO ".$phpAds_config['tbl_conversionlog'].
						" (campaignid".
						",trackerid".
						",cookieid".
						",t_stamp".
						",host".
						",country".
						",conversiontype".
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
						",".$cookieid.
						",".$t_stamp.
						",".$host.
						",".$country.
						",".$conversiontype.
						",".$action.
						",".$action_bannerid.
						",".$action_zoneid.
						",".$action_t_stamp.
						",".$action_host.
						",".$action_source.
						",".$action_country.")"
					;
		
					phpAds_dbQuery($log_query) or $report.= "Could not perform SQL: ".$log_query."\n";
				
					$conversionlogid = phpAds_dbInsertID();

					$conversion_update_query =
						"UPDATE ".$phpAds_config['tbl_adconversions'].
						" SET conversionlogid=".$conversionlogid.
						" WHERE cookieid=".$cookieid.
						" AND t_stamp=".$t_stamp.
					;
					
					phpAds_dbQuery($conversion_query) or $report.= "Could not perform SQL: ".$conversion_query."\n";
					
					$num_conversions++;
					break;
					
				}
			}
		}
	}
	$report .= "Counted ".$num_conversions." clicks in ".(time()-$time)." seconds.\n\n";
}


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