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

// Use Historical Data to calculate inventory 28 days in the future...
function phpAds_CalculateInventory()
{
	$report = "";
	// Holds the estimated hour-by-hour views for the whole week of each zone...
	$zone_avg_hourly_views = array();
	// Now, get campaign information
	$campaigns = phpAds_getCampaignsByActiveFlag('t');
	foreach ($campaigns as $campaignid => $campaign_arr)
	{
		// Do not process low priority campaigns this way - they do not have a set number of views.
		if ($campaign_arr['priority'] != 'l')
		{
			// Filter any hours/days which are blocked by delivery limitations
			$campaign_slots = phpAds_getTimeFilterByCampaign($campaign_arr);
	
			// Get an array containing the zoneid, and % of views for each zone
			$zone_views = phpAds_getZonePercentageByCampaignID($campaignid, $zone_avg_hourly_views);
	
			foreach ($zone_views as $zoneid => $zone_percentage)
			{
				// Find the amount of campaign views that we need to allocate to this zone
				$total_campaign_views = $zone_percentage * $campaign_arr['views'];
	
				$total_zone_views = 0;
				foreach ($campaign_slots as $day => $campaign_slot_hour_arr)
				{
					foreach ($campaign_slot_hour_arr as $hour => $campaign_slot_arr)
					{
						$day_of_week = $campaign_slot_arr['day_of_week'];
						$total_zone_views += $campaign_slot_arr['valid'] * $zone_avg_hourly_views[$day_of_week][$hour];
					}
				}
				// Determine the percentage of total views available to the zone should be allocated to this campaign.
				$total_campaign_percentage = $total_campaign_views / $total_zone_views;
				
				// Fill in the zone views
				foreach ($campaign_slots as $day => $campaign_slot_hour_arr)
				{
					foreach ($campaign_slot_hour_arr as $hour => $campaign_slot_arr)
					{
						$day_of_week = $campaign_slot_arr['day_of_week'];
						$campaign_target_this_hour = $campaign_slot_arr['valid'] * $total_campaign_percentage * $zone_avg_hourly_views[$day_of_week][$hour];
						
						$query = "UPDATE ".$phpAds_config['tbl_targetstats'].
							" SET target=".$campaign_target_this_hour.
							" WHERE zoneid=".$zoneid.
							" AND campaignid=".$campaignid.
							" AND day='".$day."'".
							" AND hour=".$hour
						;
						$res = phpAds_dbQuery($query)
							or $report .= " Broken Query : ".$query."\n";
		
			    		if (phpAds_dbAffectedRows($res) < 1)
						{
							$query = "INSERT INTO ".$phpAds_config['tbl_targetstats'].
								" SET target=".$campaign_target_this_hour.
								",zoneid=".$zoneid.
								",campaignid=".$campaignid.
								" AND day='".$day."'".
								" AND hour=".$hour
							;
							$res = phpAds_dbQuery($query)
								or $report .= " Broken Query : ".$query."\n";
						}
					}
				}
			}
		}
	}
	
	/*
	// Sort
	foreach ($zones as $zoneid => $zone_arr)
	{
		// Check each zone to make sure that they are not oversold.
		phpAds_checkOversoldByZoneID($zoneid);
	}
	*/
	$report .= "Compiled inventory availability in ".(time()-$overall_time)." seconds.\n\n";
	
	return $report;
}

function phpAds_getTimeFilterByCampaign($campaign)
{
	/*
		1.  Get each hour block from now until the end of the campaign.
		2.  For each hour block, estimate the % of views that will be available for this campaign (currently only for hour and day restrictions)
	*/
	
	global $current_day, $current_hour, $current_unix_timestamp;
	
	// Assume that all hour/day slots can be used, and populate the array
	// campaign_slot_array[day][hour]['day_of_week']
	//                               ['valid']
	
	$query =
		"SELECT ".$phpAds_config['tbl_acls'].".bannerid as bannerid".
		",".$phpAds_config['tbl_acls'].".logical as logical".
		",".$phpAds_config['tbl_acls'].".type as type".
		",".$phpAds_config['tbl_acls'].".comparison as comparison".
		",".$phpAds_config['tbl_acls'].".data as data".
		",".$phpAds_config['tbl_acls'].".executionorder as executionorder".
		" FROM ".$phpAds_config['tbl_acls'].
		",".$phpAds_config['tbl_banners'].
		" WHERE ".$phpAds_config['tbl_banners'].".bannerid=".$phpAds_config['tbl_acls'].".bannerid".
		" AND ".$phpAds_config['tbl_banners'].".campaignid=".$campaign['campaignid'].
		" ORDER BY bannerid,executionorder"
	;
	$res = phpAds_dbQuery($query)
		or $report .= " Broken Query : ".$query."\n";
	
	$show = false;
	while ($row = phpAds_dbFetchArray($res))
	{
		
	}
	$time_slots = array();
	$end_timestamp = strtotime($campaign['expire']);
	$timestamp = $current_unix_timestamp;
	
	while ($timestamp < $end_timestamp)
	{
		$day_of_week = date('w', $timestamp);
		$day = date('Y-m-d',  $timestamp);
		$min_hour = ($timestamp == $current_unix_timestamp) ? $current_hour : 0;
					
		for ($hour=$min_hour; $hour<24; $hour++)
		{
			$time_slots[$day][$hour]['day_of_week'] = $day_of_week;
			$time_slots[$day][$hour]['valid'] = 1;
		}
		
		$timestamp += (60*60*24);
	}		
	
	return $time_slots;
}

function phpAds_getHourlyViewsByZoneID($zoneid)
{
	$overall_time = time();
	/* The methodology is this:
		1.  Get the average number of views for this zone (over the past 28 days).
		2.  Get the trend for each day of the week and apply to the average number of views.
		3.  Get the running 7 day average trend against the average number of views.
		4.  Get the running 24 hour average trend against the average number of views.
		5.  Get the 1 hour trend against the average number of views.
		6.  Average the trends together, and apply toward the avg number of views.
		7.  Use this trend to extrapolate 28 days into the future.
	*/
	$zone_time = time();
	$report .= "Compiling Statistics for zone ".$zoneid."...\n";
	// Get the number of days to use for historical stats (at most 28)
	$days_running = phpAds_determineDaysRunningByZoneID($zoneid, $report);
	// Get the number of days that stats are available for this zone
	$hourly_views = phpAds_getHourlyViewHistoryByZoneID($zoneid, $days_running, $report);
	// Get the average views for every hour in each day of the week.
	$avg_hourly_views = phpAds_getAverageHourlyViews($hourly_views, $days_running, $report);
	// Get the trend
	$trend = phpAds_getTrend($hourly_views, $avg_hourly_views, $days_running, $report);
	// Adjust the average hourly views by the trend...
	$avg_hourly_views = phpAds_adjustAverageHourlyViews($avg_hourly_views, $trend);
	$zone_avg_hourly_views[$zoneid] = $avg_hourly_views;
	
	// Extrapolate 28 days in the future with this data
	phpAds_predictFutureTraffic($zoneid, $hourly_views, $avg_hourly_views, $trend, $days_running, $report);
	$report .= "Compiled zone ".$zoneid." information in ".(time()-$zone_time)." seconds.\n";		
	$report .= "\n";
}
	
function phpAds_getZonePercentageByCampaignID($campaignid, &$zone_avg_hourly_views)
{
	/*
		1.  Use historical data to estimate the % views made via direct selection vs. zone selection
		2.  If there is no historical data, determine if a campaign is linked to zones.
			a.  If so, then assume 100% of views will come from zones.
			b.  If not, then assume 100% of views will come via direct selection.
	*/ 
	global $phpAds_config, $current_day;

	// Try to estimate how many of the views for this campaign are going to come via direct selection vs zones...
	$query = "SELECT".
		" IF(zoneid>0, 'zone', 'direct') as zone_type".
		",SUM(views) AS sum_views".
		" FROM ".$phpAds_config['tbl_adstats'].
		",".$phpAds_config['tbl_banners'].
		" WHERE day > DATE_SUB('".$current_day."', INTERVAL 7 DAY)".
		",AND ".$phpAds_config['tbl_adstats'].".bannerid=".$phpAds_config['tbl_banners'].".bannerid".
		",AND ".$phpAds_config['tbl_banners'].".campaignid=".$campaignid.
		" GROUP BY zone_type"
	;
	$res = phpAds_dbQuery($query)
		or $report .= " Broken Query : ".$query."\n";
	
	$total_historical_direct_views = 0;
	$total_historical_views = 0;
	while ($row = phpAds_dbFetchArray($res))
	{
		$total_historical_views += $row['sum_views'];
		if ($row['zone_type'] == 'direct')
			$total_historical_direct_views += $row['sum_views'];
	}
	
	$query = "SELECT zoneid".
		" FROM ".$phpAds_config['tbl_zones'].
		",".$phpAds_config['tbl_banners'].
		" WHERE ".$phpAds_config['tbl_banners'].".campaignid=".$campaignid.
		" AND (".$phpAds_config['tbl_zones'].".what LIKE CONCAT('%bannerid:',bannerid,'%')".
		" OR ".$phpAds_config['tbl_zones'].".what LIKE CONCAT('%campaignid:',campaignid,'%'))"
	;
	$res = phpAds_dbQuery($query)
		or $report .= " Broken Query : ".$query."\n";
	
	$linked_zones = array();
	while ($row = phpAds_dbFetchArray($res))
	{
		$linked_zones[] = $row['zoneid'];
	}
	
	// Is this campaign linked to any zones?
	$linked_to_zones = (sizeof($linked_zones) > 0);
	
	if ($total_historical_views > 0)
	{
		$pct_historical_views_direct = ($linked_to_zones) ? $total_historical_direct_views / $total_historical_views : 1;
		$pct_historical_views_zone = 1 - $pct_historical_views_direct;
	}
	else
	{
		$pct_historical_views_zone = ($linked_to_zones) ? 1 : 0;
		$pct_historical_views_direct = 1 - $pct_historical_views_zone;
	}
	
	$pct_views_zone[0] = $pct_historical_views_direct;
	
	// Now that we have determined the % of direct vs. zone, we will divide up the zone views
	//   based upon the number of estimated views for each zone that they are linked to.
	
	if ($pct_historical_views_zone > 0)
	{
		$total_predicted_views = 0;
		
		foreach ($linked_zones as $zoneid)
		{
			$zone_total_views = 0;
			
			if (!isset($zone_avg_hourly_views[$zoneid]))
			{
				$zone_avg_hourly_views[$zoneid] = phpAds_getHourlyViewsByZoneID($zoneid);
			}
			
			$avg_hourly_views = $zone_avg_hourly_views[$zoneid];
			foreach ($avg_hourly_views as $day_of_week => $avg_arr)
			{
				foreach($avg_arr as $hour => $views)
				{
					$total_predicted_views += $views;
					$zone_total_predicted_views += $views;
				}
			}
			
			$zone_total_predicted_views[$zoneid] = $zone_total_predicted_views;
		}
		
		foreach ($zone_total_predicted_views as $zoneid => $zone_total_predicted_views)
		{
			$pct_views_zone[$zoneid] = $zone_total_predicted_views / $total_predicted_views;
		}
	}
	
	
	return $pct_views_zone;
}

function phpAds_getCampaignsByActiveFlag($flag)
{
	global $phpAds_config;
	
	$campaigns = array();
	
	$query = "SELECT *".
		" FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE active='".$flag."'"
	;
	$res = phpAds_dbQuery($query)
		or $report .= " Broken Query : ".$query."\n";
	
	while ($row = phpAds_dbFetchArray($res))
	{
		$campaigns[$row['campaignid']] = $row;
	}
	
	return $campaigns;
}
		
function phpAds_getZoneIDArray()
{
	global $phpAds_config;
	
	$zones = array();
	$res = phpAds_dbQuery("SELECT zoneid FROM ".$phpAds_config['tbl_zones']);

	while ($row = phpAds_dbFetchArray($res))
	{
		$zones[] = $row['zoneid'];
	}
	
	return $zones;
}

function phpAds_determineDaysRunningByZoneID($zoneid, &$report)
{
	global $phpAds_config, $current_day;

	$days_running = -1;

	$query =
		"SELECT".
		" IFNULL( (TO_DAYS(".$current_day.")-TO_DAYS(MIN(day))), -1) as days_running".
		" FROM ".$phpAds_config['tbl_adstats'].
		" WHERE zoneid=".$zoneid
	;
	$res = phpAds_dbQuery($query)
		or $report .= " Broken Query : ".$query."\n";
	
	if ($row = phpAds_dbFetchArray($res))
	{
		$days_running = $row['days_running'];
	}
	
	if ($days_running > -1)
	{
		$report .= "  This zone has stats for a total of ".$days_running." days to predict traffic.\n";
	}
	else
	{
		$report .= "  There are no statistics for this zone to predict traffic!\n";
	}
	// Chop off the last day so that there is a full day at the end.
	if ($days_running > 0)
		$days_running--;
	if ($days_running > 28)
		$days_running = 28;
	
	if ($days_running > -1)
		$report .= "  Of this total, the most recent ".$days_running." days will be used predict traffic.\n";
	
	return $days_running;
}

function phpAds_getHourlyViewHistoryByZoneID($zoneid, $days_running, &$report)
{
	/* Return an array of the past number of days of hourly stats, as well as the stats up to now today.
		The format of the array is:
			$views[days_ago]
	*/
	
	global $phpAds_config, $phpAds_dbmsname, $current_day, $current_unix_timestamp;

	$views = array();
	
	if ($days_running > -1)
	{
		// Initialise the array...
		for ($i=0; $i<=$days_running; $i++)
		{
			$timestamp = $current_unix_timestamp - ($i*60*60*24);
			$day_of_week = date('w', $timestamp);
			$day = date('Y-m-d',  $timestamp);
			$hour = date('G', $timestamp);
			
			$views[$i]['views'] = 0;
			$views[$i]['day'] = $day;
			$views[$i]['day_of_week'] = $day_of_week;
						
			$max_hour = ($i==0) ? $hour : 24;
	
			for ($j=0; $j<$max_hour; $j++)
			{
				$views[$i]['hour'][$j] = 0;
			}
		}
		
		$query =
			"SELECT".
			" (TO_DAYS('".$current_day."')-TO_DAYS(day)) AS days_ago".
			",hour".
			",SUM(views) AS sum_views".
			" FROM ".$phpAds_config['tbl_adstats'].
			" WHERE day >= DATE_SUB('".$current_day."', INTERVAL ".$days_running." DAY)".
			" AND zoneid=".$zoneid.
			" GROUP BY day,hour"
		;
		$res = phpAds_dbQuery($query)
			or $report .= " Broken Query : ".$query."\n";
		
		while ($row = phpAds_dbFetchArray($res))
		{
			$views[$row['days_ago']]['views'] += $row['sum_views'];
			$views[$row['days_ago']]['hour'][$row['hour']] += $row['sum_views'];
		}
	}
	
	return $views;
}

function phpAds_getAverageHourlyViews($views, $days_running, &$report)
{
	$daysofweek = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");
	
	// Initialise avg hourly views for each day of the week
	for ($i=0; $i<7; $i++)
	{
		for ($j=0; $j<24; $j++)
		{
			$total_view_arr[$i][$j] = 0;
			$num_arr[$i][$j] = 0;
			$avg_view_arr[$i][$j] = 0;
		}
	}
	
	if ($days_running > -1)
	{
		foreach ($views as $day_arr)
		{
			foreach($day_arr['hour'] as $hour => $hourly_views)
			{
				$total_view_arr[$day_arr['day_of_week']][$hour] += $hourly_views;
				$num_arr[$day_arr['day_of_week']][$hour]++;
			}
		}
		
		// Compute the averages
		for ($i=0; $i<7; $i++)
		{
			$report .= "  Rounded Averages for ".$daysofweek[$i]."...\n  ";
			for ($j=0; $j<24; $j++)
			{
				if ($num_arr[$i][$j] > 0)
					$avg_view_arr[$i][$j] = $total_view_arr[$i][$j] / $num_arr[$i][$j];
				
				$report .= round($avg_view_arr[$i][$j])."  ";
			}
			
			$report .= "\n";
		}
	}
		
	return $avg_view_arr;
}

function phpAds_getTrend($hourly_views, $avg_hourly_views, $days_running, &$report)
{
	/* Get a trend for traffic:
		1.  Full trend
		2.  Trend over the past 7 days
		3.  Trend over the past 1 day
		4.  Trend over the past 1 hour
		5.  Add them up
	*/
	
	$total_weekly_trend = 0;
	$num_weekly_trend = 0;
	$avg_weekly_trend = 1;
	
	$total_daily_trend = 0;
	$num_daily_trend = 0;
	$avg_daily_trend = 1;
	
	$total_hourly_trend = 0;
	$num_hourly_trend = 0;
	$avg_hourly_trend = 1;
	
	if ($days_running > -1)
	{
		for ($i=0; $i<=7; $i++)
		{
			for ($j=23; $j>=0; $j--)
			{
				if (isset($hourly_views[$i]['hour'][$j]))
				{
					$real_views_this_hour = $hourly_views[$i]['hour'][$j];
					$avg_views_this_hour = $avg_hourly_views[$hourly_views[$i]['day_of_week']][$j];
					
					$trend_this_hour = ($avg_views_this_hour > 0) ? $real_views_this_hour/$avg_views_this_hour : 1;
					
					if ($num_hourly_trend == 0)
					{
						$total_hourly_trend += $trend_this_hour;
						$num_hourly_trend++;
					}
					if ($num_daily_trend < 24)
					{
						$total_daily_trend += $trend_this_hour;
						$num_daily_trend++;
					}
					if ( ($i >= 1) && ($i <= 7) )
					{
						$total_weekly_trend += $trend_this_hour;
						$num_weekly_trend++;
					}
				}
			}
		}
		
		$avg_hourly_trend = $total_hourly_trend / $num_hourly_trend;
		$avg_daily_trend = $total_daily_trend / $num_daily_trend;
		$avg_weekly_trend = $total_weekly_trend / $num_weekly_trend;
	}
	
	$report .= "  The trend for the past hour is ".$avg_hourly_trend.".\n";
	$report .= "  The trend for the past 24 hours is ".$avg_daily_trend.".\n";
	$report .= "  The trend for the past 7 days is ".$avg_weekly_trend.".\n";
	
	$trend = (1 + $avg_hourly_trend + $avg_daily_trend + $avg_weekly_trend) / 4;
	$report .= "  This makes an overall trend of ".$trend.".\n";
	
	return $trend;
}

function phpAds_adjustAverageHourlyViews($avg_hourly_views, $trend)
{
	for ($i=0; $i<7; $i++)
	{
		for ($j=0; $j<24; $j++)
		{
			$avg_hourly_views[$i][$j] = $avg_hourly_views[$i][$j] * $trend;
		}
	}
}

function phpAds_predictFutureTraffic($zoneid, $hourly_views, $avg_hourly_views, $trend, $days_running, $report)
{
	global $phpAds_config;
	
	$day_of_week = 0;

	for ($i=0; $i<=28; $i++)
	{
		if ($i==0)
		{
			$day_of_week = ($days_running > -1) ? $hourly_views[$i]['day_of_week'] : 0;
		}
		else
		{
			$day_of_week++;
			if ($day_of_week > 6)
				$day_of_week = 0;
		}
			
		for ($j=0; $j<24; $j++)
		{
			if (!isset($hourly_views[$i]['hour'][$j]))
			{
				$target_this_hour = ($days_running > -1) ? round($avg_hourly_views[$i][$j] * $trend) : 0;

				$query = "UPDATE ".$phpAds_config['tbl_targetstats'].
					" SET target=".$target_this_hour.
					" WHERE zoneid=".$zoneid.
					" AND campaignid=0".
					" AND day=DATE_ADD('".$current_day."', INTERVAL ".$i." DAY)".
					" AND hour=".$j
				;
				$res = phpAds_dbQuery($query)
					or $report .= " Broken Query : ".$query."\n";

	    		if (phpAds_dbAffectedRows($res) < 1)
				{
					$query = "INSERT INTO ".$phpAds_config['tbl_targetstats'].
						" SET target=".$target_this_hour.
						",zoneid=".$zoneid.
						",campaignid=0".
						",day=DATE_ADD('".$current_day."', INTERVAL ".$i." DAY)".
						",hour=".$j
					;
					$res = phpAds_dbQuery($query)
						or $report .= " Broken Query : ".$query."\n";
				}
			}
		}
		
	}
}

?>