<?php
/******************************************************************************
 * Einrichtungsscript fuer die MySql-Datenbank
 *
 * Copyright    : (c) 2004 - 2007 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 *
 * Uebergaben:
 *
 * mode : 0 (Default) Erster Dialog
 *        1 Admidio installieren - Config-Datei
 *        2 Datenbank installieren
 *        3 Datenbank updaten
 *        4 Neue Organisation anlegen
 *
 ******************************************************************************
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 *****************************************************************************/

require("../adm_program/system/function.php");
require("../adm_program/system/string.php");
require("../adm_program/system/date.php");
session_start();

// lokale Variablen der Uebergabevarialben initialieren

// Uebergabevariablen pruefen
$req_mode    = 0;
$req_version = 0;
$req_orga_name_short = null;
$req_orga_name_long  = null;
$req_user_last_name  = null;
$req_user_first_name = null;
$req_user_email      = null;
$req_user_login      = null;
$req_user_password   = null;

if(isset($_GET['mode']) && is_numeric($_GET['mode']))
{
   $req_mode = $_GET['mode'];
}

// setzt die Ausfuehrungszeit des Scripts auf 2 Min., da hier teilweise sehr viel gemacht wird
// allerdings darf hier keine Fehlermeldung wg. dem safe_mode kommen
@set_time_limit(120);

// Diese Funktion zeigt eine Fehlerausgabe an
// mode = 0 (Default) Fehlerausgabe
// mode = 1 Aktion wird durchgefuehrt (Abbrechen)
// mode = 2 Aktion erfolgreich durchgefuehrt
function showError($err_msg, $err_head = "Fehler", $mode = 1)
{
    global $g_root_path;

    echo '
    <!-- (c) 2004 - 2007 The Admidio Team - http://www.admidio.org -->
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
        <title>Admidio - Installation</title>

        <meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
        <meta name="author"   content="Markus Fassbender">
        <meta name="robots"   content="index,follow">
        <meta name="language" content="de">

        <link rel="stylesheet" type="text/css" href="../adm_config/main.css">
    </head>
    <body>
        <div style="margin-top: 10px; margin-bottom: 10px;" align="center"><br />
            <div class="formHead" style="width: 300px;">'. $err_head. '</div>
            <div class="formBody" style="width: 300px;">
                <p>'. $err_msg. '</p>
                <p><button id="zurueck" type="button" value="zurueck" onclick="';
                if($mode == 1)
                {
                    // Fehlermeldung (Zurueckgehen)
                    echo 'history.back()">
                    <img src="../adm_program/images/back.png" style="vertical-align: middle; padding-bottom: 1px;" width="16" height="16" border="0" alt="Zurueck">
                    &nbsp;Zur&uuml;ck';
                }
                elseif($mode == 2)
                {
                    // Erfolgreich durchgefuehrt
                    echo 'self.location.href=\'../adm_program/index.php\'">
                    <img src="../adm_program/images/application_view_list.png" style="vertical-align: middle; padding-bottom: 1px;" width="16" height="16" border="0" alt="Zurueck">
                    &nbsp;Admidio &Uuml;bersicht';
                    // da neue Einstellungen erstellt wurden, diese komplett neu einlesen
                    unset($_SESSION['g_preferences']);
                }
                echo '</button></p>
            </div>
        </div>
    </body>
    </html>';
    exit();
}

if($req_mode == 1)
{
    // Installation 1.Seite

    // Tabellenpraefix pruefen
    $g_tbl_praefix = strStripTags($_POST['praefix']);
    if(strlen($g_tbl_praefix) == 0)
    {
        $g_tbl_praefix = "adm";
    }
    else
    {
        // wenn letztes Zeichen ein _ dann abschneiden
        if(strrpos($g_tbl_praefix, "_")+1 == strlen($g_tbl_praefix))
        {
            $g_tbl_praefix = substr($g_tbl_praefix, 0, strlen($g_tbl_praefix)-1);
        }

        // nur gueltige Zeichen zulassen
        $anz = strspn($g_tbl_praefix, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_");

        if($anz != strlen($g_tbl_praefix))
        {
            showError("Das Tabellenpr&auml;fix enth&auml;lt ung&uuml;ltige Zeichen !");
        }
    }

    // Session-Variablen merken
    $_SESSION['praefix']  = $g_tbl_praefix;
    $_SESSION['orga_name_short'] = strStripTags($_POST['orga_name_short']);
}
elseif($req_mode == 2)
{
    // Installation 2.Seite

    // MySQL-Zugangsdaten in config.php schreiben
    // Datei auslesen
    $filename     = "config.php";
    $config_file  = fopen($filename, "r");
    $file_content = fread($config_file, filesize($filename));
    fclose($config_file);

    // den Root-Pfad ermitteln
    $root_path = $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
    $root_path = substr($root_path, 0, strpos($root_path, "/adm_install"));
    if(!strpos($root_path, "http://"))
    {
        $root_path = "http://". $root_path;
    }

    $file_content = str_replace("%PRAEFIX%", $_SESSION['praefix'], $file_content);
    $file_content = str_replace("%SERVER%",  $_SESSION['server'],  $file_content);
    $file_content = str_replace("%USER%",    $_SESSION['user'],    $file_content);
    $file_content = str_replace("%PASSWORD%",  $_SESSION['password'], $file_content);
    $file_content = str_replace("%DATABASE%",  $_SESSION['database'], $file_content);
    $file_content = str_replace("%ROOT_PATH%", $root_path,            $file_content);
    $file_content = str_replace("%ORGANIZATION%", $_SESSION['orga_name_short'], $file_content);

    // die erstellte Config-Datei an den User schicken
    $filename = "config.php";
    header("Content-Type: application/force-download");
    header("Content-Type: application/download");
    header("Content-Type: text/csv; charset=ISO-8859-1");
    header("Content-Disposition: attachment; filename=$filename");
    echo $file_content;
    exit();
}
elseif($req_mode == 5)
{
    if(file_exists("../adm_config/config.php"))
    {
        showError("Die Datenbank wurde erfolgreich angelegt und die Datei config.php erstellt.<br><br>
            Sie k&ouml;nnen nun mit Admidio arbeiten.", "Fertig", 2);
    }
    else
    {
        showError("Die Datei <b>config.php</b> befindet sich nicht im Verzeichnis <b>adm_config</b> !");
    }
}
else
{
    require("../adm_config/config.php");
}

// Zugangsdaten der DB in Sessionvariablen gefiltert speichern
$_SESSION['server']   = strStripTags($_POST['server']);
$_SESSION['user']     = strStripTags($_POST['user']);
$_SESSION['password'] = strStripTags($_POST['password']);
$_SESSION['database'] = strStripTags($_POST['database']);

// Standard-Praefix ist adm auch wegen Kompatibilitaet zu alten Versionen
if(strlen($g_tbl_praefix) == 0)
{
    $g_tbl_praefix = "adm";
}

// Defines fuer alle Datenbanktabellen
define("TBL_ANNOUNCEMENTS",     $g_tbl_praefix. "_announcements");
define("TBL_CATEGORIES",        $g_tbl_praefix. "_categories");
define("TBL_DATES",             $g_tbl_praefix. "_dates");
define("TBL_GUESTBOOK",         $g_tbl_praefix. "_guestbook");
define("TBL_GUESTBOOK_COMMENTS",$g_tbl_praefix. "_guestbook_comments");
define("TBL_LINKS",             $g_tbl_praefix. "_links");
define("TBL_MEMBERS",           $g_tbl_praefix. "_members");
define("TBL_ORGANIZATIONS",     $g_tbl_praefix. "_organizations");
define("TBL_PHOTOS",            $g_tbl_praefix. "_photos");
define("TBL_PREFERENCES",       $g_tbl_praefix. "_preferences");
define("TBL_ROLE_DEPENDENCIES", $g_tbl_praefix. "_role_dependencies");
define("TBL_ROLES",             $g_tbl_praefix. "_roles");
define("TBL_SESSIONS",          $g_tbl_praefix. "_sessions");
define("TBL_TEXTS",             $g_tbl_praefix. "_texts");
define("TBL_USERS",             $g_tbl_praefix. "_users");
define("TBL_USER_DATA",         $g_tbl_praefix. "_user_data");
define("TBL_USER_FIELDS",       $g_tbl_praefix. "_user_fields");

/*------------------------------------------------------------*/
// Eingabefelder pruefen
/*------------------------------------------------------------*/

if(strlen($_SESSION['server'])   == 0
|| strlen($_SESSION['user'])     == 0
// bei localhost muss es kein Passwort geben
//|| strlen($_SESSION['password']) == 0
|| strlen($_SESSION['database']) == 0 )
{
    showError("Es sind nicht alle Zugangsdaten zur MySql-Datenbank eingegeben worden !");
}

if($req_mode == 3)
{
    if($_POST['version'] == 0 || is_numeric($_POST['version']) == false)
    {
        showError("Bei einem Update m&uuml;ssen Sie Ihre bisherige Version angeben !");
    }
    $req_version = $_POST['version'];
}

// bei Installation oder hinzufuegen einer Organisation
if($req_mode == 1 || $req_mode == 4)
{
    $req_user_last_name  = strStripTags($_POST['user_last_name']);
    $req_user_first_name = strStripTags($_POST['user_first_name']);
    $req_user_email      = strStripTags($_POST['user_email']);
    $req_user_login      = strStripTags($_POST['user_login']);
    $req_user_password   = strStripTags($_POST['user_password']);

    if(strlen($req_user_last_name)  == 0
    || strlen($req_user_first_name) == 0
    || strlen($req_user_email)      == 0
    || strlen($req_user_login)      == 0
    || strlen($req_user_password)   == 0 )
    {
        showError("Es sind nicht alle Benutzerdaten eingegeben worden !");
    }

    if(isValidEmailAddress($req_user_email) == false)
    {
        showError("Die E-Mail-Adresse ist nicht g&uuml;ltig.");
    }

    if(strlen($_POST['orga_name_long'])  == 0
    || strlen($_POST['orga_name_short']) == 0 )
    {
        showError("Sie m&uuml;ssen einen Namen f&uuml;r die Organisation / den Verein eingeben !");
    }
}

/*------------------------------------------------------------*/
// Daten verarbeiten
/*------------------------------------------------------------*/

// Verbindung zu Datenbank herstellen
$connection = mysql_connect ($_SESSION['server'], $_SESSION['user'], $_SESSION['password'])
              or showError("Es konnte keine Verbindung zur Datenbank hergestellt werden.<br /><br />
                            Pr&uuml;fen Sie noch einmal Ihre MySql-Zugangsdaten !");

if(!mysql_select_db($_SESSION['database'], $connection ))
{
    showError("Die angegebene Datenbank <b>". $_SESSION['database']. "</b> konnte nicht gefunden werden !");
}

if($req_mode == 1)
{
    $error    = 0;
    $filename = "db_scripts/db.sql";
    $file     = fopen($filename, "r")
                or showError("Die Datei <b>db.sql</b> konnte nicht im Verzeichnis <b>adm_install/db_scripts</b> gefunden werden.");
    $content  = fread($file, filesize($filename));
    $sql_arr  = explode(";", $content);
    fclose($file);

    foreach($sql_arr as $sql)
    {
        if(strlen(trim($sql)) > 0)
        {
            // Praefix fuer die Tabellen einsetzen und SQL-Statement ausfuehren
            $sql = str_replace("%PRAEFIX%", $g_tbl_praefix, $sql);
            $result = mysql_query($sql, $connection);
            if(!$result)
            {
                showError(mysql_error());
                $error++;
            }
        }
    }
    if($error > 0)
    {
        exit();
    }

    // Default-Daten anlegen

    // Messenger anlegen
    $sql = "INSERT INTO ". TBL_USER_FIELDS. " (usf_org_shortname, usf_type, usf_name, usf_description)
                                       VALUES (NULL, 'MESSENGER', 'AIM', 'AOL Instant Messenger') ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_USER_FIELDS. " (usf_org_shortname, usf_type, usf_name, usf_description)
                                       VALUES (NULL, 'MESSENGER', 'ICQ', 'ICQ') ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_USER_FIELDS. " (usf_org_shortname, usf_type, usf_name, usf_description)
                                       VALUES (NULL, 'MESSENGER', 'MSN', 'MSN Messenger') ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_USER_FIELDS. " (usf_org_shortname, usf_type, usf_name, usf_description)
                                       VALUES (NULL, 'MESSENGER', 'Yahoo', 'Yahoo! Messenger') ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_USER_FIELDS. " (usf_org_shortname, usf_type, usf_name, usf_description)
                                       VALUES (NULL, 'MESSENGER', 'Skype', 'Skype') ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_USER_FIELDS. " (usf_org_shortname, usf_type, usf_name, usf_description)
                                       VALUES (NULL, 'MESSENGER', 'Google Talk', 'Google Talk') ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
}

if($req_mode == 3)
{
    // Updatescripte fuer die Datenbank verarbeiten
    if($req_version > 0)
    {
        for($i = $req_version; $i <= 4; $i++)
        {
            $error = 0;
            if($i == 3)
            {
                $filename = "db_scripts/upd_1_3_db.sql";
            }
            elseif($i == 4)
            {
                $filename = "db_scripts/upd_1_4_db.sql";
            }
            else
            {
                $filename = "";
            }

            if(strlen($filename) > 0)
            {
                $file    = fopen($filename, "r")
                           or showError("Die Datei <b>$filename</b> konnte nicht im Verzeichnis <b>adm_install</b> gefunden werden.");
                $content = fread($file, filesize($filename));
                $sql_arr = explode(";", $content);
                fclose($file);

                foreach($sql_arr as $sql)
                {
                    if(strlen(trim($sql)) > 0)
                    {
                        // Praefix fuer die Tabellen einsetzen und SQL-Statement ausfuehren
                        $sql = str_replace("%PRAEFIX%", $g_tbl_praefix, $sql);
                        $result = mysql_query($sql, $connection);
                        if(!$result)
                        {
                            showError(mysql_error());
                            $error++;
                        }
                    }
                }
                if($error > 0)
                {
                    exit();
                }
            }

            if($i == 3)
            {
                include("db_scripts/upd_1_3_conv.php");
            }
            elseif($i == 4)
            {
                include("db_scripts/upd_1_4_conv.php");
            }
        }
    }
    else
    {
        showError("Sie haben Ihre bisherige Version nicht angegeben !");
    }
}

if($req_mode == 1 || $req_mode == 4)
{
    /************************************************************************/
    // neue Organisation anlegen oder hinzufuegen
    /************************************************************************/
    
    $req_orga_name_short = strStripTags($_POST['orga_name_short']);
    $req_orga_name_long  = strStripTags($_POST['orga_name_long']);

    $sql = "SELECT * FROM ". TBL_ORGANIZATIONS. " WHERE org_shortname = {0} ";
    $sql = prepareSQL($sql, array($req_orga_name_short));
    $result = mysql_query($sql, $connection);
    if(!$result)
    {
        showError(mysql_error());
    }

    if(mysql_num_rows($result) > 0)
    {
        showError("Eine Organisation mit dem angegebenen kurzen Namen <b>$req_orga_name_short</b> existiert bereits.<br /><br />
                   W&auml;hlen Sie bitte einen anderen kurzen Namen !");
    }

    $sql = "INSERT INTO ". TBL_ORGANIZATIONS. " (org_shortname, org_longname, org_homepage)
                                         VALUES ({0}, {1}, '". $_SERVER['HTTP_HOST']. "') ";
    $sql = prepareSQL($sql, array($req_orga_name_short, $req_orga_name_long));
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
    $org_id = mysql_insert_id($connection);

    // Einstellungen anlegen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_rss', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_bbcode', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'logout_minutes', '20') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_system_mails', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'email_administrator', 'webmaster@". $_SERVER['HTTP_HOST']. "') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Registrierungseinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'registration_mode', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_registration_captcha', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_registration_admin_mail', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Mailmoduleinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_mail_module', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'max_email_attachment_size', '1024') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_mail_captcha', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Fotomoduleinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_photo_module', '1')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'photo_save_scale', '640')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
            VALUES ($org_id, 'photo_thumbs_column', '5')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
            VALUES ($org_id, 'photo_thumbs_row', '5')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
            VALUES ($org_id, 'photo_thumbs_scale', '100')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
            VALUES ($org_id, 'photo_show_width', '500')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
            VALUES ($org_id, 'photo_show_height', '380')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
            VALUES ($org_id, 'photo_image_text', '1')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
            VALUES ($org_id, 'photo_preview_scale', '100')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Gaestebuchmoduleinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_guestbook_module', '1')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_guestbook_captcha', '1')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'flooding_protection_time', '180')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_gbook_comments4all', '0')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Weblinkseinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_weblinks_module', '1')";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Downloadmoduleinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_download_module', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'max_file_upload_size', '3072') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Ankuendigungsmoduleinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_announcements_module', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);

    //Terminmoduleinstellungen
    $sql = "INSERT INTO ". TBL_PREFERENCES. " (prf_org_id, prf_name, prf_value)
                                       VALUES ($org_id, 'enable_dates_module', '1') ";
    $result = mysql_query($sql, $connection);
    db_error($result);


    // Default-Kategorie fuer Rollen und Links eintragen
    $sql = "INSERT INTO ". TBL_CATEGORIES. " (cat_org_id, cat_type, cat_name, cat_hidden)
                                           VALUES ($org_id, 'ROL', 'Allgemein', 1)";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
    $category_common = mysql_insert_id();

    $sql = "INSERT INTO ". TBL_CATEGORIES. " (cat_org_id, cat_type, cat_name, cat_hidden)
                                           VALUES ($org_id, 'ROL', 'Gruppen', 1)";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_CATEGORIES. " (cat_org_id, cat_type, cat_name, cat_hidden)
                                           VALUES ($org_id, 'ROL', 'Kurse', 1)";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_CATEGORIES. " (cat_org_id, cat_type, cat_name, cat_hidden)
                                           VALUES ($org_id, 'ROL', 'Mannschaften', 1)";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    $sql = "INSERT INTO ". TBL_CATEGORIES. " (cat_org_id, cat_type, cat_name, cat_hidden)
                                           VALUES ($org_id, 'LNK', 'Allgemein', 0)";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    // nun die Default-Rollen anlegen

    // Webmaster
    $sql = "INSERT INTO ". TBL_ROLES. " (rol_org_shortname, rol_cat_id, rol_name, rol_description, rol_valid,
                                         rol_moderation, rol_announcements, rol_dates, rol_download,
                                         rol_guestbook, rol_guestbook_comments, rol_photo, rol_weblinks,
                                         rol_edit_user, rol_mail_logout, rol_mail_login, rol_profile)
                                 VALUES ({0}, $category_common, 'Webmaster', 'Gruppe der Administratoren des Systems', 1,
                                         1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1) ";
    $sql = prepareSQL($sql, array($req_orga_name_short));
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
    $rol_id_webmaster = mysql_insert_id();

    // Mitglied
    $sql = "INSERT INTO ". TBL_ROLES. " (rol_org_shortname, rol_cat_id, rol_name, rol_description, rol_valid,
                                         rol_moderation, rol_announcements, rol_dates, rol_download,
                                         rol_guestbook, rol_guestbook_comments, rol_photo, rol_weblinks,
                                         rol_edit_user, rol_mail_logout, rol_mail_login, rol_profile)
                                 VALUES ({0}, $category_common, 'Mitglied', 'Alle Mitglieder der Organisation', 1,
                                         0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1) ";
    $sql = prepareSQL($sql, array($req_orga_name_short));
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
    $rol_id_member = mysql_insert_id();

    // Vorstand
    $sql = "INSERT INTO ". TBL_ROLES. " (rol_org_shortname, rol_cat_id, rol_name, rol_description, rol_valid,
                                         rol_moderation, rol_announcements, rol_dates, rol_download,
                                         rol_guestbook, rol_guestbook_comments, rol_photo, rol_weblinks,
                                         rol_edit_user, rol_mail_logout, rol_mail_login, rol_profile)
                                 VALUES ({0}, $category_common, 'Vorstand', 'Vorstand des Vereins', 1,
                                         0, 1, 1, 0, 0, 0, 0, 1, 1, 1, 1, 1) ";
    $sql = prepareSQL($sql, array($req_orga_name_short));
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
    
    // User Webmaster anlegen
    $pw_md5 = md5($req_user_password);
    $sql = "INSERT INTO ". TBL_USERS. " (usr_last_name, usr_first_name, usr_email, usr_login_name, usr_password, usr_valid)
                                 VALUES ({0}, {1}, {2}, {3}, '$pw_md5', 1) ";
    $sql = prepareSQL($sql, array($req_user_last_name, $req_user_first_name, $req_user_email, $req_user_login));
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
    $user_id = mysql_insert_id();

    // Mitgliedschaft bei Rolle "Webmaster" anlegen
    $sql = "INSERT INTO ". TBL_MEMBERS. " (mem_rol_id, mem_usr_id, mem_begin, mem_valid)
                                   VALUES ($rol_id_webmaster, $user_id, NOW(), 1) ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());

    // Mitgliedschaft "Mitglied" anlegen
    $sql = "INSERT INTO ". TBL_MEMBERS. " (mem_rol_id, mem_usr_id, mem_begin, mem_valid)
                                   VALUES ($rol_id_member, $user_id, NOW(), 1) ";
    $result = mysql_query($sql, $connection);
    if(!$result) showError(mysql_error());
}

if($req_mode == 1)
{
    $location = "Location: index.php?mode=2";
    header($location);
    exit();
}
else
{
    showError("Die Einrichtung der Datenbank konnte erfolgreich abgeschlossen werden.", "Fertig", 2);
}
?>