<?php // $Revision: 1.1 $

/************************************************************************/
/* phpAdsNew 2                                                          */
/* ===========                                                          */
/*                                                                      */
/* For more information visit: http://www.phpadsnew.com                 */
/************************************************************************/

function phpAds_getPref($page_name, $var)
{
	global $Session;
	
	$value = '';
	
	if (isset($Session['prefs'][$page_name][$var]))
		$value = $Session['prefs'][$page_name][$var];
	
	return $value;
}

function phpAds_getPrefArray($page_name, $var)
{
	global $Session;
	
	$value = array();
	
	if (isset($Session['prefs'][$page_name][$var]))
		$value = explode (",", $Session['prefs'][$page_name][$var]);

	return $value;
}

function phpAds_updateExpandArray($expand_arr, $expand, $collapse)
{
	if ( ($expand != null) && ($expand != 'none') && ($expand != 'all') && !in_array($expand, $expand_arr))
		$expand_arr[] = $expand;
		
	$index = array_search($collapse, $expand_arr);
	if (is_integer($index))
		unset($expand_arr[$index]);
		
	$index = array_search('', $expand_arr);
	if (is_integer($index))
		unset($expand_arr[$index]);

	return $expand_arr;
}

function phpAds_setPref($page_name, $var, $value)
{
	global $Session;
	
	$Session['prefs'][$page_name][$var] = $value;
}

?>