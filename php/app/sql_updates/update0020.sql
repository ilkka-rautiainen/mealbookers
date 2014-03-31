ALTER TABLE  `users` ADD  `email_verified` TINYINT( 1 ) UNSIGNED NOT NULL AFTER  `joined` ;
update users set email_verified = 1;