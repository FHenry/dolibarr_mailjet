<?php
/* <Mailjet connector>
 * Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/admin_mailjet.php
* 	\ingroup	mailjet
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include("../../../main.inc.php"); // From "custom" directory
}


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "../lib/mailjet.lib.php";
dol_include_once('/mailjet/class/dolmailjet_v'.$conf->global->MAILJET_API_VERSION.'.class.php');

// Translations
$langs->load("mailjet@mailjet");
$langs->load("admin");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'int');

$error=0;

/*
 * Actions
 */

if ($action == 'setvar') {		
	$res = dolibarr_set_const($db, 'MAILJET_API_VERSION', GETPOST('MAILJET_API_VERSION'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILJET_MAIL_SMTP_SERVER', GETPOST('MAILJET_MAIL_SMTP_SERVER'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILJET_SMTP_PORT', GETPOST('MAILJET_SMTP_PORT'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILJET_MAIL_SMTPS_ID', GETPOST('MAILJET_MAIL_SMTPS_ID'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILJET_MAIL_SMTPS_PW', GETPOST('MAILJET_MAIL_SMTPS_PW'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'MAILJET_MAIL_EMAIL_TLS', GETPOST('MAILJET_MAIL_EMAIL_TLS'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;	
	$res = dolibarr_set_const($db, 'MAILJET_MAIL_EMAIL_FROM', GETPOST('MAILJET_MAIL_EMAIL_FROM'),'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	
	if ($error) {
		setEventMessage('Error','errors');
	}else {
		setEventMessage($langs->trans('MailJetSuccessSave'),'mesgs');
	}
}

if ($action=='mailjetactiv') {
	
	$res = dolibarr_set_const($db, 'MAILJET_ACTIVE', $value,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	
	if ($value==0) {
		$res =dolibarr_set_const($db, "MAIN_MAIL_SENDMODE", $conf->global->MAILJET_MAIL_SENDMODE_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",   $conf->global->MAILJET_SMTP_PORT_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER", $conf->global->MAILJET_MAIL_SMTP_SERVER_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",    $conf->global->MAILJET_MAIL_SMTPS_ID_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",   $conf->global->MAILJET_MAIL_SMTPS_PW_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",   $conf->global->MAILJET_MAIL_EMAIL_TLS_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",   $conf->global->MAILJET_MAIL_EMAIL_FROM_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
	}
	if ($value==1) {
		
		if (empty($conf->global->MAILJET_MAIL_SMTPS_ID)) {
			setEventMessage($langs->trans("MailJetAPIKeyNotSet"),'errors');
			$error++;
		}
		if (empty($conf->global->MAILJET_MAIL_SMTPS_PW)){
			setEventMessage($langs->trans("MailJetSecretKeyNotSet"),'errors');
			$error++;
		}
		
		if (empty($error)) {
			$res =dolibarr_set_const($db, "MAILJET_MAIL_SENDMODE_STD", $conf->global->MAIN_MAIL_SENDMODE,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILJET_SMTP_PORT_STD",   $conf->global->MAIN_MAIL_SMTP_PORT,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILJET_MAIL_SMTP_SERVER_STD", $conf->global->MAIN_MAIL_SMTP_SERVER,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILJET_MAIL_SMTPS_ID_STD",    $conf->global->MAIN_MAIL_SMTPS_ID, 'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILJET_MAIL_SMTPS_PW_STD",   $conf->global->MAIN_MAIL_SMTPS_PW, 'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILJET_MAIL_EMAIL_TLS_STD",   $conf->global->MAIN_MAIL_EMAIL_TLS,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAILJET_MAIL_EMAIL_FROM_STD",   $conf->global->MAIN_MAIL_EMAIL_FROM,'chaine',0,'',$conf->entity);
			if (! $res > 0) $error++;
			
			$res =dolibarr_set_const($db, "MAIN_MAIL_SENDMODE", $conf->global->MAILJET_MAIL_SENDMODE,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",   $conf->global->MAILJET_SMTP_PORT,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER", $conf->global->MAILJET_MAIL_SMTP_SERVER,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",    $conf->global->MAILJET_MAIL_SMTPS_ID, 'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",   $conf->global->MAILJET_MAIL_SMTPS_PW, 'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",   $conf->global->MAILJET_MAIL_EMAIL_TLS,'chaine',0,'',0);
			if (! $res > 0) $error++;
			$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",   $conf->global->MAILJET_MAIL_EMAIL_FROM,'chaine',0,'',0);
			if (! $res > 0) $error++;
			
			$res =dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS", 0,'chaine',0,'',0);
			if (! $res > 0) $error++;
			
			if (!empty($conf->global->MAILJET_ACTIVE_MAILING_ONLY)) {
				$res = dolibarr_set_const($db, 'MAILJET_ACTIVE_MAILING_ONLY', 0,'chaine',0,'',$conf->entity);
			}
		}
	}
	
	if ($error) {
		setEventMessage('Error','errors');
	}else {
		setEventMessage($langs->trans('MailJetSuccessSave'),'mesgs');
	}
}

if ($action=='activmailjetmailingonly') {

	$res = dolibarr_set_const($db, 'MAILJET_ACTIVE_MAILING_ONLY', $value,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	
	if (!empty($conf->global->MAILJET_ACTIVE)) {
		
		$res = dolibarr_set_const($db, 'MAILJET_ACTIVE', 0,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
		
		$res =dolibarr_set_const($db, "MAIN_MAIL_SENDMODE", $conf->global->MAILJET_MAIL_SENDMODE_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",   $conf->global->MAILJET_SMTP_PORT_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER", $conf->global->MAILJET_MAIL_SMTP_SERVER_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",    $conf->global->MAILJET_MAIL_SMTPS_ID_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",   $conf->global->MAILJET_MAIL_SMTPS_PW_STD, 'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",   $conf->global->MAILJET_MAIL_EMAIL_TLS_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
		$res =dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",   $conf->global->MAILJET_MAIL_EMAIL_FROM_STD,'chaine',0,'',0);
		if (! $res > 0) $error++;
	}
}

/*
 * View
 */
$page_name = "MailJetSetup";
llxHeader('', $langs->trans($page_name));

$form=new Form($db);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = mailjetAdminPrepareHead();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("Module123451Name"),
	0,
	"mailjet@mailjet"
);

// Setup page goes here

print '<BR>';
echo $langs->trans("MailJetExplain");
print '<BR>';

if( !in_array('curl', get_loaded_extensions()))
{
	print '<div class="error">'.$langs->trans('MailJetErrorCurlNotLoaded').'</div>';
	exit();
}


print '<table class="noborder" width="100%">';

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvar">';

print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Valeur").'</td>';
print '<td></td>';
print "</tr>\n";

//MAILJET_MAIL_SMTP_SERVER
print '<tr class="impair"><td>'.$langs->trans("MAILJET_API_VERSION").'</td>';
print '<td align="left">';
print '<SELECT calss="flat" name="MAILJET_API_VERSION"><OPTION value="1" '.($conf->global->MAILJET_API_VERSION==1?'selected="selected"':'').'>1</OPTION><OPTION value="3" '.($conf->global->MAILJET_API_VERSION==3?'selected="selected"':'').'>3</OPTION></SELECT></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILJET_API_VERSIONHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILJET_MAIL_SMTP_SERVER
print '<tr class="pair"><td>'.$langs->trans("MAILJET_MAIL_SMTP_SERVER").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILJET_MAIL_SMTP_SERVER" value="'.$conf->global->MAILJET_MAIL_SMTP_SERVER.'" size="30" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILJET_MAIL_SMTP_SERVERHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILJET_SMTP_PORT
print '<tr class="impair"><td>'.$langs->trans("MAILJET_SMTP_PORT").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILJET_SMTP_PORT" value="'.$conf->global->MAILJET_SMTP_PORT.'" size="5" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILJET_SMTP_PORTHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILJET_MAIL_SMTPS_ID
print '<tr class="pair"><td>'.$langs->trans("MAILJET_MAIL_SMTPS_ID").'</td>';
print '<td align="left">';
print '<input type="text" name="MAILJET_MAIL_SMTPS_ID" value="'.$conf->global->MAILJET_MAIL_SMTPS_ID.'" size="20" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILJET_MAIL_SMTPS_IDHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILJET_MAIL_SMTPS_PW
print '<tr class="impair"><td>'.$langs->trans("MAILJET_MAIL_SMTPS_PW").'</td>';
print '<td align="left">';
print '<input type="password" name="MAILJET_MAIL_SMTPS_PW" value="'.$conf->global->MAILJET_MAIL_SMTPS_PW.'" size="20" ></td>';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILJET_MAIL_SMTPS_PWHelp"),1,'help');
print '</td>';
print '</tr>';

//MAILJET_MAIL_EMAIL_TLS_STD
print '<tr class="pair"><td>'.$langs->trans("MAILJET_MAIL_EMAIL_TLS").'</td>';
print '<td align="left">';
if (function_exists('openssl_open')) {
	print $form->selectyesno('MAILJET_MAIL_EMAIL_TLS',(! empty($conf->global->MAILJET_MAIL_EMAIL_TLS)?$conf->global->MAILJET_MAIL_EMAIL_TLS:0),1);
}
else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
print '<td align="left">';
print $form->textwithpicto('',$langs->trans("MAILJET_MAIL_EMAIL_TLSHelp"),1,'help');
print '</td>';
print '</tr>';

if (!empty($conf->global->MAILJET_API_VERSION)) {
	//MAILJET_MAIL_EMAIL_FROM
	print '<tr class="pair"><td>'.$langs->trans("MAILJET_MAIL_EMAIL_FROM").'</td>';
	print '<td align="left">';
	print '<input type="text" name="MAILJET_MAIL_EMAIL_FROM" value="'.$conf->global->MAILJET_MAIL_EMAIL_FROM.'" size="20" ></td>';
	print '<td align="left">';
	if (!empty($conf->global->MAILJET_MAIL_EMAIL_FROM)) {
		if (!isValidEmail($conf->global->MAILJET_MAIL_EMAIL_FROM)) {
			$langs->load("errors");
			print img_warning($langs->trans("ErrorBadEMail",$conf->global->MAILJET_MAIL_EMAIL_FROM));
		} else {
			$mailjet= new DolMailjet($db);
			$result=$mailjet->checkMailSender($conf->global->MAILJET_MAIL_EMAIL_FROM);
			if ($result<0) {
				setEventMessage($mailjet->errors,'errors');
			}else {
				if (!$result) {
					print '<a href="http://www.mailjet.com/account/sender" target="_blank">'.img_warning($langs->transnoentities("MailJetSenderNameNotValid")).'</a>';
				}else {
					print img_picto_common($langs->transnoentities("MailJetSenderNameValid"), 'redstar');
				}
			}
		}
	}
	else {
		print $form->textwithpicto('',$langs->trans("MAILJET_MAIL_EMAIL_FROMHelp"),1,'help');
	}
	print '</td>';
	print '</tr>';
}

print '<tr class="liste_titre"><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

print '</table>';
print '</form>';

print '<BR>';

dol_htmloutput_mesg($langs->trans("MailJetDolibarrSettings",dol_buildpath('/admin/mails.php',1).'?mainmenu=home&leftmenu=setup'),'','warning',1);
print '<table class="noborder" width="100%">';

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="activmailjet">';

print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("MailJetActiveSending").'</td>';
print '<td align="center">';
if (!empty($conf->global->MAILJET_ACTIVE)) {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=mailjetactiv&value=0">';
	print img_picto($langs->trans("Disabled"),'switch_on');
	print "</a></td>\n";
}else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=mailjetactiv&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print "</a></td>\n";
}
print '</td>';
print '</table>';
print '</form>';

if (! empty($conf->global->MAILJET_ACTIVE)) {
	dol_htmloutput_mesg($langs->trans("MailJetDolibarrCheckSettings",dol_buildpath('/admin/mails.php',1).'?mainmenu=home&leftmenu=setup'),'','ok',1);	
}

print $langs->trans('or');
dol_htmloutput_mesg($langs->trans("MailJetActiveSendingForMailingOnlyHelp"),'','warning',1);
print '<table class="noborder" width="100%">';

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="activmailjetmailingony">';

print '<tr class="liste_titre">';
print '<td width="40%">'.$langs->trans("MailJetActiveSendingForMailingOnly").'</td>';
print '<td align="center">';
if (!empty($conf->global->MAILJET_ACTIVE_MAILING_ONLY)) {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=activmailjetmailingonly&value=0">';
	print img_picto($langs->trans("Disabled"),'switch_on');
	print "</a></td>\n";
}else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=activmailjetmailingonly&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print "</a></td>\n";
}
print '</td>';
print '</tr>';
print '</table>';
print '</form>';

llxFooter();
$db->close();