<?php // $Revision: 1.1 $

/************************************************************************/
/* phpAdsNew 2                                                          */
/* ===========                                                          */
/*                                                                      */
/* Copyright (c) 2001 by the phpAdsNew developers                       */
/* http://sourceforge.net/projects/phpadsnew                            */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/



// Include required files
if (!defined('phpAds_installing'))
{
	include ("config.php");

	// Load settings language strings
	include('../language/'.$phpAds_config['language'].'/settings.lang.php');

}

// Load settings help language strings in desired language, if present
if (file_exists('../language/'.$phpAds_config['language'].'/settings-help.lang.php'))
{
	include('../language/'.$phpAds_config['language'].'/settings-help.lang.php');
}
else
{
	include('../language/english/settings-help.lang.php');
}

include_once('lib-config.inc.php');

$phpAds_config_locked = !phpAds_isConfigWritable();

$phpAds_settings_sections = array(
	"1"			=> "Main Settings",
	"1.1"		=> "Database settings",
	"1.1.1"		=> "Database server",
	"1.1.2"		=> "Database optimalisation",
	"1.2"		=> "Invocation and delivery settings",
	"1.2.1"		=> "Keyword retrieval",
	"1.2.2"		=> "Zone retrieval",
	"1.2.3"		=> "P3P Privacy Policies",
	"1.3"		=> "Banner settings",
	"1.3.1"		=> "HTML banner options",
	"1.3.2"		=> "Webserver stored banner options",
	"1.3.3"		=> "Default banners",
	"1.4"		=> "Statistics",
	"1.4.1"		=> "Statistisc format",
	"1.4.2"		=> "E-mail warnings",
	"1.4.3"		=> "Remote hosts",
	"2"			=> "Administration settings",
	"2.1"		=> "Administrator settings",
	"2.1.1"		=> "Login credentials",
	"2.1.2"		=> "Basic information",
	"2.1.3"		=> "Preferences",
	"2.2"		=> "User interface settings",
	"2.2.1"		=> "General settings",
	"2.2.2"		=> "Client interface",
	"2.2.3"		=> "Charts",
	"2.3"		=> "Interface defaults",
	"2.3.1"		=> "Statistics",
	"2.3.2"		=> "Default weight",
	"2.3.3"		=> "Available banner types"
);

$phpAds_settings_cache = array();
$phpAds_settings_cache_on = false;
$phpAds_settings_show_submit = !$phpAds_config_locked;



/*********************************************************/
/* Start a settings section                              */
/*********************************************************/

function phpAds_SettingsSelection($parent, $section)
{
?>
<script language="JavaScript">
<!--
function settings_goto_section()
{
	s = document.settings_selection.section.selectedIndex;

	s = document.settings_selection.section.options[s].value;
	document.location = 'settings-' + s + '.php';
}
// -->
</script>

<form name='settings_selection'>
  <table border='0' width='100%' cellpadding='0' cellspacing='0'>
    <tr> 
      <td height='35'><b>
        <?php echo $GLOBALS['strChooseSection'];?>
        :&nbsp;</b> 
        <select name='section' onChange='settings_goto_section();'>
          <?php

	if ($parent == 'main')
	{
		echo "<option value='db'".($section == 'db' ? ' selected' : '').">Database settings</option>";
		echo "<option value='invocation'".($section == 'invocation' ? ' selected' : '').">Invocation and delivery settings</option>";
		echo "<option value='banner'".($section == 'banner' ? ' selected' : '').">Banner settings</option>";
		echo "<option value='stats'".($section == 'stats' ? ' selected' : '').">Statistics settings</option>";
	}
	elseif ($parent == 'admin')
	{
		echo "<option value='admin'".($section == 'admin' ? ' selected' : '').">Administrator settings</option>";
		echo "<option value='interface'".($section == 'interface' ? ' selected' : '').">User interface settings</option>";
		echo "<option value='defaults'".($section == 'defaults' ? ' selected' : '').">Interface defaults</option>";
	}
?>
        </select>
        &nbsp;<a href='javascript:void(0)' onClick='settings_goto_section();'><img src='images/go_blue.gif' border='0'></a> 
      </td>
      <td height='35' align="right"><b><a href="javascript:toggleHelp();"><img src="images/help-book.gif" width="15" height="15" border="0" align="absmiddle">&nbsp;Help</a></b></td>
    </tr>
  </table>
</form>
<?php
}



/*********************************************************/
/* Start a settings section                              */
/*********************************************************/

function phpAds_settings_start_section($section)
{
	global $phpAds_settings_sections;
	
	if (!ereg("^([0-9]+\.)*([0-9]+)$", $section, $matches))
		die();
	
	$icon = defined('phpAds_installing') ? 'setup' : 'settings';
	$title = $phpAds_settings_sections[$section];

	echo "\t<br><br>\n\n";

?> 
	
<table border='0' width='100%' cellpadding='0' cellspacing='0'>
  <tr> 
    <td height='25' colspan='3'><img src="<?php echo "images/icon-${icon}.gif"; ?>" width="16" height="16" align="absmiddle">&nbsp;<b> 
      <?php echo $title?>
      </b></td>
  </tr>
  <tr height='1'> 
    <td bgcolor='#888888' width='30'><img src='images/break.gif' height='1' width='30'></td>
    <td bgcolor='#888888' width='200'><img src='images/break.gif' height='1' width='200'></td>
    <td bgcolor='#888888' width='100%'><img src='images/break.gif' height='1' width='1'></td>
  </tr>
  <tr> 
    <td height='10' colspan='3'><img src="images/spacer.gif" width="30" height="1"></td>
  </tr>
  <?php

	if (isset($GLOBALS['errormessage'][$matches[2]]))
	{
?>
  <tr> 
    <td width='30'>&nbsp;</td>
    <td height='10' colspan='2'> 
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr> 
          <td width="16" valign='top'><img src='images/error.gif' width='16' height='16'>&nbsp;&nbsp;</td>
          <td valign='top'><font color='#AA0000'><b> 
            <?php
		while (list(, $v) = each($GLOBALS['errormessage'][$matches[2]]))
			echo $v."<br>\n";

?>
            </b></font></td>
        </tr>
      </table>
    </td>
  </tr>
  <tr> 
    <td height='10' width="30">&nbsp;</td>
    <td height='10' width="200"><img src="images/spacer.gif" width="200" height="1"></td>
    <td height='10' width="100%">&nbsp;</td>
  </tr>
  <tr> 
    <td height="14" width="30"><img src='images/spacer.gif' height='1' width='100%'></td>
    <td height="14" width="200"><img src='images/break-l.gif' height='1' width='200' vspace='6'></td>
    <td height="14" width="100%">&nbsp;</td>
  </tr>
  <?php
	}
}



/*********************************************************/
/* Add a settings text field                             */
/*********************************************************/

function phpAds_settings_text($name, $text, $size = 25, $type = 'text', $rows = 5, $parent = '', $value = '')
{
	global $phpAds_config, $phpAds_settings_information;
	
	$extra = ' onFocus="setHelp('."'$name'".')" onBlur="setHelp('."'$name'".')"';

	if ($size == 35)
		$extra .= " style='width:350px;'";
	
	if (!defined('phpAds_installing') &&
		isset($phpAds_settings_information[$name]) &&
		!$phpAds_settings_information[$name]['sql'])
	{
		if ($GLOBALS['phpAds_config_locked'])
		{
			$padlock = '<img src="images/padlock-closed.gif">';
			$locked = true;
		}
		else
		{
			$padlock = '&nbsp;';
			$locked = false;
		}
	}
	else
	{
		$GLOBALS['phpAds_settings_show_submit'] = true;
		
		$padlock = '&nbsp;';
		$locked = false;
	}
	
	if (!empty($parent) || $locked)
		$extra .= !$locked && $phpAds_config[$parent] ? '' : ' disabled';
	
	if (empty($value))
	{
		if (isset($GLOBALS[$name]))
			$value = stripslashes($GLOBALS[$name]);
		else
			$value = $phpAds_config[$name];
	}
?>
  <tr onMouseOver="setHelp('<?php echo "$name";?>')"> 
    <td width='30'><?php echo $padlock;?></td>
    <td width='200'> 
      <?php echo $text; ?>
    </td>
    <td width="100%"> 
      <?php
	if ($type == 'textarea')
		echo "<textarea name='$name' size='$size' rows='$rows'$extra>".htmlentities($value)."</textarea>";
	elseif ($type == 'password')
		echo "<input type='password' name='$name' value='$value' size='$size'$extra>";
	else
		echo "<input type='text' name='$name' size='$size'$extra value='".htmlentities($value)."'>";
?>
    </td>
  </tr>
  <?php
}



/*********************************************************/
/* Add a settings select field                           */
/*********************************************************/

function phpAds_settings_select($name, $text, $options, $parent = '', $value = '')
{
	global $phpAds_config, $phpAds_settings_information;
	
	$extra = ' onFocus="setHelp('."'$name'".')" onBlur="setHelp('."'$name'".')"';
	
	if (!defined('phpAds_installing') &&
		isset($phpAds_settings_information[$name]) &&
		!$phpAds_settings_information[$name]['sql'])
	{
		if ($GLOBALS['phpAds_config_locked'])
		{
			$padlock = '<img src="images/padlock-closed.gif">';
			$locked = true;
		}
		else
		{
			$padlock = '&nbsp;';
			$locked = false;
		}
	}
	else
	{
		$GLOBALS['phpAds_settings_show_submit'] = true;
		
		$padlock = '&nbsp;';
		$locked = false;
	}
	
	if (!empty($parent) || $locked)
		$extra .= !$locked && $phpAds_config[$parent] ? '' : ' disabled';
	
	if (empty($value))
	{
		if (isset($GLOBALS[$name]))
			$value = stripslashes($GLOBALS[$name]);
		else
			$value = $phpAds_config[$name];
	}
	
	$options = unserialize($options);
	if (!is_array($options) || !count($options))
		return;
?>
  <tr onMouseOver="setHelp('<?php echo "$name";?>')"> 
    <td width='30'><?php echo $padlock;?></td>
    <td width='200'> 
      <?php echo $text; ?>
    </td>
    <td width="100%"> 
      <?php
		echo "<select name='$name'$extra>";
		while (list($k, $v) = each($options))
		{
			echo "<option value='".htmlentities($k)."'".
				($k == $value ? " selected" : "").">".
				htmlentities($v)."</option>";
		}
		echo "</select>\n";
?>
    </td>
  </tr>
  <?php
}



/*********************************************************/
/* Add a settings break                                  */
/*********************************************************/

function phpAds_settings_break()
{
?>
  <tr> 
    <td width="30"><img src='images/spacer.gif' height='1' width='100%'></td>
    <td width="200"><img src='images/break-l.gif' height='1' width='200' vspace='6'></td>
    <td width="100%">&nbsp;</td>
  </tr>
  <?php
}



/*********************************************************/
/* Add a settings checkbox                               */
/*********************************************************/

function phpAds_settings_checkbox($name, $text, $depends = '', $parent = '', $value = '')
{
	global $phpAds_config, $phpAds_settings_information;
	
	$extra = ' onFocus="setHelp('."'$name'".')" onBlur="setHelp('."'$name'".')"';
	$onClick = '';
	
	if (!defined('phpAds_installing') &&
		isset($phpAds_settings_information[$name]) &&
		!$phpAds_settings_information[$name]['sql'])
	{
		if ($GLOBALS['phpAds_config_locked'])
		{
			$padlock = '<img src="images/padlock-closed.gif">';
			$locked = true;
		}
		else
		{
			$padlock = '&nbsp;';
			$locked = false;
		}
	}
	else
	{
		$GLOBALS['phpAds_settings_show_submit'] = true;
		
		$padlock = '&nbsp;';
		$locked = false;
	}
	
	if (!empty($parent) || $locked)
		$extra .= !$locked && $phpAds_config[$parent] ? '' : ' disabled';
	
	if (empty($value))
	{
		if (isset($GLOBALS[$name]))
			$value = stripslashes($GLOBALS[$name]);
		else
			$value = $phpAds_config[$name] ? 't' : 'f';
	}
	
	if (!$locked)
	{
		$onClick .= "this.form.$name.value = this.checked ? 't' : 'f'";
		
		$depends = unserialize($depends);
		
		if (is_array($depends))
		{
			while(list(, $v) = each($depends))
			{
				if ($GLOBALS['phpAds_config_locked'] &&
					isset($phpAds_settings_information[$v]) &&
					!$phpAds_settings_information[$v]['sql'])
					continue;
				
				$onClick .= "; this.form.$v.disabled = this.checked ? false : true";
				$onClick .= "; if (this.form.$v.type == 'hidden') this.form.${v}_chkbx.disabled = this.checked ? false : true";
			}
		}
	}
	
	
?>
  <tr onMouseOver="setHelp('<?php echo "$name";?>')"> 
    <td width='30'><?php echo $padlock;?></td>
    <td colspan='2' width='100%'> 
      <?php
	echo "<input type='checkbox' name='${name}_chkbx'".($value == 't' ? ' checked' : '')." onClick=\"$onClick\"$extra>";
	echo $text;
	if (!$locked)
		echo "<input type='hidden' name='$name' value='$value'>";
?>
    </td>
  </tr>
  <?php
}



/*********************************************************/
/* End a settings section                                */
/*********************************************************/

function phpAds_settings_end_section()
{
?>
  <tr> 
    <td height='10' colspan='3'>&nbsp;</td>
  </tr>
  <tr height='1'> 
    <td colspan='3' bgcolor='#888888'><img src='images/break.gif' height='1' width='100%'></td>
  </tr>
</table>
	
	<br><br>
<?php
}

/*********************************************************/
/* Return Settings Help HTML Code                        */
/*********************************************************/

function phpAds_SettingsHelp($name)
{
	if (!isset($GLOBALS['phpAds_hlp_'.$name]))
		$GLOBALS['phpAds_hlp_'.$name] = '';

	return "helpArray['$name'] = '".
		str_replace("'", "\\'", ereg_replace("([\n\r\t]| +)", " ", $GLOBALS['phpAds_hlp_'.$name])).
		"';\n";
}



/*********************************************************/
/* Settings GUI Functions Wrappers                       */
/*********************************************************/

function phpAds_StartSettings()
{
	global $phpAds_settings_help_cache;
	global $phpAds_settings_cache_on;
	
	// Turn on caching
	if (!$phpAds_settings_cache_on)
		$phpAds_settings_cache_on = true;
		
	$phpAds_settings_help_cache = "<script language=\"JavaScript\">\n".
		"<!--\n".
		"\n\tvar helpArray = new Array();\n\n";
}



function phpAds_EndSettings()
{
	global $phpAds_settings_help_cache;
	
	$phpAds_settings_help_cache .= "//-->\n".
		"</script>\n";
}



function phpAds_AddSettings($type, $name, $args = '')
{
	global $phpAds_settings_information;
	global $phpAds_settings_cache;
	global $phpAds_settings_help_cache;
	global $phpAds_config_locked;
	global $phpAds_settings_show_submit;
		
	// If $args is empty, set it to empty array
	if (empty($args))
		$args = array();
	elseif (!is_array($args))
		$args = array($args);
		
	while (list($k, $v) = each($args))
	{
		if (is_array($v))
			$v = serialize($v);
		
		$args[$k] = str_replace("'", "\\'", $v);
	}
	
	switch ($type)
	{
		case 'text':
		case 'select':
		case 'colorpicker':
		case 'checkbox':
			$phpAds_settings_help_cache .= phpAds_SettingsHelp($name);
			$phpAds_settings_cache[] =
				"phpAds_settings_".$type."('$name', '".
				join("', '", $args).
				"')";
			break;
		case 'start_section':
			$phpAds_settings_cache[] =
				"phpAds_settings_".$type."('$name')";
			break;
		case 'end_section':
		case 'break':
			$phpAds_settings_cache[] =
				"phpAds_settings_".$type."()";
			break;
		default:
			return '';
	}
	
	return;	
}

function phpAds_FlushSettings()
{
	global $phpAds_settings_cache, $phpAds_settings_cache_on;
	global $phpAds_settings_help_cache;
	global $phpAds_config_locked;
	global $phpAds_settings_show_submit;
	
	if (!$phpAds_settings_cache_on)
		return;
	
	$phpAds_settings_cache_on = false;
	
	if (!defined('phpAds_installing'))
	{
	$image = $phpAds_config_locked ? 'closed' : 'open';
	
	echo "<br>";
	echo "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
	echo "<tr><td valign='top'><img src='images/padlock-$image.gif' width='16' height='16' border='0' align='absmiddle'>&nbsp;&nbsp;</td><td>";
	
	echo $phpAds_config_locked ?
		"It is not possible to edit these settings because the configuration file is locked for security reasons. ".
		"If you want to make changes, you need to unlock the config.inc.php file first."
		:
		"It is possible to edit all settings because the configuration file is not locked, but this could lead to security leaks. ".
		"If you want to secure your system, you need to lock the config.inc.php file.";
		
	echo "</td></tr><tr><td colspan='2'><img src='images/break.gif' height='1' width='100%' vspace='8'></td></tr></table>";
	echo "\n";
	}
	
	if (!empty($phpAds_settings_help_cache))
		echo $phpAds_settings_help_cache;
		
	if (count($phpAds_settings_cache))
		eval(join("; ", $phpAds_settings_cache).";");
	
	if (!defined('phpAds_installing') && $phpAds_settings_show_submit)
		echo '<input type="submit" value="'.$GLOBALS['strSaveChanges'].'">';

}

?>