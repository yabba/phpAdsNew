<?php // $Revision: 2.4 $

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
$plugin_info_function		= "Plugin_GlobalhistoryInfo";


// Public info function
function Plugin_GlobalhistoryInfo()
{
	global $strGlobalHistory, $strPluginGlobal, $strDelimiter;
	
	$plugininfo = array (
		"plugin-name"			=> $strGlobalHistory,
		"plugin-description"	=> $strPluginGlobal,
		"plugin-author"			=> "Niels Leenheer",
		"plugin-export"			=> "csv",
		"plugin-authorize"		=> phpAds_Admin+phpAds_Agency,
		"plugin-execute"		=> "Plugin_GlobalhistoryExecute",
		"plugin-import"			=> array (
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

function Plugin_GlobalhistoryExecute($delimiter=",")
{
	global $phpAds_config, $date_format;
	global $strGlobalHistory, $strTotal, $strDay, $strViews, $strClicks, $strCTRShort;
	
	header ("Content-type: application/csv\nContent-Disposition: \"inline; filename=globalhistory.csv\"");
	
	if(phpAds_isUser(phpAds_Admin))
	{
	$res_query = "
		SELECT
			DATE_FORMAT(day, '".$date_format."') as day,
			SUM(views) AS adviews,
			SUM(clicks) AS adclicks
		FROM
			".$phpAds_config['tbl_adstats']."
		GROUP BY
			day
	";
	
	}
	else
	{
		$res_query = "SELECT
						DATE_FORMAT(s.day, '".$date_format."') as day,
						SUM(s.views) AS adviews,
						SUM(s.clicks) AS adclicks
					FROM
						".$phpAds_config['tbl_adstats']." 	as s,
						".$phpAds_config['tbl_banners']." 	as b,
						".$phpAds_config['tbl_campaigns']." as m,
						".$phpAds_config['tbl_clients']." 	as c
					WHERE
						s.bannerid 		= b.bannerid AND
						b.campaignid 	= m.campaignid AND
						m.clientid 		= c.clientid AND
						c.agencyid 		= " . phpAds_getUserID() ."
					GROUP BY
						day";
	}

	
	$res_banners = phpAds_dbQuery($res_query) or phpAds_sqlDie();
	
	while ($row_banners = phpAds_dbFetchArray($res_banners))
	{
		$stats [$row_banners['day']]['views'] = $row_banners['adviews'];
		$stats [$row_banners['day']]['clicks'] = $row_banners['adclicks'];
	}
	
	echo $strGlobalHistory."\n\n";
	
	echo $strDay.$delimiter.$strViews.$delimiter.$strClicks.$delimiter.$strCTRShort."\n";
	
	$totalclicks = 0;
	$totalviews = 0;
	
	if (isset($stats) && is_array($stats))
	{
		for (reset($stats);$key=key($stats);next($stats))
		{
			$row = array();
			
			//$key = implode('/',array_reverse(split('[-]',$key)));
			
			$row[] = $key;
			$row[] = $stats[$key]['views'];
			$row[] = $stats[$key]['clicks'];
			$row[] = phpAds_buildCTR ($stats[$key]['views'], $stats[$key]['clicks']);
			
			echo implode ($delimiter, $row)."\n";
			
			$totalclicks += $stats[$key]['clicks'];
			$totalviews += $stats[$key]['views'];
		}
	}
	
	echo "\n";
	echo $strTotal.$delimiter.$totalviews.$delimiter.$totalclicks.$delimiter.phpAds_buildCTR ($totalviews, $totalclicks)."\n";
}

?>