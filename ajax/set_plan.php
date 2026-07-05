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

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Access control
if (!$user->hasRight('takepos', 'run') && !$user->rights->paymentterms->schedule->write) {
	http_response_code(403);
	echo json_encode(array('status' => 'error', 'message' => 'Forbidden'));
	exit;
}

$invoiceid = GETPOSTINT('invoiceid');
$plan_id = GETPOSTINT('plan_id');

if (empty($invoiceid)) {
	http_response_code(400);
	echo json_encode(array('status' => 'error', 'message' => 'Missing invoiceid'));
	exit;
}

$invoice = new Facture($db);
if ($invoice->fetch($invoiceid) <= 0) {
	http_response_code(404);
	echo json_encode(array('status' => 'error', 'message' => 'Invoice not found'));
	exit;
}

// Update extrafield options_payment_plan_id
$invoice->array_options['options_payment_plan_id'] = $plan_id;
$result = $invoice->insertExtraFields();

if ($result >= 0) {
	echo json_encode(array('status' => 'success', 'message' => 'Payment plan updated successfully'));
} else {
	http_response_code(500);
	echo json_encode(array('status' => 'error', 'message' => 'Failed to save payment plan: ' . $invoice->error));
}
