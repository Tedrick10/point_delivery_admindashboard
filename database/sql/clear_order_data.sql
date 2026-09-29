-- =============================================================================
-- Point Delivery — clear ALL order / parcel transactional data
-- Keeps: users, roles, settings, branches, cities, routes, HR, shop catalog, etc.
-- Run:  mysql -u root -p point_delivery < database/sql/clear_order_data.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET UNIQUE_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- ---- Parcel chat / pending remarks ------------------------------------------
TRUNCATE TABLE `dispatch_item_messages`;
TRUNCATE TABLE `dispatch_item_pending_remarks`;

-- ---- Kyo Shin ---------------------------------------------------------------
TRUNCATE TABLE `kyo_shin_items`;
TRUNCATE TABLE `kyo_shin_daily_ledgers`;
TRUNCATE TABLE `kyo_shin_batches`;
TRUNCATE TABLE `kyo_shin_caps`;

-- ---- Ratings / bids / chat / history / vehicle ------------------------------
TRUNCATE TABLE `ratings`;
TRUNCATE TABLE `order_bids`;
TRUNCATE TABLE `order_chat_messages`;
TRUNCATE TABLE `order_histories`;
TRUNCATE TABLE `order_vehicle_histories`;
TRUNCATE TABLE `reschedules`;

-- ---- Payments / wallets (order-linked money movement) -----------------------
TRUNCATE TABLE `payments`;
TRUNCATE TABLE `pay_t_r_payments`;
TRUNCATE TABLE `wallet_histories`;
UPDATE `wallets` SET `total_amount` = 0, `updated_at` = NOW();
TRUNCATE TABLE `withdraw_details`;
TRUNCATE TABLE `withdraw_requests`;

-- ---- Claims / support (often order-linked) ----------------------------------
TRUNCATE TABLE `claims_histories`;
TRUNCATE TABLE `claims`;
TRUNCATE TABLE `support_chathistories`;
TRUNCATE TABLE `customer_supports`;

-- ---- Proof photos + order/chat media ----------------------------------------
DELETE FROM `media`
 WHERE `model_type` IN (
   'App\\Models\\Profofpictures',
   'App\\Models\\SupportChathistory',
   'App\\Models\\DispatchItemMessage',
   'App\\Models\\DispatchOrderItem',
   'App\\Models\\Order'
 );
TRUNCATE TABLE `profofpictures`;

-- ---- OS settlement / money transfer / cash payout ---------------------------
TRUNCATE TABLE `os_settlement_drafts`;
TRUNCATE TABLE `os_receive_settlements`;
TRUNCATE TABLE `os_settlement_batches`;
TRUNCATE TABLE `os_money_transfers`;
TRUNCATE TABLE `os_cash_payouts`;

-- ---- Rider remit / daily check / DM payout ----------------------------------
TRUNCATE TABLE `rider_remit_logs`;
TRUNCATE TABLE `rider_remits`;
TRUNCATE TABLE `daily_check_invoices`;
TRUNCATE TABLE `delivery_man_payout_reports`;

-- ---- Notifications (almost all are order/parcel events) ---------------------
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `push_notifications`;

-- ---- REST API histories that reference orders -------------------------------
DELETE FROM `rest_api_histories` WHERE `order_id` IS NOT NULL;

-- ---- Dispatch items then orders (parent) ------------------------------------
TRUNCATE TABLE `dispatch_order_items`;
TRUNCATE TABLE `orders`;

SET FOREIGN_KEY_CHECKS = 1;
SET UNIQUE_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;
