-- =============================================================================
-- Point Delivery — clear Expenses + Expense Summary data
-- Works in phpMyAdmin even when "Enable foreign key checks" is ON.
-- Uses DELETE (not TRUNCATE) so parent/child FKs do not block.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- 1) Child tables first
DELETE FROM `expense_items`;
DELETE FROM `expense_summary_triple_checks`;
DELETE FROM `expense_summaries`;

-- 2) Parent cards
DELETE FROM `expense_cards`;

-- 3) Optional media tied to expense models
DELETE FROM `media`
 WHERE `model_type` IN (
   'App\\Models\\ExpenseItem',
   'App\\Models\\ExpenseCard',
   'App\\Models\\ExpenseSummary'
 );

-- Reset auto-increment (optional, clean IDs from 1)
ALTER TABLE `expense_items` AUTO_INCREMENT = 1;
ALTER TABLE `expense_summary_triple_checks` AUTO_INCREMENT = 1;
ALTER TABLE `expense_summaries` AUTO_INCREMENT = 1;
ALTER TABLE `expense_cards` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
