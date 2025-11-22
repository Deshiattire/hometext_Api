-- =====================================================
-- FIX REMAINING ERRORS - Run this after demo_data.sql
-- =====================================================
-- This file fixes the remaining foreign key constraint errors
-- by using your actual product IDs: 30, 31, 32, 39, 40, 41, 42, 48
-- =====================================================

-- =====================================================
-- 1. LINK PRODUCTS TO TAGS (PIVOT TABLE) - FIXED
-- =====================================================

-- For product ID 30
INSERT INTO product_tag_pivot (product_id, product_tag_id, created_at, updated_at)
SELECT 30, id, NOW(), NOW() FROM product_tags WHERE slug IN ('cotton', 'premium', 'double-bed')
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- For product ID 31
INSERT INTO product_tag_pivot (product_id, product_tag_id, created_at, updated_at)
SELECT 31, id, NOW(), NOW() FROM product_tags WHERE slug IN ('bath', 'premium')
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- For product ID 32
INSERT INTO product_tag_pivot (product_id, product_tag_id, created_at, updated_at)
SELECT 32, id, NOW(), NOW() FROM product_tags WHERE slug IN ('cotton', 'comfortable')
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- =====================================================
-- 2. INSERT PRODUCT REVIEWS - FIXED
-- =====================================================

INSERT INTO product_reviews (
    product_id, user_id, reviewer_name, reviewer_email, rating, title, review,
    is_verified_purchase, is_recommended, is_approved, is_helpful_count, created_at, updated_at
) VALUES
(30, NULL, 'Customer One', 'customer1@example.com', 5, 'Great Product!', 'This product exceeded my expectations. High quality and very comfortable.', 1, 1, 1, 5, NOW() - INTERVAL 25 DAY, NOW() - INTERVAL 25 DAY),
(30, NULL, 'Customer Two', 'customer2@example.com', 4, 'Good Value', 'Good product for the price. Quality is decent and meets expectations.', 1, 1, 1, 3, NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 20 DAY),
(31, NULL, 'Customer Three', 'customer3@example.com', 5, 'Excellent!', 'Amazing quality and fast delivery. Will definitely buy again!', 1, 1, 1, 7, NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 15 DAY),
(31, NULL, 'Customer Four', 'customer4@example.com', 4, 'Nice Product', 'Good quality, fast shipping. Would recommend to others.', 1, 1, 1, 4, NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 12 DAY),
(32, NULL, 'Customer Five', 'customer5@example.com', 5, 'Perfect!', 'Exactly as described. Very satisfied with my purchase.', 1, 1, 1, 6, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY);

-- =====================================================
-- 3. INSERT PRODUCT ANALYTICS - FIXED
-- =====================================================

INSERT INTO product_analytics (
    product_id, views_count, clicks_count, add_to_cart_count, purchase_count, wishlist_count, conversion_rate, created_at, updated_at
) VALUES
(30, 850, 620, 320, 180, 95, 21.18, NOW(), NOW()),
(31, 920, 710, 380, 210, 110, 22.83, NOW(), NOW()),
(32, 680, 490, 250, 145, 75, 21.32, NOW(), NOW()),
(39, 1100, 820, 420, 230, 130, 20.91, NOW(), NOW()),
(40, 750, 560, 290, 165, 85, 22.00, NOW(), NOW()),
(41, 980, 720, 380, 200, 105, 20.41, NOW(), NOW()),
(42, 1150, 850, 440, 240, 125, 20.87, NOW(), NOW()),
(48, 890, 650, 330, 185, 98, 21.35, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
    views_count = VALUES(views_count),
    clicks_count = VALUES(clicks_count),
    add_to_cart_count = VALUES(add_to_cart_count),
    purchase_count = VALUES(purchase_count),
    wishlist_count = VALUES(wishlist_count),
    conversion_rate = VALUES(conversion_rate);

-- =====================================================
-- 4. INSERT RELATED PRODUCTS - FIXED
-- =====================================================

INSERT INTO related_products (product_id, related_product_id, relation_type, sort_order, is_active, created_at, updated_at) VALUES
(412, 30, 'similar', 1, 1, NOW(), NOW()),
(412, 31, 'similar', 2, 1, NOW(), NOW()),
(412, 32, 'similar', 3, 1, NOW(), NOW()),
(412, 39, 'frequently_bought_together', 1, 1, NOW(), NOW()),
(412, 40, 'frequently_bought_together', 2, 1, NOW(), NOW()),
(412, 41, 'customers_also_viewed', 1, 1, NOW(), NOW()),
(412, 42, 'customers_also_viewed', 2, 1, NOW(), NOW()),
(412, 48, 'recently_viewed', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if data was inserted successfully
SELECT 'Product Tags' as table_name, COUNT(*) as count FROM product_tags
UNION ALL
SELECT 'Product Tag Pivot', COUNT(*) FROM product_tag_pivot
UNION ALL
SELECT 'Product Variations', COUNT(*) FROM product_variations
UNION ALL
SELECT 'Product Reviews', COUNT(*) FROM product_reviews
UNION ALL
SELECT 'Product Videos', COUNT(*) FROM product_videos
UNION ALL
SELECT 'Bulk Pricing', COUNT(*) FROM bulk_pricing
UNION ALL
SELECT 'Product Analytics', COUNT(*) FROM product_analytics
UNION ALL
SELECT 'Related Products', COUNT(*) FROM related_products;

