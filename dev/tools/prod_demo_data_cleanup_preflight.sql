-- Read-only preflight for the production demo-data cleanup.
-- Run with: dev/tools/remote_db.sh --file dev/tools/prod_demo_data_cleanup_preflight.sql
-- Every count below must match the "expected" value in the comment before
-- prod_demo_data_cleanup.sql is safe to run. See
-- dev/plans/prod-demo-data-removal-verification-2026-09-23.md for the
-- full methodology and the discrepancies deliberately excluded here.

SELECT 'demo products' AS check_name, COUNT(*) AS actual, 67 AS expected
FROM catalog_product_entity WHERE entity_id IN (
    42,43,46,50,51,52,53,54,55,56,57,58,59,60,
    140,141,142,143,144,145,146,147,148,149,150,151,152,153,154,
    155,156,157,158,159,160,161,162,163,164,165,166,167,168,169,
    170,171,172,173,174,175,176,177,178,179,180,181,182,183,184,
    185,186,187,258,280,281,282,283
);

SELECT 'demo reviews', COUNT(*), 9
FROM review WHERE review_id IN (41,43,47,50,51,52,53,54,192);

SELECT 'live Kratom reviews retained', COUNT(*), 24
FROM review WHERE review_id BETWEEN 239 AND 262;

SELECT 'demo orders (25, incl. 5-10)', COUNT(*), 25
FROM sales_order WHERE entity_id IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25);

SELECT 'demo customers', COUNT(*), 19
FROM customer_entity WHERE entity_id BETWEEN 1 AND 19;

SELECT 'demo CMS pages', COUNT(*), 3
FROM cms_page WHERE page_id IN (5,6,7);

SELECT 'demo attributes', COUNT(*), 3
FROM eav_attribute WHERE attribute_id IN (140,141,142);

SELECT 'live storefront (store 111)', COUNT(*), 1
FROM store WHERE store_id = 111 AND is_active = 1;

SELECT 'demo store views', COUNT(*), 80
FROM store WHERE store_id IN (1, 39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,
    60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,
    91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,
    112,113,114,115,116,117,118);

SELECT 'demo store groups', COUNT(*), 40
FROM store_group WHERE group_id IN (1,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,
    27,28,29,30,31,32,33,34,35,36,37,38,39,40,42,43,44);

SELECT 'protected product 470 present', COUNT(*), 1
FROM catalog_product_entity WHERE entity_id = 470;

SELECT 'protected about-us page (8) present', COUNT(*), 1
FROM cms_page WHERE page_id = 8;

SELECT 'Auto attribute set id', attribute_set_id AS actual, NULL AS expected
FROM eav_attribute_set WHERE attribute_set_name = 'Auto';

SELECT 'Auto attribute set product count (must be 0)', COUNT(*), 0
FROM catalog_product_entity
WHERE attribute_set_id = (SELECT attribute_set_id FROM eav_attribute_set WHERE attribute_set_name = 'Auto');

SELECT 'out-of-scope orders NOT selected (26,27,29)', COUNT(*), 0
FROM sales_order WHERE entity_id IN (26,27,29) AND entity_id IN
    (1,2,3,4,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25);
