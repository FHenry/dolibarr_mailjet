<?php
/* Copyright (C) 2013 Florian Henry  <florian.henry@open-concept.pro>
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
 * \file /mailjet/mailjet/mailjet.class.php
 * \ingroup mailjet
 */

// Put here all includes required by your class file
dol_include_once("/mailjet/class/dolmailjet.class.php");
dol_include_once("/mailjet/class/php-mailjet-v3-simple.class.php");

/**
 * Put here description of your class
 */
class DolMailjet extends AbstractDolMailjet {
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array (); // !< To return several error codes (or messages)
	var $element = 'mailjet'; // !< Id that identify managed objects
	var $table_element = 'mailjet'; // !< Name of table without prefix where object is stored
	var $id;
	var $entity;
	var $fk_mailing;
	var $mailjet_id;
	var $mailjet_stat_id;
	var $mailjet_url;
	var $mailjet_uri;
	var $mailjet_contact_list_id;
	var $mailjet_lang;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $tms = '';
	var $mailjet_sender_name;
	var $mailjet_permalink;
	var $currentmailing;
	var $mailjet;
	
	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db) {
		$this->db = $db;
		return 1;
	}
	
	/**
	 * Get Current instance of Mailjet Connector
	 *
	 * return MailJet Object of rise error
	 */
	private function getInstanceMailJet() {
		global $conf, $langs;
		
		if (! is_object($this->mailjet)) {
			if (empty($conf->global->MAILJET_MAIL_SMTPS_ID)) {
				$langs->load("mailjet@mailjet");
				$this->error = $langs->trans("MailJetAPIKeyNotSet");
				dol_syslog(get_class($this) . "::getInstanceMailJet " . $this->error, LOG_ERR);
				return - 1;
			}
			if (empty($conf->global->MAILJET_MAIL_SMTPS_PW)) {
				$langs->load("mailjet@mailjet");
				$this->error = $langs->trans("MailJetSecretKeyNotSet");
				dol_syslog(get_class($this) . "::getInstanceMailJet " . $this->error, LOG_ERR);
				return - 1;
			}
			
			// $mailjet =new MailjetApi\Api($conf->global->MAILJET_MAIL_SMTPS_ID,$conf->global->MAILJET_MAIL_SMTPS_PW);
			$mailjet = new Mailjet($conf->global->MAILJET_MAIL_SMTPS_ID, $conf->global->MAILJET_MAIL_SMTPS_PW);
			// var_dump($mailjet);
			
			$this->mailjet = $mailjet;
		}
		
		return 1;
	}
	
	/**
	 * Create MailJet Contact List
	 *
	 * @param string $contactlistname List Name
	 * @return int New mailjet id of contact list
	 */
	function createContactList($contactlistname) {
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createContactList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$params = array (
				'method' => 'POST',
				'Name' => $contactlistname 
		);
		
		// Call
		$response = $this->mailjet->contactslist($params);

		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::createContactList " . $this->error, LOG_ERR);
			return - 1;
		} else {
			return $response->Data[0]->ID;
		}
	}
	
	/**
	 * Send MailJet campaign
	 *
	 * @param User $user that creates
	 * @return int New mailjet id of contact list
	 */
	function sendMailJetCampaign($user) {
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::sendMailJetCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$params = array (
				'method' => 'POST',
				'ID' => $this->mailjet_id 
		);
		
		// Call
		$response = $this->mailjet->newsletterSend($params);
		
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::sendMailJetCampaign " . $this->error, LOG_ERR);
			return - 1;
		} else {
			return 1;
		}
	}
	
	/**
	 * get MailJet campaign Attrbiutes
	 *
	 * @param User $user that creates
	 * @return int if KO, >0 if OK
	 */
	function updateMailJetCampaignAttr($user) {
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateMailJetCampaignAttr " . $this->error, LOG_ERR);
			return - 1;
		}
		
		
		// Call
		$response = $this->mailjet->newsletter(array('ID'=>$this->mailjet_id));
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::updateMailJetCampaignAttr " . $this->error, LOG_ERR);
			return - 1;
		} else {		
			$this->mailjet_url = $response->Data[0]->Url;
			$result = $this->update($user);
			if ($result < 0) {
				dol_syslog(get_class($this) . "::updateMailJetCampaignAttr " . $this->error, LOG_ERR);
				return - 1;
			}
		}
		
		// Also update dolibarr destinaries status
		$mailjet_contact_arr = $this->listCampaignConcatsListStatus();
		if (count($mailjet_contact_arr) > 0) {
			foreach ( $mailjet_contact_arr as $obj ) {
				
				$dolibarr_contact_status = 1;
				
				if ($obj->status == 'queued') {
					$dolibarr_contact_status = 0;
				}
				if (($obj->status == 'sent') || ($obj->status == 'spam')) {
					$dolibarr_contact_status = 1;
				}
				if (($obj->status == 'opened') || ($obj->status == 'clicked')) {
					$dolibarr_contact_status = 2;
				}
				if ($obj->status == 'bounce') {
					$dolibarr_contact_status = - 1;
				}
				if ($obj->status == 'unsub') {
					$dolibarr_contact_status = 3;
				}
				
				$sql = "UPDATE " . MAIN_DB_PREFIX . "mailing_cibles";
				$sql .= " SET statut=" . $dolibarr_contact_status;
				$sql .= " WHERE fk_mailing=" . $this->fk_mailing . " AND email = '" . $obj->email . "'";
				
				dol_syslog(get_class($this) . "::updateMailJetCampaignAttr sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::updateMailJetCampaignAttr Error:" . $this->error, LOG_ERR);
					return - 1;
				}
				// Also mark the thridparty as "do not contact anymore
				if ($dolibarr_contact_status == 3) {
					// Update status communication of thirdparty prospect
					$sql = "UPDATE " . MAIN_DB_PREFIX . "societe SET fk_stcomm=-1 WHERE rowid IN (SELECT source_id FROM " . MAIN_DB_PREFIX . "mailing_cibles WHERE fk_mailing=" . $this->fk_mailing . " AND email = '" . $obj->email . "' AND source_type='thirdparty' AND source_id is not null)";
					
					dol_syslog(get_class($this) . "::updateMailJetCampaignAttr sql=" . $sql, LOG_DEBUG);
					
					$resql = $this->db->query($sql);
					if (! $resql) {
						$this->error = "Error " . $this->db->lasterror();
						dol_syslog(get_class($this) . "::updateMailJetCampaignAttr Error:" . $this->error, LOG_ERR);
						return - 1;
					}
				}
			}
		}
	}
	
	/**
	 * get MailJet campaign status
	 *
	 * @param int $mode 1 with picto, 0 only text
	 * @return string status
	 */
	function getMailJetCampaignStatus($mode = 1) {
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getMailJetCampaignStatus " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$params = array (
				"method" => "GET",
				'ID' => $this->mailjet_id 
		);
		
		// Call
		$response = $this->mailjet->newsletter($params);
		
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::getMailJetCampaignStatus " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$status = $response->Data[0]->Status;
			
			return DolMailjet::getLibStatus($status, $mode);
		}
	}
	
	/**
	 * get MailJet campaign status
	 *
	 * @param string $status Status to convert
	 * @param int $mode 1 with picto, 0 only text
	 * @return String status
	 */
	static function getLibStatus($status, $mode = 1) {
		global $langs;
		
		$langs->load("mailjet@mailjet");
		
		if ($mode == 0) {
			return $langs->trans('MailJet' . $status);
		}
		if ($mode == 1) {
			if ($status == '0') {
				return img_picto($langs->trans('MailJetdraft'), 'stcomm0') . ' ' . $langs->trans('MailJetdraft');
			}
			if ($status == '1') {
				return img_picto($langs->trans('MailJetprogrammed'), 'stcomm2') . ' ' . $langs->trans('MailJetprogrammed');
			}
			if ($status == '2') {
				return img_picto($langs->trans('MailJetsent'), 'stcomm3') . ' ' . $langs->trans('MailJetsent');
			}
			if ($status == '-1') {
				return img_picto($langs->trans('MailJetarchived'), 'stcomm2_grayed') . ' ' . $langs->trans('MailJetarchived');
			}
		}
	}
	
	/**
	 * Add contact to mailing list id
	 *
	 * @return int if KO, >0 if OK
	 */
	function addContactList() {
		
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::addContactList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		// Build the mailing contact list
		$sql = 'SELECT email FROM ' . MAIN_DB_PREFIX . 'mailing_cibles WHERE fk_mailing=\'' . $this->currentmailing->id . '\' AND statut<>3';
		
		dol_syslog(get_class($this) . "::addContactList sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			
			if ($num) {
				$mail_arr = array ();
				
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					
					if (! in_array($obj->email, $mail_arr)) {
						$mail_arr[] = $obj->email;
					}
					$i ++;
				}
				$this->db->free($resql);
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::addContactList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		if (count($mail_arr) > 0) {
			// Parameters
			$params = array (
					'method' => 'POST',
					'Action' => 'Add',
					'Addresses' => $mail_arr,
					'Force' =>true,
					'ListID' => $this->mailjet_contact_list_id
			);
			// Call
			$response = $this->mailjet->manycontacts($params);
		} else {
			$this->error = "Error No Mail found to add to contact list";
			dol_syslog(get_class($this) . "::addContactList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::addContactList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		return $num;
	}
	
	/**
	 * Create MailJet campaign
	 *
	 * @param User $user that creates
	 * @return int <0 if KO, >0 if OK
	 */
	function createCampaign() {
		global $langs;
		
		$error = 0;
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		
		if (empty($this->currentmailing->sujet)) {
			$this->errors[] = 'Subject is required';
			$error ++;
		}
		
		// Unsubscribe link mandatory
		if (preg_match('/\[\[UNSUB_LINK_.*\]\]/', $this->currentmailing->body) == 0) {
			$this->errors[] = 'MailJet Unsucribe link is mandatory';
			$error ++;
		}
		
		// Standard substitution are not allow in MailJet Mailing
		$subs_arr = array ();
		if (preg_match_all('/__.*__/', $this->currentmailing->body, $subs_arr)) {
			$subs_uses = '';
			if (count($subs_arr[0]) > 0) {
				$subs_uses = implode(',', $subs_arr[0]);
			}
			$this->errors[] = 'The substitution ' . $subs_uses . ' will not work with MailJet';
			$error ++;
		}
		
		if (empty($this->mailjet_lang)) {
			$this->errors[] = 'MailJet lang is required';
			$error ++;
		}
		
		if (empty($this->currentmailing->email_from)) {
			$this->errors[] = 'Mail From is required';
			$error ++;
		}
		
		if (empty($this->mailjet_sender_name)) {
			$this->errors[] = 'Mailjet sender name is required';
			$error ++;
		}
		
		//find sender ID
		if (empty($error)) {
			$params = array (
					"Email"  =>  $conf->global->MAILJET_MAIL_EMAIL_FROM
			);
				
			$response = $this->mailjet->sender($params);
			$emailsenders_array = $response->Data;
			if (count($emailsenders_array)>0) {
				$senderid=$emailsenders_array[0]->ID;
			}
		}
		
		if (empty($error)) {
			$params = array (
					'method' => 'POST',
					'Subject' => $this->currentmailing->sujet,
					'ContactsListID' => $this->mailjet_contact_list_id,
					'Locale' => $this->mailjet_lang,
					'Sender' => $senderid,
					'SenderEmail' => $this->currentmailing->email_from,
					'SenderName' => $this->mailjet_sender_name,
					'Footer' => 'default',
					'EditMode' => 'html',
					'Permalink' => $this->mailjet_permalink,
					'Status'=>0,
					'Title' => $this->currentmailing->titre . ' - le ' . dol_print_date(dol_now(), '%d-%m-%Y %H:%M') 
			);
			
			// Call
			$response = $this->mailjet->newsletter($params);

			if ($response === false) {
				$this->error = print_r($this->mailjet->_response->errors, true);
				dol_syslog(get_class($this) . "::createCampaign Error" . $this->error, LOG_ERR);
				return - 1;
			} else {
				// Result
				$this->mailjet_url = $response->Data[0]->Url;
				$this->mailjet_id = $response->Data[0]->ID;
				return 1;
			}
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::createCampaign " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		}
	}
	
	/**
	 * Update MailJet campaign
	 *
	 * @param User $user that creates
	 * @return int <0 if KO, >0 if OK
	 */
	function updateCampaign() {
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$params = array (
				'method' => 'PUT',
				'Subject' => $this->currentmailing->sujet,
				'ContactsListID' => $this->mailjet_contact_list_id,
				'Locale' => $this->mailjet_lang,
				'Sender' => $senderid,
				'SenderEmail' => $this->currentmailing->email_from,
				'SenderName' => $this->mailjet_sender_name,
				'Footer' => 'default',
				'EditMode' => 'html',
				'Permalink' => $this->mailjet_permalink,
				'Status'=>0,
				//'Title' => $this->currentmailing->titre . ' - le ' . dol_print_date(dol_now(), '%d-%m-%Y %H:%M') 
		);
		
		// Call
		$response = $this->mailjet->newsletter($params);
		
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response->errors, true);
			dol_syslog(get_class($this) . "::updateCampaign Error" . $this->error, LOG_ERR);
			return - 1;
		} else {
			return 1;
		}
	}
	
	/**
	 * Delete a contact list in MailJet
	 *
	 * @param User $user that creates
	 * @return int <0 if KO, >0 if OK
	 */
	function deleteContactList() {
		
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::deleteContactList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		// Parameters
		$params = array (
				'method' => 'DELETE',
				"ID" => $this->mailjet_contact_list_id 
		);
		// Call
		$response = $this->mailjet->contactslist($params);
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::deleteContactList Error" . $this->error, LOG_ERR);
			return - 1;
		} else {
			return 1;
		}
	}
	
	/**
	 * Check if sender mail is already a validated sender
	 *
	 * @param string $mail_sender use to send mails
	 * @return int <0 if KO, >0 if OK
	 */
	function checkMailSender($mail_sender = '') {
		global $conf;
		if (! empty($mail_sender) && isValidEmail($mail_sender)) {
			// Get Current MailJetInstace
			$result = $this->getInstanceMailJet();
			if ($result < 0) {
				dol_syslog(get_class($this) . "::checkMailSender " . $this->error, LOG_ERR);
				return - 1;
			}
			
			// Call
			$response = $this->mailjet->sender($params);
			$emailsenders_array = $response->Data;
			if ($response === false) {
				$this->error = print_r($this->mailjet->_response, true);
				dol_syslog(get_class($this) . "::checkMailSender Error" . $this->error, LOG_ERR);
				return - 1;
			} else {
				$sender_found = false;
				foreach ( $emailsenders_array as $obj ) {
					if ($mail_sender == $obj->Email) {
						$sender_found = true;
						break;
					}
					if (preg_match('/^\*@.*/', $obj->Email, $matches)) {
						foreach ( $matches as $key => $val ) {
							$domain_arr = explode('@', $val);
							$domain_sender_arr = explode('@', $mail_sender);
							if ($domain_arr[1] == $domain_sender_arr[1]) {
								$sender_found = true;
								break;
							}
						}
					}
				}
				return $sender_found;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * Update contact list with current contact
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function updateContactList() {
		
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateContactList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$contact_arr = $this->listCampaignConcatsList();
		if ($contacts < 0) {
			dol_syslog(get_class($this) . "::updateContactList " . $this->error, LOG_ERR);
			return $contacts;
		}
		$email_arr = array ();
		foreach ( $contact_arr as $obj ) {
			$email_arr[] = $obj->email;
		}
		
		$params = array (
				'method' => 'POST',
				'contacts' => implode(',', $email_arr),
				'id' => $this->mailjet_contact_list_id 
		);
		
		// Call
		$response = $this->mailjet->listsRemoveManyContacts($params);
		
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response->errors, true);
			dol_syslog(get_class($this) . "::updateContactList Error" . $this->error, LOG_ERR);
			return - 1;
		}
		
		$result = $this->addContactList();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateContactList " . $this->error, LOG_ERR);
			return $result;
		}
		
		return 1;
	}
	
	/**
	 * Update MailJet campaign
	 *
	 * @param User $user that creates
	 * @return int <0 if KO, >0 if OK
	 */
	function updateMailJetCampaign($user) {
		global $conf;
		
		// Not use because once the Dolibarr is validated no destinaries can be added
		/*
		//Update contact to contact list
		$result=$this->updateContactList();
		if ($result < 0) {
			dol_syslog(get_class($this)."::updateMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}*/
		
		// Create Mail Jet Campaign
		$result = $this->updateCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateMailJetCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		
		// Set Body Text of campaign
		$result = $this->setBodyCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateMailJetCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Create MailJet Full campaign (contact list/contact and campaign)
	 *
	 * @param User $user that creates
	 * @return int <0 if KO, >0 if OK
	 */
	function createMailJetCampaign($user) {
		global $conf;
		
		$this->fk_mailing = $this->currentmailing->id;
		if (empty($this->id)) {
			$result = $this->create($user);
		} else {
			$result = $this->update($user);
		}
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		
		// Create Contact List
		$contactlistname = $this->currentmailing->id . preg_replace('/[^a-zA-Z0-9\-]/', '', $this->currentmailing->titre) . dol_print_date(dol_now(), 'dayhourlog');
		
		$result = $this->createContactList($contactlistname);
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$this->mailjet_contact_list_id = $result;
		}
		
		// Add all contact to contact list
		$result = $this->addContactList();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
			$result = $this->deleteContactList();
			if ($result < 0) {
				dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
				return - 2;
			}
			return - 1;
		}
		
		// Create Mail Jet Campaign
		$result = $this->createCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
			$result = $this->deleteContactList();
			if ($result < 0) {
				dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
				return - 2;
			}
			return - 1;
		}
		
		// Set Body Text of campaign
		$result = $this->setBodyCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$result = $this->update($user);
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
			$result = $this->deleteContactList();
			if ($result < 0) {
				dol_syslog(get_class($this) . "::createMailJetCampaign " . $this->error, LOG_ERR);
				return - 2;
			}
			return - 1;
		}
	}
	
	/**
	 * Set the HTML body of the campaign
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function setBodyCampaign() {
		global $langs;
		$langs->load("mailjet@mailjet");
		$langs->load("mails");
		
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::setBodyCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$substitutionarray = array (
				'[[UNSUB_LINK_EN]]' => '<a href="[[UNSUB_LINK_EN]]" target="_blank">' . $langs->trans("MailUnsubcribe") . '</a>',
				'[[UNSUB_LINK_FR]]' => '<a href="[[UNSUB_LINK_FR]]" target="_blank">' . $langs->trans("MailUnsubcribe") . '</a>',
				'[[UNSUB_LINK_DE]]' => '<a href="[[UNSUB_LINK_DE]]" target="_blank">' . $langs->trans("MailUnsubcribe") . '</a>',
				'[[UNSUB_LINK_ES]]' => '<a href="[[UNSUB_LINK_ES]]" target="_blank">' . $langs->trans("MailUnsubcribe") . '</a>',
				'[[UNSUB_LINK_NL]]' => '<a href="[[UNSUB_LINK_NL]]" target="_blank">' . $langs->trans("MailUnsubcribe") . '</a>',
				'[[UNSUB_LINK_IT]]' => '<a href="[[UNSUB_LINK_IT]]" target="_blank">' . $langs->trans("MailUnsubcribe") . '</a>',
				'[[SHARE_FACEBOOK]]' => '<a href="[[SHARE_FACEBOOK]]" target="_blank">' . $langs->trans("MailJetSocialNetworkLink") . '</a>',
				'[[SHARE_TWITTER]]' => '<a href="[[SHARE_TWITTER]]" target="_blank">' . $langs->trans("MailJetSocialNetworkLink") . '</a>',
				'[[SHARE_GOOGLE]]' => '<a href="[[SHARE_GOOGLE]]" target="_blank">' . $langs->trans("MailJetSocialNetworkLink") . '</a>',
				'[[SHARE_LINKEDIN]]' => '<a href="[[SHARE_LINKEDIN]]" target="_blank">' . $langs->trans("MailJetSocialNetworkLink") . '</a>' 
		);
		$body = make_substitutions($this->currentmailing->body, $substitutionarray);
		
		// Parameters
		$params = array (
				'method' => 'PUT',
				'ID' => $this->mailjet_id,
				'Html-part' => $body,
				'Text-part' => $this->currentmailing->body 
		);
		// Call
		$response = $this->mailjet->newsletterDetailContent($params);
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::setBodyCampaign Error" . $this->error, LOG_ERR);
			return - 1;
		} else {
			return 1;
		}
	}
	
	/**
	 * MailJet Contact List
	 *
	 * @param $typeresult string
	 * @return mixed array of contact email, or string with email only
	 */
	function listCampaignConcatsList($typeresult = 'array') {
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::listCampaignConcatsList " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$params = array (
				'id' => $this->mailjet_contact_list_id 
		);
		
		// Call
		$response = $this->mailjet->listsContacts($params);
		if (! $response) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::listCampaignConcatsList " . $this->error, LOG_ERR);
			return - 1;
		} else {
			if ($typeresult == 'array') {
				return $response->result;
			} else {
				$str_return = '';
				
				if ($response->total_cnt > 0) {
					$i = 1;
					foreach ( $response->result as $obj ) {
						$str_return .= $obj->email;
						if ($i != $response->total_cnt) {
							$str_return .= ', ';
						}
						if (($i % 7) == 0) {
							$str_return .= '<BR>';
						}
						$i ++;
					}
				}
				return $str_return;
			}
		}
	}
	
	/**
	 * MailJet Contact List with status
	 *
	 * @param $typeresult string
	 * @return mixed array of contact email, or string with email only
	 */
	function listCampaignConcatsListStatus($typeresult = 'array') {
		// Get Current MailJetInstace
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::listCampaignConcatsListStatus error:" . $this->error, LOG_ERR);
			return - 1;
		}
		
		//Build contact Id
		$mail_arr=array();
		$sql = 'SELECT email FROM ' . MAIN_DB_PREFIX . 'mailing_cibles WHERE fk_mailing=' . $this->currentmailing->id;
		dol_syslog(get_class($this) . "::addContactList sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
				
			if ($num) {
				$mail_arr = array ();
		
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
						
					if (! in_array($obj->email, $mail_arr)) {
						$mail_arr[] = $obj->email;
					}
					$i ++;
				}
				$this->db->free($resql);
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::listCampaignConcatsListStatus " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$contact_id=array();
		$response = $this->mailjet->contact();
		if (! $response) {
			$this->error = print_r($this->mailjet->_response->error, true);
			dol_syslog(get_class($this) . "::listCampaignConcatsListStatus $this->mailjet_id error1:" . $this->error, LOG_ERR);
			return - 1;
		} else {
			foreach($response->Data as $data) {
				if (in_array($data->Email,$mail_arr)) {
					$contact_id[]=$data->ID;
				}
			}
		}
		
		//Find current campaing ID
		$params = array (
				'NewsLetterID' => $this->mailjet_id
		);
		
		$response = $this->mailjet->campaignstatistics($params);
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::getCampaignStatistics " . $this->error, LOG_ERR);
			return - 1;
		}
		$CampaignID=$response->Data[0]->CampaignID;
		
		
		
		$params = array (
				'CampaignID' => $CampaignID,
		);
		
		// Call
		$response = $this->mailjet->openinformation($params);
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::getCampaignStatistics " . $this->error, LOG_ERR);
			return - 1;
		}
		else {
				foreach($response->Data as $data) {
					if (in_array($data->ContactID,$contact_id) && $data->CampaignID==$CampaignID) {
						if (date($data->ArrivedAt)>dol_now()) {
							
						}
					}
				}
			}
		
		
		
		
		exit;
		
		if (! $response) {
			$this->error = print_r($this->mailjet->_response->error, true);
			dol_syslog(get_class($this) . "::listCampaignConcatsListStatus $this->mailjet_id error1:" . $this->error, LOG_ERR);
			return - 1;
		} else {
			if ($typeresult == 'array') {
				return $response->result;
			} else {
				$str_return = '';
				
				if ($response->total_cnt > 0) {
					foreach ( $response->result as $obj ) {
						$str_return .= $obj->email . ' ' . DolMailjet::getLibContactStatus($obj->status) . '<BR>';
					}
				}
				return $str_return;
			}
		}
	}
	
	/**
	 * get MailJet campaign status
	 *
	 * @param string $status Status to convert
	 * @param int $mode 1 with picto, 0 only text
	 * @return String status
	 */
	static function getLibContactStatus($status, $mode = 1) {
		global $langs;
		
		$langs->load("mailjet@mailjet");
		
		if ($mode == 0) {
			return $langs->trans('MailJetContactStatus' . $status);
		}
		
		if ($mode == 1) {
			if ($status == 'queued') {
				return img_picto($langs->trans('MailJetContactStatus' . $status), 'stcomm0_grayed') . ' ' . $langs->trans('MailJetContactStatus' . $status);
			}
			if ($status == 'sent') {
				return img_picto($langs->trans('MailJetContactStatus' . $status), 'stcomm2') . ' ' . $langs->trans('MailJetContactStatus' . $status);
			}
			if ($status == 'opened') {
				return img_picto($langs->trans('MailJetContactStatus' . $status), 'stcomm3') . ' ' . $langs->trans('MailJetContactStatus' . $status);
			}
			if ($status == 'clicked') {
				return img_picto($langs->trans('MailJetContactStatus' . $status), 'stcomm4') . ' ' . $langs->trans('MailJetContactStatus' . $status);
			}
			if ($status == 'bounce') {
				return img_picto($langs->trans('MailJetContactStatus' . $status), 'warning') . ' ' . $langs->trans('MailJetContactStatus' . $status);
			}
			if ($status == 'spam') {
				return img_picto($langs->trans('MailJetContactStatus' . $status), 'stcomm-1') . ' ' . $langs->trans('MailJetContactStatus' . $status);
			}
			if ($status == 'unsub') {
				return img_picto($langs->trans('MailJetContactStatus' . $status), 'stcomm4_grayed') . ' ' . $langs->trans('MailJetContactStatus' . $status);
			}
		}
	}
	
	/**
	 * get MailJet campaign statistics
	 *
	 * @return Array statistics
	 */
	function getCampaignStatistics() {
		$result = $this->getInstanceMailJet();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getCampaignStatistics " . $this->error, LOG_ERR);
			return - 1;
		}
		
		// Call
		$params = array (
				'NewsLetterID' => $this->mailjet_id
		);
		
		$response = $this->mailjet->campaignstatistics($params);
		if ($response === false) {
			$this->error = print_r($this->mailjet->_response, true);
			dol_syslog(get_class($this) . "::getCampaignStatistics " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$stats = new stdClass();
		$stats->opened=0;
		$stats->total=0;
		$stats->blocked=0;
		$stats->spam=0;
		$stats->delivered=0;
		$stats->queued=0;
		$stats->clicked=0;
		
		if (count($response->Data)>0) {
			foreach ($response->Data as $data) {
					$stats->opened+=$data->OpenedCount;
					$stats->total+=$data->ProcessedCount;
					$stats->blocked+=$data->BouncedCount;
					$stats->spam+=$data->SpamComplaintCount;
					$stats->delivered+=$data->DeliveredCount;
					$stats->queued+=$data->QueuedCount;
					$stats->clicked+=$data->ClickedCount;
			}
		}
		
		return $stats;
	}
}