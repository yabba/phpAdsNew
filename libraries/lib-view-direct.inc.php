<?php // $Revision: 2.6 $

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


require_once('lib-io.inc.php');

// Set define to prevent duplicate include
define ('LIBVIEWDIRECT_INCLUDED', true);


/*********************************************************/
/* Get a banner                                          */
/*********************************************************/

function phpAds_fetchBannerDirect($remaining, $clientid = 0, $campaignid = 0, $context = 0, $source = '', $richmedia = true)
{
	global $phpAds_config;

	/*
	//log
	ob_start();
	print_r($GLOBALS);
	$log = ob_get_contents();
	ob_end_clean();
	
	$fh = @fopen('/var/www/html/origin.awarez.net/cache/mydebug.log','a');
	@fwrite($fh, $log);
	@fclose($fh);
	*/


	
	// Get first part, store second part
	$what = strtok($remaining, '|');
	$remaining = strtok ('');
	
	
	// Expand paths to regular statements
	if (strpos($what, '/') > 0)
	{
		if (strpos($what, '@') > 0)
			list ($what, $append) = explode ('@', $what);
		else
			$append = '';
		
		$seperate  = explode ('/', $what);
		$expanded  = '';
		$collected = array();
		
		while (list(,$v) = each($seperate))
		{
			$expanded .= ($expanded != '' ? ',+' : '') . $v;
			$collected[] = $expanded . ($append != '' ? ',+'.$append : '');
		}
		
		$what = strtok(implode('|', array_reverse ($collected)), '|');
		$remaining = strtok('').($remaining != '' ? '|'.$remaining : '');
	}
	
	$cacheid = 'what='.$what.'&clientid='.$clientid.'&campaignid='.$campaignid.'&remaining='.($remaining == '' ? 'true' : 'false');
	
	
	
	// Get cache
	if (!defined('LIBVIEWCACHE_INCLUDED'))  include (phpAds_path.'/libraries/deliverycache/cache-'.$phpAds_config['delivery_caching'].'.inc.php');
	$cache = phpAds_cacheFetch ($cacheid);
	
	if (!$cache)
	{
		if (!defined('LIBVIEWQUERY_INCLUDED'))  include (phpAds_path.'/libraries/lib-view-query.inc.php');
		
		if ($campaignid > 0)
			$precondition = " AND ".$phpAds_config['tbl_campaigns'].".campaignid=".$campaignid." ";
		elseif ($clientid > 0)
			$precondition = " AND ".$phpAds_config['tbl_campaigns'].".clientid=".$clientid." ";
		else
			$precondition = '';
		
		$select = phpAds_buildQuery ($what, $remaining == '', $precondition);
		$res    = phpAds_dbQuery($select);
		
		
		// Build array for further processing...
		$rows = array();
		$prioritysum = 0;
		while ($tmprow = phpAds_dbFetchArray($res))
		{
			// weight of 0 disables the banner
			if ($tmprow['priority'])
			{
				$prioritysum += $tmprow['priority'];
				$rows[] = $tmprow; 
			}
		}
		
		$cache = array (
			$rows,
			$what,
			$prioritysum
		);
		
		phpAds_cacheStore ($cacheid, $cache);
		
		// Unpack cache
		list ($rows, $what, $prioritysum) = $cache;
	}
	else
	{
		// Unpack cache
		list ($rows, $what, $prioritysum) = $cache;
	}
	
	
	
	
	
	
	// Build preconditions
	$excludeBannerID = array();
	$excludeCampaignID = array();
	$includeBannerID = array();
	$includeCampaignID = array();
	
	if (is_array ($context))
	{
		for ($i=0; $i < count($context); $i++)
		{
			list ($key, $value) = each($context[$i]);
			{
				$type = 'bannerid';
				$valueArray = explode(':', $value);
				
				if (count($valueArray) == 1)
					list($value) = $valueArray;
				else
					list($type, $value) = $valueArray;
				
				if ($type == 'bannerid')
				{
					switch ($key)
					{
						case '!=': $excludeBannerID[$value] = true; break;
						case '==': $includeBannerID[$value] = true; break;
					}
				}
				
				if ($type == 'campaignid')
				{
					switch ($key)
					{
						case '!=': $excludeCampaignID[$value] = true; break;
						case '==': $includeCampaignID[$value] = true; break;
					}
				}
			}
		}
	}
	
	
	
	$maxindex = sizeof($rows);
	
	while ($prioritysum && sizeof($rows))
	{
		$low = 0;
		$high = 0;
		$ranweight = ($prioritysum > 1) ? mt_rand(0, $prioritysum - 1) : 0;
		
		for ($i=0; $i<$maxindex; $i++)
		{
			if (is_array($rows[$i]))
			{
				$low = $high;
				$high += $rows[$i]['priority'];
				
				if ($high > $ranweight && $low <= $ranweight)
				{
					$postconditionSucces = true;
					
					// Excludelist banners
					if (isset($excludeBannerID[$rows[$i]['bannerid']]))
						$postconditionSucces = false;
					
					// Excludelist campaigns
					elseif (isset($excludeCampaignID[$rows[$i]['clientid']]))
						$postconditionSucces = false;
					
					// Includelist banners
					elseif (sizeof($includeBannerID) &&
					    !isset ($includeBannerID[$rows[$i]['bannerid']]))
						$postconditionSucces = false;
					
					// Includelist campaigns
					elseif (sizeof($includeCampaignID) &&
					    !isset ($includeCampaignID[$rows[$i]['clientid']]))
						$postconditionSucces = false;
					
					// HTML or Flash banners
					elseif ($richmedia == false &&
					    ($rows[$i]['contenttype'] != 'jpeg' && $rows[$i]['contenttype'] != 'gif' && $rows[$i]['contenttype'] != 'png'))
						$postconditionSucces = false;
					
					// Blocked
					elseif (phpAds_isAdBlocked($rows[$i]['bannerid'], $rows[$i]['block']))
						$postconditionSucces = false;
					
					// Capped
					elseif (phpAds_isAdCapped($rows[$i]['bannerid'], $rows[$i]['capping'], $rows[$i]['session_capping']))
						$postconditionSucces = false;
					
					
					if ($postconditionSucces == false)
					{
						// Failed one of the postconditions
						// Delete this row and adjust $prioritysum
						$prioritysum -= $rows[$i]['priority'];
						$rows[$i] = '';
						
						// Break out of the for loop to try again
						break;
					}
					
					// Banner was not on exclude list
					// and was on include list (if one existed)
					// Now continue with ACL check
					
					if ($phpAds_config['acl'])
					{
						if (phpAds_aclCheck($rows[$i], $source))
						{
							$rows[$i]['zoneid'] = 0;
							return ($rows[$i]);
						}
						
						// Matched, but phpAds_aclCheck failed.
						// Delete this row and adjust $prioritysum
						$prioritysum -= $rows[$i]['priority'];
						$rows[$i] = '';
						
						// Break out of the for loop to try again
						break;
					}
					else
					{
						// Don't check ACLs, found banner!
						$rows[$i]['zoneid'] = 0;
						return ($rows[$i]);
					}
				}
			}
		}
	}
	
	return ($remaining);
}


?>