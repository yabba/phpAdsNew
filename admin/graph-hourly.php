<?php // $Revision: 2.1 $

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


// Register input variables
phpAds_registerGlobal ('where');


/*********************************************************/
/* Prepare data for graph                                */
/*********************************************************/

$where   = urldecode($where); 
$query	 = "SELECT views,clicks,hour".
	" FROM ".$phpAds_config['tbl_adstats'].
	" WHERE ($where)";
	
$result  = phpAds_dbQuery($query)
	or phpAds_sqlDie();
    
	
if (isset ($GLOBALS['phpAds_CharSet']) && $GLOBALS['phpAds_CharSet'] != '')
		$text=array(
		    'value1' => 'AdViews',
		    'value2' => 'AdClicks');
else
		$text=array(
		    'value1' => $GLOBALS['strViews'],
		    'value2' => $GLOBALS['strClicks']);
    
	
$items = array();
// preset array (not every hour may be occupied)
for ($i=0;$i<=23;$i++)
{
    	$items[$i] = array();
    	$items[$i]['value1'] = 0;
    	$items[$i]['value2'] = 0;
    	$items[$i]['text'] = '';
}
	
while ($row = phpAds_dbFetchArray($result))
{
	$i=$row['hour'];
	$items[$i]['value1'] = $row['views'];
	$items[$i]['value2'] = $row['clicks'];
    	$items[$i]['text'] = sprintf("%d",$i);
}
    
$width=385;   // absolute definition due to width/height declaration in stats.inc.php
$height=150;  // adapt this if embedding html-document will change
    
// Build the graph
include("lib-graph.inc.php");

?>