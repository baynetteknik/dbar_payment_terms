CREATE TABLE llx_paymentterm_line (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_paymentterm_plan integer NOT NULL,
  line_num integer NOT NULL,
  amount_formula varchar(128) NOT NULL,
  date_formula varchar(64) NOT NULL,
  description varchar(255),
  is_balance_line tinyint DEFAULT 0 NOT NULL,
  fk_bank_account integer,
  datec datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_paymentterm_line ADD UNIQUE INDEX uk_paymentterm_line_plan_num (fk_paymentterm_plan, line_num);
ALTER TABLE llx_paymentterm_line ADD INDEX idx_paymentterm_line_plan (fk_paymentterm_plan);
