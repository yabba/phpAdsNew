<?php

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

// Security check
phpAds_checkAccess(phpAds_Admin + phpAds_Agency);


// Register input variables
phpAds_registerGlobal (
	'action'
);


/*********************************************************/
/* Affiliate interface security                          */
/*********************************************************/

if (phpAds_isUser(phpAds_Agency))
{
	$query = "SELECT c.clientid as clientid".
		" FROM ".$phpAds_config['tbl_clients']." AS c".
		",".$phpAds_config['tbl_trackers']." AS t".
		" WHERE t.clientid=c.clientid".
		" AND c.clientid=".$clientid.
		" AND t.trackerid=".$trackerid.
		" AND c.agencyid=".phpAds_getUserID();

	$res = phpAds_dbQuery($query)
		or phpAds_sqlDie();
	
	if (phpAds_dbNumRows($res) == 0)
	{
		phpAds_PageHeader("1");
		phpAds_Die ($strAccessDenied, $strNotAdmin);
	}
}



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if (!isset($variables))
	if (isset($Session['prefs']['tracker-variables.php']['variables']) && $Session['prefs']['tracker-variables.php']['trackerid']==$trackerid)
		$variables = $Session['prefs']['tracker-variables.php']['variables'];



if (isset($trackerid) && $trackerid != '')
{

	if (!isset($variables))
	{
		// get variables from db
		$variables_result = phpAds_dbQuery(
			"SELECT 
				*
			 FROM 
				".$phpAds_config['tbl_variables']."
			WHERE trackerid=".$trackerid
		) or phpAds_sqlDie();
	
		while ($vars = phpAds_dbFetchArray($variables_result))
			$variables[] = $vars;
	}
	else
	{
		// Get values on the form
		for ($f=0; $f < sizeof($variables)+1; $f++)
		if (isset($HTTP_POST_VARS['name'.$f]))
		{
			$variables[$f]['name'] = $HTTP_POST_VARS['name'.$f];
			$variables[$f]['description'] = $HTTP_POST_VARS['description'.$f];
			$variables[$f]['variabletype'] = $HTTP_POST_VARS['variabletype'.$f];
			$variables[$f]['datatype'] = $HTTP_POST_VARS['datatype'.$f];								
		}
	}

	// insert a new variable 
	if (isset($action['new']))
			$variables[] = array();


	// has user clicked on save changes?
	if (isset($action['save']))
	{

		foreach($variables as $v)
		{
			// delete variables from db
			if (isset($v['variableid']) && isset($v['delete']))
				$variables_update = phpAds_dbQuery(
					"DELETE
						FROM ".$phpAds_config['tbl_variables']."
					WHERE
						variableid=".$v['variableid']."
					LIMIT 1 "
				) or phpAds_sqlDie();
		
		
			// update variable info
			else if (isset($v['variableid']) && !isset($v['delete']))
				$variables_update = phpAds_dbQuery(
					"UPDATE 
						".$phpAds_config['tbl_variables']."
					 SET
						name 			= '".$v['name']."',
						description 	= '".$v['description']."',
						variabletype 	= '".$v['variabletype']."',
						datatype 		= '".$v['datatype']."'
					WHERE
						variableid=".$v['variableid']
				) or phpAds_sqlDie();
			

			else
				$variables_insert = phpAds_dbQuery(
					"INSERT ".($phpAds_config['insert_delayed'] ? 'DELAYED' : '')." 
						INTO ".$phpAds_config['tbl_variables']."
						(trackerid,
						 name,
						 description,
						 variabletype,
						 datatype)
					VALUES
						(".$trackerid.",
						'".$v['name']."',
						'".$v['description']."',
						'".$v['variabletype']."',
						'".$v['datatype']."'
						)"
				) or phpAds_sqlDie();

		}
		
		// unset variables!
		unset	($Session['prefs']['tracker-variables.php']);
		phpAds_SessionDataStore();
		phpAds_CacheDelete('what=tracker:' . $trackerid);

		// redirect to the next page
		header 	("Location: tracker-invocation.php?clientid=".$clientid."&trackerid=".$trackerid);
		exit;
		
	}

}


if (phpAds_isUser(phpAds_Admin) || phpAds_isUser(phpAds_Agency))
{
	//phpAds_PageShortcut($strClientProperties, 'advertiser-edit.php?clientid='.$clientid, 'images/icon-advertiser.gif');
	//phpAds_PageShortcut($strTrackerHistory, 'stats-tracker-history.php?clientid='.$clientid.'&trackerid='.$trackerid, 'images/icon-statistics.gif');


	phpAds_PageHeader("4.1.4.5", $extra);
		echo "\t\t\t\t<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;".phpAds_getClientName($clientid)."\n";
		echo "\t\t\t\t<img src='images/".$phpAds_TextDirection."/caret-rs.gif'>\n";
		echo "\t\t\t\t<img src='images/icon-tracker.gif' align='absmiddle'>\n";
		echo "\t\t\t\t<b>".phpAds_getTrackerName($trackerid)."</b><br><br><br>\n";
		phpAds_ShowSections(array("4.1.4.2", "4.1.4.3", "4.1.4.5", "4.1.4.4"));
}


//Start


if (isset($trackerid) && $trackerid != '')
{

			echo "<form action='".$HTTP_SERVER_VARS['PHP_SELF']."?trackerid=$trackerid' method='post'>\n";
				echo "<input type='hidden' name='clientid' value='".$clientid."'";
				echo "<input type='hidden' name='trackerid' value='".$trackerid."'";
				echo "<input type='hidden' name='submit' value='true'>";
				echo "<input type='image' name='dummy' src='images/spacer.gif' border='0' width='1' height='1'>\n";
				echo "<br><br>\n";
				echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
					echo "<tr><td height='25' colspan='4' bgcolor='#FFFFFF'><b>".$strVariables."</b></td></tr>\n";
					echo "<tr><td height='1' colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>\n";

		if ($variables)
		{
				
			if (isset($action['del'])) 
			{
				$key = array_keys($action['del']);
				$variables[$key[0]]['delete']= true;
			}
			
					foreach ($variables as $k=>$v)
					{
						if (!isset($v['delete']))
						{
					
							// variable area
							echo "<tr><td height='25' colspan='4' bgcolor='#F6F6F6'>&nbsp;&nbsp;".$strTrackFollowingVars."</td></tr>\n";
							echo "<tr><td colspan='4'><img src='images/break-el.gif' width='100%' height='1'></td></tr>\n";
							echo "<tr><td colspan='4' bgcolor='#F6F6F6'><br></td></tr>\n";
							echo "<tr height='35' bgcolor='#F6F6F6' valign='top'>\n";
								echo "<td width='100'></td>\n";
								echo "<td width='130'><img src='images/icon-acl.gif' align='absmiddle'>&nbsp;Variable</td>\n";
								echo "<td>\n";
									echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>\n";
										echo "<tr>\n";
											echo "<td>".$strVariableName."</td>\n";
											echo "<td><input class='flat' type='text' name='name".$k."' value='".$v['name']."'></td>\n";
										echo "</tr>\n";
										echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
										echo "<tr>\n";
											echo "<td>".$strVariableDescription."</td>\n";
											echo "<td><input class='flat' type='text' name='description".$k."' value='".$v['description']."'></td>\n";
										echo "</tr>\n";
										echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
										echo "<tr>\n";
											echo "<td>".$strVariableType."</td>\n";
											echo "<td><select name='variabletype".$k."'>\n";
											echo "<option ".($v['variabletype'] =='js' ? 'selected ' : '')."value='js'>".$strJavascript."</option>\n";
											echo "<option ".($v['variabletype'] =='qs' ? 'selected ' : '')."value='qs'>".$strQuerystring."</option>\n";
											echo "</select></td>\n";
										echo "</tr>\n";
										echo "<tr><td colspan='2'>&nbsp;</td></tr>\n";
										echo "<tr>\n";
											echo "<td>".$strVariableDataType."</td>\n";
											echo "<td><select name='datatype".$k."'>\n";
											echo "<option ".($v['datatype'] =='string' ? 'selected ' : '')."value='string'>".$strString."</option>\n";
											echo "<option ".($v['datatype'] =='int' 	? 'selected ' : '')."value='int'>".$strInteger."</option>\n";
											echo "</select></td>\n";
										echo "</tr>\n";
									echo "</table>\n";
								echo"</td>\n";
								echo "<td align='right'><input type='image' name='action[del][".$k."]' src='images/icon-recycle.gif' border='0' align='absmiddle' alt='Delete'>&nbsp;&nbsp;</td>";
							echo "</tr>";
							echo "<tr bgcolor='#F6F6F6'>\n";
								echo "<td>&nbsp;</td>\n";
								echo "<td>&nbsp;</td>\n";
								echo "<td colspan='2'></td>\n";
							echo "</tr>\n";
							echo "<tr>";
								echo "<td height='1' colspan='4' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>";
							echo "</tr>";
						
						}
					
					}
					
					echo "<tr>";
						echo "<td colspan='4'><img src='images/spacer.gif' width='1' height='10' /></td>";
					echo "</tr>";
					
					echo "<tr>";
						echo "<td colspan='4' align='right'>";
							echo "<img src='images/icon-acl-add.gif' align='absmiddle'>&nbsp;&nbsp;".$strAddVariable."&nbsp;&nbsp;";
							echo "<input type='image' name='action[new]' src='images/".$phpAds_TextDirection."/go_blue.gif' border='0' align='absmiddle' alt='$strSave'>";
						echo "</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td colspan='4'><img src='images/spacer.gif' width='1' height='10' /></td>\n";
					echo "</tr>";
					echo "<tr>";
						echo "<td colspan='4'>";			
							echo "<input type='submit' name='action[save]' value='Save Changes' tabindex='15'>\n";
						echo "</td>";
					echo "</tr>";
				echo "</form>";
			
			
			echo "</table>";
	
		}
		else
		{
			echo "<tr><td height='25' colspan='4' bgcolor='#F6F6F6'>&nbsp;&nbsp;".$strNoVarsToTrack."</td></tr>\n";
			echo "<tr><td colspan='4'><img src='images/break-el.gif' width='100%' height='1'></td></tr>\n";
		
					echo "<tr>";
						echo "<td colspan='4'><img src='images/spacer.gif' width='1' height='10' /></td>";
					echo "</tr>";
					
					echo "<tr>";
						echo "<td colspan='4' align='right'>";
							echo "<img src='images/icon-acl-add.gif' align='absmiddle'>&nbsp;&nbsp;".$strAddVariable."&nbsp;&nbsp;";
							echo "<input type='image' name='action[new]' src='images/".$phpAds_TextDirection."/go_blue.gif' border='0' align='absmiddle' alt='$strSave'>";
						echo "</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td colspan='4'><img src='images/spacer.gif' width='1' height='10' /></td>\n";
					echo "</tr>";
					echo "<tr>";
						echo "<td colspan='4'>";			
							echo "<input type='submit' name='action[save]' value='Save Changes' tabindex='15'>\n";
						echo "</td>";
					echo "</tr>";
				echo "</form>";
			
			
			echo "</table>";
		}
		
}
/*********************************************************/
/* Store preferences                                     */
/*********************************************************/

$Session['prefs']['tracker-variables.php']['variables'] = $variables;
$Session['prefs']['tracker-variables.php']['trackerid'] = $trackerid;
	

phpAds_SessionDataStore();


phpAds_PageFooter();


//********************************************************
?>