<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010 Jean-François FERRY  <jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *   	\file       htdocs/ovh/admin/ovh_click2dial.php
 *		\ingroup    ovh
 *		\brief      Configuration du module ovh
 *		\version    $Id: ovh_click2dial.php,v 1.3 2011/06/17 22:25:08 eldy Exp $
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res) die("Include of main fails");
include_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once("/ovh/class/ovhsms.class.php");
dol_include_once("/ovh/lib/ovh.lib.php");

// Load traductions files requiredby by page
$langs->load("admin");
$langs->load("companies");
$langs->load("ovh@ovh");

if (!$user->admin)
accessforbidden();
// Get parameters


// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}


/*
 * Actions
 */

if ($_POST["action"] == 'setvalue' && $user->admin)
{
    //$result=dolibarr_set_const($db, "PAYBOX_IBS_DEVISE",$_POST["PAYBOX_IBS_DEVISE"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "OVHSMS_NICK",$_POST["OVHSMS_NICK"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "OVHSMS_PASS",$_POST["OVHSMS_PASS"],'chaine',0,'',$conf->entity);
    $result=dolibarr_set_const($db, "OVHSMS_SOAPURL",$_POST["OVHSMS_SOAPURL"],'chaine',0,'',$conf->entity);


    if ($result >= 0)
    {
        $mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
    }
    else
    {
        dol_print_error($db);
    }
}



if ($_POST["action"] == 'setvalue_account' && $user->admin)
{
    $result=dolibarr_set_const($db, "OVHSMS_ACCOUNT",$_POST["OVHSMS_ACCOUNT"],'chaine',0,'',$conf->entity);

    if ($result >= 0)
    {
        $mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
    }
    else
    {
        dol_print_error($db);
    }
}




/*
 * View
 */

llxHeader('','OvhSmsSetup','','');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("OvhSmsSetup"),$linkback,'setup');

$head=ovhadmin_prepare_head();

dol_fiche_head($head, 'click2dial', $langs->trans("Ovh"));


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

print '<table class="nobordernopadding" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";


$var=!$var;
print '<tr '.$bc[$var].'><td width="200px" class="fieldrequired">';
print $langs->trans("OvhSmsNick").'</td><td>';
print '<input size="64" type="text" name="OVHSMS_NICK" value="'.$conf->global->OVHSMS_NICK.'">';
print '<br>'.$langs->trans("Example").': AA123-OVH';
print '</td></tr>';


$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("OvhSmsPass").'</td><td>';
print '<input size="64" type="password" name="OVHSMS_PASS" value="'.$conf->global->OVHSMS_PASS.'">';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td class="fieldrequired">';
print $langs->trans("OvhSmsSoapUrl").'</td><td>';
print '<input size="64" type="text" name="OVHSMS_SOAPURL" value="'.$conf->global->OVHSMS_SOAPURL.'">';
print '<br>'.$langs->trans("Example").': https://www.ovh.com/soapi/soapi-re-1.26.wsdl';
print '</td></tr>';

print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

dol_fiche_end();


if ($mesg)
{
    if (preg_match('/class="error"/',$mesg)) dol_htmloutput_mesg($mesg,'','error');
    else
    {
        dol_htmloutput_mesg($mesg,'','ok',1);
        print '<br>';
    }
}


// Connect area
require_once(NUSOAP_PATH.'/nusoap.php');     // Include SOAP

$WS_DOL_URL = $conf->global->OVHSMS_SOAPURL;

dol_syslog("Create nusoap_client for URL=".$WS_DOL_URL, LOG_DEBUG);

if (empty($conf->global->OVHSMS_NICK) || empty($WS_DOL_URL))
{
    echo '<br>'.'<div class="warning">'.$langs->trans("OvhSmsNotConfigured").'</div>';
}
else
{

    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test">'.$langs->trans("TestLoginToAPI").'</a><br><br>';

    if (GETPOST('action') == 'test')
    {
        $soap = new soapclient($WS_DOL_URL);

        try {
            //login
            $session = $soap->login($conf->global->OVHSMS_NICK, $conf->global->OVHSMS_PASS, "fr", false);
            print '<div class="ok">'.$langs->trans("OvhSmsLoginSuccessFull").'</div><br>';

            //logout
            $soap->logout($session);
            //  echo "logout successfull\n";

        }
        catch(Exception $e)
        {
            print 'Error '.$e->getMessage().'<br>';
        }
    }

    print '<br>';
}




// Show message
$message='';
$url='<a href="'.dol_buildpath('/ovh/wrapper.php?login=__LOGIN__&password=__PASS__&caller=__PHONEFROM__&called=__PHONETO__',2).'" target="_blank">'.dol_buildpath('/ovh/wrapper.php?login=__LOGIN__&password=__PASS__&caller=__PHONEFROM__&called=__PHONETO__',2).'</a>';
$message.=img_picto('','object_globe.png').' '.$langs->trans("ClickToDialLink",'OVH',$url);
$message.='<br>';
$message.='<br>';
print $message;

// End of page
$db->close();

llxFooter('');
?>
