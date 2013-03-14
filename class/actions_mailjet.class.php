<?php
/* Copyright (C) 2013	Florian HENRY 		<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file 		/mailjet/class/actions_mailjet.class.php
 *	\ingroup    mailjet
 *	\brief      File of class to manage mailjet hook action
 */


/**
 * 	\class 		ActionsMailjet
 *	\brief      Class to manage Mailjet
 */
class ActionsMailjet
{
	var $db;

	var $error;
	var $errors=array();


	/**
	 *  Constructor
	 *
	 *	@param	DoliDB	$db			Database handler
	*/
	function __construct($db)
	{
		$this->db = $db ;
		$this->error = 0;
		$this->errors = array();

	}
	
	/**
	 * 	Return action of hook
	 *
	 *	@param	array	$parameters		Linked object
	 *	@param	object	$object			Object
	 *	@param	string	$action			Action type
	 *	@return	void
	 */
	function doActions($parameters=false, &$object, &$action='')
	{
		global $langs,$conf;
		
		$langs->load("mailjet@mailjet");
		
		//Change substitution array
		$object->substitutionarray = array('[[UNSUB_LINK_EN]]' => 'MailjetUnsubscribeEN',
    								'[[UNSUB_LINK_FR]]' => 'MailjetUnsubscribeFR',
									'[[UNSUB_LINK_DE]]' => 'MailjetUnsubscribeDE',
									'[[UNSUB_LINK_ES]]' => 'MailjetUnsubscribeES',
									'[[UNSUB_LINK_NL]]' => 'MailjetUnsubscribeNL',
									'[[UNSUB_LINK_IT]]' => 'MailjetUnsubscribeIT',
									'[[EMAIL_TO]]'=> 'MailjeteMailto',
									'[[SHARE_FACEBOOK]]' => 'MailJetURLFacebook',
									'[[SHARE_TWITTER]]' => 'MailJetURLTwitter',
									'[[SHARE_GOOGLE]]' => 'MailJetURLGoogle',
									'[[SHARE_LINKEDIN]]' => 'MailJetURLLinkedin'
		
		);
		
		$object->substitutionarrayfortest = array('[[UNSUB_LINK_EN]]' => 'MailjetUnsubscribeEN',
    								'[[UNSUB_LINK_FR]]' => 'MailjetUnsubscribeFR',
									'[[UNSUB_LINK_DE]]' => 'MailjetUnsubscribeDE',
									'[[UNSUB_LINK_ES]]' => 'MailjetUnsubscribeES',
									'[[UNSUB_LINK_NL]]' => 'MailjetUnsubscribeNL',
									'[[UNSUB_LINK_IT]]' => 'MailjetUnsubscribeIT',
									'[[EMAIL_TO]]'=> 'MailjeteMailto',
									'[[SHARE_FACEBOOK]]' => 'MailJetURLFacebook',
									'[[SHARE_TWITTER]]' => 'MailJetURLTwitter',
									'[[SHARE_GOOGLE]]' => 'MailJetURLGoogle',
									'[[SHARE_LINKEDIN]]' => 'MailJetURLLinkedin');
		
		if ($action=='' || $action=='valid') {
			$error_mailjet_control=0;
			//Unsubscribe link mandatory
			if (preg_match('/\[\[UNSUB_LINK_.*\]\]/',$object->body)==0) {
				setEventMessage('MailJet:'.$langs->trans("MailJetUnsubLinkMandatory"),'warnings');
				$error_mailjet_control++;
			}
			
			//Standard substitution are not allow in MailJet Mailing
			$subs_arr=array();
			if (preg_match_all('/__.*__/',$object->body,$subs_arr)) {
				$subs_uses='';
				if (count($subs_arr[0])>0) {
					$subs_uses=implode(',',$subs_arr[0]);
				}
				setEventMessage('MailJet:'.$langs->trans("MailJetNoStdReplacement",$subs_uses),'warnings');
				$error_mailjet_control++;
			}
			
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			
			//Attached file are not allowed for MailJet Mailing
			$error_file_attach=false;
			$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);
			$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
			if (count($listofpaths))
			{
				setEventMessage('MailJet:'.$langs->trans("MailJetNoFileAttached"),'warnings');
				$error_mailjet_control++;
			}
			
			if ($action=='valid' && !empty($error_mailjet_control)) {
				setEventMessage('MailJet:'.$langs->trans("MailJetCannotSendControlNotOK"),'warnings');
			}
		}
		
		if ($action=='sendall') {
			setEventMessage('MailJet:'.$langs->trans("MailJetToSendByMailJetGoToMailJet"),'warnings');
		}
	
	}
}