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


// Public name of the plugin info function 
$plugin_info_function		= "Plugin_TrackerHistoryInfo";


// Public info function
function Plugin_TrackerHistoryInfo()
{
	global $strCampaignHistory, $strClient, $strCampaign, $strPluginCampaign, $strDelimiter;
	
	$plugininfo = array (
		"plugin-name"			=> "Tracker History",
		"plugin-description"	=> $strPluginCampaign,
		"plugin-author"			=> "Luis",
		"plugin-export"			=> "csv",
		"plugin-authorize"		=> phpAds_Admin+phpAds_Agency,
		"plugin-execute"		=> "Plugin_TrackerHistoryExecute",
		"plugin-import"			=> array (
			"clientid"			=> array (
				"title"					=> $strClient,
				"type"					=> "clientid-dropdown" ),
			"start"		=> array (
				"title"					=> "Start Date",
				"type"					=> "edit",
				"size"					=> 10,
				"default"				=> date("Y/m/d",mktime (0,0,0,date("m"),date("d")-7,  date("Y")))),
			"end"		=> array (
				"title"					=> "End Date",
				"type"					=> "edit",
				"size"					=> 10,
				"default"				=> date("Y/m/d",mktime (0,0,0,date("m"),date("d")-1,  date("Y")))),
			"delimiter"		=> array (
				"title"					=> $strDelimiter,
				"type"					=> "edit",
				"size"					=> 1,
				"default"				=> "," ) )
	);
	
	return ($plugininfo);
}



/*********************************************************/
/* Private plugin function                               */
/*********************************************************/

function Plugin_TrackerHistoryExecute($clientid, $start, $end, $delimiter=",")
{
	global $phpAds_config, $date_format;
	global $strCampaign, $strTotal, $strDay, $strViews, $strClicks, $strCTRShort;
	
	header ("Content-type: application/csv\nContent-Disposition: inline; filename=trackerhistory.csv");
	
	
	// get all trackers and group them by advertiser and campaign
	
		$res_trackers = phpAds_dbQuery("SELECT
										trackers.trackerid,
										trackers.trackername
									FROM
										".$phpAds_config['tbl_trackers']." as trackers
									WHERE
										trackers.clientid = ".$clientid."
									");

		$trackers = array();

		while ($row = phpAds_dbFetchArray($res_trackers)) {
			$trackers[$row['trackerid']] = array();
			$trackers[$row['trackerid']]['name'] = $row['trackername'];
		}

		$res_total_conversions = phpAds_dbQuery("SELECT
											trackers.trackerid,
											count(conversions.conversionid) as hits
										FROM
											".$phpAds_config['tbl_adconversions']." as conversions,
											".$phpAds_config['tbl_trackers']." as trackers
										WHERE
											trackers.trackerid = conversions.trackerid
											AND trackers.clientid = ".$clientid."
											AND conversions.t_stamp >= '".str_replace("/","",$start)."000000'
											AND conversions.t_stamp <= '".str_replace("/","",$end)."235959'
										GROUP BY
											conversions.trackerid
								");

		while ($row = phpAds_dbFetchArray($res_total_conversions))
			$trackers[$row['trackerid']]['total_conversions'] = $row['hits'];

		$res_conversions = phpAds_dbQuery("SELECT
											trackers.trackerid,
											count(*) as hits
										FROM
											".$phpAds_config['tbl_conversionlog']." as conversions,
											".$phpAds_config['tbl_trackers']." as trackers
										WHERE
											trackers.trackerid = conversions.trackerid
											AND trackers.clientid = ".$clientid."
											AND conversions.t_stamp >= '".str_replace("/","",$start)."000000'
											AND conversions.t_stamp <= '".str_replace("/","",$end)."235959'
											
										GROUP BY
											conversions.trackerid
								");

		while ($row = phpAds_dbFetchArray($res_conversions))
			$trackers[$row['trackerid']]['conversions'] = $row['hits'];

	//echo "<pre>";
	//print_r($trackers);
	//echo "</pre>";

	echo "Client: ".strip_tags(phpAds_getClientName($clientid))." - ".$start." - ".$end."\n\n";
	

	echo 	$GLOBALS['strName'].$delimiter.
			$GLOBALS['strID'].$delimiter.
			"Conversions".$delimiter.
			"Total Hits"."\n";									
	echo "\n";


	foreach($trackers as $id=>$tracker)
	{
			echo 	$tracker['name'].$delimiter.
					$id.$delimiter.
					$tracker['conversions'].$delimiter.
					$tracker['total_conversions'].$delimiter."\n";

	}
	
	
	
}

?>