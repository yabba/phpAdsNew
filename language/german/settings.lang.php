<?php // $Revision: 1.4 $

/************************************************************************/
/* phpAdsNew 2                                                          */
/* ===========                                                          */
/*                                                                      */
/* Copyright (c) 2001 by the phpAdsNew developers                       */
/* http://sourceforge.net/projects/phpadsnew                            */
/* Translations by Stefan Morgenroth (dandra@users.sf.net)              */
/*                                                                      */
/* This program is free software. You can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License.       */
/************************************************************************/



// Installer translation strings
$GLOBALS['strInstall']					= "Installation";
$GLOBALS['strChooseInstallLanguage']	= "W�hle die Sprache f�r die Installation";
$GLOBALS['strLanguageSelection']		= "Sprachauswahl";
$GLOBALS['strDatabaseSettings']			= "Datenbankeinstellungen";
$GLOBALS['strAdminSettings']			= "Administratoreinstellungen";
$GLOBALS['strAdvancedSettings']			= "Erweiterte Einstellungen";
$GLOBALS['strOtherSettings']			= "Sonstige Einstellungen";

$GLOBALS['strWarning']					= "Warnung";
$GLOBALS['strFatalError']				= "Ein fataler Fehler ist aufgetreten";
$GLOBALS['strAlreadyInstalled']			= "phpAdsNew ist bereits auf diesem System installiert. Zur Konfiguration bitte das <a href='settings-index.php'>Einstellungsinterface</a> nutzen.";
$GLOBALS['strCouldNotConnectToDB']		= "Es kann keine Verbindung zu Datenbank hergestellt werden. Bitte �berpr�fe die vorgenommenen Einstellungen";
$GLOBALS['strCreateTableTestFailed']	= "Der gew�hlte User hat keine Rechte eine Datenbankstruktur zu erstellen bzw. zu ver�ndern. Bitte kontaktiere den Datenbankadministrator.";
$GLOBALS['strUpdateTableTestFailed']	= "Der gew�hlte User hat keine Rechte eine Datenbankstruktur zu erstellen bzw. zu ver�ndern. Bitte kontaktiere den Datenbankadministrator.";
$GLOBALS['strTablePrefixInvalid']		= "Die Tabellennamensvorgabe enth�lt ung�ltige Zeichen";

$GLOBALS['strInstallMessage']			= "Es wird nun phpAdsNew auf dem System installiert. Bitte klicke <b>Fortsetzen</b>, um den Vorgang zu starten.";
$GLOBALS['strConfigLockedDetected']		= "phpAdsNew hat festgestellt, da� die Datei <b>config.inc.php</b> vom Server nicht ver�ndert werden kann (keine Schreibrechte). <br>Der Vorgang kann nicht forgesetzt werden bis die Schreib-Lese-Rechte f�r diese Datei freigegeben wurden. <br>Bitte lies die beiliegende Dokumentation, um zu erfahren, wie dies funktioniert.";
$GLOBALS['strCantUpdateDB']  			= "Es ist z.Z. nicht m�glich ein Update der Datenbank durchzuf�hren. Wenn dennoch fortgefahren wird, werden alle existierenden Banner, Statistiken und Clients unwiderruflich gel�scht!";
$GLOBALS['strTableNames']				= "Tabellennamen";
$GLOBALS['strTablesPrefix']				= "Tabellennamenvorgabe";
$GLOBALS['strTablesType']				= "Tabellentyp";

$GLOBALS['strUrlPrefix']				= "URL Vorgabe";

$GLOBALS['strProceed']					= "Fortsetzen &gt;";
$GLOBALS['strInstallDatabase']			= "Datenbankstruktur Installation";
$GLOBALS['strFunctionAlreadyExists']	= "Funktion %s existiert bereits";
$GLOBALS['strFunctionInAllDotSqlErr']	= "Kann keine Funktion aus 'all.sql' erzeugen";
$GLOBALS['strFunctionClickProceed']		= "Sollen bereits bestehende Funktionen �berschrieben werden?";
$GLOBALS['strYes']						= "Ja";
$GLOBALS['strNo']						= "Nein";
$GLOBALS['strDatabaseCreated']			= "Datenbankstruktur erfolgreich erzeugt:";
$GLOBALS['strTableCreated']				= "Tabelle <b>%s</b> erfolgreich erzeugt";
$GLOBALS['strSequenceCreated']			= "Sequenz <b>%s</b> erfolgreich erzeugt";
$GLOBALS['strIndexCreated']				= "Index <b>%s</b> erfolgreich erzeugt";
$GLOBALS['strFunctionCreated']			= "Funktion <b>%s</b> erfolgreich erzeugt";
$GLOBALS['strFunctionReplaced']			= "Funktion <b>%s</b> erfolgreich erzeugt";
$GLOBALS['strUnknownStatementExec']		= "Unbekanntes Statement ausgef�hrt";
$GLOBALS['strAdminPasswordSetup']		= "Admin Passwort Setup";
$GLOBALS['strRepeatPassword']			= "Wiederhole Passwort";
$GLOBALS['strNotSamePasswords']			= "Passworte sind nicht identisch";
$GLOBALS['strInvalidUserPwd']			= "Ung�ltiger Username oder Passwort";
$GLOBALS['strInstallCompleted']			= "Installation abgeschlossen";
$GLOBALS['strInstallCompleted2']		= "Klick <b>Fortsetzen</b>, um zur Konfiguration zu gelangen und um die sontigen Einstellungen anzupassen.";

$GLOBALS['strUpgrade']					= "Upgrade";
$GLOBALS['strSystemUpToDate']			= "System ist Up-To-Date, ein Upgrade ist z.Z. nicht notwendig. Klicke <a href='index.php'>hier</a>, um zur Homepage zu gelangen.";
$GLOBALS['strSystemNeedsUpgrade']		= "Ein Upgrade f�r das System ist notwendig. Klicke <b>Fortsetzen</b> zum starten.<br><br>Bitte etwas gedultig sein, das Upgrade kann u.U. bis zu 2 Minuten dauern. <b>Bitte nicht Doppelklicken!</b>";
$GLOBALS['strServiceUnavalable']		= "Der Dienst ist zeitweise nicht verf�gbar. Systemupgrade l�uft...";
$GLOBALS['strDownloadConfig']			= "Downloade die <b>config.inc.php</b> und uploade sie auf den Server, dann klicke <b>Fortsetzen</b>.";
$GLOBALS['strDownload']					= "Download";

$GLOBALS['strConfigNotWritable']		= "Die Datei <b>config.inc.php</b> ist nicht beschreibbar";

// Settings translation strings
$GLOBALS['strChooseSection']			= "Abschnitt schlie�en";

$GLOBALS['strDbHost']					= "Datenbank Hostname";
$GLOBALS['strDbUser']					= "Datenbank Username";
$GLOBALS['strDbPassword']				= "Datenbank Passwort";
$GLOBALS['strDbName']					= "Datenbankname";
$GLOBALS['strPersistentConnections']	= "St�ndige Dantenbankverbindung nutzen (persistent)";
$GLOBALS['strInsertDelayed']			= "Verz�gerte Inserts (Einf�gungen) nutzen";
$GLOBALS['strCantConnectToDb']			= "Es kann keine Verbindung zur Datenbank aufgebaut werden";

$GLOBALS['strAdminUsername']			= "Admins Username";
$GLOBALS['strAdminFullName']			= "Admins voller Vor-,Nachname";
$GLOBALS['strAdminEmail']				= "Admins E-Mail Adresse";
$GLOBALS['strAdminEmailHeaders']		= "Mailkopf zur Wiedergabe des Senders der t�glichen Werbeberichte";
$GLOBALS['strAdminNovice']				= "Des Admins L�schvorg�nge ben�tigen zur Sicherheit eine Best�tigung";
$GLOBALS['strOldPassword']				= "Altes Passwort";
$GLOBALS['strNewPassword']				= "Neues Passwort";
$GLOBALS['strInvalidUsername']			= "Ung�ltiger Username";
$GLOBALS['strInvalidPassword']			= "Ung�ltiges Passwort";

$GLOBALS['strGuiSettings']				= "User Interface Konfiguration";
$GLOBALS['strMyHeader']					= "Mein Header";
$GLOBALS['strMyFooter']					= "Mein Footer";
$GLOBALS['strTableBorderColor']			= "Tabellenrahmenfarbe";
$GLOBALS['strTableBackColor']			= "Tabellenhintergrundfarbe";
$GLOBALS['strTableBackColorAlt']		= "Tabellenhintergrundfarbe (alternativ)";
$GLOBALS['strMainBackColor']			= "Seiten Haupthintergrundfarbe";
$GLOBALS['strAppName']					= "Applikationsname";
$GLOBALS['strCompanyName']				= "Unternehmensname";
$GLOBALS['strOverrideGD']				= "�berschreiben des GD-Bildformats";
$GLOBALS['strTimeZone']					= "Zeitzone";

$GLOBALS['strDayFullNames'] = array("Sonntag","Montag","Dienstag","Mittwoch","Donnerstag","Freitag","Sonnabend");

$GLOBALS['strIgnoreHosts']				= "Ignoriere Hosts";
$GLOBALS['strWarnLimit']				= "Limitwarnung";
$GLOBALS['strWarnLimitErr']				= "Limitwarnung sollte eine positive ganze Zahl sein";
$GLOBALS['strBeginOfWeek']				= "Beginn der Woche";
$GLOBALS['strPercentageDecimals']		= "Percentage Decimals";
$GLOBALS['strCompactStats']				= "Nutze kompackte Statistik";
$GLOBALS['strLogAdviews']				= "Log Adviews";
$GLOBALS['strLogAdclicks']				= "Log Adclicks";
$GLOBALS['strReverseLookup']			= "Reverse DNS Lookup";
$GLOBALS['strWarnAdmin']				= "Adminwarnung";
$GLOBALS['strWarnClient']				= "Clientwarnung";

$GLOBALS['strAllowedBannerTypes']		= "Erlaubte Bannertypen";
$GLOBALS['strTypeSqlAllow']				= "Erlaube in Datenbank gespeicherte Banner";
$GLOBALS['strTypeWebAllow']				= "Erlaube auf Webserver gespeicherte Banner";
$GLOBALS['strTypeUrlAllow']				= "Erlaube URL verkn�pfte Banner";
$GLOBALS['strTypeHtmlAllow']			= "Erlaube HTML-Banner";
$GLOBALS['strTypeWebSettings']			= "Webbanner Konfiguration";
$GLOBALS['strTypeWebMode']				= "Speichermethode";
$GLOBALS['strTypeWebModeLocal']			= "Local Mode (in einem lokalen Verzeichnis gespeichert)";
$GLOBALS['strTypeWebModeFtp']			= "FTP Mode (auf einem externen FTP-Server gespeichert)";
$GLOBALS['strTypeWebDir']				= "Local Mode Webbanner Verzeichnis";
$GLOBALS['strTypeWebFtp']				= "FTP Mode Webbanner Server";
$GLOBALS['strTypeWebUrl']				= "�ffentliche URL des lokalen Verzeichnisses / FTP-Servers";
$GLOBALS['strTypeHtmlSettings']			= "HTML-Banner Konfiguration";
$GLOBALS['strTypeHtmlAuto']				= "Automatisch HTML-Banner anpassen, um ein Adclick Loggen zu erm�glichen";
$GLOBALS['strTypeHtmlPhp']				= "Erlaube die Ausf�hrung von PHP-Funktionen innerhalb eines HTML-Banners";

$GLOBALS['strBannerRetrieval']			= "Banner Einblendungsmethode";
$GLOBALS['strRetrieveRandom']			= "Zuf�llige Bannereinblendung (Standard)";
$GLOBALS['strRetrieveNormalSeq']		= "Normal sequentielle Bannereinblendung";
$GLOBALS['strWeightSeq']				= "Gewichtet sequentielle Bannereinblendung";
$GLOBALS['strFullSeq']					= "Volle sequentielle Bannereinblendung";
$GLOBALS['strDefaultBannerUrl']			= "Standard Banner-URL";
$GLOBALS['strDefaultBannerTarget']		= "Standard Banner Klick-Ziel";
$GLOBALS['strUseConditionalKeys']		= "Nutze bedingte Schl�sselw�rter";
$GLOBALS['strUseMultipleKeys']			= "Nutze multiple Schl�sselw�rter";
$GLOBALS['strUseAcl']					= "Nutze Einblendungsbegrenzungen (ACL)";

$GLOBALS['strZonesSettings']			= "Zonen Einstellungen";
$GLOBALS['strZoneCache']				= "Cache Zonen, dies sollte die Geschwindigkeit bei Zonennutzung erh�hen";
$GLOBALS['strZoneCacheLimit']			= "Zeit zwischen Cache Updates (in Sekunden)";
$GLOBALS['strZoneCacheLimitErr']		= "Zeit zwischen Cache Updates sollte eine positive ganze Zahl sein";

$GLOBALS['strP3PSettings']				= "P3P Einstellungen";
$GLOBALS['strUseP3P']					= "Nutze P3P Policies";
$GLOBALS['strP3PCompactPolicy']			= "P3P Compact Policy";
$GLOBALS['strP3PPolicyLocation']		= "P3P Policy Location";

$GLOBALS['strClientWelcomeMessage']		= "Client Willkommen Nachricht";
$GLOBALS['strClientWelcomeEnabled']		= "Aktiviere Client Willkommen Nachricht";
$GLOBALS['strClientWelcomeText']		= "Client Willkommen Text<br>(HTML-Tags erlaubt)";

$GLOBALS['strDefaultBannerWeight']		= "Standard Bannergewichtung";
$GLOBALS['strDefaultCampaignWeight']	= "Standard Kampagnengewichtung";

$GLOBALS['strDefaultBannerWErr']		= "Standard Bannergewichtung sollte eine positive ganze Zahl sein";
$GLOBALS['strDefaultCampaignWErr']		= "Standard Kampagnengewichtung sollte eine positive ganze Zahl sein";

?>