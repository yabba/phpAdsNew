<?php // $Revision: 2.22 $

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


// Set define to prevent duplicate include
define ('LIBDBCONFIG_INCLUDED', true);


// Current phpAds version
$phpAds_version = 200.165;
$phpAds_version_readable = "2.1 Alpha 1";
$phpAds_productname = "phpAdsNew";
$phpAds_producturl = "www.phpadsnew.com";
$phpAds_dbmsname = "MySQL";

$GLOBALS['phpAds_settings_information'] = array(
	'acl' =>  						array ('type' => 'boolean', 'sql' => false),
	'admin' =>						array ('type' => 'string', 'sql' => true),
	'admin_email' =>				array ('type' => 'string', 'sql' => true),
	'admin_email_headers' =>		array ('type' => 'string', 'sql' => true),
	'admin_fullname' =>				array ('type' => 'string', 'sql' => true),
	'admin_novice' =>				array ('type' => 'boolean', 'sql' => true),
	'admin_pw' =>					array ('type' => 'string', 'sql' => true),
	'allow_invocation_frame' =>		array ('type' => 'boolean', 'sql' => true),
	'allow_invocation_js' =>		array ('type' => 'boolean', 'sql' => true),
	'allow_invocation_interstitial' =>	array ('type' => 'boolean', 'sql' => true),
	'allow_invocation_local' =>		array ('type' => 'boolean', 'sql' => true),
	'allow_invocation_plain' =>		array ('type' => 'boolean', 'sql' => true),
	'allow_invocation_plain_nocookies' =>		array ('type' => 'boolean', 'sql' => true),
	'allow_invocation_popup' =>		array ('type' => 'boolean', 'sql' => true),
	'allow_invocation_xmlrpc' =>	array ('type' => 'boolean', 'sql' => true),
	'autotarget_factor' =>			array ('type' => 'double', 'sql' => true),
	'auto_clean_tables' =>			array ('type' => 'boolean', 'sql' => true),
	'auto_clean_tables_interval' =>	array ('type' => 'integer', 'sql' => true),
	'auto_clean_userlog' =>			array ('type' => 'boolean', 'sql' => true),
	'auto_clean_userlog_interval' =>  array ('type' => 'integer', 'sql' => true),
	'begin_of_week' =>				array ('type' => 'integer', 'sql' => true),
	'block_adclicks' =>				array ('type' => 'integer', 'sql' => false),
	'block_adconversions' =>		array ('type' => 'integer', 'sql' => false),
	'block_adviews' =>				array ('type' => 'integer', 'sql' => false),
	'client_welcome' =>				array ('type' => 'boolean', 'sql' => true),
	'client_welcome_msg' =>			array ('type' => 'string', 'sql' => true),
	'compact_stats' =>				array ('type' => 'boolean', 'sql' => true),
	'company_name' =>				array ('type' => 'string', 'sql' => true),
	'compatibility_mode' => 		array ('type' => 'boolean', 'sql' => false),
	'config_version' =>				array ('type' => 'string', 'sql' => true),
	'content_gzip_compression' =>	array ('type' => 'boolean', 'sql' => true),
	'con_key' =>					array ('type' => 'boolean', 'sql' => false),
	'dbhost' => 					array ('type' => 'string', 	'sql' => false),
	'dbname' => 					array ('type' => 'string', 	'sql' => false),
	'dbpassword' => 				array ('type' => 'string', 	'sql' => false),
	'dbport' => 					array ('type' => 'integer', 'sql' => false),
	'dbuser' => 					array ('type' => 'string', 	'sql' => false),
	'default_banner_url' => 		array ('type' => 'string', 	'sql' => false),
	'default_banner_target' =>		array ('type' => 'string', 	'sql' => false),
	'default_banner_weight' =>		array ('type' => 'integer', 'sql' => true),
	'delivery_caching' =>			array ('type' => 'string', 	'sql' => false),
	'default_campaign_weight' =>	array ('type' => 'integer', 'sql' => true),
	'default_conversion_clickwindow' =>	array ('type' => 'integer', 'sql' => false),
	'default_conversion_viewwindow' =>	array ('type' => 'integer', 'sql' => false),
	'geotracking_type' => 			array ('type' => 'string',  'sql' => false),
	'geotracking_location' => 		array ('type' => 'string',  'sql' => false),
	'geotracking_stats' =>			array ('type' => 'boolean', 'sql' => false),
	'geotracking_cookie' =>			array ('type' => 'boolean', 'sql' => false),
	'gui_hide_inactive' =>			array ('type' => 'boolean', 'sql' => true),
	'gui_link_compact_limit' =>		array ('type' => 'integer', 'sql' => true),
	'gui_show_banner_html' =>		array ('type' => 'boolean', 'sql' => true),
	'gui_show_banner_info' =>		array ('type' => 'boolean', 'sql' => true),
	'gui_show_banner_preview' =>	array ('type' => 'boolean', 'sql' => true),
	'gui_show_campaign_info' =>		array ('type' => 'boolean', 'sql' => true),
	'gui_show_campaign_preview' =>	array ('type' => 'boolean', 'sql' => true),
	'gui_show_matching' =>			array ('type' => 'boolean', 'sql' => true),
	'gui_show_parents' =>			array ('type' => 'boolean', 'sql' => true),
	'ignore_hosts' => 				array ('type' => 'array',	'sql' => false),
	'insert_delayed' => 			array ('type' => 'boolean', 'sql' => false),
	'language' =>					array ('type' => 'string', 'sql' => true),
	'log_adviews' =>				array ('type' => 'boolean', 'sql' => false),
	'log_adclicks' => 				array ('type' => 'boolean', 'sql' => false),
	'log_adconversions' => 			array ('type' => 'boolean', 'sql' => false),
	'log_beacon' =>					array ('type' => 'boolean', 'sql' => false),
	'log_hostname' =>				array ('type' => 'boolean', 'sql' => false),
	'log_iponly' =>					array ('type' => 'boolean', 'sql' => false),
	'log_source' =>					array ('type' => 'boolean', 'sql' => false),
	'maintenance_timestamp' =>		array ('type' => 'integer', 'sql' => true),
	'mult_key' =>	 				array ('type' => 'boolean', 'sql' => false),
	'my_header' =>					array ('type' => 'string', 'sql' => true),
	'my_footer' =>					array ('type' => 'string', 'sql' => true),
	'name' =>						array ('type' => 'string', 'sql' => true),
	'override_gd_imageformat' =>	array ('type' => 'string', 'sql' => true),
	'p3p_compact_policy' => 		array ('type' => 'string', 	'sql' => false),
	'p3p_policies' => 				array ('type' => 'boolean', 'sql' => false),
	'p3p_policy_location' => 		array ('type' => 'string', 	'sql' => false),
	'percentage_decimals' =>		array ('type' => 'integer', 'sql' => true),
	'persistent_connections' =>		array ('type' => 'boolean', 'sql' => false),
	'proxy_lookup' =>				array ('type' => 'boolean', 'sql' => false),
	'obfuscate' =>					array ('type' => 'boolean', 'sql' => false),
	'qmail_patch' =>				array ('type' => 'boolean', 'sql' => true),
	'reverse_lookup' =>				array ('type' => 'boolean', 'sql' => false),
	'table_prefix' =>				array ('type' => 'string', 	'sql' => false),
	'table_type' =>					array ('type' => 'string', 	'sql' => false),
	'tbl_acls' => 					array ('type' => 'string', 	'sql' => false),
	'tbl_adclicks' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_adconversions' => 			array ('type' => 'string', 	'sql' => false),
	'tbl_agency' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_adstats' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_adviews' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_affiliates' =>				array ('type' => 'string', 	'sql' => false),
	'tbl_banners' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_cache' =>					array ('type' => 'string', 	'sql' => false),
	'tbl_campaigns' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_campaigns_trackers' => 	array ('type' => 'string', 	'sql' => false),
	'tbl_clients' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_config' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_conversionlog' => 			array ('type' => 'string', 	'sql' => false),
	'tbl_images' =>					array ('type' => 'string', 	'sql' => false),
	'tbl_session' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_targetstats' => 			array ('type' => 'string', 	'sql' => false),
	'tbl_trackers' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_userlog' =>				array ('type' => 'string', 	'sql' => false),
	'tbl_zones' => 					array ('type' => 'string', 	'sql' => false),
	'tbl_variables' => 				array ('type' => 'string', 	'sql' => false),
	'tbl_variablevalues' =>			array ('type' => 'string', 	'sql' => false),
	'type_html_allow' =>			array ('type' => 'boolean', 'sql' => true),
	'type_html_auto' => 			array ('type' => 'boolean', 'sql' => false),
	'type_html_php' => 				array ('type' => 'boolean', 'sql' => false),
	'type_sql_allow' =>				array ('type' => 'boolean', 'sql' => true),
	'type_txt_allow' =>				array ('type' => 'boolean', 'sql' => true),
	'type_url_allow' =>				array ('type' => 'boolean', 'sql' => true),
	'type_web_allow' =>				array ('type' => 'boolean', 'sql' => true),
	'type_web_dir' =>				array ('type' => 'string', 'sql' => true),
	'type_web_ftp' =>				array ('type' => 'string', 'sql' => true),
	'type_web_mode' =>				array ('type' => 'integer', 'sql' => true),
	'type_web_url' =>				array ('type' => 'string', 'sql' => false),
	'type_web_ssl_url' =>			array ('type' => 'string', 'sql' => false),
	'ui_enabled' =>					array ('type' => 'boolean', 'sql' => false),
	'ui_forcessl' =>				array ('type' => 'boolean', 'sql' => false),
	'updates_frequency' =>			array ('type' => 'integer', 'sql' => true),
	'updates_last_seen' =>			array ('type' => 'string', 'sql' => true),
	'updates_timestamp' =>			array ('type' => 'integer', 'sql' => true),
	'url_prefix' => 				array ('type' => 'string', 	'sql' => false),
	'ssl_url_prefix' => 			array ('type' => 'string', 	'sql' => false),
	'use_keywords' =>	 			array ('type' => 'boolean', 'sql' => false),
	'userlog_email' =>				array ('type' => 'boolean', 'sql' => true),
	'userlog_priority' =>			array ('type' => 'boolean', 'sql' => true),
	'userlog_autoclean' =>			array ('type' => 'boolean', 'sql' => true),
	'warn_admin' =>					array ('type' => 'boolean', 'sql' => true),
	'warn_agency' => 				array ('type' => 'boolean', 'sql' => true),	
	'warn_client' => 				array ('type' => 'boolean', 'sql' => true),
	'warn_limit' =>					array ('type' => 'integer', 'sql' => true)
);



/*********************************************************/
/* Load configuration from database                      */
/*********************************************************/

function phpAds_LoadDbConfig($agencyid = 0)
{
	global $phpAds_config, $phpAds_settings_information;
	
	if ((!empty($GLOBALS['phpAds_db_link']) || phpAds_dbConnect()) && isset($phpAds_config['tbl_config']))
	{
		$query = "SELECT *".
			" FROM ".$phpAds_config['tbl_config'].
			" WHERE agencyid=".$agencyid;
			
		if ($res = phpAds_dbQuery($query))
		{
			if ($row = phpAds_dbFetchArray($res, 0))
			{
				while (list($k, $v) = each($phpAds_settings_information))
				{
					if (!$v['sql'] || !isset($row[$k]))
						continue;
					
					switch($v['type'])
					{
						case 'boolean': $row[$k] = $row[$k] == 't'; break;
						case 'integer': $row[$k] = (int)$row[$k]; break;
						case 'array': $row[$k] = unserialize($row[$k]); break;
						case 'float': $row[$k] = (float)$row[$k]; break;
					}
					
					$phpAds_config[$k] = $row[$k];
				}
				
				reset($phpAds_settings_information);
				
				return true;
			}
		}
	}
	
	return false;
}

?>