<?php 

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


function phpAds_fetchJavascriptVariables($trackerid)
{
	global $phpAds_config;
	
	include (phpAds_path.'/libraries/deliverycache/cache-'.$phpAds_config['delivery_caching'].'.inc.php');
	
	// Get cache
	$cache = phpAds_cacheFetch ('what=tracker:' . $trackerid);
	
	if (!$cache)
	{
		$variables_result = phpAds_dbQuery("
								SELECT 
									variableid,
									name,
									variabletype
								FROM 
									".$phpAds_config['tbl_variables']."
								WHERE 
									trackerid='".$trackerid."'"
							);

		while ($variable = phpAds_dbFetchArray($variables_result))
			$cache[$variable['variableid']] = array('name' => $variable['name'], 'variabletype' => $variable['variabletype']);
			
		if (count(cache) > 0)
			phpAds_cacheStore ('what=tracker:'.$trackerid, $cache);
		else
			return;
	}
	
	return ($cache);
}

?>