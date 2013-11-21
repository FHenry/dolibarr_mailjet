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
 *  \file       /mailjet/mailjet/mailjet.class.php
 *  \ingroup    mailjet
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
dol_include_once("/mailjet/class/php-mailjet.class-mailjet-0.1.php");


/**
 *	Put here description of your class
*/
class DolMailjet extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='mailjet';			//!< Id that identify managed objects
	var $table_element='mailjet';	//!< Name of table without prefix where object is stored

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
	var $datec='';
	var $fk_user_mod;
	var $tms='';
	var $mailjet_sender_name;
	var $mailjet_permalink;

	var $currentmailing;

	var $mailjet;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	function __construct($db) {
		$this->db = $db;
		return 1;
	}

	/**
	 *   Get Current instance of Mailjet Connector
	 *
	 *   return 			MailJet Object of rise error
	 */
	private function getInstanceMailJet() {
		 
		global $conf,$langs;
		 
		if (! is_object($this->mailjet)) {
			if (empty($conf->global->MAILJET_MAIL_SMTPS_ID)) {
				$langs->load("mailjet@mailjet");
				$this->error=$langs->trans("MailJetAPIKeyNotSet");
				dol_syslog(get_class($this)."::getInstanceMailJet ".$this->error, LOG_ERR);
				return -1;
			}
			if (empty($conf->global->MAILJET_MAIL_SMTPS_PW)) {
				$langs->load("mailjet@mailjet");
				$this->error=$langs->trans("MailJetSecretKeyNotSet");
				dol_syslog(get_class($this)."::getInstanceMailJet ".$this->error, LOG_ERR);
				return -1;
			}

			$mailjet = new Mailjet($conf->global->MAILJET_MAIL_SMTPS_ID,$conf->global->MAILJET_MAIL_SMTPS_PW);

			$mailjet->debug=0;

			//$mailjet->debug=1; //Errors only

			//$mailjet->debug=2; //For debug session

			$this->mailjet=$mailjet;
		}
	  
		return 1;
	}


	/**
	 *  Create object into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger=0) {
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_mailing)) $this->fk_mailing=trim($this->fk_mailing);
		if (isset($this->mailjet_id)) $this->mailjet_id=trim($this->mailjet_id);
		if (isset($this->mailjet_stat_id)) $this->mailjet_stat_id=trim($this->mailjet_stat_id);
		if (isset($this->mailjet_url)) $this->mailjet_url=trim($this->mailjet_url);
		if (isset($this->mailjet_uri)) $this->mailjet_uri=trim($this->mailjet_uri);
		if (isset($this->mailjet_contact_list_id)) $this->mailjet_contact_list_id=trim($this->mailjet_contact_list_id);
		if (isset($this->mailjet_sender_name)) $this->mailjet_sender_name=trim($this->mailjet_sender_name);
		if (isset($this->mailjet_permalink)) $this->mailjet_permalink=trim($this->mailjet_permalink);
		if (isset($this->mailjet_lang)) $this->mailjet_lang=trim($this->mailjet_lang);

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailjet(";

		$sql.= "entity,";
		$sql.= "fk_mailing,";
		$sql.= "mailjet_id,";
		$sql.= "mailjet_stat_id,";
		$sql.= "mailjet_url,";
		$sql.= "mailjet_uri,";
		$sql.= "mailjet_contact_list_id,";
		$sql.= "mailjet_lang,";
		$sql.= "mailjet_sender_name,";
		$sql.= "mailjet_permalink,";
		$sql.= "fk_user_author,";
		$sql.= "datec,";
		$sql.= "fk_user_mod";


		$sql.= ") VALUES (";

		$sql.= " ".$conf->entity.",";
		$sql.= " ".(! isset($this->fk_mailing)?'NULL':"'".$this->fk_mailing."'").",";
		$sql.= " ".(! isset($this->mailjet_id)?'NULL':"'".$this->mailjet_id."'").",";
		$sql.= " ".(! isset($this->mailjet_stat_id)?'NULL':"'".$this->mailjet_stat_id."'").",";
		$sql.= " ".(! isset($this->mailjet_url)?'NULL':"'".$this->db->escape($this->mailjet_url)."'").",";
		$sql.= " ".(! isset($this->mailjet_uri)?'NULL':"'".$this->db->escape($this->mailjet_uri)."'").",";
		$sql.= " ".(! isset($this->mailjet_contact_list_id)?'NULL':"'".$this->mailjet_contact_list_id."'").",";
		$sql.= " ".(! isset($this->mailjet_lang)?'NULL':"'".$this->db->escape($this->mailjet_lang)."'").",";
		$sql.= " ".(! isset($this->mailjet_sender_name)?'NULL':"'".$this->db->escape($this->mailjet_sender_name)."'").",";
		$sql.= " ".(! isset($this->mailjet_permalink)?'NULL':"'".$this->db->escape($this->mailjet_permalink)."'").",";
		$sql.= " ".$user->id.",";
		$sql.= " ".$this->db->idate(dol_now()).",";
		$sql.= " ".$user->id;


		$sql.= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mailjet");

			if (! $notrigger)	{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)	{
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.entity,";
		$sql.= " t.fk_mailing,";
		$sql.= " t.mailjet_id,";
		$sql.= " t.mailjet_stat_id,";
		$sql.= " t.mailjet_url,";
		$sql.= " t.mailjet_uri,";
		$sql.= " t.mailjet_lang,";
		$sql.= " t.mailjet_contact_list_id,";
		$sql.= " t.mailjet_sender_name,";
		$sql.= " t.mailjet_permalink,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.datec,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.tms";


		$sql.= " FROM ".MAIN_DB_PREFIX."mailjet as t";
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)	{
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->mailjet_id = $obj->mailjet_id;
				$this->mailjet_stat_id = $obj->mailjet_stat_id;
				$this->mailjet_url = $obj->mailjet_url;
				$this->mailjet_uri = $obj->mailjet_uri;
				$this->mailjet_contact_list_id = $obj->mailjet_contact_list_id;
				$this->mailjet_lang = $obj->mailjet_lang;
				$this->mailjet_sender_name = $obj->mailjet_sender_name;
				$this->mailjet_permalink = $obj->mailjet_permalink;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);

			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    Id of mailing
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch_by_mailing($id) {
		global $langs;
		 
		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.entity,";
		$sql.= " t.fk_mailing,";
		$sql.= " t.mailjet_id,";
		$sql.= " t.mailjet_stat_id,";
		$sql.= " t.mailjet_url,";
		$sql.= " t.mailjet_uri,";
		$sql.= " t.mailjet_lang,";
		$sql.= " t.mailjet_contact_list_id,";
		$sql.= " t.mailjet_sender_name,";
		$sql.= " t.mailjet_permalink,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.datec,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.tms";


		$sql.= " FROM ".MAIN_DB_PREFIX."mailjet as t";
		$sql.= " WHERE t.fk_mailing = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)	{
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->mailjet_id = $obj->mailjet_id;
				$this->mailjet_stat_id = $obj->mailjet_stat_id;
				$this->mailjet_url = $obj->mailjet_url;
				$this->mailjet_uri = $obj->mailjet_uri;
				$this->mailjet_contact_list_id = $obj->mailjet_contact_list_id;
				$this->mailjet_lang = $obj->mailjet_lang;
				$this->mailjet_sender_name = $obj->mailjet_sender_name;
				$this->mailjet_permalink = $obj->mailjet_permalink;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);


			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function update($user=0, $notrigger=0) {
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_mailing)) $this->fk_mailing=trim($this->fk_mailing);
		if (isset($this->mailjet_id)) $this->mailjet_id=trim($this->mailjet_id);
		if (isset($this->mailjet_stat_id)) $this->mailjet_stat_id=trim($this->mailjet_stat_id);
		if (isset($this->mailjet_url)) $this->mailjet_url=trim($this->mailjet_url);
		if (isset($this->mailjet_uri)) $this->mailjet_uri=trim($this->mailjet_uri);
		if (isset($this->mailjet_contact_list_id)) $this->mailjet_contact_list_id=trim($this->mailjet_contact_list_id);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);
		if (isset($this->mailjet_lang)) $this->mailjet_lang=trim($this->mailjet_lang);
		if (isset($this->mailjet_sender_name)) $this->mailjet_sender_name=trim($this->mailjet_sender_name);
		if (isset($this->mailjet_permalink)) $this->mailjet_permalink=trim($this->mailjet_permalink);

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."mailjet SET";

		$sql.= " entity=".$conf->entity.",";
		$sql.= " fk_mailing=".(isset($this->fk_mailing)?$this->fk_mailing:"null").",";
		$sql.= " mailjet_id=".(isset($this->mailjet_id)?$this->mailjet_id:"null").",";
		$sql.= " mailjet_stat_id=".(isset($this->mailjet_stat_id)?$this->mailjet_stat_id:"null").",";
		$sql.= " mailjet_url=".(isset($this->mailjet_url)?"'".$this->db->escape($this->mailjet_url)."'":"null").",";
		$sql.= " mailjet_uri=".(isset($this->mailjet_uri)?"'".$this->db->escape($this->mailjet_uri)."'":"null").",";
		$sql.= " mailjet_contact_list_id=".(isset($this->mailjet_contact_list_id)?$this->mailjet_contact_list_id:"null").",";
		$sql.= " mailjet_lang=".(isset($this->mailjet_lang)?"'".$this->db->escape($this->mailjet_lang)."'":"null").",";
		$sql.= " mailjet_sender_name=".(isset($this->mailjet_sender_name)?"'".$this->db->escape($this->mailjet_sender_name)."'":"null").",";
		$sql.= " mailjet_permalink=".(isset($this->mailjet_permalink)?"'".$this->db->escape($this->mailjet_permalink)."'":"null").",";
		$sql.= " fk_user_mod=".$user->id;

		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}

		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)	{
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0) {
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		if (! $error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailjet";
			$sql.= " WHERE rowid=".$this->id;

			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error++; $this->errors[]="Error ".$this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error)	{
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen() {
		$this->id=0;

		$this->entity=$conf->entity;
		$this->fk_mailing='';
		$this->mailjet_id='';
		$this->mailjet_stat_id='';
		$this->mailjet_url='';
		$this->mailjet_uri='';
		$this->mailjet_lang='';
		$this->mailjet_contact_list_id='';
		$this->fk_user_author='';
		$this->mailjet_permalink='';
		$this->mailjet_sender_name='';
		$this->datec='';
		$this->fk_user_mod='';
		$this->tms='';
	}

	/**
	 *	Create MailJet Contact List
	 *
	 *	@param	string		$contactlistname     Contact List Name
	 * 	@return	int							  New mailjet id of contact list
	 */
	function createContactList($contactlistname) {

		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::createContactList ".$this->error, LOG_ERR);
			return -1;
		}

		$params = array(
		'method' => 'POST',
		'label' => $contactlistname,
		'name' => $contactlistname
		);

		# Call
		$response = $this->mailjet->listsCreate($params);

		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::createContactList ".$this->error, LOG_ERR);
			return -1;
		}else {
			return $response->list_id;
		}

	}
	
	/**
	 *	Send MailJet campaign
	 *
	 *	@param	User	$user        User that creates
	 * 	@return	int							  New mailjet id of contact list
	 */
	function sendMailJetCampaign($user) {
	
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::sendMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}
	
		$params = array(
			'method' => 'POST',
    		'id' => $this->mailjet_id
		);
	
		# Call
		$response = $this->mailjet->messageSendCampaign($params);
	
		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::sendMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}else {
			return 1;
		}
	
	}
	
	/**
	 *	get MailJet campaign Attrbiutes
	 *
	 *  @param	User	$user        User that creates
	 * 	@return	int			<0 if KO, >0 if OK
	 */
	function updateMailJetCampaignAttr($user) {
	
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::updateMailJetCampaignAttr ".$this->error, LOG_ERR);
			return -1;
		}
	
		$params = array(
		'id' => $this->mailjet_id
		);
	
		# Call
		$response = $this->mailjet->messageCampaigns($params);
	
		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::updateMailJetCampaignAttr ".$this->error, LOG_ERR);
			return -1;
		}else {
			$this->mailjet_uri=$response->result[0]->report_uri;
			$this->mailjet_stat_id=$response->result[0]->stats_campaign_id;
			$this->mailjet_url=$response->result[0]->url;
			$result=$this->update($user);
			if ($result<0) {
				dol_syslog(get_class($this)."::updateMailJetCampaignAttr ".$this->error, LOG_ERR);
				return -1;
			}
		}
		
		//Also update dolibarr destinaries status
		$mailjet_contact_arr=$this->listCampaignConcatsListStatus();
		if (count($mailjet_contact_arr)>0) {
			foreach($mailjet_contact_arr as $obj) {
				
				$dolibarr_contact_status=1;
					
				if ($obj->status=='queued') {
					$dolibarr_contact_status=0;
				}
				if (($obj->status=='sent') || ($obj->status=='spam')) {
					$dolibarr_contact_status=1;
				}
				if (($obj->status=='opened') || ($obj->status=='clicked')){
					$dolibarr_contact_status=2;
				}
				if ($obj->status=='bounce') {
					$dolibarr_contact_status=-1;
				}
				if ($obj->status=='unsub') {
					$dolibarr_contact_status=3;
				}
	
				$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
		        $sql .= " SET statut=".$dolibarr_contact_status;
		        $sql .= " WHERE fk_mailing=".$this->fk_mailing." AND email = '".$obj->email."'";
		        
		        dol_syslog(get_class($this)."::updateMailJetCampaignAttr sql=".$sql, LOG_DEBUG);
		        $resql=$this->db->query($sql);
		        if (!$resql) {
		        	$this->error="Error ".$this->db->lasterror();
					dol_syslog(get_class($this)."::updateMailJetCampaignAttr Error:".$this->error, LOG_ERR);
					return -1;
		        }
		        //Also mark the thridparty as "do not contact anymore
		        if ($dolibarr_contact_status==3) {
		        	//Update status communication of thirdparty prospect
		        	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=-1 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE fk_mailing=".$this->fk_mailing." AND email = '".$obj->email."' AND source_type='thirdparty' AND source_id is not null)";
		        	
		        	 dol_syslog(get_class($this)."::updateMailJetCampaignAttr sql=".$sql, LOG_DEBUG);
		        	
		        	$resql=$this->db->query($sql);
		        	if (!$resql) {
		        		$this->error="Error ".$this->db->lasterror();
		        		dol_syslog(get_class($this)."::updateMailJetCampaignAttr Error:".$this->error, LOG_ERR);
		        		return -1;
		        	}
		        }
			}
		}
	}
	
	/**
	 *	get MailJet campaign status
	 *
	 *  @param int	 	$mode    	1 with picto, 0 only text
	 * 	@return	string			Campagin status
	 */
	function getMailJetCampaignStatus($mode=1) {
	
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::getMailJetCampaignStatus ".$this->error, LOG_ERR);
			return -1;
		}
	
		$params = array(
			'id' => $this->mailjet_id
		);
	
		# Call
		$response = $this->mailjet->messageCampaigns($params);
	
		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::getMailJetCampaignStatus ".$this->error, LOG_ERR);
			return -1;
		}else {
			$status = $response->result[0]->status;
			
			return DolMailjet::getLibStatus($status,$mode);
		}
	}
	
	/**
	 *	get MailJet campaign status
	 *
	 *  @param string 	$status    Status to convert
	 *  @param int	 	$mode    	1 with picto, 0 only text
	 * 	@return	String			Campagin status
	 */
	static function getLibStatus($status,$mode=1) {
		global $langs;
		
		$langs->load("mailjet@mailjet");
		
		if ($mode==0) {
			return $langs->trans('MailJet'.$status); 
		}
		if ($mode==1) {
			if ($status=='draft') {
				return img_picto($langs->trans('MailJet'.$status),'stcomm0').' '.$langs->trans('MailJet'.$status);
			}
			if ($status=='programmed') {
				return img_picto($langs->trans('MailJet'.$status),'stcomm2').' '.$langs->trans('MailJet'.$status);
			}
			if ($status=='sent') {
				return img_picto($langs->trans('MailJet'.$status),'stcomm3').' '.$langs->trans('MailJet'.$status);
			}
			if ($status=='archived') {
				return img_picto($langs->trans('MailJet'.$status),'stcomm2_grayed').' '.$langs->trans('MailJet'.$status);
			}
		}
	}
	

	/**
	 *	Add contact to mailing list id
	 *
	 * 	@return	int			<0 if KO, >0 if OK
	 */
	function addContactList() {

		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::addContactList ".$this->error, LOG_ERR);
			return -1;
		}

		//Build the mailing contact list
		$sql='SELECT email FROM '.MAIN_DB_PREFIX.'mailing_cibles WHERE fk_mailing=\''.$this->currentmailing->id.'\' AND statut<>3';

		dol_syslog(get_class($this)."::addContactList sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)	{
			$num=$this->db->num_rows($resql);
			$i=0;
				
			if ($num) {
				$mail_arr=array();

				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
						
					if (!in_array($obj->email, $mail_arr)) {
						$mail_arr[]=$obj->email;
					}
					$i++;
				}
				$this->db->free($resql);
			}
		} else {
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::addContactList ".$this->error, LOG_ERR);
			return -1;
		}

		if (count($mail_arr)>0) {
			# Parameters
			$params = array(
				'method' => 'POST',
				'contacts' => implode(',',$mail_arr),
				'id' => $this->mailjet_contact_list_id
			);
			# Call
			$response = $this->mailjet->listsAddManyContacts($params);
				
		}else {
			$this->error="Error No Mail found to add to contact list";
			dol_syslog(get_class($this)."::addContactList ".$this->error, LOG_ERR);
			return -1;
		}

		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::addContactList ".$this->error, LOG_ERR);
			return -1;
		}
		
		return $num;
	}

	/**
	 *	Create MailJet campaign
	 *
	 *  @param	User	$user        User that creates
	 * 	@return	int			 		<0 if KO, >0 if OK
	 */
	function createCampaign() {
		
		global $langs;
		
		$error=0;
		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::createCampaign ".$this->error, LOG_ERR);
			return -1;
		}
		
		if (empty($this->currentmailing->sujet)) {
			$this->errors[]='Subject is required';
			$error++;
		}
		
		//Unsubscribe link mandatory
		if (preg_match('/\[\[UNSUB_LINK_.*\]\]/',$this->currentmailing->body)==0) {
			$this->errors[]='MailJet Unsucribe link is mandatory';
			$error++;
		}
			
		//Standard substitution are not allow in MailJet Mailing
		$subs_arr=array();
		if (preg_match_all('/__.*__/',$this->currentmailing->body,$subs_arr)) {
			$subs_uses='';
			if (count($subs_arr[0])>0) {
				$subs_uses=implode(',',$subs_arr[0]);
			}
			$this->errors[]='The substitution '.$subs_uses.' will not work with MailJet';
			$error++;
		}

		if (empty($this->mailjet_lang)) {
			$this->errors[]='MailJet lang is required';
			$error++;
		}
		
		if (empty($this->currentmailing->email_from)) {
			$this->errors[]='Mail From is required';
			$error++;
		}
		
		if (empty($this->mailjet_sender_name)) {
			$this->errors[]='Mailjet sender name is required';
			$error++;
		}
		
		if (empty($error)) { 
			$params = array(
				'method' => 'POST',
				'subject' => $this->currentmailing->sujet,
				'list_id' => $this->mailjet_contact_list_id,
				'lang' => $this->mailjet_lang,
				'from' => $this->currentmailing->email_from,
				'from_name' => $this->mailjet_sender_name,
				'footer' => 'default',
				'edition_mode' => 'html',
				'permalink'=>$this->mailjet_permalink,
				'title'=>$this->currentmailing->titre .' - le '.dol_print_date(dol_now(),'%d-%m-%Y %H:%M')
			);
	
			# Call
			$response = $this->mailjet->messageCreateCampaign($params);
	
			if ($response===false) {
				$this->error=print_r($this->mailjet->_response->errors,true);
				dol_syslog(get_class($this)."::createCampaign Error".$this->error, LOG_ERR);
				return -1;
			}else {
				# Result
				$this->mailjet_url=$response->campaign->url;
				$this->mailjet_id=$response->campaign->id;
				return 1;
			}
		} else {
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::createCampaign ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			return -1;
		}
	}

	/**
	 *	Update MailJet campaign
	 *
	 *  @param	User	$user        User that creates
	 * 	@return	int			 		<0 if KO, >0 if OK
	 */
	function updateCampaign() {
		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::updateCampaign ".$this->error, LOG_ERR);
			return -1;
		}

		$params = array(
			'method' => 'POST',
			'id' => $this->mailjet_id,
			'subject' => $this->currentmailing->sujet,
			'list_id' => $this->mailjet_contact_list_id,
			'lang' => $this->mailjet_lang,
			'from' => $this->currentmailing->email_from,
			'from_name' => $this->mailjet_sender_name,
			'footer' => 'default',
			'edition_mode' => 'html',
			'permalink'=>$this->mailjet_permalink,
			'title'=>$this->currentmailing->titre
		);

		# Call
		$response = $this->mailjet->messageUpdateCampaign($params);

		if ($response===false) {
			$this->error=print_r($this->mailjet->_response->errors,true);
			dol_syslog(get_class($this)."::updateCampaign Error".$this->error, LOG_ERR);
			return -1;
		}else {
			return 1;
		}
	}


	/**
	 *	Delete a contact list in MailJet
	 *
	 *  @param	User	$user        User that creates
	 * 	@return	int			 		<0 if KO, >0 if OK
	 */
	function deleteContactList() {

		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::deleteContactList ".$this->error, LOG_ERR);
			return -1;
		}

		# Parameters
		$params = array(
			'method' => 'POST',
			'id' => $this->mailjet_contact_list_id
		);
		# Call
		$response = $this->mailjet->listsDelete($params);
		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::deleteContactList Error".$this->error, LOG_ERR);
			return -1;
		}else {
			return 1;
		}
	}

	/**
	 *	Check if sender mail is already a validated sender
	 *
	 *  @param	string	$mail_sender        email use to send mails
	 * 	@return	int			 		<0 if KO, >0 if OK
	 */
	function checkMailSender($mail_sender='') {

		if (!empty($mail_sender) && isValidEmail($mail_sender)) {
			//Get Current MailJetInstace
			$result=$this->getInstanceMailJet();
			if ($result<0) {
				dol_syslog(get_class($this)."::checkMailSender ".$this->error, LOG_ERR);
				return -1;
			}

			# Call
			$response = $this->mailjet->userSenderlist();
			if ($response===false) {
				$this->error=print_r($this->mailjet->_response,true);
				dol_syslog(get_class($this)."::checkMailSender Error".$this->error, LOG_ERR);
				return -1;
			}else {
				$sender_found=false;
				foreach($response->senders as $obj) {
						
					if ($mail_sender==$obj->email){
						$sender_found=true;
						break;
					}
					if (preg_match('/^\*@.*/',$obj->email,$matches)) {
						foreach($matches as $key=>$val) {
							$domain_arr=explode('@',$val);
							$domain_sender_arr=explode('@',$mail_sender);
							if ($domain_arr[1]==$domain_sender_arr[1]) {
								$sender_found=true;
								break;
							}
						}

					}
				}
				return $sender_found;
			}
		}else {return false;}
	}


	/**
	 *	Update contact list with current contact
	 *
	 * 	@return	int			 		<0 if KO, >0 if OK
	 */
	function updateContactList() {

		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::updateContactList ".$this->error, LOG_ERR);
			return -1;
		}

		$contact_arr=$this->listCampaignConcatsList();
		if ($contacts<0) {
			dol_syslog(get_class($this)."::updateContactList ".$this->error, LOG_ERR);
			return $contacts;
		}
		$email_arr=array();
		foreach($contact_arr as $obj) {
			$email_arr[]= $obj->email;
		}

		$params = array(
			'method' => 'POST',
			'contacts' => implode(',',$email_arr),
			'id' => $this->mailjet_contact_list_id
		);

		# Call
		$response = $this->mailjet->listsRemoveManyContacts($params);

		if ($response===false) {
			$this->error=print_r($this->mailjet->_response->errors,true);
			dol_syslog(get_class($this)."::updateContactList Error".$this->error, LOG_ERR);
			return -1;
		}

		$result=$this->addContactList();
		if ($result<0) {
			dol_syslog(get_class($this)."::updateContactList ".$this->error, LOG_ERR);
			return $result;
		}

		return 1;

	}

	/**
	 *	Update MailJet campaign
	 *
	 *  @param	User	$user        User that creates
	 * 	@return	int			 		<0 if KO, >0 if OK
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

		//Create Mail Jet Campaign
		$result=$this->updateCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this)."::updateMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}

		//Set Body Text of campaign
		$result=$this->setBodyCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this)."::updateMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Create MailJet Full campaign (contact list/contact and campaign)
	 *
	 *  @param	User	$user        User that creates
	 * 	@return	int			 		<0 if KO, >0 if OK
	 */
	function createMailJetCampaign($user) {

		global $conf;

		$this->fk_mailing=$this->currentmailing->id;
		if (empty($this->id)) {
			$result=$this->create($user);
		}else {
			$result=$this->update($user);
		}
		if ($result < 0) {
			dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}

		//Create Contact List
		$contactlistname=$this->currentmailing->id.preg_replace('/[^a-zA-Z0-9\-]/').dol_print_date(dol_now(),'dayhourlog','',$this->currentmailing->titre);

		$result=$this->createContactList($contactlistname);
		if ($result < 0) {
			dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}else {
			$this->mailjet_contact_list_id=$result;
		}

		//Add all contact to contact list
		$result=$this->addContactList();
		if ($result < 0) {
			dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
			$result=$this->deleteContactList();
			if ($result < 0) {
				dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
				return -2;
			}
			return -1;
		}

		//Create Mail Jet Campaign
		$result=$this->createCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
			$result=$this->deleteContactList();
			if ($result < 0) {
				dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
				return -2;
			}
			return -1;
		}

		//Set Body Text of campaign
		$result=$this->setBodyCampaign();
		if ($result < 0) {
			dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
			return -1;
		}

		$result=$this->update($user);
		if ($result < 0) {
			dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
			$result=$this->deleteContactList();
			if ($result < 0) {
				dol_syslog(get_class($this)."::createMailJetCampaign ".$this->error, LOG_ERR);
				return -2;
			}
			return -1;
		}
	}


	/**
	 *	Set the HTML body of the campaign
	 *
	 * 	@return	int <0 if KO, >0 if OK
	 */
	function setBodyCampaign() {
		
		global $langs;
		$langs->load("mailjet@mailjet");
		$langs->load("mails");
		
		
		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::setBodyCampaign ".$this->error, LOG_ERR);
			return -1;
		}

		$substitutionarray=array(
			'[[UNSUB_LINK_EN]]' => '<a href="[[UNSUB_LINK_EN]]" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
			'[[UNSUB_LINK_FR]]' => '<a href="[[UNSUB_LINK_FR]]" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
			'[[UNSUB_LINK_DE]]' => '<a href="[[UNSUB_LINK_DE]]" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
			'[[UNSUB_LINK_ES]]' => '<a href="[[UNSUB_LINK_ES]]" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
			'[[UNSUB_LINK_NL]]' => '<a href="[[UNSUB_LINK_NL]]" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
			'[[UNSUB_LINK_IT]]' => '<a href="[[UNSUB_LINK_IT]]" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
			'[[SHARE_FACEBOOK]]' => '<a href="[[SHARE_FACEBOOK]]" target="_blank">'.$langs->trans("MailJetSocialNetworkLink").'</a>',
			'[[SHARE_TWITTER]]' => '<a href="[[SHARE_TWITTER]]" target="_blank">'.$langs->trans("MailJetSocialNetworkLink").'</a>',
			'[[SHARE_GOOGLE]]' => '<a href="[[SHARE_GOOGLE]]" target="_blank">'.$langs->trans("MailJetSocialNetworkLink").'</a>',
			'[[SHARE_LINKEDIN]]' => '<a href="[[SHARE_LINKEDIN]]" target="_blank">'.$langs->trans("MailJetSocialNetworkLink").'</a>'
		);
		$body=make_substitutions($this->currentmailing->body,$substitutionarray);
		
		# Parameters
		$params = array(
			'method' => 'POST',
			'id' => $this->mailjet_id,
			'html' => $body,
			'text' => $this->currentmailing->body
		);
		# Call
		$response = $this->mailjet->messageSetHtmlCampaign($params);
		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::setBodyCampaign Error".$this->error, LOG_ERR);
			return -1;
		}else {
			return 1;
		}
	}

	/**
	 *	MailJet Contact List
	 *
	 *	@param $typeresult   string		'string','array'
	 * 	@return	mixed			 		array of contact email, or string with email only
	 */
	function listCampaignConcatsList($typeresult='array') {
		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::listCampaignConcatsList ".$this->error, LOG_ERR);
			return -1;
		}

		$params = array(
			'id' => $this->mailjet_contact_list_id
		);

		# Call
		$response = $this->mailjet->listsContacts($params);
		if (!$response) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::listCampaignConcatsList ".$this->error, LOG_ERR);
			return -1;
		}else {
			if ($typeresult=='array') {
				return $response->result;
			} else {
				$str_return='';

				if ($response->total_cnt>0){
					$i=1;
					foreach($response->result as $obj) {
						$str_return .= $obj->email;
						if ($i!=$response->total_cnt) {
							$str_return .=', ';
						}
						if (($i % 7)==0) {
							$str_return .= '<BR>';
						}
						$i++;
					}
				}
				return $str_return;
			}
		}
	}
	
	/**
	 *	MailJet Contact List with status
	 *
	 *	@param $typeresult   string		'string','array'
	 * 	@return	mixed			 		array of contact email, or string with email only
	 */
	function listCampaignConcatsListStatus($typeresult='array') {
		//Get Current MailJetInstace
		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::listCampaignConcatsListStatus error:".$this->error, LOG_ERR);
			return -1;
		}
	
		$params = array(
			'id' => $this->mailjet_stat_id
		);
	
		# Call
		$response = $this->mailjet->messageContacts($params);
		if (!$response) {
			$this->error=print_r($this->mailjet->_response->error,true);
			dol_syslog(get_class($this)."::listCampaignConcatsListStatus $this->mailjet_id error1:".$this->error, LOG_ERR);
			return -1;
		}else {
			if ($typeresult=='array') {
				return $response->result;
			} else {
				$str_return='';
	
				if ($response->total_cnt>0){
					foreach($response->result as $obj) {
						$str_return .= $obj->email.' '. DolMailjet::getLibContactStatus($obj->status).'<BR>';
					}
				}
				return $str_return;
			}
		}
	}
	
	/**
	 *	get MailJet campaign status
	 *
	 *  @param string 	$status    Status to convert
	 *  @param int	 	$mode    	1 with picto, 0 only text
	 * 	@return	String			Campagin status
	 */
	static function getLibContactStatus($status,$mode=1) {
		global $langs;
	
		$langs->load("mailjet@mailjet");
	
		if ($mode==0) {
			return $langs->trans('MailJetContactStatus'.$status);
		}
		
		if ($mode==1) {
			if ($status=='queued') {
				return img_picto($langs->trans('MailJetContactStatus'.$status),'stcomm0_grayed').' '.$langs->trans('MailJetContactStatus'.$status);
			}
			if ($status=='sent') {
				return img_picto($langs->trans('MailJetContactStatus'.$status),'stcomm2').' '.$langs->trans('MailJetContactStatus'.$status);
			}
			if ($status=='opened') {
				return img_picto($langs->trans('MailJetContactStatus'.$status),'stcomm3').' '.$langs->trans('MailJetContactStatus'.$status);
			}
			if ($status=='clicked') {
				return img_picto($langs->trans('MailJetContactStatus'.$status),'stcomm4').' '.$langs->trans('MailJetContactStatus'.$status);
			}
			if ($status=='bounce') {
				return img_picto($langs->trans('MailJetContactStatus'.$status),'warning').' '.$langs->trans('MailJetContactStatus'.$status);
			}
			if ($status=='spam') {
				return img_picto($langs->trans('MailJetContactStatus'.$status),'stcomm-1').' '.$langs->trans('MailJetContactStatus'.$status);
			}
			if ($status=='unsub') {
				return img_picto($langs->trans('MailJetContactStatus'.$status),'stcomm4_grayed').' '.$langs->trans('MailJetContactStatus'.$status);
			}
		}
	}

	/**
	 *	get MailJet campaign statistics
	 *
	 * 	@return	Array			Campaign statistics
	 */
	function getCampaignStatistics() {

		$result=$this->getInstanceMailJet();
		if ($result<0) {
			dol_syslog(get_class($this)."::getCampaignStatistics ".$this->error, LOG_ERR);
			return -1;
		}

		$params = array(
			'campaign_id' => $this->mailjet_id
		);

		# Call
		$response = $this->mailjet->reportEmailStatistics();

		if ($response===false) {
			$this->error=print_r($this->mailjet->_response,true);
			dol_syslog(get_class($this)."::getCampaignStatistics ".$this->error, LOG_ERR);
			return -1;
		}else {
			$stats = $response->stats;

			return $stats;
		}
	}


}