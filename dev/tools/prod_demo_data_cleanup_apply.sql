-- Production demo-data cleanup — APPLY. Irreversible without the backup at
-- backups/prod-pre-demo-cleanup-20260923_163542/. Do not run this without
-- having reviewed dev/tools/prod_demo_data_cleanup_preflight.sql output
-- first and confirmed every count matches.
--
-- Run with: dev/tools/remote_db.sh --file dev/tools/prod_demo_data_cleanup_apply.sql
--
-- Scope mirrors dev/tools/dev3_demo_data_cleanup.php, independently
-- re-verified against production on 2026-09-23 (see
-- dev/plans/prod-demo-data-removal-verification-2026-09-23.md), PLUS orders
-- 5-10 (same demo customers, same 2021 era, not in the original dev3 review
-- but explicitly approved for inclusion here). Out-of-scope orders 26/27/29
-- are deliberately NOT included.

START TRANSACTION;

SET @order_ids = '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25';
SET @review_ids = '41,43,47,50,51,52,53,54,192';
SET @product_ids = '42,43,46,50,51,52,53,54,55,56,57,58,59,60,140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,185,186,187,258,280,281,282,283';
SET @customer_ids = '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19';
SET @page_ids = '5,6,7';
SET @attribute_ids = '140,141,142';
SET @group_ids = '1,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,42,43,44';
SET @store_ids = '1,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,112,113,114,115,116,117,118';
SET @auto_set_id = (SELECT attribute_set_id FROM eav_attribute_set WHERE attribute_set_name = 'Auto');

-- Orphaned grid/index and URL-rewrite rows are not all protected by FKs.
SET @sql = CONCAT('DELETE FROM sales_order_grid WHERE entity_id IN (', @order_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM sales_invoice_grid WHERE order_id IN (', @order_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM sales_shipment_grid WHERE order_id IN (', @order_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM sales_creditmemo_grid WHERE order_id IN (', @order_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM sales_order WHERE entity_id IN (', @order_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM review WHERE review_id IN (', @review_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM review_entity_summary WHERE entity_pk_value IN (', @product_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM url_rewrite WHERE entity_type = ''product'' AND entity_id IN (', @product_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM catalog_product_entity WHERE entity_id IN (', @product_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM customer_entity WHERE entity_id IN (', @customer_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Remove configuration at deleted scopes. No cms_block or cms_block_store row is touched.
SET @sql = CONCAT('DELETE FROM core_config_data WHERE scope = ''stores'' AND scope_id IN (', @store_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM core_config_data WHERE scope = ''groups'' AND scope_id IN (', @group_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM store WHERE store_id IN (', @store_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM store_group WHERE group_id IN (', @group_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = CONCAT('DELETE FROM cms_page WHERE page_id IN (', @page_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

DELETE FROM eav_attribute_set WHERE attribute_set_id = @auto_set_id;

SET @sql = CONCAT('DELETE FROM eav_attribute WHERE attribute_id IN (', @attribute_ids, ')');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Post-delete sanity checks before commit: protected records must remain untouched.
SELECT 'post-delete: protected product 470' AS check_name, COUNT(*) AS actual, 1 AS expected
FROM catalog_product_entity WHERE entity_id = 470;

SELECT 'post-delete: live store 111', COUNT(*), 1
FROM store WHERE store_id = 111 AND is_active = 1;

SELECT 'post-delete: live Kratom reviews', COUNT(*), 24
FROM review WHERE review_id BETWEEN 239 AND 262;

SELECT 'post-delete: about-us page 8', COUNT(*), 1
FROM cms_page WHERE page_id = 8;

SELECT 'post-delete: demo products remaining (must be 0)', COUNT(*), 0
FROM catalog_product_entity WHERE entity_id IN (
    42,43,46,50,51,52,53,54,55,56,57,58,59,60,
    140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,
    155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,
    170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,
    185,186,187,258,280,281,282,283
);

-- FINAL_STATEMENT_PLACEHOLDER
--
-- This file is a template, not meant to be run directly via `--file`: a
-- non-interactive `mysql < file` session that ends without COMMIT or
-- ROLLBACK will implicitly roll back on disconnect anyway, so there is no
-- safe way to "just run this" and inspect results before deciding.
-- Generate the two real variants instead:
--   sed 's/-- FINAL_STATEMENT_PLACEHOLDER/ROLLBACK;/' dev/tools/prod_demo_data_cleanup_apply.sql > var/tmp/prod_cleanup_rehearsal.sql
--   sed 's/-- FINAL_STATEMENT_PLACEHOLDER/COMMIT;/'   dev/tools/prod_demo_data_cleanup_apply.sql > var/tmp/prod_cleanup_commit.sql
-- Run the rehearsal first (it deletes, prints the post-delete sanity
-- checks, then explicitly rolls back — production is left unchanged).
-- Only after reviewing that output does the commit variant get run.
