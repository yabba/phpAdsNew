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



// Include required files 
require ("config.php");
require ("lib-statistics.inc.php");
require ("lib-languages.inc.php");


// Register input variables
phpAds_registerGlobal (
	 'errormessage'
	,'agencyid'
	,'name'
	,'contact'
	,'email'
	,'language'
	,'username'
	,'password'
	,'submit'
);


// Security check
phpAds_checkAccess(phpAds_Admin);

/*********************************************************/
/* Interface security                                    */
/*********************************************************/


/*********************************************************/
/* Process submitted form                                */
/*********************************************************/

if (isset($submit))
{
	$errormessage = array();
	
	// Get previous values
	if (isset($agencyid) && ($agencyid != ''))
	{
		$res = phpAds_dbQuery("
			SELECT
				*
			FROM 
				".$phpAds_config['tbl_agency']."
			WHERE
				agencyid = ".$agencyid
		) or phpAds_sqlDie();
		
		if (phpAds_dbNumRows($res))	
			$agency = phpAds_dbFetchArray($res);
	}
	
	// Name
	$agency['name'] = trim($name);
	
	// Default fields
	$agency['contact'] 	 	= trim($contact);
	$agency['email'] 	 	= trim($email);
	$agency['language']   	= trim($language);
		
	// Password
	if (isset($password))
	{
		if ($password == '')
			$agency['password'] = '';
		elseif ($password != '********')
			$agency['password'] = md5($password);
	}
		
	// Username
	if (isset($username) && $username != '')
	{
		// Check whether chosen username already exists
		$res = phpAds_dbQuery("
			SELECT
				username
			FROM
				".$phpAds_config['tbl_affiliates']."
			WHERE
				LOWER(username) = '".strtolower($username)."'
		") or phpAds_sqlDie();

		if (phpAds_dbNumRows($res) > 0)
			$duplicateaffiliate 	= phpAds_dbNumRows($res);

		if (strtolower($phpAds_config['admin']) == strtolower($username))
			$duplicateadmin = $phpAds_config['admin'];
	
			if ($agencyid == '')
			{
				$res = phpAds_dbQuery("
					SELECT
						username
					FROM
						".$phpAds_config['tbl_agency']."
					WHERE
						LOWER(username) = '".strtolower($username)."'
				") or phpAds_sqlDie(); 
				
				if (phpAds_dbNumRows($res) > 0 || $duplicateaffiliate || $duplicateadmin)
					$errormessage[] = $strDuplicateAgencyName;
			}
			else
			{
				$res = phpAds_dbQuery("
					SELECT
						*
					FROM
						".$phpAds_config['tbl_agency']."
					WHERE
						LOWER(username) = '".strtolower($username)."' AND
						agencyid != '$agencyid'
					") or phpAds_sqlDie(); 
				
				if (phpAds_dbNumRows($res) > 0 || $duplicateaffiliate || $duplicateadmin)
					$errormessage[] = $strDuplicateAgencyName;
			}
	}
			
	if (count($errormessage) == 0)
		$agency['username'] = $username;

	// Password
	if (isset($pwold) && strlen($pwold) || isset($pw) && strlen($pw) ||	isset($pw2) && strlen($pw2))
	{
		if (md5($pwold) != $agency['password'])
			$errormessage[] = $strPasswordWrong;
		elseif (!strlen($pw) || strstr("\\", $pw))
			$errormessage[] = $strInvalidPassword;
		elseif (strcmp($pw, $pw2))
			$errormessage[] = $strNotSamePasswords;
		else
			$agency['password'] = md5($pw);
	}


	if (count($errormessage) == 0)
	{
		if (!isset($agencyid) || $agencyid == '')
		{
			$keys = array();
			$values = array();
			
			while (list($key, $value) = each($agency))
			{
				$keys[] = $key;
				$values[] = $value;
			}
			
			$query  = "INSERT INTO ".$phpAds_config['tbl_agency']." (";
			$query .= implode(", ", $keys);
			$query .= ") VALUES ('";
			$query .= implode("', '", $values);
			$query .= "')";
			
			// Insert
			phpAds_dbQuery($query) 
				or phpAds_sqlDie();
			
			$agencyid = phpAds_dbInsertID();
			
			// When adding an agency, copy the values in the config table to this new agency
			$query = "SELECT * FROM ".$phpAds_config['tbl_config']." WHERE agencyid=0";
			$res = phpAds_dbQuery($query)
				or phpAds_sqlDie();
			if ($row = phpAds_dbFetchArray($res))
			{
				$row['agencyid'] = $agencyid;
				
				$keys = array_keys($row);
				$values = array_values($row);
				$query = "INSERT INTO ".$phpAds_config['tbl_config']."(".implode(',',$keys).") VALUES ('".implode("','",$values)."')";
				phpAds_dbQuery($query)
					or phpAds_sqlDie();
			}
		}
		else
		{
			$pairs = array();
			
			while (list($key, $value) = each($agency))
				$pairs[] = " ".$key."='".$value."'";
			
			$query  = "UPDATE ".$phpAds_config['tbl_agency']." SET ";
			$query .= trim(implode(",", $pairs))." ";
			$query .= "WHERE agencyid = ".$agencyid;
			
			// Update
			phpAds_dbQuery($query) 
				or phpAds_sqlDie();
		}

		// Go to next page
		header ("Location: agency-index.php");
		exit;

	}
	else
	{
		// If an error occured set the password back to its previous value
		$agency['password'] = $password;
	}
	

}
/*********************************************************/
/* Process submitted form  END                           */
/*********************************************************/



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

if ($agencyid != '')
{
		if (isset($Session['prefs']['agency-index.php']['listorder']))
			$navorder = $Session['prefs']['agency-index.php']['listorder'];
		else
			$navorder = '';
		
		if (isset($Session['prefs']['agency-index.php']['orderdirection']))
			$navdirection = $Session['prefs']['agency-index.php']['orderdirection'];
		else
			$navdirection = '';
		

		$query = "	SELECT 
						*
					FROM 
						".$phpAds_config['tbl_agency'];

		// Get other agencies
		$res = phpAds_dbQuery($query)
			or phpAds_sqlDie();
		
		while ($row = phpAds_dbFetchArray($res))
		{
			phpAds_PageContext (
				phpAds_buildName ($row['agencyid'], $row['name']),
				"agency-edit.php?agencyid=".$row['agencyid'],
				$agencyid == $row['agencyid']
			);
		}
		
		phpAds_PageHeader("5.5.2");
			echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;<b>".phpAds_getAgencyName($agencyid)."</b><br><br><br>";
		phpAds_ShowSections(array("5.5.2"));
	
	
	// Do not get this information if the page
	// is the result of an error message
	if (!isset($agency))
	{
		$res = phpAds_dbQuery("SELECT * FROM ".$phpAds_config['tbl_agency']." WHERE agencyid=".$agencyid)
			or phpAds_sqlDie();
		
		if (phpAds_dbNumRows($res))
		{
			$agency = phpAds_dbFetchArray($res);
		}
		
		// Set password to default value
		if ($agency['password'] != '')
			$agency['password'] = '********';
	}
}
else
{
		phpAds_PageHeader("5.5.1");
			echo "<img src='images/icon-advertiser.gif' align='absmiddle'>&nbsp;<b>".phpAds_getClientName($agencyid)."</b><br><br><br>";
		//phpAds_ShowSections(array("5.1", "5.3", "5.4", "5.2", "5.5"));
		phpAds_ShowSections(array("5.5.1"));
	
	// Do not set this information if the page
	// is the result of an error message
	if (!isset($agency))
	{
		$agency['name']			= $strUntitled;
		$agency['contact']		= '';
		$agency['email']		= '';
		
		$agency['username']		= '';
		$agency['password']		= '';
	}
}

$tabindex = 1;



/*********************************************************/
/* Main code                                             */
/*********************************************************/

echo "<br><br>";
echo "<form name='agencyform' method='post' action='agency-edit.php' onSubmit='return phpAds_formCheck(this);'>";
echo "<input type='hidden' name='agencyid' value='".(isset($agencyid) && $agencyid != '' ? $agencyid : '')."'>";

// Header
echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
echo "<tr><td height='25' colspan='3'><b>".$strBasicInformation."</b></td></tr>";
echo "<tr height='1'><td width='30'><img src='images/break.gif' height='1' width='30'></td>";
echo "<td width='200'><img src='images/break.gif' height='1' width='200'></td>";
echo "<td width='100%'><img src='images/break.gif' height='1' width='100%'></td></tr>";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>";

// Agency Name
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strName."</td>";

echo "<td><input onBlur='phpAds_formUpdate(this);' class='flat' type='text' name='name' size='25' value='".phpAds_htmlQuotes($agency['name'])."' style='width: 350px;' tabindex='".($tabindex++)."'></td>";

echo "</tr><tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";


// Contact
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strContact."</td><td>";
echo "<input onBlur='phpAds_formUpdate(this);' class='flat' type='text' name='contact' size='25' value='".phpAds_htmlQuotes($agency['contact'])."' style='width: 350px;' tabindex='".($tabindex++)."'>";
echo "</td></tr><tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";

// Email
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strEMail."</td><td>";
echo "<input onBlur='phpAds_formUpdate(this);' class='flat' type='text' name='email' size='25' value='".phpAds_htmlQuotes($agency['email'])."' style='width: 350px;' tabindex='".($tabindex++)."'>";
echo "</td></tr><tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";

// Language
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strLanguage."</td><td>";
echo "<select name='language' tabindex='".($tabindex++)."'>";
echo "<option value='' SELECTED>".$strDefault."</option>"; 

$languages = phpAds_AvailableLanguages();
while (list($k, $v) = each($languages))
{
	if (isset($agency['language']) && $agency['language'] == $k)
		echo "<option value='$k' selected>$v</option>";
	else
		echo "<option value='$k'>$v</option>";
}

echo "</select></td></tr><tr><td height='10' colspan='3'>&nbsp;</td></tr>";

// Footer
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
echo "</table>";

// Spacer
echo "<br><br>";
echo "<br><br>";

// Header
echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
echo "<tr><td height='25' colspan='3'><b>".$strLoginInformation."</b></td></tr>";
echo "<tr height='1'><td width='30'><img src='images/break.gif' height='1' width='30'></td>";
echo "<td width='200'><img src='images/break.gif' height='1' width='200'></td>";
echo "<td width='100%'><img src='images/break.gif' height='1' width='100%'></td></tr>";
echo "<tr><td height='10' colspan='3'>&nbsp;</td></tr>";


// Error message?
if (isset($errormessage) && count($errormessage))
{
	echo "<tr><td>&nbsp;</td><td height='10' colspan='2'>";
	echo "<table cellpadding='0' cellspacing='0' border='0'><tr><td>";
	echo "<img src='images/error.gif' align='absmiddle'>&nbsp;";
	
	while (list($k,$v) = each($errormessage))
		echo "<font color='#AA0000'><b>".$v."</b></font><br>";
	
	echo "</td></tr></table></td></tr><tr><td height='10' colspan='3'>&nbsp;</td></tr>";
	echo "<tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
	echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";
}


echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strUsername."</td>";
echo "<td><input onBlur='phpAds_formUpdate(this);' class='flat' type='text' name='username' size='25' value='".phpAds_htmlQuotes($agency['username'])."' tabindex='".($tabindex++)."'></td>";
echo "</tr><tr><td><img src='images/spacer.gif' height='1' width='100%'></td>";
echo "<td colspan='2'><img src='images/break-l.gif' height='1' width='200' vspace='6'></td></tr>";


// Password
echo "<tr><td width='30'>&nbsp;</td><td width='200'>".$strPassword."</td>";
echo "<td width='370'><input class='flat' type='password' name='password' size='25' value='".$agency['password']."' tabindex='".($tabindex++)."'></td>";
echo "</tr><tr><td height='10' colspan='3'>&nbsp;</td></tr>";
// Footer
echo "<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>";
echo "</table>";

echo "<br><br>";
echo "<input type='submit' name='submit' value='".$strSaveChanges."' tabindex='".($tabindex++)."'>";
echo "</form>";



/*********************************************************/
/* Form requirements                                     */
/*********************************************************/

// Get unique agencyname
$unique_names = array();

$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_agency'].
	" WHERE agencyid != ".$agencyid
);

while ($row = phpAds_dbFetchArray($res))
	$unique_names[] = $row['name'];


// Get unique username
$unique_users = array($phpAds_config['admin']);

$res = phpAds_dbQuery(
	"SELECT *".
	" FROM ".$phpAds_config['tbl_agency'].
	" WHERE username != ''".
	" AND agencyid != ".$agencyid
);

while ($row = phpAds_dbFetchArray($res))
	$unique_users[] = $row['username'];

$res = phpAds_dbQuery("SELECT * FROM ".$phpAds_config['tbl_affiliates']." WHERE username != ''");
while ($row = phpAds_dbFetchArray($res))
	$unique_users[] = $row['username'];

?>

<script language='JavaScript'>
<!--
	phpAds_formSetRequirements('contact', '<?php echo addslashes($strContact); ?>', true);
	phpAds_formSetRequirements('email', '<?php echo addslashes($strEMail); ?>', true, 'email');
<?php if (phpAds_isUser(phpAds_Admin)) { ?>
	phpAds_formSetRequirements('name', '<?php echo addslashes($strName); ?>', true, 'unique');
	phpAds_formSetRequirements('username', '<?php echo addslashes($strUsername); ?>', false, 'unique');
	
	phpAds_formSetUnique('name', '|<?php echo addslashes(implode('|', $unique_names)); ?>|');
	phpAds_formSetUnique('username', '|<?php echo addslashes(implode('|', $unique_users)); ?>|');
<?php } ?>
//-->
</script>

<?php



/*********************************************************/
/* HTML framework                                        */
/*********************************************************/

phpAds_PageFooter();

?>