CREATE TABLE llx_paymentterm_schedule (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  fk_paymentterm_plan integer NOT NULL,
  fk_object integer NOT NULL,
  object_type varchar(32) NOT NULL,
  line_num integer NOT NULL,
  amount decimal(24,8) DEFAULT 0 NOT NULL,
  amount_ttc decimal(24,8) DEFAULT 0 NOT NULL,
  due_date date NOT NULL,
  early_discount_amount decimal(24,8) DEFAULT 0,
  early_discount_deadline date,
  status varchar(16) DEFAULT 'PENDING' NOT NULL,
  paid_amount decimal(24,8) DEFAULT 0 NOT NULL,
  fk_payment integer,
  note_private text,
  entity integer DEFAULT 1 NOT NULL,
  datec datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
) ENGINE=innodb;

ALTER TABLE llx_paymentterm_schedule ADD UNIQUE INDEX uk_paymentterm_schedule_obj_line (object_type, fk_object, line_num);
ALTER TABLE llx_paymentterm_schedule ADD INDEX idx_paymentterm_schedule_due (due_date, status);
ALTER TABLE llx_paymentterm_schedule ADD INDEX idx_paymentterm_schedule_object (object_type, fk_object);
ALTER TABLE llx_paymentterm_schedule ADD INDEX idx_paymentterm_schedule_status (status, entity);
