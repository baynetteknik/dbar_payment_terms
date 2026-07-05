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
 * \file       htdocs/custom/paymentterms/core/triggers/interface_99_modPaymentTerms_PaymentTermsTriggers.class.php
 * \brief      Trigger handlers for paymentterms module.
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once __DIR__.'/../../class/formula/FormulaLexer.class.php';
require_once __DIR__.'/../../class/formula/FormulaParser.class.php';
require_once __DIR__.'/../../class/formula/FormulaEvaluator.class.php';
require_once __DIR__.'/../../class/date/DateCalculator.class.php';

class InterfacePaymentTermsTriggers extends DolibarrTriggers
{
	public function __construct($db)
	{
		$this->db = $db;
		$this->name = 'PaymentTermsTriggers';
		$this->family = 'financial';
		$this->description = 'Handles automated payment schedule generation on validation';
		$this->version = '1.0';
		$this->picto = 'generic';
	}

	public function runTrigger($action, $object, $user, $langs, $conf)
	{
		if (!isModEnabled('paymentterms')) {
			return 0;
		}

		$socid = 0;
		$object_type = '';
		$plan_id = 0;

		if ($action === 'BILL_VALIDATE' && is_object($object) && $object->element === 'facture') {
			$socid = $object->socid;
			$object_type = 'invoice';
			$plan_id = (int) ($object->array_options['options_payment_plan_id'] ?? 0);
		} elseif ($action === 'ORDER_VALIDATE' && is_object($object) && $object->element === 'commande') {
			$socid = $object->socid;
			$object_type = 'order';
			$plan_id = (int) ($object->array_options['options_payment_plan_id'] ?? 0);
		} elseif ($action === 'PROPAL_VALIDATE' && is_object($object) && $object->element === 'propal') {
			$socid = $object->socid;
			$object_type = 'proposal';
			$plan_id = (int) ($object->array_options['options_payment_plan_id'] ?? 0);
		}

		if (empty($plan_id) || empty($object_type)) {
			return 0;
		}

		$this->db->begin();

		try {
			// Fetch plan details
			$sqlPlan = "SELECT calculation_method, date_calculation_base, is_early_discount_enabled, early_discount_formula, early_discount_days, is_active FROM " . MAIN_DB_PREFIX . "paymentterm_plan";
			$sqlPlan .= " WHERE rowid = " . ((int) $plan_id) . " AND entity = " . ((int) $object->entity);
			$resPlan = $this->db->query($sqlPlan);
			if (!$resPlan || !$this->db->num_rows($resPlan)) {
				return 0; // Plan not found or inactive
			}
			$plan = $this->db->fetch_object($resPlan);
			if (empty($plan->is_active)) {
				return 0;
			}

			// Delete existing schedules for this object to prevent duplicates
			$sqlDelete = "DELETE FROM " . MAIN_DB_PREFIX . "paymentterm_schedule WHERE object_type = '" . $this->db->escape($object_type) . "' AND fk_object = " . ((int) $object->id);
			$this->db->query($sqlDelete);

			// Fetch plan lines
			$sqlLines = "SELECT line_num, amount_formula, date_formula, is_balance_line FROM " . MAIN_DB_PREFIX . "paymentterm_line";
			$sqlLines .= " WHERE fk_paymentterm_plan = " . ((int) $plan_id);
			$sqlLines .= " ORDER BY line_num ASC";
			$resLines = $this->db->query($sqlLines);
			if (!$resLines || !$this->db->num_rows($resLines)) {
				$this->db->commit();
				return 0;
			}

			$lines = array();
			while ($line = $this->db->fetch_object($resLines)) {
				$lines[] = $line;
			}

			$total_ttc = (double) $object->total_ttc;
			$total_ht = (double) $object->total_ht;
			$accumulated_ttc = 0.0;
			$accumulated_ht = 0.0;
			$numLines = count($lines);

			// Determine base date
			$baseDateStr = date('Y-m-d');
			if (!empty($object->date)) {
				$baseDateStr = dol_print_date($object->date, '%Y-%m-%d');
			}

			foreach ($lines as $index => $line) {
				$isLast = ($index === $numLines - 1);
				$line_amount_ttc = 0.0;

				if ($line->is_balance_line || $isLast) {
					$line_amount_ttc = $total_ttc - $accumulated_ttc;
				} else {
					$lexer = new FormulaLexer($line->amount_formula);
					$tokens = $lexer->tokenize();
					$parser = new FormulaParser($tokens);
					$ast = $parser->parse();
					$line_amount_ttc = FormulaEvaluator::evaluate($ast, $total_ttc);
				}

				// Enforce bounds
				$line_amount_ttc = max(0.0, round($line_amount_ttc, 2));
				
				// Calculate HT proportionally
				$line_amount_ht = 0.0;
				if ($total_ttc > 0) {
					$line_amount_ht = round($line_amount_ttc * ($total_ht / $total_ttc), 2);
				}

				if ($isLast) {
					$line_amount_ht = $total_ht - $accumulated_ht;
				}

				$accumulated_ttc += $line_amount_ttc;
				$accumulated_ht += $line_amount_ht;

				// Calculate due date
				$dueDate = DateCalculator::calculate($line->date_formula, $baseDateStr);

				// Early discount calculation
				$earlyDiscountAmount = 0.0;
				$earlyDiscountDeadline = 'NULL';
				if (!empty($plan->is_early_discount_enabled) && !empty($plan->early_discount_formula) && !empty($plan->early_discount_days)) {
					$lexer = new FormulaLexer($plan->early_discount_formula);
					$tokens = $lexer->tokenize();
					$parser = new FormulaParser($tokens);
					$ast = $parser->parse();
					$earlyDiscountAmount = FormulaEvaluator::evaluate($ast, $line_amount_ttc);
					$earlyDiscountAmount = max(0.0, round($earlyDiscountAmount, 2));

					$deadlineDate = new DateTime($dueDate);
					$days = (int) $plan->early_discount_days;
					$deadlineDate->modify("-$days days");
					$earlyDiscountDeadline = "'" . $this->db->escape($deadlineDate->format('Y-m-d')) . "'";
				}

				// Insert schedule line
				$sqlInsert = "INSERT INTO " . MAIN_DB_PREFIX . "paymentterm_schedule ";
				$sqlInsert .= "(fk_paymentterm_plan, fk_object, object_type, line_num, amount, amount_ttc, due_date, early_discount_amount, early_discount_deadline, status, paid_amount, entity, datec) ";
				$sqlInsert .= "VALUES (";
				$sqlInsert .= ((int) $plan_id) . ", ";
				$sqlInsert .= ((int) $object->id) . ", ";
				$sqlInsert .= "'" . $this->db->escape($object_type) . "', ";
				$sqlInsert .= ((int) $line->line_num) . ", ";
				$sqlInsert .= ((double) $line_amount_ht) . ", ";
				$sqlInsert .= ((double) $line_amount_ttc) . ", ";
				$sqlInsert .= "'" . $this->db->escape($dueDate) . "', ";
				$sqlInsert .= ((double) $earlyDiscountAmount) . ", ";
				$sqlInsert .= $earlyDiscountDeadline . ", ";
				$sqlInsert .= "'PENDING', ";
				$sqlInsert .= "0, ";
				$sqlInsert .= ((int) $object->entity) . ", ";
				$sqlInsert .= "'" . $this->db->idate(dol_now()) . "'";
				$sqlInsert .= ")";

				$this->db->query($sqlInsert);
			}

			$this->db->commit();
		} catch (Exception $e) {
			$this->db->rollback();
			$langs->load('paymentterms@paymentterms');
			$object->error = $langs->trans("ErrorFailedToGeneratePaymentSchedule") . ": " . $e->getMessage();
			return -1;
		}

		return 1;
	}
}
