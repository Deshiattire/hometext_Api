<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\BannerSliderController;
use App\Http\Controllers\ChildSubCategoryController;
use App\Http\Controllers\CsvController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ProductPhotoController;
use App\Http\Controllers\SalesManagerController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\web_api\CheckOutController;
use App\Http\Controllers\web_api\EcomUserController;
use App\Http\Controllers\web_api\OrderDetailsController;
use App\Http\Controllers\web_api\PaymentController;
use App\Http\Controllers\web_api\WishListController;
use App\Http\Controllers\ProductTransferController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/testing', function () {
    return 'Hello World';
});

//post csv in folder
Route::post('/save-csv', [CsvController::class, 'saveCsv']);

// Route::get('test', [scriptManager::class, 'getCountry']);
Route::post('login', [AuthController::class, 'login'])->name('login');

//==============Routes for Product [Working]==============
Route::get('product/menu', [ProductController::class, 'ProductMenu']);
Route::get('products', [ProductController::class, 'index']);

// Product filter endpoints (optimized with caching) - Must come before {id} route
Route::get('products/featured', [ProductController::class, 'featured']);
Route::get('products/new-arrivals', [ProductController::class, 'newArrivals']);
Route::get('products/trending', [ProductController::class, 'trending']);
Route::get('products/bestsellers', [ProductController::class, 'bestsellers']);
Route::get('products/on-sale', [ProductController::class, 'onSale']);
Route::get('products/category/{categoryId}', [ProductController::class, 'byCategory']);
Route::get('products/brand/{brandId}', [ProductController::class, 'byBrand']);

// Product detail and related endpoints - Must come after filter routes
Route::get('products/{id}/similar', [ProductController::class, 'similar']);
Route::get('products/{id}/recommendations', [ProductController::class, 'recommendations']);

// Slug-based product lookup (for SEO and public URLs)
Route::get('products/slug/{slug}', [ProductController::class, 'showBySlug'])->name('products.show.slug');

// ID-based product lookup (for internal/admin use)
Route::get('products/{id}', [ProductController::class, 'show'])->name('products.show');

// Legacy routes (keeping for backward compatibility - can be removed later)
Route::get('products-details-web/{id}', [ProductController::class, 'productsdetails']);

//==============Routes for Product Reviews [Working]==============
Route::get('products/{productId}/reviews', [ProductReviewController::class, 'getByProduct']);
Route::post('store-review', [ProductReviewController::class, 'store']);
Route::get('get-review/{id}', [ProductReviewController::class, 'show']);
Route::put('update-review/{id}', [ProductReviewController::class, 'update']);
Route::delete('delete-review/{id}', [ProductReviewController::class, 'destroy']);

/** ===============Admin Routes for Reviews =============== */
Route::group(['middleware' => ['auth:sanctum', 'auth:admin']], function () {
    Route::get('reviews/pending', [ProductReviewController::class, 'getPending']);
    Route::post('reviews/{id}/approve', [ProductReviewController::class, 'approve']);
    Route::post('reviews/{id}/reject', [ProductReviewController::class, 'reject']);
    Route::post('reviews/bulk-approve', [ProductReviewController::class, 'bulkApprove']);
    Route::post('reviews/bulk-reject', [ProductReviewController::class, 'bulkReject']);
});


//==============Routes for Banner/Slider [Working]==============
Route::get('hero-banners', [BannerSliderController::class, 'index']);

//==============Routes for Division [Working]==============
Route::get('divisions', [DivisionController::class, 'index']);
Route::get('district/{division_id}', [DistrictController::class, 'index']);
Route::get('area/{district_id}', [AreaController::class, 'index']);



/** ===============Admin Routes =============== */
Route::group(['middleware' => ['auth:sanctum', 'auth:admin']], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('get-attribute-list', [AttributeController::class, 'get_attribute_list']);
    Route::get('get-supplier-list', [SupplierController::class, 'get_provider_list']);
    Route::get('get-country-list', [CountryController::class, 'get_country_list']);
    Route::get('get-brand-list', [BrandController::class, 'get_brand_list']);
    Route::get('get-category-list', [CategoryController::class, 'get_category_list']);
    Route::get('get-shop-list', [ShopController::class, 'get_shop_list']);
    Route::apiResource('product', ProductController::class);
    Route::get('get-product-list-for-bar-code', [ProductController::class, 'get_product_list_for_bar_code']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::get('get-sub-category-list/{category_id}', [SubCategoryController::class, 'get_sub_category_list']);
    Route::post('product-photo-upload/{id}', [ProductPhotoController::class, 'store']);
    Route::group(['prefix' => 'transfers'], function () {
        Route::post('/', [ProductTransferController::class, 'store']); // Create a new transfer
        Route::get('/', [ProductTransferController::class, 'index']);   // Retrieve a list of transfers
        Route::get('/{transfer}', [ProductTransferController::class, 'show']); // Retrieve a specific transfer
        Route::put('/{transfer}/approve', [ProductTransferController::class, 'approve']); // Approve a transfer
        Route::put('/{transfer}/reject', [ProductTransferController::class, 'reject']); // Reject a transfer
    });
    Route::apiResource('category', CategoryController::class);
    Route::apiResource('sub-category', SubCategoryController::class);
    Route::apiResource('brand', BrandController::class);
    Route::apiResource('supplier', SupplierController::class);
    Route::apiResource('attribute', AttributeController::class);
    Route::apiResource('attribute-value', AttributeValueController::class);
    Route::apiResource('photo', ProductPhotoController::class);
    Route::apiResource('shop', ShopController::class);
    Route::apiResource('customer', CustomerController::class);
    Route::apiResource('order', OrderController::class);
    Route::get('get-payment-methods', [PaymentMethodController::class, 'index']);
    // Removed duplicate products/{id} route - using public route instead
});

/** ===============Sales Manager Routes =============== */
Route::group(['middleware' => ['auth:sanctum', 'auth:sales_manager']], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::apiResource('sales-manager', SalesManagerController::class);
    Route::post('product/{id}/duplicate', [ProductController::class, 'duplicate']);
    Route::apiResource('product', ProductController::class);
    Route::get('get-product-columns', [ProductController::class, 'get_product_columns']);
    Route::get('get-product-list-for-bar-code', [ProductController::class, 'get_product_list_for_bar_code']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::get('get-attribute-list', [AttributeController::class, 'get_attribute_list']);
    Route::get('get-supplier-list', [SupplierController::class, 'get_provider_list']);
    Route::get('get-country-list', [CountryController::class, 'get_country_list']);
    Route::get('get-brand-list', [BrandController::class, 'get_brand_list']);
    Route::get('get-category-list', [CategoryController::class, 'get_category_list']);
    Route::get('get-sub-category-list', [SubCategoryController::class, 'get_sub_category_list_fc']);
    Route::get('get-child-sub-category-list', [ChildSubCategoryController::class, 'get_child_sub_category_list']);
    Route::get('get-shop-list', [ShopController::class, 'get_shop_list']);
    Route::get('get-sub-category-list/{category_id}', [SubCategoryController::class, 'get_sub_category_list']);
    Route::get('get-child-sub-category-list/{category_id}', [ChildSubCategoryController::class, 'get_child_sub_category_list']);
    Route::post('product-photo-upload/{id}', [ProductPhotoController::class, 'store']);
    Route::group(['prefix' => 'transfers'], function () {
        Route::post('/', [ProductTransferController::class, 'store']); // Create a new transfer
        Route::get('/', [ProductTransferController::class, 'index']);   // Retrieve a list of transfers
        Route::get('/{transfer}', [ProductTransferController::class, 'show']); // Retrieve a specific transfer
        Route::put('/{transfer}/approve', [ProductTransferController::class, 'approve']); // Approve a transfer
        Route::put('/{transfer}/reject', [ProductTransferController::class, 'reject']); // Reject a transfer
    });
    Route::apiResource('category', CategoryController::class);
    Route::apiResource('sub-category', SubCategoryController::class);
    Route::apiResource('child-sub-category', ChildSubCategoryController::class);
    Route::apiResource('brand', BrandController::class);
    Route::apiResource('formula', FormulaController::class);
    Route::apiResource('supplier', SupplierController::class);
    Route::apiResource('attribute', AttributeController::class);
    Route::apiResource('attribute-value', AttributeValueController::class);
    Route::apiResource('photo', ProductPhotoController::class);
    Route::apiResource('shop', ShopController::class);
    Route::apiResource('customer', CustomerController::class);
    Route::apiResource('order', OrderController::class);
    Route::get('get-payment-methods', [PaymentMethodController::class, 'index']);
    Route::get('get-reports', [ReportController::class, 'index']);
    Route::get('get-add-product-data', [ProductController::class, 'get_add_product_data']);
    // Removed duplicate products/{id} route - using public route instead


});

Route::get('product/duplicate/@_jkL_qwErtOp~_lis/{id}', [ProductController::class, 'duplicate']);

//==============Routes for Checkout [Working]==============
Route::post('check-out', [CheckOutController::class, 'checkout']);
Route::post('check-out-logein-user', [CheckOutController::class, 'checkoutbyloginuser']);

//==============Routes for Payment [Working]==============
Route::get('get-payment-details', [PaymentController::class, 'getpaymentdetails']);
Route::post('payment-success', [PaymentController::class, 'paymentsuccess']);
Route::get('payment-cancel', [PaymentController::class, 'paymentcancel']);
Route::get('payment-fail', [PaymentController::class, 'paymentfail']);
Route::post('check-out', [CheckOutController::class, 'checkout']);
Route::post('check-out-logein-user', [CheckOutController::class, 'checkoutbyloginuser']);
// Route::get('my-order', [CheckOutController::class, 'myorder']);
Route::get('get-payment-details', [PaymentController::class, 'getpaymentdetails']);
Route::post('payment-success', [PaymentController::class, 'paymentsuccess']);
Route::get('payment-cancel', [PaymentController::class, 'paymentcancel']);
Route::get('payment-fail', [PaymentController::class, 'paymentfail']);

//==============Routes for Order Details [Working]==============
Route::get('my-order', [OrderDetailsController::class, 'myorder']);


//==============Routes for User [Working]==============
Route::post('user-registration', [EcomUserController::class, 'registration']);
Route::post('user-login', [EcomUserController::class, 'UserLogin']);
// Route::post('user-signup', [EcomUserController::class, 'signup']);
Route::get('my-profile', [EcomUserController::class, 'myprofile']);
Route::post('my-profile-update', [EcomUserController::class, 'updateprofile']);
// Route::post('user-signout',[EcomUserController::class,'signout']);

//==============Routes for Wishlist [Working]==============
Route::post('wish-list', [WishListController::class, 'wishlist']);
Route::post('get-wish-list', [WishListController::class, 'getWishlist']);
Route::post('delete-wish-list', [WishListController::class, 'deleteWishlist']);


//==============Routes for Payment Gateway [Working]==============
Route::get('get-token', [PaymentGatewayController::class, 'getToken']);

