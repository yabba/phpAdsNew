<?php // $Revision: 2.12 $

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



// Include config file and check need to upgrade
require ("../config.inc.php");


// Figure out our location
if (!defined("phpAds_path"))
{
	if (strlen(__FILE__) > strlen(basename(__FILE__)))
	    define ('phpAds_path', ereg_replace("[/\\\\]admin[/\\\\][^/\\\\]+$", '', __FILE__));
	else
	    define ('phpAds_path', '..');
}



if (!defined('phpAds_installed'))
{
	// Old style configuration present
	header('Location: upgrade.php');
	exit;
}
elseif (!phpAds_installed)
{
	// Post configmanager, but not installed -> install
	header('Location: install.php');
	exit;
}


// Include required files
include_once ("../libraries/lib-io.inc.php");
include ("../libraries/lib-db.inc.php");
include ("../libraries/lib-dbconfig.inc.php");
include ("lib-gui.inc.php");
include ("lib-permissions.inc.php");
include ("../libraries/lib-userlog.inc.php");



// Open the database connection
$link = phpAds_dbConnect();
if (!$link)
{
	// This text isn't translated, because if it is shown the language files are not yet loaded
	phpAds_Die ("A fatal error occurred", $phpAds_productname." can't connect to the database.
				Because of this it isn't possible to use the administrator interface. The delivery
				of banners might also be affected. Possible reasons for the problem are:
				<ul><li>The database server isn't functioning at the moment</li>
				<li>The location of the database server has changed</li>
				<li>The username or password used to contact the database server are not correct</li>
				</ul>");
}


// Load settings from the database
phpAds_LoadDbConfig();


if (!isset($phpAds_config['config_version']) ||
	$phpAds_version > $phpAds_config['config_version'])
{
	// Post configmanager, but not up to date -> update
	header("Location: upgrade.php");
	exit;
}




// Check for SLL requirements
if ($phpAds_config['ui_forcessl'] && 
	$HTTP_SERVER_VARS['SERVER_PORT'] != 443)
{
	header ('Location: '.$phpAds_config['ssl_url_prefix'].$HTTP_SERVER_VARS['PHP_SELF']);
	exit;
}

// Adjust url_prefix if SLL is used
if ($HTTP_SERVER_VARS['SERVER_PORT'] == 443)
	$phpAds_config['url_prefix'] = $phpAds_config['ssl_url_prefix'];



// First thing to do is clear the $Session variable to
// prevent users from pretending to be logged in.
unset($Session);

// Authorize the user
phpAds_Start();

// Reload settings from the database because we are using agency settings.
$agencyid = phpAds_getAgencyID();
if ($agencyid > 0) phpAds_LoadDbConfig($agencyid);


// Load language strings
@include (phpAds_path.'/language/english/default.lang.php');
if ($phpAds_config['language'] != 'english' && file_exists(phpAds_path.'/language/'.$phpAds_config['language'].'/default.lang.php'))
	@include (phpAds_path.'/language/'.$phpAds_config['language'].'/default.lang.php');


// Register variables
phpAds_registerGlobal (
	 'affiliateid'
	,'agencyid'
	,'bannerid'
	,'campaignid'
	,'clientid'
	,'day'
	,'trackerid'
	,'userlogid'
	,'zoneid'
);

if (!isset($affiliateid))	$affiliateid = '';
if (!isset($agencyid))		$agencyid = '';
if (!isset($bannerid))		$bannerid = '';
if (!isset($campaignid))	$campaignid = '';
if (!isset($clientid))		$clientid = '';
if (!isset($day))			$day = '';
if (!isset($trackerid))		$trackerid = '';
if (!isset($userlogid))		$userlogid = '';
if (!isset($zoneid))		$zoneid = '';


// Setup navigation
$phpAds_nav = array (
	"admin"	=> array (
		"2"							=>  array("stats-global-advertiser.php" => $strStats),
 	  	  "2.1"						=>  array("stats-global-advertiser.php" => $strClientsAndCampaigns),
		    "2.1.1"					=>  array("stats-advertiser-history.php?clientid=$clientid" => $strClientHistory),
		      "2.1.1.1"				=> 	array("stats-advertiser-daily.php?clientid=$clientid&day=$day" => $strDailyStats),
		      "2.1.1.2"				=> 	array("stats-advertiser-daily-hosts.php?clientid=$clientid&day=$day" => $strHosts),
		    "2.1.2"					=>  array("stats-advertiser-campaigns.php?clientid=$clientid" => $strCampaignOverview),
    	      "2.1.2.1"		 		=> 	array("stats-campaign-history.php?clientid=$clientid&campaignid=$campaignid" => $strCampaignHistory),
		        "2.1.2.1.1"			=> 	array("stats-campaign-daily.php?clientid=$clientid&campaignid=$campaignid&day=$day" => $strDailyStats),
		        "2.1.2.1.2"			=> 	array("stats-campaign-daily-hosts.php?clientid=$clientid&campaignid=$campaignid&day=$day" => $strHosts),
			  "2.1.2.2"				=> 	array("stats-campaign-banners.php?clientid=$clientid&campaignid=$campaignid" => $strBannerOverview),
			    "2.1.2.2.1" 		=> 	array("stats-banner-history.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strBannerHistory),
		          "2.1.2.2.1.1"		=> 	array("stats-banner-daily.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid&day=$day" => $strDailyStats),
		          "2.1.2.2.1.2"		=> 	array("stats-banner-daily-hosts.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid&day=$day" => $strHosts),
    	        "2.1.2.2.2" 		=> 	array("stats-banner-affiliates.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strDistribution),
    	      "2.1.2.3"		 		=> 	array("stats-campaign-affiliates.php?clientid=$clientid&campaignid=$campaignid" => $strDistribution),
    	      "2.1.2.4"		 		=> 	array("stats-campaign-target.php?clientid=$clientid&campaignid=$campaignid" => $strTargetStats),
    	      "2.1.2.5"		 		=> 	array("stats-campaign-optimise.php?clientid=$clientid&campaignid=$campaignid" => $strOptimise),
    	      "2.1.2.6"		 		=> 	array("stats-campaign-keywords.php?clientid=$clientid&campaignid=$campaignid" => $strKeywordStatistics),
		  "2.2"						=>  array("stats-global-history.php" => $strGlobalHistory),
		    "2.2.1"					=> 	array("stats-global-daily.php?day=$day" => $strDailyStats),
		    "2.2.2"					=> 	array("stats-global-daily-hosts.php?day=$day" => $strHosts),
	      "2.4"		 				=> 	array("stats-global-affiliates.php" => $strAffiliatesAndZones),
		    "2.4.1"					=>  array("stats-affiliate-history.php?affiliateid=$affiliateid" => $strAffiliateHistory),
			  "2.4.1.1"				=>  array("stats-affiliate-daily.php?affiliateid=$affiliateid&day=$day" => $strDailyStats),
			  "2.4.1.2"				=>  array("stats-affiliate-daily-hosts.php?affiliateid=$affiliateid&day=$day" => $strHosts),
		    "2.4.2"					=>  array("stats-affiliate-zones.php?affiliateid=$affiliateid" => $strZoneOverview),
		      "2.4.2.1"				=>  array("stats-zone-history.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strZoneHistory),
		        "2.4.2.1.1"			=>  array("stats-zone-daily.php?affiliateid=$affiliateid&zoneid=$zoneid&day=$day" => $strDailyStats),
		        "2.4.2.1.2"			=>  array("stats-zone-daily-hosts.php?affiliateid=$affiliateid&zoneid=$zoneid&day=$day" => $strHosts),
		      "2.4.2.2"				=>  array("stats-zone-linkedbanners.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strLinkedBannersOverview),
		        "2.4.2.2.1"			=>  array("stats-linkedbanner-history.php?affiliateid=$affiliateid&zoneid=$zoneid&bannerid=$bannerid" => $strLinkedBannerHistory),
	      "2.5"		 				=> 	array("stats-global-misc.php" => $strMiscellaneous),
		"3"							=>  array("report-index.php" => $strReports),
		"4"							=>	array("advertiser-index.php" => $strAdminstration),
		  "4.1"						=>	array("advertiser-index.php" => $strClientsAndCampaigns),
		    "4.1.1"					=> 	array("advertiser-edit.php" => $strAddClient),
		    "4.1.2"					=> 	array("advertiser-edit.php?clientid=$clientid" => $strClientProperties),
		    "4.1.3"					=> 	array("advertiser-campaigns.php?clientid=$clientid" => $strCampaignOverview),
		      "4.1.3.1"				=>  array("campaign-edit.php?clientid=$clientid" => $strAddCampaign),
		      "4.1.3.2"				=>	array("campaign-edit.php?clientid=$clientid&campaignid=$campaignid" => $strCampaignProperties),
		      "4.1.3.3"				=> 	array("campaign-banners.php?clientid=$clientid&campaignid=$campaignid" => $strBannerOverview),
		        "4.1.3.3.1"			=> 	array("banner-edit.php?clientid=$clientid&campaignid=$campaignid" => $strAddBanner),
		        "4.1.3.3.2"			=> 	array("banner-edit.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strBannerProperties),
		        "4.1.3.3.3"			=> 	array("banner-acl.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strModifyBannerAcl),
		        "4.1.3.3.4"			=> 	array("banner-zone.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strLinkedZones),
			    "4.1.3.3.5"			=>  array("banner-swf.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strConvertSWFLinks),
			    "4.1.3.3.6"			=>  array("banner-append.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strAppendOthers),
		      "4.1.3.4"				=> 	array("campaign-zone.php?clientid=$clientid&campaignid=$campaignid" => $strLinkedZones),
		      "4.1.3.5"				=> 	array("campaign-trackers.php?clientid=$clientid&campaignid=$campaignid" => $strLinkedTrackers),
		    "4.1.4"					=> 	array("advertiser-trackers.php?clientid=$clientid" => $strTrackerOverview),
		      "4.1.4.1"				=>  array("tracker-edit.php?clientid=$clientid" => $strAddTracker),
		      "4.1.4.2"				=>	array("tracker-edit.php?clientid=$clientid&trackerid=$trackerid" => $strTrackerProperties),
		      "4.1.4.3"				=> 	array("tracker-campaigns.php?clientid=$clientid&trackerid=$trackerid" => $strLinkedCampaigns),
		      "4.1.4.4"				=> 	array("tracker-invocation.php?clientid=$clientid&trackerid=$trackerid" => $strInvocationcode),
		      "4.1.4.5"				=> 	array("tracker-variables.php?clientid=$clientid&trackerid=$trackerid" => $strTrackerVariables),			  
		  "4.2" 					=> 	array("affiliate-index.php" => $strAffiliatesAndZones),
		    "4.2.1" 				=> 	array("affiliate-edit.php" => $strAddNewAffiliate),
		    "4.2.2" 				=> 	array("affiliate-edit.php?affiliateid=$affiliateid" => $strAffiliateProperties),
		    "4.2.3" 				=> 	array("affiliate-zones.php?affiliateid=$affiliateid" => $strZoneOverview),
		      "4.2.3.1"				=> 	array("zone-edit.php?affiliateid=$affiliateid" => $strAddNewZone),
  		      "4.2.3.2"				=> 	array("zone-edit.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strZoneProperties),
		      "4.2.3.3"				=> 	array("zone-include.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strIncludedBanners),
		      "4.2.3.4"				=> 	array("zone-probability.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strProbability),
		      "4.2.3.5"				=> 	array("zone-invocation.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strInvocationcode),
  		      "4.2.3.6"				=> 	array("zone-advanced.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strAdvanced),
		  "4.3" 					=> 	array("admin-generate.php" => $strGenerateBannercode),
		"5"							=> 	array("settings-index.php" => $strSettings),
		  "5.1" 					=> 	array("settings-db.php" => $strMainSettings),
		  "5.2" 					=> 	array("userlog-index.php" => $strUserLog),
		  	"5.2.1" 				=> 	array("userlog-details.php?userlogid=$userlogid" => $strUserLogDetails),
		  "5.3" 					=> 	array("maintenance-index.php" => $strMaintenance),
		  "5.4" 					=> 	array("maintenance-updates.php" => $strProductUpdates),
		  "5.5" 					=> 	array("agency-index.php" => $strAgencyManagement),
		  	"5.5.1" 				=> 	array("agency-edit.php" => $strAddAgency),
		    "5.5.2"					=> 	array("agency-edit.php?agencyid=$agencyid" => $strAgencyProperties)

	),

	"agency"	=> array (
		"2"							=>  array("stats-global-advertiser.php" => $strStats),
 	  	  "2.1"						=>  array("stats-global-advertiser.php" => $strClientsAndCampaigns),
		    "2.1.1"					=>  array("stats-advertiser-history.php?clientid=$clientid" => $strClientHistory),
		      "2.1.1.1"				=> 	array("stats-advertiser-daily.php?clientid=$clientid&day=$day" => $strDailyStats),
		      "2.1.1.2"				=> 	array("stats-advertiser-daily-hosts.php?clientid=$clientid&day=$day" => $strHosts),
		    "2.1.2"					=>  array("stats-advertiser-campaigns.php?clientid=$clientid" => $strCampaignOverview),
    	      "2.1.2.1"		 		=> 	array("stats-campaign-history.php?clientid=$clientid&campaignid=$campaignid" => $strCampaignHistory),
		        "2.1.2.1.1"			=> 	array("stats-campaign-daily.php?clientid=$clientid&campaignid=$campaignid&day=$day" => $strDailyStats),
		        "2.1.2.1.2"			=> 	array("stats-campaign-daily-hosts.php?clientid=$clientid&campaignid=$campaignid&day=$day" => $strHosts),
			  "2.1.2.2"				=> 	array("stats-campaign-banners.php?clientid=$clientid&campaignid=$campaignid" => $strBannerOverview),
			    "2.1.2.2.1" 		=> 	array("stats-banner-history.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strBannerHistory),
		          "2.1.2.2.1.1"		=> 	array("stats-banner-daily.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid&day=$day" => $strDailyStats),
		          "2.1.2.2.1.2"		=> 	array("stats-banner-daily-hosts.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid&day=$day" => $strHosts),
    	        "2.1.2.2.2" 		=> 	array("stats-banner-affiliates.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strDistribution),
    	      "2.1.2.3"		 		=> 	array("stats-campaign-affiliates.php?clientid=$clientid&campaignid=$campaignid" => $strDistribution),
    	      "2.1.2.4"		 		=> 	array("stats-campaign-target.php?clientid=$clientid&campaignid=$campaignid" => $strTargetStats),
    	      "2.1.2.5"		 		=> 	array("stats-campaign-optimise.php?clientid=$clientid&campaignid=$campaignid" => $strOptimise),
    	      "2.1.2.6"		 		=> 	array("stats-campaign-keywords.php?clientid=$clientid&campaignid=$campaignid" => $strKeywordStatistics),
		  "2.2"						=>  array("stats-global-history.php" => $strGlobalHistory),
		    "2.2.1"					=> 	array("stats-global-daily.php?day=$day" => $strDailyStats),
		    "2.2.2"					=> 	array("stats-global-daily-hosts.php?day=$day" => $strHosts),
	      "2.4"		 				=> 	array("stats-global-affiliates.php" => $strAffiliatesAndZones),
		    "2.4.1"					=>  array("stats-affiliate-history.php?affiliateid=$affiliateid" => $strAffiliateHistory),
			  "2.4.1.1"				=>  array("stats-affiliate-daily.php?affiliateid=$affiliateid&day=$day" => $strDailyStats),
			  "2.4.1.2"				=>  array("stats-affiliate-daily-hosts.php?affiliateid=$affiliateid&day=$day" => $strHosts),
		    "2.4.2"					=>  array("stats-affiliate-zones.php?affiliateid=$affiliateid" => $strZoneOverview),
		      "2.4.2.1"				=>  array("stats-zone-history.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strZoneHistory),
		        "2.4.2.1.1"			=>  array("stats-zone-daily.php?affiliateid=$affiliateid&zoneid=$zoneid&day=$day" => $strDailyStats),
		        "2.4.2.1.2"			=>  array("stats-zone-daily-hosts.php?affiliateid=$affiliateid&zoneid=$zoneid&day=$day" => $strHosts),
		      "2.4.2.2"				=>  array("stats-zone-linkedbanners.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strLinkedBannersOverview),
		        "2.4.2.2.1"			=>  array("stats-linkedbanner-history.php?affiliateid=$affiliateid&zoneid=$zoneid&bannerid=$bannerid" => $strLinkedBannerHistory),
	      "2.5"		 				=> 	array("stats-global-misc.php" => $strMiscellaneous),
		"3"							=>  array("report-index.php" => $strReports),
		"4"							=>	array("advertiser-index.php" => $strAdminstration),
		  "4.1"						=>	array("advertiser-index.php" => $strClientsAndCampaigns),
		    "4.1.1"					=> 	array("advertiser-edit.php" => $strAddClient),
		    "4.1.2"					=> 	array("advertiser-edit.php?clientid=$clientid" => $strClientProperties),
		    "4.1.3"					=> 	array("advertiser-campaigns.php?clientid=$clientid" => $strCampaignOverview),
		      "4.1.3.1"				=>  array("campaign-edit.php?clientid=$clientid" => $strAddCampaign),
		      "4.1.3.2"				=>	array("campaign-edit.php?clientid=$clientid&campaignid=$campaignid" => $strCampaignProperties),
		      "4.1.3.3"				=> 	array("campaign-banners.php?clientid=$clientid&campaignid=$campaignid" => $strBannerOverview),
		        "4.1.3.3.1"			=> 	array("banner-edit.php?clientid=$clientid&campaignid=$campaignid" => $strAddBanner),
		        "4.1.3.3.2"			=> 	array("banner-edit.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strBannerProperties),
		        "4.1.3.3.3"			=> 	array("banner-acl.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strModifyBannerAcl),
		        "4.1.3.3.4"			=> 	array("banner-zone.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strLinkedZones),
			    "4.1.3.3.5"			=>  array("banner-swf.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strConvertSWFLinks),
			    "4.1.3.3.6"			=>  array("banner-append.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strAppendOthers),
		      "4.1.3.4"				=> 	array("campaign-zone.php?clientid=$clientid&campaignid=$campaignid" => $strLinkedZones),
		      "4.1.3.5"				=> 	array("campaign-trackers.php?clientid=$clientid&campaignid=$campaignid" => $strLinkedTrackers),
		    "4.1.4"					=> 	array("advertiser-trackers.php?clientid=$clientid" => $strTrackerOverview),
		      "4.1.4.1"				=>  array("tracker-edit.php?clientid=$clientid" => $strAddTracker),
		      "4.1.4.2"				=>	array("tracker-edit.php?clientid=$clientid&trackerid=$trackerid" => $strTrackerProperties),
		      "4.1.4.3"				=> 	array("tracker-campaigns.php?clientid=$clientid&trackerid=$trackerid" => $strLinkedCampaigns),
		      "4.1.4.4"				=> 	array("tracker-invocation.php?clientid=$clientid&trackerid=$trackerid" => $strInvocationcode),
		      "4.1.4.5"				=> 	array("tracker-variables.php?clientid=$clientid&trackerid=$trackerid" => $strTrackerVariables),			  
		  "4.2" 					=> 	array("affiliate-index.php" => $strAffiliatesAndZones),
		    "4.2.1" 				=> 	array("affiliate-edit.php" => $strAddNewAffiliate),
		    "4.2.2" 				=> 	array("affiliate-edit.php?affiliateid=$affiliateid" => $strAffiliateProperties),
		    "4.2.3" 				=> 	array("affiliate-zones.php?affiliateid=$affiliateid" => $strZoneOverview),
		      "4.2.3.1"				=> 	array("zone-edit.php?affiliateid=$affiliateid" => $strAddNewZone),
  		      "4.2.3.2"				=> 	array("zone-edit.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strZoneProperties),
		      "4.2.3.3"				=> 	array("zone-include.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strIncludedBanners),
		      "4.2.3.4"				=> 	array("zone-probability.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strProbability),
		      "4.2.3.5"				=> 	array("zone-invocation.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strInvocationcode),
  		      "4.2.3.6"				=> 	array("zone-advanced.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strAdvanced),
		  "4.3" 					=> 	array("admin-generate.php" => $strGenerateBannercode),
		"5"							=> 	array("settings-index.php" => $strSettings),
		  "5.1" 					=> 	array("settings-invocation.php" => $strMainSettings)
	),

	"client" => array (
		"1"							=>  array("stats-advertiser-history.php?clientid=$clientid" => $strHome),
		  "1.1"						=>  array("stats-advertiser-history.php?clientid=$clientid" => $strClientHistory),
	        "1.1.1"					=> 	array("stats-advertiser-daily.php?clientid=$clientid&day=$day" => $strDailyStats),
		    "1.1.2"					=> 	array("stats-advertiser-daily-hosts.php?clientid=$clientid&day=$day" => $strHosts),
		  "1.2"						=>  array("stats-advertiser-campaigns.php?clientid=$clientid" => $strCampaignOverview),
    	    "1.2.1"		 			=> 	array("stats-campaign-history.php?clientid=$clientid&campaignid=$campaignid" => $strCampaignHistory),
		      "1.2.1.1"				=> 	array("stats-campaign-daily.php?clientid=$clientid&campaignid=$campaignid&day=$day" => $strDailyStats),
		      "1.2.1.2"				=> 	array("stats-campaign-daily-hosts.php?clientid=$clientid&campaignid=$campaignid&day=$day" => $strHosts),
			"1.2.2"					=> 	array("stats-campaign-banners.php?clientid=$clientid&campaignid=$campaignid" => $strBannerOverview),
			  "1.2.2.1" 			=> 	array("stats-banner-history.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strBannerHistory),
		        "1.2.2.1.1"			=> 	array("stats-banner-daily.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid&day=$day" => $strDailyStats),
		        "1.2.2.1.2"			=> 	array("stats-banner-daily-hosts.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid&day=$day" => $strHosts),
		      "1.2.2.2"				=> 	array("banner-edit.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strBannerProperties),
			  "1.2.2.3"				=>  array("banner-swf.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strConvertSWFLinks),
    	      "1.2.2.4" 			=> 	array("stats-banner-affiliates.php?clientid=$clientid&campaignid=$campaignid&bannerid=$bannerid" => $strDistribution),
    	    "1.2.3"		 			=> 	array("stats-campaign-affiliates.php?clientid=$clientid&campaignid=$campaignid" => $strDistribution),
    	    "1.2.4"					=> 	array("stats-campaign-target.php?clientid=$clientid&campaignid=$campaignid" => $strTargetStats),
		"3"							=>  array("report-index.php" => $strReports)
	),

	"affiliate" => array (
		"1"						=>  array("stats-affiliate-zones.php?affiliateid=$affiliateid" => $strHome),
		  "1.1"					=>  array("stats-affiliate-zones.php?affiliateid=$affiliateid" => $strCampaigns),
		    "1.1.1"  			=>  array("stats-zone-history.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strZoneHistory),
		      "1.1.1.1"			=>  array("stats-zone-daily.php?affiliateid=$affiliateid&zoneid=$zoneid&day=$day" => $strDailyStats),
		      "1.1.1.2"			=>  array("stats-zone-daily-hosts.php?affiliateid=$affiliateid&zoneid=$zoneid&day=$day" => $strHosts),
		    "1.1.2"  			=>  array("stats-zone-linkedbanners.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strLinkedBannersOverview),
		      "1.1.2.1"			=>  array("stats-linkedbanner-history.php?affiliateid=$affiliateid&zoneid=$zoneid&bannerid=$bannerid" => $strLinkedBannerHistory),
		  "1.2"					=>  array("stats-affiliate-history.php?affiliateid=$affiliateid" => $strAffiliateHistory),
			"1.2.1"				=>  array("stats-affiliate-daily.php?affiliateid=$affiliateid&day=$day" => $strDailyStats),
			"1.2.2"				=>  array("stats-affiliate-daily-hosts.php?affiliateid=$affiliateid&day=$day" => $strHosts),
		"3"						=>  array("report-index.php" => $strReports)
// remove inventory tab from affiliate pages
/*	    
	    "2" 					=> 	array("affiliate-zones.php?affiliateid=$affiliateid" => $strAdminstration),
	      "2.1" 				=> 	array("affiliate-zones.php?affiliateid=$affiliateid" => $strZones),
		    "2.1.1"				=> 	array("zone-edit.php?affiliateid=$affiliateid&zoneid=0" => $strAddZone),
  		    "2.1.2"				=> 	array("zone-edit.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strModifyZone),
		    "2.1.3"				=> 	array("zone-include.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strIncludedBanners),
		    "2.1.4"				=> 	array("zone-probability.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strProbability),
		    "2.1.5"				=> 	array("zone-invocation.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strInvocationcode),
  		    "2.1.6"				=> 	array("zone-advanced.php?affiliateid=$affiliateid&zoneid=$zoneid" => $strChains),
	      "2.2" 				=> 	array("affiliate-edit.php?affiliateid=$affiliateid" => $strPreferences)
*/		  
	)
);

if (phpAds_isUser(phpAds_Client) && phpAds_isAllowed(phpAds_ModifyInfo))
	$phpAds_nav["client"]["2"] =  array("advertiser-edit.php" => $strPreferences);

?>