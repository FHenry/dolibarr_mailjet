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
 * 	\defgroup	mymodule	MyModule module
 * 	\brief		MyModule module descriptor.
 * 	\file		core/modules/modMyModule.class.php
* 	\ingroup	mailjet
 * 	\brief		Description and activation file for module MyModule
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module MyModule
 */
class modMailjet extends DolibarrModules
{

	/**
	 * 	Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * 	@param	DoliDB		$db	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		$this->numero = 123451;

		$this->rights_class = 'mailjet';

		$this->family = "other";

		$this->name = preg_replace('/^mod/i', '', get_class($this));

		$this->description = "Mailjet Connector";

		$this->version = '1.0';

		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		$this->special = 1;

		$this->picto = 'mailjet@mailjet'; // mypicto@mymodule

		$this->module_parts = array('hooks' => array('mailingcard'));

		$this->dirs = array();

		$this->config_page_url = array("admin_mailjet.php@mailjet");

		$this->depends = array("mailing");

		$this->requiredby = array();

		$this->phpmin = array(5, 3);

		$this->need_dolibarr_version = array(3, 3);
		$this->langfiles = array("mailjet@mailjet"); // langfiles@mymodule

		$this->const = array(
				0=>array(
					'MAILJET_MAIL_SENDMODE_STD',
					'chaine',
					'',
					'Normal Send mode',
					0,
					'current',
					1
				),
				1=>array(
					'MAILJET_SMTP_PORT_STD',
					'chaine',
					'',
					'Normal SMTP port',
					0,
					'current',
					1			
				),
				2=>array(
					'MAILJET_MAIL_SMTP_SERVER_STD',
					'chaine',
					'',
					'Normal SMTP server',
					0,
					'current',
					1
				),
				3=>array(
					'MAILJET_MAIL_SMTPS_ID_STD',
					'chaine',
					'',
					'Normal SMTP identification credential id',
					0,
					'current',
					1
				),
				4=>array(
					'MAILJET_MAIL_SMTPS_PW_STD',
					'chaine',
					'',
					'Normal SMTP identification credential  password',
					0,
					'current',
					1
				),
				5=>array(
					'MAILJET_MAIL_EMAIL_TLS_STD',
					'chaine',
					'',
					'Normal SMTP server use of TSL(SSL)',
					0,
					'current',
					1
				),	
				6=>array(
					'MAILJET_MAIL_SENDMODE',
					'chaine',
					'smtps',
					'MailJet Send mode',
					0,
					'current',
					1
				),
				7=>array(
					'MAILJET_SMTP_PORT',
					'chaine',
					'465',
					'MailJet SMTP port',
					0,
					'current',
					1
				),
				8=>array(
					'MAILJET_MAIL_SMTP_SERVER',
					'chaine',
					'in.mailjet.com',
					'MailJet SMTP server',
					0,
					'current',
					1
				),
				9=>array(
					'MAILJET_MAIL_SMTPS_ID',
					'chaine',
					'',
					'MailJet SMTP identification credential id',
					0,
					'current',
					1
				),
				10=>array(
					'MAILJET_MAIL_SMTPS_PW',
					'chaine',
					'',
					'MailJet SMTP identification credential  password',
					0,
					'current',
					1
				),
				11=>array(
					'MAILJET_MAIL_EMAIL_TLS',
					'chaine',
					'1',
					'Normal SMTP server use of TSL(SSL)',
					0,
					'current',
					1
				),
				12=>array(
					'MAILJET_ACTIVE',
					'chaine',
					'0',
					'Module is active',
					0,
					'current',
					1
				)
		);

		// Array to add new pages in new tabs
		// Example:
		$this->tabs = array(
				'emailing:+tabMailJetSending:MailJetSending:mailjet@mailjet:$user->rights->mailing->creer:/mailjet/mailjet/mailjet.php?id=__ID__'
		);

		if (! isset($conf->mailjet->enabled)) {
			$conf->mailjet->enabled = 0;
		}
		$this->dictionnaries = array();

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // Boxes list
		$r = 0;

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		// Main menu entries
		$this->menus = array(); // List of menus to add
		$r = 0;
		
	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array();

		$result = $this->loadTables();

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /mymodule/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/mailjet/sql/');
	}
}
