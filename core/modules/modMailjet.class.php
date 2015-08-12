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

		$this->numero = 103087;

		$this->rights_class = 'mailjet';

		$this->family = "other";

		$this->name = preg_replace('/^mod/i', '', get_class($this));

		$this->description = "Mailjet Connector";

		$this->version = '1.16';

		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);

		$this->special = 1;

		$this->picto = 'mailjet@mailjet'; // mypicto@mymodule

		$this->module_parts = array('hooks' => array('mailingcard'));

		$this->dirs = array();

		$this->config_page_url = array("admin_mailjet.php@mailjet");

		$this->depends = array("modMailing");

		$this->requiredby = array();

		$this->phpmin = array(5, 3);

		$this->need_dolibarr_version = array(3, 5);
		
		$this->langfiles = array("mailjet@mailjet"); // langfiles@mymodule

		$this->const = array(
				0=>array(
					'MAILJET_MAIL_SENDMODE_STD',
					'chaine',
					'',
					'Normal Send mode',
					0,
					'current',
					0
				),
				1=>array(
					'MAILJET_SMTP_PORT_STD',
					'chaine',
					'',
					'Normal SMTP port',
					0,
					'current',
					0			
				),
				2=>array(
					'MAILJET_MAIL_SMTP_SERVER_STD',
					'chaine',
					'',
					'Normal SMTP server',
					0,
					'current',
					0
				),
				3=>array(
					'MAILJET_MAIL_SMTPS_ID_STD',
					'chaine',
					'',
					'Normal SMTP identification credential id',
					0,
					'current',
					0
				),
				4=>array(
					'MAILJET_MAIL_SMTPS_PW_STD',
					'chaine',
					'',
					'Normal SMTP identification credential  password',
					0,
					'current',
					0
				),
				5=>array(
					'MAILJET_MAIL_EMAIL_TLS_STD',
					'chaine',
					'',
					'Normal SMTP server use of TSL(SSL)',
					0,
					'current',
					0
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
					'in-v3.mailjet.com',
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
				),
				13=>array(
					'MAILJET_MAIL_EMAIL_FROM',
					'chaine',
					'',
					'MailJet default senders',
					0,
					'current',
					0
				),
				14=>array(
					'MAILJET_MAIL_EMAIL_FROM_STD',
					'chaine',
					'',
					'Normal default mail sender',
					0,
					'current',
					0
				),
				15=>array(
					'MAILJET_ACTIVE_MAILING_ONLY',
					'chaine',
					'0',
					'Module is active only for mailing',
					0,
					'current',
					1
				),
				16=>array(
						'MAILJET_API_VERSION',
						'chaine',
						'3',
						'API version',
						0,
						'current',
						1
				)
		);

		// Array to add new pages in new tabs
		$this->tabs = array(
				'emailing:+tabMailJetSending:MailJetSending:mailjet@mailjet:$user->rights->mailing->creer:/mailjet/mailjet/mailjet.php?id=__ID__'
		);

		if (! isset($conf->mailjet->enabled)) {
			$conf->mailjet = (object) array();
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
		
		$this->rights[$r][0] = 1234511;
		$this->rights[$r][1] = 'read';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';
		$r++;
		
		$this->rights[$r][0] = 1234512;
		$this->rights[$r][1] = 'write';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'write';
		$r++;

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
		global $conf;
		
		$sql = array(
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SENDMODE.'\' WHERE name=\'MAILJET_MAIL_SENDMODE_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTP_PORT.'\' WHERE name=\'MAILJET_SMTP_PORT_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTP_SERVER.'\' WHERE name=\'MAILJET_MAIL_SMTP_SERVER_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTPS_ID.'\' WHERE name=\'MAILJET_MAIL_SMTPS_ID_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTPS_ID.'\' WHERE name=\'MAILJET_MAIL_SMTPS_ID_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_SMTPS_PW.'\' WHERE name=\'MAILJET_MAIL_SMTPS_PW_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_EMAIL_TLS.'\' WHERE name=\'MAILJET_MAIL_EMAIL_TLS_STD\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAIN_MAIL_EMAIL_FROM.'\' WHERE name=\'MAILJET_MAIL_EMAIL_FROM_STD\''
		);

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
		global $conf;
		
		$sql = array(
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_MAIL_SENDMODE_STD.'\' WHERE name=\'MAIN_MAIL_SENDMODE\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_SMTP_PORT_STD.'\' WHERE name=\'MAIN_MAIL_SMTP_PORT\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_MAIL_SMTP_SERVER_STD.'\' WHERE name=\'MAIN_MAIL_SMTP_SERVER\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_MAIL_SMTPS_ID_STD.'\' WHERE name=\'MAIN_MAIL_SMTPS_ID\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_MAIL_SMTPS_ID_STD.'\' WHERE name=\'MAIN_MAIL_SMTPS_ID\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_MAIL_SMTPS_PW_STD.'\' WHERE name=\'MAIN_MAIL_SMTPS_PW\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_MAIL_EMAIL_TLS_STD.'\' WHERE name=\'MAIN_MAIL_EMAIL_TLS\'',
			'UPDATE '.MAIN_DB_PREFIX.'const SET value=\''.$conf->global->MAILJET_MAIL_EMAIL_FROM_STD.'\' WHERE name=\'MAIN_MAIL_EMAIL_FROM\''
		);

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
