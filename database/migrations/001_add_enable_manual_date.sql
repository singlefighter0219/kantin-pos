INSERT INTO `pengaturan` (`param_key`, `param_value`) 
VALUES ('enable_manual_date', '0')
ON DUPLICATE KEY UPDATE `param_value` = VALUES(`param_value`);