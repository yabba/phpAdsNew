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


	// get active campaigns which are set to be optimized
	$result_campaigns	= phpAds_dbQuery("
	
								SELECT
									campaignname as name,
									campaignid as id,
									active,
									optimise
								FROM 
									".$phpAds_config['tbl_campaigns']."
								WHERE 
									active		= 't' AND
									optimise 	= 't'
								LIMIT
									15
									
						");

	while ($campaigns = phpAds_dbFetchArray($result_campaigns))
	{
	
		// get banners in this campaign
		$result_banners	= phpAds_dbQuery("
		
									SELECT
										b.bannerid	 		as bannerid,
										b.campaignid		as campaignid,
										b.weight 			as weight,
										sum(s.views)		as views,
										sum(s.clicks) 		as clicks,
										sum(s.conversions) 	as conversions
									FROM 
										".$phpAds_config['tbl_banners']." as b,
										".$phpAds_config['tbl_adstats']." as s									
									WHERE 
										b.bannerid = s.bannerid AND
										b.campaignid = ".$campaigns['id']."
									GROUP BY 
										b.bannerid
							");
							
							
		if (mysql_num_rows($result_banners) >1)							
		{

			$banners_opt = array();
			
			// create array with banners for this campaign
			while ($banners = phpAds_dbFetchArray($result_banners))
				$banners_opt[] = $banners;

			// sort array of banners by CTR descending
			phpAds_sortArray($banners_opt, 'conversions', false);

			// reset all banners in this campaign to weight=1
			$update	= phpAds_dbQuery("
		
									UPDATE
										".$phpAds_config['tbl_banners']."
									SET
										weight = 1
									WHERE
										campaignid = ".$campaigns['id']."
							");
							
			// change the banner with most conversions to weight=10				
			$update	= phpAds_dbQuery("
		
									UPDATE
										".$phpAds_config['tbl_banners']."
									SET
										weight = 10
									WHERE
										bannerid = ".$banners_opt[0]['bannerid']."
							");
	
		}
	}
?>