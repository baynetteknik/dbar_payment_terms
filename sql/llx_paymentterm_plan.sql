CREATE TABLE llx_paymentterm_plan (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  ref varchar(32) NOT NULL,
  label varchar(128) NOT NULL,
  description varchar(255),
  fk_c_paiement integer,
  calculation_method varchar(16) DEFAULT 'FORMULA' NOT NULL,
  date_calculation_base varchar(16) DEFAULT 'INVOICE_DATE' NOT NULL,
  is_early_discount_enabled tinyint DEFAULT 0 NOT NULL,
  early_discount_formula varchar(64),
  early_discount_days integer,
  active_in_pos tinyint DEFAULT 0 NOT NULL,
  is_active tinyint DEFAULT 1 NOT NULL,
  entity integer DEFAULT 1 NOT NULL,
  datec datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
  fk_user_author integer,
  fk_user_modif integer
) ENGINE=innodb;

ALTER TABLE llx_paymentterm_plan ADD UNIQUE INDEX uk_paymentterm_plan_ref (ref, entity);
ALTER TABLE llx_paymentterm_plan ADD INDEX idx_paymentterm_plan_active (is_active, entity);
