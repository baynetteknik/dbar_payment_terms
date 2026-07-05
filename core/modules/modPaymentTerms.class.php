<?php
/* Copyright (C) 2026	Baynet Bilişim	<your@email.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/custom/paymentterms/core/modules/modPaymentTerms.class.php
 * \brief      Description and activation file for module PaymentTerms.
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modPaymentTerms extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->numero = 500000;
		$this->rights_class = 'paymentterms';

		$this->family = "financial";
		$this->module_position = '51';

		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Advanced Payment Terms & Installment Calculator";
		$this->descriptionlong = "Configure complex multi-installment payment templates using formulas, maturity calculations, and auto-collection of down-payments";

		$this->editor_name = 'Baynet Bilişim';
		$this->editor_url = '';
		$this->editor_squarred_logo = '';

		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'generic';

		$this->module_parts = array(
			'triggers' => 1,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 1,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(
				'/custom/paymentterms/css/paymentterms.css.php',
			),
			'js' => array(
				'/custom/paymentterms/js/paymentterms.js',
			),
			'hooks' => array(
				'invoicecard',
				'paymentsuppliercard',
				'thirdpartycard',
			),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0
		);

		$this->dirs = array("/paymentterms/temp");

		$this->config_page_url = array(dol_buildpath('/custom/paymentterms/admin/paymentterms_setup.php', 1));

		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();

		$this->langfiles = array("paymentterms@paymentterms");

		$this->phpmin = array(7, 4);
		$this->need_dolibarr_version = array(18, 0);
		$this->need_javascript_ajax = 1;

		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		$this->const = array();

		if (!isModEnabled("paymentterms")) {
			$conf->paymentterms = new stdClass();
			$conf->paymentterms->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();

		$this->rights = array();
		$r = 0;

		$r++;
		$this->rights[$r][0] = 500001;
		$this->rights[$r][1] = 'Read payment plans';
		$this->rights[$r][4] = 'plan';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 500002;
		$this->rights[$r][1] = 'Create/modify payment plans';
		$this->rights[$r][4] = 'plan';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 500003;
		$this->rights[$r][1] = 'Delete payment plans';
		$this->rights[$r][4] = 'plan';
		$this->rights[$r][5] = 'delete';

		$r++;
		$this->rights[$r][0] = 500004;
		$this->rights[$r][1] = 'Read payment schedule';
		$this->rights[$r][4] = 'schedule';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 500005;
		$this->rights[$r][1] = 'Manage payment schedule';
		$this->rights[$r][4] = 'schedule';
		$this->rights[$r][5] = 'write';

		$this->menu = array(
			array(
				'fk_menu' => 'fk_mainmenu=billing',
				'type' => 'left',
				'titre' => 'PaymentTerms',
				'mainmenu' => 'billing',
				'leftmenu' => 'paymentterms',
				'url' => '/custom/paymentterms/paymenttermplan_list.php',
				'langs' => 'paymentterms@paymentterms',
				'position' => 300,
				'enabled' => '$conf->paymentterms->enabled',
				'perms' => '$user->rights->paymentterms->plan->read',
				'user' => 2,
			),
			array(
				'fk_menu' => 'fk_mainmenu=billing,fk_leftmenu=paymentterms',
				'type' => 'left',
				'titre' => 'PaymentTermPlans',
				'mainmenu' => 'billing',
				'leftmenu' => 'paymentterms_plan',
				'url' => '/custom/paymentterms/paymenttermplan_list.php',
				'langs' => 'paymentterms@paymentterms',
				'position' => 301,
				'enabled' => '$conf->paymentterms->enabled',
				'perms' => '$user->rights->paymentterms->plan->read',
				'user' => 2,
			),
			array(
				'fk_menu' => 'fk_mainmenu=billing,fk_leftmenu=paymentterms',
				'type' => 'left',
				'titre' => 'PaymentSchedule',
				'mainmenu' => 'billing',
				'leftmenu' => 'paymentterms_schedule',
				'url' => '/custom/paymentterms/schedule_list.php',
				'langs' => 'paymentterms@paymentterms',
				'position' => 302,
				'enabled' => '$conf->paymentterms->enabled',
				'perms' => '$user->rights->paymentterms->schedule->read',
				'user' => 2,
			)
		);
	}

	/**
	 * Function called when module is enabled.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$logFile = dol_buildpath('/custom/paymentterms/activation.log', 0);
		$logData = date('Y-m-d H:i:s') . " - Init started\n";

		// Load DDL sql schemas
		$logData .= date('Y-m-d H:i:s') . " - Loading SQL tables...\n";
		$result = $this->_load_tables('/paymentterms/sql/');
		$logData .= date('Y-m-d H:i:s') . " - SQL table load result: " . var_export($result, true) . "\n";
		if ($result < 0) {
			$logData .= date('Y-m-d H:i:s') . " - SQL load failed. Error: " . $this->error . "\n";
			file_put_contents($logFile, $logData, FILE_APPEND);
			return -1;
		}

		// Register Extrafields
		$logData .= date('Y-m-d H:i:s') . " - Registering extrafields...\n";
		try {
			include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);

			// Add payment plan selection extrafield to proposals, orders and invoices
			$param_query = 'llx_paymentterm_plan:label:rowid:is_active=1';
			
			$result1 = $extrafields->addExtraField(
				'payment_plan_id', "PaymentPlan", 'select', 10, '',
				'facture', 0, 0, $param_query, '', 1, '', 1, 0, '', '',
				'paymentterms@paymentterms', 'isModEnabled("paymentterms")'
			);
			$logData .= date('Y-m-d H:i:s') . " - Extrafield facture result: " . var_export($result1, true) . "\n";

			$result2 = $extrafields->addExtraField(
				'payment_plan_id', "PaymentPlan", 'select', 10, '',
				'commande', 0, 0, $param_query, '', 1, '', 1, 0, '', '',
				'paymentterms@paymentterms', 'isModEnabled("paymentterms")'
			);
			$logData .= date('Y-m-d H:i:s') . " - Extrafield commande result: " . var_export($result2, true) . "\n";

			$result3 = $extrafields->addExtraField(
				'payment_plan_id', "PaymentPlan", 'select', 10, '',
				'propal', 0, 0, $param_query, '', 1, '', 1, 0, '', '',
				'paymentterms@paymentterms', 'isModEnabled("paymentterms")'
			);
			$logData .= date('Y-m-d H:i:s') . " - Extrafield propal result: " . var_export($result3, true) . "\n";
		} catch (Exception $e) {
			$logData .= date('Y-m-d H:i:s') . " - Extrafield registry threw exception: " . $e->getMessage() . "\n";
			file_put_contents($logFile, $logData, FILE_APPEND);
			return -1;
		}

		$logData .= date('Y-m-d H:i:s') . " - Calling parent _init()...\n";
		$sql = array();
		$init_res = $this->_init($sql, $options);
		$logData .= date('Y-m-d H:i:s') . " - Parent _init() returned: " . var_export($init_res, true) . "\n";
		$logData .= date('Y-m-d H:i:s') . " - Init completed.\n";
		file_put_contents($logFile, $logData, FILE_APPEND);

		return $init_res;
	}

	/**
	 * Function called when module is disabled.
	 *
	 * @param string $options Options when disabling module
	 * @return int 1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		global $conf, $langs;

		// We keep tables by default but we can remove extrafields
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		$extrafields->deleteExtraField('payment_plan_id', 'facture');
		$extrafields->deleteExtraField('payment_plan_id', 'commande');
		$extrafields->deleteExtraField('payment_plan_id', 'propal');

		return 1;
	}
}
