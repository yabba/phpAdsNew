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
require ("lib-statistics.inc.php");
require ("lib-invocation.inc.php");
require ("lib-zones.inc.php");


// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageHeader("4.3");
phpAds_ShowSections(array("4.1", "4.2", "4.3"));




/*********************************************************/
/* Main code                                             */
/*********************************************************/

$tabindex = 1;
phpAds_placeInvocationForm();



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();


?>