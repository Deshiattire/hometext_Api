-- =====================================================
-- DEMO DATA INSERT QUERIES FOR PRODUCT SYSTEM
-- =====================================================
-- This file contains SQL queries to insert demo data
-- for all new tables and modified columns
-- =====================================================

-- =====================================================
-- 1. UPDATE EXISTING TABLES WITH NEW COLUMNS
-- =====================================================

-- Update products table with new enhanced fields
-- (Assuming you have at least one product with ID 412)
UPDATE products 
SET 
    short_description = 'Premium quality product for ultimate comfort and durability',
    visibility = 'visible',
    type = 'simple',
    published_at = NOW(),
    tax_rate = 15.00,
    tax_included = 0,
    tax_class = 'standard',
    currency = 'BDT',
    currency_symbol = '৳',
    stock_status = 'in_stock',
    low_stock_threshold = 10,
    allow_backorders = 0,
    manage_stock = 1,
    sold_count = 245,
    weight = 0.5,
    weight_unit = 'kg',
    length = 30.0,
    width = 20.0,
    height = 5.0,
    dimension_unit = 'cm',
    shipping_class = 'standard',
    free_shipping = 0,
    ships_from_country = 'Bangladesh',
    ships_from_city = 'Dhaka',
    min_delivery_days = 3,
    max_delivery_days = 7,
    express_available = 1,
    is_bestseller = 1,
    is_limited_edition = 0,
    is_exclusive = 0,
    is_eco_friendly = 0,
    minimum_order_quantity = 1,
    maximum_order_quantity = 10,
    has_warranty = 1,
    warranty_duration = 12,
    warranty_duration_unit = 'months',
    warranty_type = 'manufacturer',
    warranty_details = '12 months manufacturer warranty',
    returnable = 1,
    return_window_days = 7,
    return_conditions = 'Product must be in original condition with tags'
WHERE id = 412;

-- Update more products (adjust IDs based on your existing products)
UPDATE products 
SET 
    short_description = 'High-quality bedding set made from premium materials',
    visibility = 'visible',
    type = 'simple',
    published_at = NOW(),
    tax_rate = 15.00,
    currency = 'BDT',
    currency_symbol = '৳',
    stock_status = 'in_stock',
    is_bestseller = 1
WHERE id IN (30, 31, 32, 39, 40, 41,42,48);

-- Update countries table with code (if not already updated)
UPDATE countries 
SET code = 'BD' 
WHERE name = 'Bangladesh' AND (code IS NULL OR code = '');

UPDATE countries 
SET code = 'US' 
WHERE name = 'United States' AND (code IS NULL OR code = '');

UPDATE countries 
SET code = 'CN' 
WHERE name = 'China' AND (code IS NULL OR code = '');

-- Update shops table with slug (if not already updated)
UPDATE shops 
SET slug = 'main-branch' 
WHERE id = 1 AND (slug IS NULL OR slug = '');

UPDATE shops 
SET slug = 'ecommerce-warehouse' 
WHERE id = 4 AND (slug IS NULL OR slug = '');

-- Update product_photos with new fields
UPDATE product_photos 
SET 
    alt_text = 'Product main image',
    width = 800,
    height = 600,
    position = 1
WHERE is_primary = 1 
LIMIT 10;

-- Update product_specifications with group
UPDATE product_specifications 
SET `group` = 'General' 
WHERE `group` IS NULL OR `group` = ''
LIMIT 20;

-- =====================================================
-- 2. INSERT PRODUCT TAGS
-- =====================================================

INSERT INTO product_tags (name, slug, description, created_at, updated_at) VALUES
('cotton', 'cotton', 'Made from premium cotton material', NOW(), NOW()),
('premium', 'premium', 'Premium quality products', NOW(), NOW()),
('comfortable', 'comfortable', 'Comfortable and soft', NOW(), NOW()),
('single-bed', 'single-bed', 'Suitable for single bed', NOW(), NOW()),
('double-bed', 'double-bed', 'Suitable for double bed', NOW(), NOW()),
('queen-bed', 'queen-bed', 'Suitable for queen size bed', NOW(), NOW()),
('king-bed', 'king-bed', 'Suitable for king size bed', NOW(), NOW()),
('bath', 'bath', 'Bathroom accessories', NOW(), NOW()),
('kitchen', 'kitchen', 'Kitchen essentials', NOW(), NOW()),
('home-decor', 'home-decor', 'Home decoration items', NOW(), NOW()),
('eco-friendly', 'eco-friendly', 'Environmentally friendly products', NOW(), NOW()),
('bestseller', 'bestseller', 'Best selling products', NOW(), NOW()),
('new-arrival', 'new-arrival', 'Newly arrived products', NOW(), NOW()),
('on-sale', 'on-sale', 'Products currently on sale', NOW(), NOW()),
('trending', 'trending', 'Trending products', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- 3. LINK PRODUCTS TO TAGS (PIVOT TABLE)
-- =====================================================
-- Note: Adjust product_tag_pivot table name if different
-- Check your migration - it might be 'product_product_tag'

-- For product ID 412
INSERT INTO product_tag_pivot (product_id, product_tag_id, created_at, updated_at) VALUES
(412, (SELECT id FROM product_tags WHERE slug = 'cotton' LIMIT 1), NOW(), NOW()),
(412, (SELECT id FROM product_tags WHERE slug = 'premium' LIMIT 1), NOW(), NOW()),
(412, (SELECT id FROM product_tags WHERE slug = 'comfortable' LIMIT 1), NOW(), NOW()),
(412, (SELECT id FROM product_tags WHERE slug = 'single-bed' LIMIT 1), NOW(), NOW())
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- For more products (using your actual product IDs)
INSERT INTO product_tag_pivot (product_id, product_tag_id, created_at, updated_at)
SELECT 30, id, NOW(), NOW() FROM product_tags WHERE slug IN ('cotton', 'premium', 'double-bed')
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

INSERT INTO product_tag_pivot (product_id, product_tag_id, created_at, updated_at)
SELECT 31, id, NOW(), NOW() FROM product_tags WHERE slug IN ('bath', 'premium')
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

INSERT INTO product_tag_pivot (product_id, product_tag_id, created_at, updated_at)
SELECT 32, id, NOW(), NOW() FROM product_tags WHERE slug IN ('cotton', 'comfortable')
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- =====================================================
-- 4. INSERT PRODUCT VARIATIONS
-- =====================================================
-- For variable products (e.g., different sizes/colors)

-- Example: Product with variations (assuming product ID 100 exists or create one)
INSERT INTO product_variations (
    product_id, sku, name, slug, regular_price, sale_price, 
    stock_quantity, stock_status, weight, length, width, height,
    attributes, sort_order, is_active, created_at, updated_at
) VALUES
-- Size variations
(412, 'PROD-412-S', 'Small Size', 'product-412-small', 550.00, 495.00, 50, 'in_stock', 0.4, 25, 18, 4, '{"Size": "Small", "Color": "White"}', 1, 1, NOW(), NOW()),
(412, 'PROD-412-M', 'Medium Size', 'product-412-medium', 650.00, 585.00, 75, 'in_stock', 0.5, 30, 20, 5, '{"Size": "Medium", "Color": "White"}', 2, 1, NOW(), NOW()),
(412, 'PROD-412-L', 'Large Size', 'product-412-large', 750.00, 675.00, 60, 'in_stock', 0.6, 35, 22, 6, '{"Size": "Large", "Color": "White"}', 3, 1, NOW(), NOW()),
-- Color variations
(412, 'PROD-412-BLUE', 'Blue Color', 'product-412-blue', 650.00, NULL, 40, 'in_stock', 0.5, 30, 20, 5, '{"Size": "Medium", "Color": "Blue"}', 4, 1, NOW(), NOW()),
(412, 'PROD-412-RED', 'Red Color', 'product-412-red', 650.00, NULL, 35, 'in_stock', 0.5, 30, 20, 5, '{"Size": "Medium", "Color": "Red"}', 5, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- =====================================================
-- 5. INSERT PRODUCT REVIEWS
-- =====================================================

INSERT INTO product_reviews (
    product_id, user_id, reviewer_name, reviewer_email, rating, title, review,
    is_verified_purchase, is_recommended, is_approved, is_helpful_count, created_at, updated_at
) VALUES
-- Approved reviews
(412, NULL, 'John Doe', 'john.doe@example.com', 5, 'Excellent Product!', 'This product exceeded my expectations. High quality and very comfortable. Highly recommended!', 1, 1, 1, 12, NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 30 DAY),
(412, NULL, 'Jane Smith', 'jane.smith@example.com', 5, 'Love it!', 'Amazing quality and fast delivery. Will definitely buy again!', 1, 1, 1, 8, NOW() - INTERVAL 25 DAY, NOW() - INTERVAL 25 DAY),
(412, NULL, 'Mike Johnson', 'mike.j@example.com', 4, 'Good Value', 'Good product for the price. Quality is decent and meets expectations.', 1, 1, 1, 5, NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 20 DAY),
(412, NULL, 'Sarah Williams', 'sarah.w@example.com', 5, 'Perfect!', 'Exactly as described. Very satisfied with my purchase.', 1, 1, 1, 15, NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 15 DAY),
(412, NULL, 'David Brown', 'david.b@example.com', 4, 'Nice Product', 'Good quality, fast shipping. Would recommend to others.', 0, 1, 1, 3, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),
(412, NULL, 'Emily Davis', 'emily.d@example.com', 3, 'Average', 'Product is okay but could be better. Expected more for the price.', 1, 0, 1, 2, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY),
-- Pending reviews (not approved yet)
(412, NULL, 'Robert Wilson', 'robert.w@example.com', 5, 'Great Product', 'Really happy with this purchase. Great quality!', 1, 1, 0, 0, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY),
(412, NULL, 'Lisa Anderson', 'lisa.a@example.com', 4, 'Good Quality', 'Good product, satisfied with the purchase.', 1, 1, 0, 0, NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

-- More reviews for other products (using your actual product IDs)
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
-- 6. INSERT PRODUCT VIDEOS
-- =====================================================

INSERT INTO product_videos (
    product_id, type, url, thumbnail, title, description, position, is_active, created_at, updated_at
) VALUES
(412, 'youtube', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', 'Product Overview Video', 'Watch this video to learn more about our product features', 1, 1, NOW(), NOW()),
(412, 'youtube', 'https://www.youtube.com/watch?v=example2', 'https://img.youtube.com/vi/example2/maxresdefault.jpg', 'How to Use', 'Tutorial on how to use this product', 2, 1, NOW(), NOW()),
(412, 'vimeo', 'https://vimeo.com/123456789', 'https://vumbnail.com/123456789.jpg', 'Customer Review Video', 'See what our customers say', 3, 1, NOW(), NOW());

-- =====================================================
-- 7. INSERT BULK PRICING
-- =====================================================

INSERT INTO bulk_pricing (
    product_id, min_quantity, max_quantity, price, discount_percentage, sort_order, is_active, created_at, updated_at
) VALUES
(412, 1, 4, 650.00, NULL, 1, 1, NOW(), NOW()),
(412, 5, 9, 600.00, 7.69, 2, 1, NOW(), NOW()),
(412, 10, 24, 550.00, 15.38, 3, 1, NOW(), NOW()),
(412, 25, 49, 500.00, 23.08, 4, 1, NOW(), NOW()),
(412, 50, NULL, 450.00, 30.77, 5, 1, NOW(), NOW());

-- =====================================================
-- 8. INSERT PRODUCT ANALYTICS
-- =====================================================

INSERT INTO product_analytics (
    product_id, views_count, clicks_count, add_to_cart_count, purchase_count, wishlist_count, conversion_rate, created_at, updated_at
) VALUES
(412, 1250, 890, 450, 245, 120, 19.60, NOW(), NOW()),
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
-- 9. INSERT RELATED PRODUCTS
-- =====================================================

-- Similar products (using your actual product IDs)
INSERT INTO related_products (product_id, related_product_id, relation_type, sort_order, is_active, created_at, updated_at) VALUES
(412, 30, 'similar', 1, 1, NOW(), NOW()),
(412, 31, 'similar', 2, 1, NOW(), NOW()),
(412, 32, 'similar', 3, 1, NOW(), NOW()),
-- Frequently bought together
(412, 39, 'frequently_bought_together', 1, 1, NOW(), NOW()),
(412, 40, 'frequently_bought_together', 2, 1, NOW(), NOW()),
-- Customers also viewed
(412, 41, 'customers_also_viewed', 1, 1, NOW(), NOW()),
(412, 42, 'customers_also_viewed', 2, 1, NOW(), NOW()),
-- Recently viewed
(412, 48, 'recently_viewed', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);

-- Reverse relationships (optional - creates bidirectional relationships)
-- Uncomment if you want reverse relationships
/*
INSERT INTO related_products (product_id, related_product_id, relation_type, sort_order, is_active, created_at, updated_at)
SELECT related_product_id, product_id, relation_type, sort_order, is_active, NOW(), NOW()
FROM related_products
WHERE product_id = 412
ON DUPLICATE KEY UPDATE product_id = VALUES(product_id);
*/

-- =====================================================
-- 10. INSERT PRODUCT SEO META DATA
-- =====================================================
-- (If table exists)

INSERT INTO product_seo_meta_data (product_id, name, content, created_at, updated_at) VALUES
(412, 'meta_title', 'Premium Bed Sheet - High Quality Cotton Bedding', NOW(), NOW()),
(412, 'meta_description', 'Buy premium quality bed sheets made from 100% cotton. Comfortable, durable, and available in multiple sizes. Free shipping available.', NOW(), NOW()),
(412, 'meta_keywords', 'bed sheet, cotton bedding, premium bed sheets, home textiles', NOW(), NOW()),
(412, 'og_title', 'Premium Bed Sheet - Hometex', NOW(), NOW()),
(412, 'og_description', 'High-quality bed sheets for ultimate comfort', NOW(), NOW()),
(412, 'og_image', 'https://example.com/images/product-412-og.jpg', NOW(), NOW())
ON DUPLICATE KEY UPDATE content = VALUES(content);

-- =====================================================
-- 11. UPDATE PRODUCT TYPE FOR VARIABLE PRODUCTS
-- =====================================================
-- If a product has variations, update its type to 'variable'

UPDATE products 
SET type = 'variable' 
WHERE id IN (
    SELECT DISTINCT product_id 
    FROM product_variations 
    WHERE product_id IS NOT NULL
);

-- =====================================================
-- 12. UPDATE PRODUCT PHOTOS WITH ENHANCED FIELDS
-- =====================================================
-- Update additional photos (non-primary)

UPDATE product_photos 
SET 
    alt_text = CONCAT('Product gallery image ', id),
    width = 800,
    height = 600,
    position = id
WHERE is_primary = 0 
AND (alt_text IS NULL OR alt_text = '')
LIMIT 20;

-- =====================================================
-- NOTES AND INSTRUCTIONS
-- =====================================================
-- 
-- 1. Before running these queries:
--    - Backup your database
--    - Adjust product IDs (412, 1, 2, 3, etc.) to match your existing products
--    - Check if pivot table is 'product_tag_pivot' or 'product_product_tag'
--    - Verify shop IDs (1, 4) exist in your shops table
--    - Verify category, brand, supplier IDs exist
--
-- 2. To find existing product IDs:
--    SELECT id, name FROM products LIMIT 10;
--
-- 3. To find existing category IDs:
--    SELECT id, name FROM categories LIMIT 10;
--
-- 4. To find existing brand IDs:
--    SELECT id, name FROM brands LIMIT 10;
--
-- 5. To verify data was inserted:
--    SELECT COUNT(*) FROM product_tags;
--    SELECT COUNT(*) FROM product_variations;
--    SELECT COUNT(*) FROM product_reviews;
--    SELECT COUNT(*) FROM product_videos;
--    SELECT COUNT(*) FROM bulk_pricing;
--    SELECT COUNT(*) FROM product_analytics;
--    SELECT COUNT(*) FROM related_products;
--
-- 6. To test APIs:
--    - GET /api/products/412 (should return full product details)
--    - GET /api/products/412/reviews (should return reviews)
--    - GET /api/products/412/similar (should return similar products)
--    - GET /api/reviews/pending (admin) - should show pending reviews
--
-- =====================================================

