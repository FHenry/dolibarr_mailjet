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
 *	\file		/mailjet/mailjet/mailjet.php
 *	\ingroup	mailjet
 */

/*error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);*/

$res = 0;
if (! $res && file_exists("../main.inc.php")) {
	$res = @include("../main.inc.php");
}
if (! $res && file_exists("../../main.inc.php")) {
	$res = @include("../../main.inc.php");
}
if (! $res && file_exists("../../../main.inc.php")) {
	$res = @include("../../../main.inc.php");
}
if (! $res) {
	die("Main include failed");
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
dol_include_once('/mailjet/class/dolmailjet.class.php');

// Load translation files required by the page
$langs->load("mailjet@mailjet");
$langs->load("mails");

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

// Access control
if (! $user->rights->mailing->creer || (empty($conf->global->EXTERNAL_USERS_ARE_AUTHORIZED) && $user->societe_id > 0)) { 
	accessforbidden();
}


$object=new Mailing($db);
$result=$object->fetch($id);
if ($result<0) {
	setEventMessage($object->errors,'errors');
}

$mailjet= new DolMailjet($db);
$result=$mailjet->fetch_by_mailing($id);
if ($result<0) {
	setEventMessage($mailjet->errors,'errors');
}

$extrafields = new ExtraFields($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('mailjetcard'));


/*
 * Control for MailJet regarding Dolibarr standard mailing
 */
$error_mailjet_control=0;

//Standard substitution are not allow in MailJet Mailing
$error_std_subst=false;
$subs_arr=array();
if (preg_match_all('/__.*__/',$object->body,$subs_arr)) {
	$subs_uses='';
	if (count($subs_arr[0])>0) {
		$subs_uses=implode(',',$subs_arr[0]);
	}
	$error_std_subst=true;
	$error_mailjet_control++;
}
//Attached file are not allowed for MailJet Mailing
$error_file_attach=false;
$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);
$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
if (count($listofpaths))
{
	$error_file_attach =true;
	$error_mailjet_control++;
}
//Unsubscribe link mandatory
$error_no_unscubscribe_link=false;
if (preg_match('/\[\[UNSUB_LINK_.*\]\]/',$object->body)==0) {
	$error_no_unscubscribe_link=true;
	$error_mailjet_control++;
}
//Sender is registered as valid sender into MailJet
$warning_sender_not_valid=false;
$result=$mailjet->checkMailSender($object->email_from);
if ($result<0) {
	setEventMessage($mailjet->errors,'errors');
}else {
	if (!$result) {
		$warning_sender_not_valid=true;
		$error_mailjet_control++;
	}
}


/*
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

// Action update description of emailing
if ($action == 'settitre' || $action == 'setemail_from' || $actino == 'setreplyto' || $action == 'setemail_errorsto') {

	if ($action == 'settitre')					$object->titre          = trim(GETPOST('titre','alpha'));
	else if ($action == 'setemail_from')		$object->email_from     = trim(GETPOST('email_from','alpha'));
	else if ($action == 'setemail_replyto')		$object->email_replyto  = trim(GETPOST('email_replyto','alpha'));
	else if ($action == 'setemail_errorsto')	$object->email_errorsto = trim(GETPOST('email_errorsto','alpha'));

	else if ($action == 'settitre' && empty($object->titre))		$mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailTitle"));
	else if ($action == 'setfrom' && empty($object->email_from))	$mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailFrom"));

	if (empty($mesg)) {
		if ($object->update($user) >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			setEventMessage($object->errors,'errors');
		}
	} else {
		setEventMessage($mesg,'errors');
	}

	$action="";
}


if ($action=='setsendername') {
	$mailjet->mailjet_sender_name  = GETPOST('sendername','alpha');
	if (empty($mailjet->id)) {
		$mailjet->fk_mailing=$object->id;
		$result=$mailjet->create($user);
	}else {
		$result=$mailjet->update($user);
	}
	if ($result<0) {
		setEventMessage($mailjet->error,'errors');
	}else {
		$result=$mailjet->fetch_by_mailing($id);
		if ($result<0) {
			setEventMessage($mailjet->error,'errors');
		}
	}
}

if ($action=='setpermalink') {
	$mailjet->mailjet_permalink  = GETPOST('permalink','alpha');
	if (empty($mailjet->id)) {
		$mailjet->fk_mailing=$object->id;
		$result=$mailjet->create($user);
	}else {
		$result=$mailjet->update($user);
	}
	if ($result<0) {
		setEventMessage($mailjet->error,'errors');
	}else {
		$result=$mailjet->fetch_by_mailing($id);
		if ($result<0) {
			setEventMessage($mailjet->error,'errors');
		}
	}
}

if ($action=='setlang') {
	$mailjet->mailjet_lang  = GETPOST('lang','alpha');
	if (empty($mailjet->id)) {
		$mailjet->fk_mailing=$object->id;
		$result=$mailjet->create($user);
	}else {
		$result=$mailjet->update($user);
	}
	if ($result<0) {
		setEventMessage($mailjet->error,'errors');
	}else {
		$result=$mailjet->fetch_by_mailing($id);
		if ($result<0) {
			setEventMessage($mailjet->error,'errors');
		}
	}
}

if ($action == 'createmailjetcampaign') {
	
	$mailjet->currentmailing=$object;
	
	$result=$mailjet->createMailJetCampaign($user);
	if ($result<0) {
		setEventMessage($mailjet->error,'errors');
	}
}

if ($action=='updatemailjetcampaign') {
	$mailjet->currentmailing=$object;
	
	$result=$mailjet->updateMailJetCampaign($user);
	if ($result<0) {
		setEventMessage($mailjet->error,'errors');
	}
}


if ($action=='sendmailjetcampaign') {
	//Send campaign
	$result=$mailjet->sendMailJetCampaign($user);
	if ($result<0) {
		setEventMessage($mailjet->error,'errors');
	} else {
		//Update mailing general status
		$object->statut=3;
		$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$object->statut." WHERE rowid=".$object->id;
		dol_syslog("mailjet/mailjet/mailjet.php: update global status sql=".$sql, LOG_DEBUG);
		$resql2=$db->query($sql);
		if (! $resql2)	{
			setEventMessage($db->lasterror(),'errors');
		}
		
		//Update inforamtion from mailjet
		$result=$mailjet->updateMailJetCampaignAttr($user);
		if ($result<0) {
			setEventMessage($mailjet->error,'errors');
		}
	}
}

if (action=='refreshstatus') {
	//Update inforamtion from mailjet
	$result=$mailjet->updateMailJetCampaignAttr($user);
	if ($result<0) {
		setEventMessage($mailjet->error,'errors');
	}
}

/*
 * VIEW
 *
 * Put here all code to build page
 */

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('mailing');

llxHeader('',$langs->trans("Mailing"));

$head = emailing_prepare_head($object);

dol_fiche_head($head, 'tabMailJetSending', $langs->trans("MailJet"), 0, 'email');

$form = new Form($db);

print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/liste.php">'.$langs->trans("BackToList").'</a>';

print '<tr><td width="15%">'.$langs->trans("Ref").'</td>';
print '<td colspan="3">';
print $form->showrefnav($object,'id', $linkback);
print '</td></tr>';

// Description
print '<tr><td>'.$form->editfieldkey("MailTitle",'titre',$object->titre,$object,$user->rights->mailing->creer && $object->statut < 3,'string').'</td><td colspan="3">';
print $form->editfieldval("MailTitle",'titre',$object->titre,$object,$user->rights->mailing->creer && $object->statut < 3,'string');
print '</td></tr>';

// From
print '<tr><td>'.$form->editfieldkey("MailFrom",'email_from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'string').'</td><td colspan="3">';
print $form->editfieldval("MailFrom",'email_from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'string');
print '</td></tr>';

// Status
print '<tr><td width="15%">'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

// Nb of distinct emails
print '<tr><td width="15%">';
print $langs->trans("TotalNbOfDistinctRecipients");
print '</td><td colspan="3">';
$nbemail = ($object->nbemail?$object->nbemail:img_warning('').' <font class="warning">'.$langs->trans("NoTargetYet").'</font>');
if ($object->statut != 3 && !empty($conf->global->MAILING_LIMIT_SENDBYWEB) && is_numeric($nbemail) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
{
	if ($conf->global->MAILING_LIMIT_SENDBYWEB > 0)	{
		$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
		print $form->textwithpicto($nbemail,$text,1,'warning');
	} else {
		$text=$langs->trans('NotEnoughPermissions');
		print $form->textwithpicto($nbemail,$text,1,'warning');
	}
} else {
	print $nbemail;
}
print '</td></tr>';

// MailJet Sender Name
print '<tr><td width="15%">';
print $form->editfieldkey("MailJetSenderName",'sendername',$mailjet->mailjet_sender_name,$object,$user->rights->mailing->creer && $object->statut < 3 && empty($mailjet->mailjet_id),'string');
print '</td><td colspan="3">';
print $form->editfieldval("MailJetSenderName",'sendername',$mailjet->mailjet_sender_name,$object,$user->rights->mailing->creer && $object->statut < 3 && empty($mailjet->mailjet_id),'string');
print '</td></tr>';

// MailJet permalink
print '<tr><td width="15%">';
print $form->editfieldkey("MailJetPermalink",'permalink',$mailjet->mailjet_permalink,$object,$user->rights->mailing->creer && $object->statut < 3  && empty($mailjet->mailjet_id),'select;default:'.$langs->trans('Yes').',none:'.$langs->trans('No'));
print '</td><td colspan="2">';
print $form->editfieldval("MailJetPermalink",'permalink',$mailjet->mailjet_permalink,$object,$user->rights->mailing->creer && $object->statut < 3  && empty($mailjet->mailjet_id),'select;default:'.$langs->trans('Yes').',none:'.$langs->trans('No'));
print '</td>';
print '<td>';
print $form->textwithpicto('',$langs->trans("MailJetPermalinkHelp"),1,'help');
print '</td>';
print '</tr>';

// MailJet lang
print '<tr><td width="15%">';
print $form->editfieldkey("MailJetLang",'lang',$mailjet->mailjet_lang,$object,$user->rights->mailing->creer && $object->statut < 3  && empty($mailjet->mailjet_id),'select;en:en,fr:fr,de:de,it:it,es:es,nl:nl');
print '</td><td colspan="3">';
print $form->editfieldval("MailJetLang",'lang',$mailjet->mailjet_lang,$object,$user->rights->mailing->creer && $object->statut < 3  && empty($mailjet->mailjet_id),'select;en:en,fr:fr,de:de,it:it,es:es,nl:nl');
print '</td>';
print '</tr>';

if (!empty($mailjet->mailjet_id)) {
	
	//Status campaign mailjet
	print '<tr><td width="15%">';
	print $langs->trans("MailJetStatus");
	print '</td><td colspan="3">';
	if (!empty($mailjet->mailjet_id)) {
		print $mailjet->getMailJetCampaignStatus();
	}
	print '</td></tr>';
	
	// MailJet Campaign
	print '<tr><td width="15%">';
	print $langs->trans("MailJetCampaign");
	print '</td><td colspan="3">';
	if ($mailjet->getMailJetCampaignStatus(0)==$langs->trans('MailJetdraft') || $mailjet->getMailJetCampaignStatus(0)==$langs->trans('MailJetprogrammed')) {
		if (!empty($mailjet->mailjet_id)) {
			print '<a href="'.$mailjet->mailjet_url.'" target="_blank">MailJet</a>';
		}
	}else {
		print '<a href="http://www.mailjet.com'.$mailjet->mailjet_uri.'" target="_blank">MailJet Stats.</a>';
	}
	print '</td></tr>';
	
	// MailJet Contact
	print '<tr><td width="15%">';
	print $langs->trans("MailJetCampaignListContact");
	print '</td><td colspan="3">';
	if (!empty($mailjet->mailjet_contact_list_id)) {
		if ($mailjet->getMailJetCampaignStatus(0)==$langs->trans('MailJetdraft') || $mailjet->getMailJetCampaignStatus(0)==$langs->trans('MailJetprogrammed')) {
			$contacts=$mailjet->listCampaignConcatsList('string');
			if ($contacts<0) {
				setEventMessage($mailjet->error,'errors');
			}else {
				print $contacts;
			}
		}else {
			$contacts=$mailjet->listCampaignConcatsListStatus('string');
			if ($contacts<0) {
				setEventMessage($mailjet->error,'errors');
			}else {
				print $contacts;
			}
		}
	}
	print '</td></tr>';
}


// Other attributes
$parameters=array();
$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
if (empty($reshook) && ! empty($extrafields->attribute_label)) {
	foreach($extrafields->attribute_label as $key=>$label) {
		$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
		print '<tr><td';
		if (! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
		print '>'.$label.'</td><td colspan="3">';
		print $extrafields->showInputField($key,$value);
		print "</td></tr>\n";
	}
}

print '</table>';

print "</div>";

if ($error_std_subst) {
	dol_htmloutput_mesg($langs->trans("MailJetNoStdReplacement",$subs_uses),'','error',1);
}
if ($error_file_attach) {
	dol_htmloutput_mesg($langs->trans("MailJetNoFileAttached"),'','error',1);
}
if ($error_no_unscubscribe_link) {
	dol_htmloutput_mesg($langs->trans("MailJetUnsubLinkMandatory"),'','error',1);
}
if (empty($mailjet->mailjet_lang)) {
	dol_htmloutput_mesg($langs->trans("MailJetLangMandatory"),'','error',1);
	$error_mailjet_control++;
}
if (empty($mailjet->mailjet_sender_name)) {
	dol_htmloutput_mesg($langs->trans("MailJetSenderNameMandatory"),'','error',1);
	$error_mailjet_control++;
}
if ($object->statut == 0) {
	dol_htmloutput_mesg($langs->trans("MailJetNotValidated").' : <a href="'.dol_buildpath('/comm/mailing/fiche.php',1).'?id='.$object->id.'">'.$langs->trans('Mailing').'</a>','','warning',1);
}
if ($warning_sender_not_valid) {
	dol_htmloutput_mesg($langs->trans("MailJetSenderNameNotValid").' : <a href="http://www.mailjet.com/account/sender" target="_blank">MailJet</a>','','error',1);
}

print "\n\n<div class=\"tabsAction\">\n";
if (($object->statut == 0 || $object->statut == 1) && $user->rights->mailing->creer) {
	print '<a class="butAction" href="'.dol_buildpath('/comm/mailing/fiche.php',1).'?action=edit&amp;id='.$object->id.'">'.$langs->trans("EditMailing").'</a>';
}

if (($object->statut == 1 || $object->statut == 2) && $object->nbemail > 0 && $user->rights->mailing->valider && !$error_mailjet_control) {
	if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("MailJetCreateCampaign").'</a>';
	} else {
		if (empty($mailjet->mailjet_id)) {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=createmailjetcampaign&amp;id='.$object->id.'">'.$langs->trans("MailJetCreateCampaign").'</a>';
		}else {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=updatemailjetcampaign&amp;id='.$object->id.'">'.$langs->trans("MailJetUpdateCampaign").'</a>';
		}
	}
}else {
	print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("MailJetCannotSendControlNotOK")).'">'.$langs->trans("MailJetCreateCampaign").'</a>';
}

if (!empty($mailjet->mailjet_id) && !$error_mailjet_control) {
	if (($object->statut == 1 || $object->statut == 2) && $object->nbemail > 0 && $user->rights->mailing->valider) {
		if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendMailing").'</a>';
		} else {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=sendmailjetcampaign&amp;id='.$object->id.'">'.$langs->trans("MailJetSendMailing").'</a>';
		}
	}
}

if (!empty($mailjet->mailjet_id) && !$error_mailjet_control && $object->statut == 3) {
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=refreshstatus&amp;id='.$object->id.'">'.$langs->trans("MailJetRefreshStatus").'</a>';
}

print '<br><br></div>';


// Print mail content
print_fiche_titre($langs->trans("EMail"),'','');
print '<table class="border" width="100%">';

// Subject
print '<tr><td width="15%">'.$langs->trans("MailTopic").'</td><td colspan="3">'.$object->sujet.'</td></tr>';

// Message
print '<tr><td valign="top">'.$langs->trans("MailMessage").'</td>';
print '<td colspan="3" bgcolor="'.($object->bgcolor?(preg_match('/^#/',$object->bgcolor)?'':'#').$object->bgcolor:'white').'">';
print dol_htmlentitiesbr($object->body);
print '</td>';
print '</tr>';

print '</table>';
print "<br>";

// End of page
llxFooter();
$db->close();
