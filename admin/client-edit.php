<?

require ("config.php");
require ("lib-statistics.inc.php");

phpAds_checkAccess(phpAds_Admin+phpAds_Client);


if (phpAds_isUser(phpAds_Client))
{
	if (!phpAds_isAllowed(phpAds_ModifyInfo))
	{
		phpAds_PageHeader("$phpAds_name");
		phpAds_ShowNav("2.3");
		php_die ($strAccessDenied, $strNotAdmin);
	}

	$clientID = $Session["clientID"];
}


// If submit is set, shove the data into the database (well, after some error checking)
if (isset($submit))
{ 
	if (phpAds_isUser(phpAds_Admin))
	{
		// If ID is not set, it should be a null-value for the auto_increment
		$message = $strClientModified;
		
		if (empty($clientID))
		{
			$clientID = "null";
			$message = $strClientAdded;
		}
		
		if (strtolower($unlimitedviews)=="on")
			$views=-1;
		if (strtolower($unlimitedclicks)=="on")
			$clicks=-1;
		
		if (strtolower($unlimiteddays_left)=="on")
			$expire = '0000-00-00';
		else
			$expire = "DATE_ADD(CURDATE(), INTERVAL $days_left DAY)";
		
		if($clicks>0 OR $views>0 OR $clicks==-1 OR $views==-1)
		{
			$active="true";
			db_query("UPDATE $phpAds_tbl_banners SET active='$active' WHERE clientID='$clientID'");
		}
		
		$permissions = 0;
		for ($i=0;$i<sizeof($clientpermissions);$i++)
		{
			$permissions += $clientpermissions[$i];
		}
		
		
		$res = db_query("
			REPLACE INTO
				$phpAds_tbl_clients(clientID,
				clientname,
				contact,
				email,
				views,
				clicks,
				clientusername,
				clientpassword,
				expire,
				permissions,
				language)
			VALUES
				('$clientID',
				'$clientname',
				'$contact',
				'$email',
				'$views',
				'$clicks',
				'$clientusername',
				'$clientpassword',
				$expire,
				$permissions,
				'$clientlanguage')")
			or mysql_die();  
		
		Header("Location: admin.php?message=".urlencode($message));
		exit;
	}
	
	if (phpAds_isUser(phpAds_Client))
	{
		$message = $strClientModified;
		$res = db_query("
			UPDATE 
				$phpAds_tbl_clients
			SET
				clientname = '$clientname',
				contact = '$contact',
				email = '$email',
				clientpassword = '$clientpassword',
				language = '$clientlanguage'
			WHERE
				clientID = '$clientID'")
			or mysql_die();  
		
		$Session[language] = $clientlanguage;
		phpAds_SessionDataStore();
		
		Header("Location: index.php?message=".urlencode($message));
		exit;
	}
}







// If we find an ID, means that we're in update mode  
if ($clientID != "")
{
	if (phpAds_isUser(phpAds_Admin))
	{
		phpAds_PageHeader("$strModifyClient");
	
		$res = db_query("
			SELECT
				*
			FROM
				$phpAds_tbl_clients  
			") or mysql_die();
		
		while ($row = mysql_fetch_array($res))
		{
			if ($clientID == $row[clientID])
				$extra .= "&nbsp;&nbsp;&nbsp;<img src='images/box-1.gif'>&nbsp;";
			else
				$extra .= "&nbsp;&nbsp;&nbsp;<img src='images/box-0.gif'>&nbsp;";
			
			$extra .= "<a href=client-edit.php?clientID=$row[clientID]>".phpAds_buildClientName ($row[clientID], $row[clientname])."</a>";
			$extra .= "<br>"; 
		}
		$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
		
		$extra .= "<br><br><br><br><br>";
		$extra .= "<b>$strShortcuts</b><br>";
		$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
		$extra .= "<img src='images/caret-rs.gif'>&nbsp;<a href=banner-client.php?clientID=$clientID>$strBannerAdmin</a><br>";
		$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";
		$extra .= "<img src='images/caret-rs.gif'>&nbsp;<a href=stats-client.php?clientID=$clientID>$strStats</a><br>";
		$extra .= "&nbsp;&nbsp;&nbsp;<img src='images/caret-rs.gif'>&nbsp;<a href=stats-weekly.php?clientID=$clientID>$strWeeklyStats</a><br>";
		$extra .= "<img src='images/break.gif' height='1' width='160' vspace='4'><br>";

		
		phpAds_ShowNav("1.2", $extra);
	}
	else
	{
		phpAds_PageHeader("$strPreferences");
		phpAds_ShowNav("2.3");
	}
	
	$res = db_query("
		SELECT
			*,
			to_days(expire) as expire_day,
			to_days(curdate()) as cur_date
		FROM
			$phpAds_tbl_clients
		WHERE
			clientID = $clientID
		") or mysql_die();
	$row = mysql_fetch_array($res);
	
	if ($row["expire"]=="0000-00-00")
		$days_left=-1;
	else
		$days_left=$row["expire_day"] - $row["cur_date"];
}
else
{
	phpAds_PageHeader("$strAddClient");
	phpAds_ShowNav("1.1");   
}

if ($row["views"] == "")
	$row["views"] = -1;
if ($row["clicks"] == "")
	$row["clicks"] = -1;
if ($days_left == "")
	$days_left = -1;

if (phpAds_isUser(phpAds_Admin))
{
	?>

	<script language="JavaScript">
	<!--
		function typeviews()
		{
			checkbox = document.clientform.unlimitedviews.checked;
			inputfield = document.clientform.views.value;
			
			if (checkbox == true)
			{
				document.clientform.unlimitedviews.checked = false;
			}
		}

		function typeclicks()
		{
			checkbox = document.clientform.unlimitedclicks.checked;
			inputfield = document.clientform.clicks.value;
			
			if (checkbox == true)
			{
				document.clientform.unlimitedclicks.checked = false;
			}
		}

		function typedays_left()
		{
			checkbox = document.clientform.unlimiteddays_left.checked;
			inputfield = document.clientform.days_left.value;
			
			if (checkbox == true)
			{
				document.clientform.unlimiteddays_left.checked = false;
			}
		}

		
		function checkunlimitedviews()
		{
			if (eval(document.clientform.unlimitedviews.checked) == true)
			{
				document.clientform.views.value="-";
			} else
			{
				document.clientform.views.value="";
				document.clientform.views.focus();
			}
		}
		function checkunlimitedclicks()
		{
			if (eval(document.clientform.unlimitedclicks.checked) == true)
			{
				document.clientform.clicks.value="-";
			} else
			{
				document.clientform.clicks.value="";
				document.clientform.clicks.focus();
			}
		}
		function checkunlimiteddays_left()
		{
			if (eval(document.clientform.unlimiteddays_left.checked) == true)
			{
				document.clientform.days_left.value="-";
			} else
			{
				document.clientform.days_left.value="";
				document.clientform.days_left.focus();
			}
		}
		function valid(form)
		{
			var views=parseInt(form.views.value);
			if (!views)
			{
				if (eval(form.unlimitedviews.checked) == false)
				{
					alert("<?print $GLOBALS['strErrorViews'];?>");
					return false;
				}
			} else if (views < 0)
			{
				alert("<?print $GLOBALS['strErrorNegViews'];?>");
				return false;
			}
			var clicks=parseInt(form.clicks.value);
			if (!clicks)
			{
				if (eval(form.unlimitedclicks.checked) == false)
				{
					alert("<?print $GLOBALS['strErrorClicks'];?>");
					return false;
				}
			} else if (clicks < 0)
			{
				alert("<?print $GLOBALS['strErrorNegClicks'];?>");
				return false;
			}
			var days_left=parseInt(form.days_left.value);
			if (!days_left)
			{
				if (eval(form.unlimiteddays_left.checked) == false)
				{
					alert("<?print $GLOBALS['strErrorDays'];?>");
					return false;
				}
			} else if (days_left < 0)
			{
				alert("<?print $GLOBALS['strErrorNegDays'];?>");
				return false;
			}
		}
	//-->
	</script>
	<?
}
?>





<form name="clientform" method="post" action="<?echo basename($PHP_SELF);?>" onSubmit="return valid(this)">
<input type="hidden" name="clientID" value="<?if(IsSet($clientID)) echo $clientID;?>">

<table border='0' width='100%' cellpadding='0' cellspacing='0'>
	<tr><td height='25' colspan='3'><b><?echo $strBasicInformation;?></b></td></tr>
	<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>
	<tr height='30'>
		<td width='200'><?echo $strClientName;?></td>
		<td colspan='2'><input type="text" name="clientname" size='50' value="<?if(isset($row["clientname"]))echo $row["clientname"];?>"></td>
	</tr>
	<tr height='30'>
		<td width='200'><?echo $strContact;?></td>
		<td colspan='2'><input type="text" name="contact" size='50' value="<?if(isset($row["contact"]))echo $row["contact"];?>"></td>
	</tr>
	<tr height='30'>
		<td width='200'><?echo $strEMail;?></td>
		<td colspan='2'><input type="text" name="email" size='50' value="<?if(isset($row["email"]))echo $row["email"];?>"></td>
	</tr>
	<tr height='30'>
		<td width='200'><?echo $GLOBALS[strLanguage]; ?></td>	
		<td colspan='2'>
			<select name="clientlanguage">
		<?
		echo "<option value='' SELECTED>".$GLOBALS[strDefault]."</option>\n";

		$langdir = opendir ("../language/");
		while ($langfile = readdir ($langdir))
		{
			if (ereg ("^([a-zA-Z0-9\-]*)\.inc\.php$", $langfile, $matches))
			{
				$option = $matches[1];
				if ($row["language"] == $option)
					echo "<option value='$option' SELECTED>".ucfirst($option)."</option>\n";
				else
					echo "<option value='$option'>".ucfirst($option)."</option>\n";
			}
		}
		closedir ($langdir);
		?>
			</select>
		</td>
	</tr>
	<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>
</table>

<br><br>

<table border='0' width='100%' cellpadding='0' cellspacing='0'>
	<tr><td height='25' colspan='3'><b><?echo $strContractInformation;?></b></td></tr>
	<tr height='1'><td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>
	<tr height='30'>
		<td width='200'><?echo $strViewsPurchased;?></td>
		<?
		if (phpAds_isUser(phpAds_Admin))
		{
			?>
			<td>
				<input type="text" name="views" size='25' value="<?if($row["views"]!=-1)echo $row["views"];else echo '-';?>" onKeyUp="typeviews();">
				<input type="checkbox" name="unlimitedviews"<?if($row["views"]==-1)print " CHECKED";?> onClick="checkunlimitedviews();">
				<? echo $GLOBALS['strUnlimited']; ?>
			</td>
			<?
		}
		else {
			?>
			<td><?if($row["views"]!=-1)echo $row["views"];else echo $GLOBALS['strUnlimited'];?></td>
			<?
		}
		?>
	</tr>
	<tr height='30'>
		<td width='200'><?echo $strClicksPurchased;?></td>
		<?
		if (phpAds_isUser(phpAds_Admin))
		{
			?>
			<td>
				<input type="text" name="clicks" size='25' value="<?if($row["clicks"]!=-1)echo $row["clicks"];else echo '-';?>" onKeyUp="typeclicks();">
				<input type="checkbox" name="unlimitedclicks"<?if($row["clicks"]==-1)print " CHECKED";?> onClick="checkunlimitedclicks();">
				<? echo $GLOBALS['strUnlimited']; ?>
			</td>
			<?
		}
		else {
			?>
			<td><?if($row["clicks"]!=-1)echo $row["clicks"];else echo $GLOBALS['strUnlimited'];?></td>
			<?
		}
		?>
	</tr>
	<tr height='30'>
		<td width='200'><?echo $strDaysPurchased;?></td>
		<?
		if (phpAds_isUser(phpAds_Admin))
		{
			?>
			<td>
				<input type="text" name="days_left" size='25' value="<?if($days_left!=-1)echo $days_left;else echo '-';?>" onKeyUp="typedays_left();">
				<input type="checkbox" name="unlimiteddays_left"<?if($days_left==-1)print " CHECKED";?> onClick="checkunlimiteddays_left();">
				<? echo $GLOBALS['strUnlimited']; ?>
			</td>
			<?
		}
		else 
		{
			?>
			<td><?if($days_left!=-1)echo $days_left;else echo $GLOBALS['strUnlimited'];?></td>
			<?
		}
		?>
	</tr>
	<tr height='1'><td colspan='2' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>
</table>

<br><br>

<table border='0' width='100%' cellpadding='0' cellspacing='0'>
	<tr><td height='25' colspan='2'><b><?echo $strLoginInformation;?></b></td></tr>
	<tr height='1'><td colspan='2' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>
	<tr height='30'>
		<td width='200'><?echo $strUsername;?></td>
		<?
		if (phpAds_isUser(phpAds_Admin))
		{
			?>
			<td><input type="text" name="clientusername" size='25' value="<?if(isset($row["clientusername"]))echo $row["clientusername"];?>">
			<?
		}
		else 
		{
			?>
			<td><?if(isset($row["clientusername"]))echo $row["clientusername"];?>
			<?
		}
		?>
	</tr>
	<tr height='30'>
		<td width='200'><?echo $strPassword;?></td>
		<td><input type="text" name="clientpassword" size='25' value="<?if(isset($row["clientpassword"]))echo $row["clientpassword"];?>">
	</tr>
	<tr height='1'><td colspan='2' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>
</table>

<br><br>
		<?
		if (phpAds_isUser(phpAds_Admin))
		{
			?>
<table border='0' width='100%' cellpadding='0' cellspacing='0'>
	<tr><td height='25' colspan='2'><b><?echo $strPermissions;?></b></td></tr>
	<tr height='1'><td colspan='2' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td></tr>
	<tr>
		<td colspan'2'>
			<input type="checkbox" name="clientpermissions[]" value="<?echo phpAds_ModifyInfo; ?>"<?echo (phpAds_ModifyInfo & $row["permissions"]) ? " CHECKED" : ""; ?>>
			<?echo $GLOBALS[strAllowClientModifyInfo]; ?>
		</td>
	</tr>
	<tr>
		<td colspan'2'>
			<input type="checkbox" name="clientpermissions[]" value="<?echo phpAds_ModifyBanner; ?>"<?echo (phpAds_ModifyBanner & $row["permissions"]) ? " CHECKED" : ""; ?>>
			<?echo $GLOBALS[strAllowClientModifyBanner]; ?>
		</td>
	</tr>	
	<!-- Still working on this (Niels)
	<tr>
		<td colspan'2'>
			<input type="checkbox" name="clientpermissions[]" value="<?echo phpAds_AddBanner; ?>"<?echo (phpAds_AddBanner & $row["permissions"]) ? " CHECKED" : ""; ?>>
			<?echo $GLOBALS[strAllowClientAddBanner]; ?>
		</td>
	</tr>	
	-->
</table>
			<?
		}
		?>
		
<br><br>
		
<input type="submit" name="submit" value="<?echo $strSaveChanges;?>">

</form>

<?
phpAds_PageFooter();
?>