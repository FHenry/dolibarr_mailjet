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
 * \file /mailjet/mailjet/dolmailjet.class.php
 * \ingroup mailjet
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Put here description of your class
 */
abstract class AbstractDolMailjet extends CommonObject {
	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		if (isset($this->fk_mailing))
			$this->fk_mailing = trim($this->fk_mailing);
		if (isset($this->mailjet_id))
			$this->mailjet_id = trim($this->mailjet_id);
		if (isset($this->mailjet_stat_id))
			$this->mailjet_stat_id = trim($this->mailjet_stat_id);
		if (isset($this->mailjet_url))
			$this->mailjet_url = trim($this->mailjet_url);
		if (isset($this->mailjet_uri))
			$this->mailjet_uri = trim($this->mailjet_uri);
		if (isset($this->mailjet_contact_list_id))
			$this->mailjet_contact_list_id = trim($this->mailjet_contact_list_id);
		if (isset($this->mailjet_sender_name))
			$this->mailjet_sender_name = trim($this->mailjet_sender_name);
		if (isset($this->mailjet_permalink))
			$this->mailjet_permalink = trim($this->mailjet_permalink);
		if (isset($this->mailjet_lang))
			$this->mailjet_lang = trim($this->mailjet_lang);
			
			// Check parameters
			// Put here code to add control on parameters values
			
		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "mailjet(";
		
		$sql .= "entity,";
		$sql .= "fk_mailing,";
		$sql .= "mailjet_id,";
		$sql .= "mailjet_stat_id,";
		$sql .= "mailjet_url,";
		$sql .= "mailjet_uri,";
		$sql .= "mailjet_contact_list_id,";
		$sql .= "mailjet_lang,";
		$sql .= "mailjet_sender_name,";
		$sql .= "mailjet_permalink,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod";
		
		$sql .= ") VALUES (";
		
		$sql .= " " . $conf->entity . ",";
		$sql .= " " . (! isset($this->fk_mailing) ? 'NULL' : "'" . $this->fk_mailing . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_id) ? 'NULL' : "'" . $this->mailjet_id . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_stat_id) ? 'NULL' : "'" . $this->mailjet_stat_id . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_url) ? 'NULL' : "'" . $this->db->escape($this->mailjet_url) . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_uri) ? 'NULL' : "'" . $this->db->escape($this->mailjet_uri) . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_contact_list_id) ? 'NULL' : "'" . $this->mailjet_contact_list_id . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_lang) ? 'NULL' : "'" . $this->db->escape($this->mailjet_lang) . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_sender_name) ? 'NULL' : "'" . $this->db->escape($this->mailjet_sender_name) . "'") . ",";
		$sql .= " " . (! isset($this->mailjet_permalink) ? 'NULL' : "'" . $this->db->escape($this->mailjet_permalink) . "'") . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . $this->db->idate(dol_now()) . ",";
		$sql .= " " . $user->id;
		
		$sql .= ")";
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "mailjet");
			
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		
		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.mailjet_id,";
		$sql .= " t.mailjet_stat_id,";
		$sql .= " t.mailjet_url,";
		$sql .= " t.mailjet_uri,";
		$sql .= " t.mailjet_lang,";
		$sql .= " t.mailjet_contact_list_id,";
		$sql .= " t.mailjet_sender_name,";
		$sql .= " t.mailjet_permalink,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "mailjet as t";
		$sql .= " WHERE t.rowid = " . $id;
		
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				
				$this->id = $obj->rowid;
				
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
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param int $id of mailing
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_by_mailing($id) {
		global $langs;
		
		$sql = "SELECT";
		$sql .= " t.rowid,";
		
		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.mailjet_id,";
		$sql .= " t.mailjet_stat_id,";
		$sql .= " t.mailjet_url,";
		$sql .= " t.mailjet_uri,";
		$sql .= " t.mailjet_lang,";
		$sql .= " t.mailjet_contact_list_id,";
		$sql .= " t.mailjet_sender_name,";
		$sql .= " t.mailjet_permalink,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "mailjet as t";
		$sql .= " WHERE t.fk_mailing = " . $id;
		
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				
				$this->id = $obj->rowid;
				
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
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user = 0, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		if (isset($this->fk_mailing))
			$this->fk_mailing = trim($this->fk_mailing);
		if (isset($this->mailjet_id))
			$this->mailjet_id = trim($this->mailjet_id);
		if (isset($this->mailjet_stat_id))
			$this->mailjet_stat_id = trim($this->mailjet_stat_id);
		if (isset($this->mailjet_url))
			$this->mailjet_url = trim($this->mailjet_url);
		if (isset($this->mailjet_uri))
			$this->mailjet_uri = trim($this->mailjet_uri);
		if (isset($this->mailjet_contact_list_id))
			$this->mailjet_contact_list_id = trim($this->mailjet_contact_list_id);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);
		if (isset($this->mailjet_lang))
			$this->mailjet_lang = trim($this->mailjet_lang);
		if (isset($this->mailjet_sender_name))
			$this->mailjet_sender_name = trim($this->mailjet_sender_name);
		if (isset($this->mailjet_permalink))
			$this->mailjet_permalink = trim($this->mailjet_permalink);
			
			// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "mailjet SET";
		
		$sql .= " entity=" . $conf->entity . ",";
		$sql .= " fk_mailing=" . (isset($this->fk_mailing) ? $this->fk_mailing : "null") . ",";
		$sql .= " mailjet_id=" . (isset($this->mailjet_id) ? $this->mailjet_id : "null") . ",";
		$sql .= " mailjet_stat_id=" . (isset($this->mailjet_stat_id) ? $this->mailjet_stat_id : "null") . ",";
		$sql .= " mailjet_url=" . (isset($this->mailjet_url) ? "'" . $this->db->escape($this->mailjet_url) . "'" : "null") . ",";
		$sql .= " mailjet_uri=" . (isset($this->mailjet_uri) ? "'" . $this->db->escape($this->mailjet_uri) . "'" : "null") . ",";
		$sql .= " mailjet_contact_list_id=" . (isset($this->mailjet_contact_list_id) ? $this->mailjet_contact_list_id : "null") . ",";
		$sql .= " mailjet_lang=" . (isset($this->mailjet_lang) ? "'" . $this->db->escape($this->mailjet_lang) . "'" : "null") . ",";
		$sql .= " mailjet_sender_name=" . (isset($this->mailjet_sender_name) ? "'" . $this->db->escape($this->mailjet_sender_name) . "'" : "null") . ",";
		$sql .= " mailjet_permalink=" . (isset($this->mailjet_permalink) ? "'" . $this->db->escape($this->mailjet_permalink) . "'" : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id;
		
		$sql .= " WHERE rowid=" . $this->id;
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Delete object in database
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		$this->db->begin();
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mailjet";
			$sql .= " WHERE rowid=" . $this->id;
			
			dol_syslog(get_class($this) . "::delete sql=" . $sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	function initAsSpecimen() {
		$this->id = 0;
		
		$this->entity = $conf->entity;
		$this->fk_mailing = '';
		$this->mailjet_id = '';
		$this->mailjet_stat_id = '';
		$this->mailjet_url = '';
		$this->mailjet_uri = '';
		$this->mailjet_lang = '';
		$this->mailjet_contact_list_id = '';
		$this->fk_user_author = '';
		$this->mailjet_permalink = '';
		$this->mailjet_sender_name = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}
}