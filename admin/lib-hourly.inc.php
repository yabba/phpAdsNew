<?php // $Revision: 2.2 $

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




/*********************************************************/
/* Show hourly statistics                                */
/*********************************************************/

$result = phpAds_dbQuery("
	SELECT
		hour,
		SUM(views) AS views,
		SUM(clicks) AS clicks,
		SUM(conversions) AS conversions
	FROM
		".$phpAds_config['tbl_adstats']."
	WHERE
		day = ".$day."
		".(isset($lib_hourly_where) ? 'AND '.$lib_hourly_where : '')."
	GROUP BY 
		hour
") or phpAds_sqlDie();


while ($row = phpAds_dbFetchArray($result))
{
	$views[$row['hour']] = $row['views'];
	$clicks[$row['hour']] = $row['clicks'];
	$conversions[$row['hour']] 	= $row['conversions'];
}

echo "<br><br>";

echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
echo "<tr bgcolor='#FFFFFF' height='25'>";
echo "<td align='".$phpAds_TextAlignLeft."' nowrap height='25'><b>$strHour</b></td>";
echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strViews."</b></td>";
echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strClicks."</b></td>";
echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strCTRShort."</b>&nbsp;&nbsp;</td>";
echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strConversions."</b>&nbsp;&nbsp;</td>";
echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strCNVR."</b>&nbsp;&nbsp;</td>";
echo "</tr>";

echo "<tr><td height='1' colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";

$totalviews = 0;
$totalclicks = 0;

for ($i=0; $i<24; $i++)
{
	$bgcolor = ($i % 2 ? "#FFFFFF": "#F6F6F6");
	
	if (!isset($views[$i])) $views[$i] = 0;
	if (!isset($clicks[$i])) $clicks[$i] = 0;
	if (!isset($conversions[$i])) $conversions[$i] = 0;
	
	$totalviews += $views[$i];
	$totalclicks += $clicks[$i];
	$totalconversions 	+= $conversions[i];
	
	
	if ($views[$i] || $clicks[$i])
	{
		$ctr = phpAds_buildCTR($views[$i], $clicks[$i]);
		$views[$i] = phpAds_formatNumber($views[$i]);
		$clicks[$i] = phpAds_formatNumber($clicks[$i]);
		$conversions[$i]	= phpAds_formatNumber($conversions[$i]);

		$cr					= number_format(($clicks[$i] > 0 ? ($conversions[$i] / $clicks[$i]) : 0), $phpAds_config['percentage_decimals'], $phpAds_DecimalPoint, $phpAds_ThousandsSeperator) . "%";
	}
	else
	{
		$ctr = '-';
		$views[$i] = '-';
		$clicks[$i] = '-';
		$conversions[$i]	= '-';
		$cr					= '-';
	}
	
	$basestamp = mktime ($i, 0, 0, date('m'), date('d'), date('Y'));
	
	echo "<tr>";
	echo "<td height='25' bgcolor='$bgcolor'>&nbsp;";
	echo "<img src='images/icon-time.gif' align='absmiddle'>&nbsp;"; 
	echo strftime ($minute_format, $basestamp)." - ". strftime ($minute_format, $basestamp + (59 * 60));
	echo "</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25' bgcolor='$bgcolor'>".$views[$i]."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25' bgcolor='$bgcolor'>".$clicks[$i]."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25' bgcolor='$bgcolor'>".$ctr."&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25' bgcolor='$bgcolor'>".$conversions[$i]."&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25' bgcolor='$bgcolor'>".$cr."&nbsp;&nbsp;</td>";
	echo "</tr>";
	
	echo "<tr><td height='1' colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
}

if ($totalviews > 0 || $totalclicks > 0)
{
	echo "<tr><td colspan='6'>&nbsp;</td></tr>";	
	echo "<tr><td colspan='6'>&nbsp;</td></tr>";

	echo "<tr bgcolor='#FFFFFF' height='25'>";
	echo "<td></td>";
	echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strViews."</b></td>";
	echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strClicks."</b></td>";
	echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strCTRShort."</b>&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strConversions."</b>&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."' width='15%' nowrap height='25'><b>".$strCNVR."</b>&nbsp;&nbsp;</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td height='25'>&nbsp;<b>$strTotal</b></td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".phpAds_formatNumber($totalviews)."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".phpAds_formatNumber($totalclicks)."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".phpAds_buildCTR($totalviews, $totalclicks)."&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".phpAds_formatNumber($totalconversions)."&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".number_format(($totalclicks > 0 ? phpAds_formatNumber($cr / $totalclicks) : 0), $phpAds_config['percentage_decimals'], $phpAds_DecimalPoint, $phpAds_ThousandsSeperator) . "%" ."&nbsp;&nbsp;</td>";
	echo "</tr>";
	
	echo "<tr><td height='1' colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
	
	echo "<tr>";
	echo "<td height='25'>&nbsp;".$strAverage."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".phpAds_formatNumber($totalviews / 24)."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".phpAds_formatNumber($totalclicks / 24)."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>"."</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>".phpAds_formatNumber($totalconversions / 24)."&nbsp;&nbsp;</td>";
	echo "<td align='".$phpAds_TextAlignRight."' height='25'>"."&nbsp;&nbsp;</td>";
	echo "</tr>";
	
	echo "<tr><td height='1' colspan='6' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
}

echo "</table>";
echo "<br><br>";


?>