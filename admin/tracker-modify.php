<?php // $Revision: 1.3 $

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



// Include required files
require ("config.php");
require ("lib-storage.inc.php");
require ("lib-zones.inc.php");


// Register input variables
phpAds_registerGlobal (
	 'duplicate'
	,'moveto'
	,'returnurl'
);


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);

if (phpAds_isUser(phpAds_Agency))
{
	$res = phpAds_dbQuery(
		"SELECT clientid".
		" FROM ".$phpAds_config['tbl_clients']." AS c".
		",".$phpAds_config['tbl_trackers']." AS t".
		" WHERE t.trackerid=c.trackterid".
		" AND t.trackerid=".$trackerid.
		" AND c.agencyid=".phpAds_getUserID()
	) or phpAds_sqlDie();
	
	if (phpAds_dbNumRows($res) == 0)
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}


/*********************************************************/
/* Main code                                             */
/*********************************************************/

if (isset($trackerid) && $trackerid != '')
{
	if (isset($moveto) && $moveto != '')
	{
		// Delete any campaign-tracker links
		$res = phpAds_dbQuery(
			"DELETE FROM ".$phpAds_config['tbl_campaigns_trackers'].
			" WHERE trackerid=".$trackerid
		) or phpAds_sqlDie();

		// Move the campaign
		$res = phpAds_dbQuery(
			"UPDATE ".$phpAds_config['tbl_trackers'].
			" SET clientid=".$moveto.
			" WHERE trackerid=".$trackerid
		) or phpAds_sqlDie();
		
		Header ("Location: ".$returnurl."?clientid=".$moveto."&trackerid=".$trackerid);
		exit;
	}
	elseif (isset($duplicate) && $duplicate == 'true')
	{
		// Duplicate the zone
		
		$res = phpAds_dbQuery(
			"SELECT *".
			" FROM ".$phpAds_config['tbl_trackers'].
			" WHERE trackerid=".$trackerid
		) or phpAds_sqlDie();
		
		
		if ($row = phpAds_dbFetchArray($res))
		{
			// Get names
			if (ereg("^(.*) \([0-9]+\)$", $row['trackername'], $regs))
				$basename = $regs[1];
			else
				$basename = $row['trackername'];
			
			$names = array();
			
			$res = phpAds_dbQuery(
				"SELECT *".
				" FROM ".$phpAds_config['tbl_trackers']
			) or phpAds_sqlDie();
			
			while ($name = phpAds_dbFetchArray($res))
				$names[] = $name['trackername'];
			
			
			// Get unique name
			$i = 2;
			
			while (in_array($basename.' ('.$i.')', $names))
				$i++;
			
			$row['trackername'] = $basename.' ('.$i.')';
			
			
			// Remove tracker
			unset($row['trackerid']);
	   		
			$values = array();
			
			while (list($name, $value) = each($row))
				$values[] = $name." = '".addslashes($value)."'";
			
	   		$res = phpAds_dbQuery("
		   		INSERT INTO
		   			".$phpAds_config['tbl_trackers']."
				SET
					".implode(", ", $values)."
	   		") or phpAds_sqlDie();
			
			$new_trackerid = phpAds_dbInsertID();
			
			// Copy any linked campaigns
			$res = phpAds_dbQuery(
				"SELECT".
				" campaignid".
				",trackerid".
				",logstats".
				",clickwindow".
				",viewwindow".
				" FROM ".$phpAds_config['tbl_campaigns_trackers'].
				" WHERE trackerid=".$trackerid
			) or phpAds_sqlDie();
			
			while($row = phpAds_dbFetchArray($res))
			{
				$res2 = phpAds_dbQuery(
					" INSERT INTO ".$phpAds_config['tbl_campaigns_trackers'].
					" SET campaignid=".$row['campaignid'].
					",trackerid=".$new_trackerid.
					",logstats='".$row['logstats']."'".
					",clickwindow=".$row['clickwindow'].
					",viewwindow=".$row['viewwindow']
				) or phpAds_sqlDie();
			}
			
			Header ("Location: ".$returnurl."?clientid=".$clientid."&trackerid=".$new_trackerid);
			exit;
		}
	}
}

Header ("Location: ".$returnurl."?clientid=".$clientid."&trackerid=".$trackerid);

?>