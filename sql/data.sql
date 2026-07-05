-- Sample payment plans for testing
INSERT INTO llx_paymentterm_plan (rowid, ref, label, description, calculation_method, date_calculation_base, active_in_pos, is_active, entity) VALUES
(1, '3TAKSIT', '3 Eşit Taksit (+30/+60/+90 Gün)', 'Tutar üçe bölünür, 30, 60 ve 90 gün vadeli taksitlendirilir.', 'FORMULA', 'INVOICE_DATE', 1, 1, 1),
(2, 'PESIN_2TAKSIT', '%10 Peşin + 2 Taksit (+30/+60 Gün)', '%10 peşinat hemen ödenir, kalan %90 tutar 30 ve 60 gün vadeli iki taksite bölünür.', 'FORMULA', 'INVOICE_DATE', 1, 1, 1);

-- Sample payment plan lines
INSERT INTO llx_paymentterm_line (fk_paymentterm_plan, line_num, amount_formula, date_formula, description, is_balance_line) VALUES
-- Plan 1 lines
(1, 1, 'G/3', '+30', '1. Taksit', 0),
(1, 2, 'G/3', '+60', '2. Taksit', 0),
(1, 3, 'G/3', '+90', '3. Taksit (Bakiye)', 1),
-- Plan 2 lines
(2, 1, 'G*0.10', '0', 'Peşinat', 0),
(2, 2, 'G*0.45', '+30', '1. Taksit', 0),
(2, 3, 'G*0.45', '+60', '2. Taksit (Bakiye)', 1);
