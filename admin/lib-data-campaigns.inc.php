<?php // $Revision: 1.1 $

/************************************************************************/
/* phpAdsNew 2                                                          */
/* ===========                                                          */
/*                                                                      */
/* Copyright (c) 2001 by TOMO <groove@spencernetwork.org>               */
/* For more information visit: http://www.phpadsnew.com                 */
/************************************************************************/

function phpAds_getCampaignByCampaignID($campaignid)
{
	global $phpAds_config;
	
	$query =
		"SELECT *".
		" FROM ".$phpAds_config['tbl_campaigns'].
		" WHERE campaignid=".$campaignid
	;
	
	$campaigns = phpAds_getCampaign($query);
	return $campaigns[$campaignid];
}

function phpAds_getCampaign($query)
{
	$campaigns = array();
	
	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();

	while ($row = phpAds_dbFetchArray($res))
	{
		$campaigns[$row['campaignid']] = $row;
	}
	
	return $campaigns;
}

?>