# Demo Data Insertion Guide

## Overview
This guide helps you insert demo data for all new tables and modified columns in the product system.

## Files
- `demo_data.sql` - Contains all SQL INSERT/UPDATE queries

## Prerequisites

1. **Backup your database** before running these queries
2. **Check existing data** - You need to know:
   - Product IDs that exist in your database
   - Category IDs
   - Brand IDs
   - Shop IDs
   - Supplier IDs

## Quick Start

### Step 1: Find Your Existing IDs

Run these queries to find existing IDs:

```sql
-- Find products
SELECT id, name, sku FROM products LIMIT 10;

-- Find categories
SELECT id, name FROM categories LIMIT 10;

-- Find brands
SELECT id, name FROM brands LIMIT 10;

-- Find shops
SELECT id, name FROM shops LIMIT 10;

-- Find suppliers
SELECT id, name FROM suppliers LIMIT 10;
```

### Step 2: Update the SQL File

Open `demo_data.sql` and replace placeholder IDs:
- Replace `412` with an actual product ID from your database
- Replace `1, 2, 3, 4, 5` with actual product IDs
- Replace shop IDs `1, 4` with actual shop IDs
- Adjust category/brand/supplier IDs as needed

### Step 3: Run the SQL File

**Option A: Using MySQL Command Line**
```bash
mysql -u your_username -p your_database_name < database/demo_data.sql
```

**Option B: Using phpMyAdmin or MySQL Workbench**
1. Open phpMyAdmin/MySQL Workbench
2. Select your database
3. Go to SQL tab
4. Copy and paste contents of `demo_data.sql`
5. Click Execute

**Option C: Using Laravel Tinker**
```bash
php artisan tinker
```
Then run:
```php
DB::unprepared(file_get_contents('database/demo_data.sql'));
```

## What Gets Inserted

### 1. Enhanced Product Fields
- Updates existing products with new columns:
  - `short_description`, `visibility`, `type`
  - `tax_rate`, `currency`, `stock_status`
  - `weight`, `dimensions`, `shipping` info
  - `warranty`, `return_policy` info

### 2. Product Tags
- 15 common tags (cotton, premium, comfortable, etc.)
- Links tags to products via pivot table

### 3. Product Variations
- Size variations (Small, Medium, Large)
- Color variations (Blue, Red)
- For variable products

### 4. Product Reviews
- 8 reviews for product 412 (6 approved, 2 pending)
- 5 reviews for other products
- Mix of ratings (3-5 stars)
- Some verified purchases, some not

### 5. Product Videos
- 3 videos (2 YouTube, 1 Vimeo)
- Different positions and types

### 6. Bulk Pricing
- 5 pricing tiers (1-4, 5-9, 10-24, 25-49, 50+)
- Discount percentages for bulk orders

### 7. Product Analytics
- Views, clicks, add to cart, purchases
- Wishlist counts
- Conversion rates

### 8. Related Products
- Similar products
- Frequently bought together
- Customers also viewed
- Recently viewed

### 9. SEO Meta Data
- Meta titles, descriptions, keywords
- Open Graph tags

## Verification Queries

After running the SQL, verify data was inserted:

```sql
-- Check tags
SELECT COUNT(*) as tag_count FROM product_tags;
SELECT * FROM product_tags LIMIT 5;

-- Check variations
SELECT COUNT(*) as variation_count FROM product_variations;
SELECT * FROM product_variations WHERE product_id = 412;

-- Check reviews
SELECT COUNT(*) as review_count FROM product_reviews;
SELECT COUNT(*) as approved_reviews FROM product_reviews WHERE is_approved = 1;
SELECT COUNT(*) as pending_reviews FROM product_reviews WHERE is_approved = 0;

-- Check videos
SELECT COUNT(*) as video_count FROM product_videos;
SELECT * FROM product_videos WHERE product_id = 412;

-- Check bulk pricing
SELECT COUNT(*) as bulk_pricing_count FROM bulk_pricing;
SELECT * FROM bulk_pricing WHERE product_id = 412 ORDER BY min_quantity;

-- Check analytics
SELECT COUNT(*) as analytics_count FROM product_analytics;
SELECT * FROM product_analytics WHERE product_id = 412;

-- Check related products
SELECT COUNT(*) as related_count FROM related_products;
SELECT * FROM related_products WHERE product_id = 412;

-- Check product tags pivot
SELECT COUNT(*) as pivot_count FROM product_tag_pivot;
SELECT pt.name, COUNT(*) as product_count 
FROM product_tag_pivot ptp
JOIN product_tags pt ON ptp.product_tag_id = pt.id
GROUP BY pt.id, pt.name;
```

## Testing APIs

After inserting demo data, test these endpoints:

### Product Details
```bash
GET http://127.0.0.1:8000/api/products/412
```
Should return:
- Full product details
- Tags array
- Variations (if any)
- Reviews summary
- Analytics
- Related products

### Product Reviews
```bash
GET http://127.0.0.1:8000/api/products/412/reviews
```
Should return:
- List of approved reviews
- Pagination info

### Pending Reviews (Admin)
```bash
GET http://127.0.0.1:8000/api/reviews/pending
```
Should return:
- List of pending reviews (2 for product 412)

### Similar Products
```bash
GET http://127.0.0.1:8000/api/products/412/similar
```
Should return:
- Related products with relation_type = 'similar'

### Recommendations
```bash
GET http://127.0.0.1:8000/api/products/412/recommendations
```
Should return:
- Frequently bought together products

## Troubleshooting

### Error: Foreign key constraint fails
- **Solution**: Make sure the product_id, category_id, brand_id, etc. exist in their respective tables

### Error: Duplicate entry
- **Solution**: The data might already exist. Use `INSERT IGNORE` or `ON DUPLICATE KEY UPDATE`

### No data showing in API
- **Solution**: 
  1. Check if product ID exists
  2. Verify relationships are set up correctly
  3. Check if `is_approved = 1` for reviews
  4. Verify pivot table name matches (product_tag_pivot vs product_product_tag)

### Pivot table name mismatch
- **Solution**: Check your migration file. The table might be named:
  - `product_tag_pivot` (as per migration)
  - `product_product_tag` (Laravel convention)
  
  Update the SQL file accordingly.

## Customization

### Add More Products
```sql
-- Update more products
UPDATE products 
SET short_description = 'Your description',
    visibility = 'visible',
    type = 'simple'
WHERE id IN (your_product_ids);
```

### Add More Reviews
```sql
INSERT INTO product_reviews (
    product_id, reviewer_name, reviewer_email, rating, title, review,
    is_verified_purchase, is_recommended, is_approved, created_at, updated_at
) VALUES
(your_product_id, 'Name', 'email@example.com', 5, 'Title', 'Review text', 1, 1, 1, NOW(), NOW());
```

### Add More Variations
```sql
INSERT INTO product_variations (
    product_id, sku, name, regular_price, stock_quantity, created_at, updated_at
) VALUES
(your_product_id, 'SKU-001', 'Variation Name', 100.00, 50, NOW(), NOW());
```

## Notes

- All timestamps use `NOW()` - adjust if you want specific dates
- Ratings are between 1-5 stars
- Review approval status: `1` = approved, `0` = pending
- Product status: `1` = active, `0` = inactive
- All prices are in BDT (৳) by default

