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
 * \file       htdocs/custom/paymentterms/core/hooks/paymentterms_hooks.class.php
 * \brief      Hook actions for paymentterms module in user interface.
 */

class PaymentTermsHooks
{
	/**
	 * Overwrite hook options to show schedule details on invoice card.
	 *
	 * @param array             $parameters  Hook parameters
	 * @param CommonObject      $object      Active object (Facture)
	 * @param string            $action      Action (view, edit, etc.)
	 * @param HookManager       $hookmanager Hook manager
	 * @return int                           0 if OK
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $conf;

		// We only want to output on the invoice card view page
		if (empty($parameters['context']) || !in_array('invoicecard', explode(':', $parameters['context']))) {
			return 0;
		}

		if ($action !== 'view' && !empty($action)) {
			return 0;
		}

		$invoice_id = $object->id;
		if (empty($invoice_id)) {
			return 0;
		}

		// Query schedules
		$sql = "SELECT line_num, amount_ttc, due_date, status, paid_amount FROM " . MAIN_DB_PREFIX . "paymentterm_schedule";
		$sql .= " WHERE fk_object = " . ((int) $invoice_id) . " AND object_type = 'invoice'";
		$sql .= " ORDER BY line_num ASC";

		$resql = $db->query($sql);
		if ($resql && $db->num_rows($resql) > 0) {
			$langs->load('paymentterms@paymentterms');

			print '<div id="paymentterms_schedule_block" style="margin-top: 20px; clear: both;">';
			print '<table class="border centpercent">';
			print '<tr class="liste_titre"><td colspan="5" style="text-transform: uppercase; font-weight: bold;">'.$langs->trans('PaymentSchedule').'</td></tr>';
			print '<tr class="liste_titre">';
			print '<td><strong>Taksit No</strong></td>';
			print '<td><strong>Vade Tarihi</strong></td>';
			print '<td align="right"><strong>Taksit Tutarı</strong></td>';
			print '<td align="right"><strong>Ödenen</strong></td>';
			print '<td align="center"><strong>Durum</strong></td>';
			print '</tr>';

			while ($row = $db->fetch_object($resql)) {
				$statusLabel = $row->status;
				$statusColor = '#28a745'; // green
				if ($row->status === 'PENDING') {
					$statusColor = '#ffc107'; // yellow
					$statusLabel = 'Ödenmedi';
				} elseif ($row->status === 'PARTIAL') {
					$statusColor = '#17a2b8'; // blue
					$statusLabel = 'Kısmi Ödeme';
				} else {
					$statusLabel = 'Ödendi';
				}

				print '<tr>';
				print '<td>' . $row->line_num . '</td>';
				print '<td>' . dol_print_date($db->jdate($row->due_date), 'day') . '</td>';
				print '<td align="right"><strong>' . price($row->amount_ttc, 2, $langs, 0, 0, -1, $object->multicurrency_code ?? $conf->currency) . '</strong></td>';
				print '<td align="right">' . price($row->paid_amount, 2, $langs, 0, 0, -1, $object->multicurrency_code ?? $conf->currency) . '</td>';
				print '<td align="center"><span style="font-weight:bold;color:' . $statusColor . ';">' . $statusLabel . '</span></td>';
				print '</tr>';
			}

			print '</table>';
			print '</div>';
		}

		return 0;
	}

	/**
	 * Overwrite completePayment hook to show payment plan selector in TakePOS.
	 *
	 * @param array             $parameters  Hook parameters
	 * @param CommonObject      $object      Active object (Facture)
	 * @param string            $action      Action (view, edit, etc.)
	 * @param HookManager       $hookmanager Hook manager
	 * @return int                           0 if OK
	 */
	public function completePayment($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $conf;

		// We only want to output on the takepospay context
		if (empty($parameters['context']) || !in_array('takepospay', explode(':', $parameters['context']))) {
			return 0;
		}

		// Let's query plans with active_in_pos = 1
		$sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "paymentterm_plan WHERE active_in_pos = 1 AND is_active = 1 AND entity = " . ((int) $object->entity);
		$resql = $db->query($sql);
		if (!$resql) {
			return 0;
		}

		$options = '<option value="0">-- Standart Ödeme Koşulu --</option>';
		$selected_plan = (int) ($object->array_options['options_payment_plan_id'] ?? 0);

		while ($row = $db->fetch_object($resql)) {
			$selected = ($row->rowid == $selected_plan) ? ' selected' : '';
			$options .= '<option value="' . $row->rowid . '"' . $selected . '>' . dol_escape_htmltag($row->label) . '</option>';
		}

		$langs->load('paymentterms@paymentterms');

		// Render the dropdown select box and jQuery/AJAX logic
		$html = "\n" . '<!-- PaymentTerms POS Integration -->' . "\n";
		$html .= '<div class="paymentterms-pos-block" style="margin-top: 15px; text-align: center; width: 100%; clear: both;">';
		$html .= '<label for="pos_payment_plan_id" style="color: white; font-weight: bold; margin-right: 10px;">' . $langs->trans('PaymentPlan') . ':</label>';
		$html .= '<select id="pos_payment_plan_id" name="pos_payment_plan_id" style="padding: 5px; font-size: 1.1em; border-radius: 4px; min-width: 200px; color: black !important;">';
		$html .= $options;
		$html .= '</select>';
		$html .= '</div>';

		// Add script to automatically update the payment plan via AJAX on change
		$ajaxUrl = dol_buildpath('/custom/paymentterms/ajax/set_plan.php', 1);
		$html .= '<script type="text/javascript">
			jQuery(document).ready(function() {
				jQuery("#pos_payment_plan_id").change(function() {
					var planId = jQuery(this).val();
					var invoiceId = ' . ((int) $object->id) . ';
					if (invoiceId > 0) {
						jQuery.ajax({
							url: "' . $ajaxUrl . '",
							type: "POST",
							data: {
								invoiceid: invoiceId,
								plan_id: planId,
								token: "' . currentToken() . '"
							},
							success: function(response) {
								console.log("PaymentTerms: Plan updated to " + planId);
							},
							error: function() {
								console.error("PaymentTerms: Failed to update plan");
							}
						});
					}
				});
			});
		</script>' . "\n";

		$this->resPrint = $html;
		return 0;
	}
}
