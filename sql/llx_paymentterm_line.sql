CREATE TABLE IF NOT EXISTS llx_paymentterm_line (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_paymentterm_plan integer NOT NULL,
  line_num integer NOT NULL,
  amount_formula varchar(128) NOT NULL,
  date_formula varchar(64) NOT NULL,
  description varchar(255),
  is_balance_line tinyint DEFAULT 0 NOT NULL,
  fk_bank_account integer,
  datec datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
  UNIQUE KEY uk_paymentterm_line_plan_num (fk_paymentterm_plan, line_num),
  INDEX idx_paymentterm_line_plan (fk_paymentterm_plan)
) ENGINE=innodb;
