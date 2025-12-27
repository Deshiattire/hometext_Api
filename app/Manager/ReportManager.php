<?php
namespace App\Manager;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportManager
{

    public int $total_product = 0;

    public const LOW_STOCK_ALERT = 5;
    public int $total_stock = 0;
    public int $low_stock = 0;
    public int $buying_stock_price = 0;
    public int $saleing_stock_price = 0;
    public int $possible_profit = 0;
    public int $total_sale = 0;
    public int $total_sale_today = 0;
    public int $total_purchase = 0;
    public int $total_purchase_today = 0;
    private bool $is_admin = false;
    private bool $sales_admin_id;
    private Collection $products;
    private Collection $orders;

    function  __construct($auth)
    {
        if ($auth->guard('admin')->check()){
            $this->is_admin = true;
        }
        
        $user = $auth->user();
        if ($user) {
            $this->sales_admin_id = $user->id;
        } else {
            // Handle case when user is not authenticated
            $this->sales_admin_id = 0;
        }
        
        $this->getProducts();
        $this->getOrders();
        $this->setTotalProduct();
        $this->calculateStock();
        $this->findLowStock();
        $this->calculateBuyingStockPrice();
        $this->calculateSaleingStockPrice();
        $this->calculatePossibleProfit();
        $this->calculateTotalSale();
        $this->calculateTotalSaleToday();
        $this->calculateTotalPurchase();
        $this->calculatePurchaseToday();
    }

    private function getProducts()
    {
     $this->products = (new Product)->getAllProduct();

}
    private function setTotalProduct()
    {
        $this->total_product = count($this->products);
    }
    private function calculateStock()
    {
        $this->total_stock = $this->products->sum('stock');
    }
    private function findLowStock()
    {
        $lowStockProducts = $this->products->where('stock', '<=', self::LOW_STOCK_ALERT);
        $this->low_stock = $lowStockProducts->count();
    }
    private function calculateBuyingStockPrice()
    {
        foreach ($this->products as $product){
            $this->buying_stock_price+= ($product->cost * $product->stock);
        }
    }
    private function calculateSaleingStockPrice()
    {
        foreach ($this->products as $product){
            $this->saleing_stock_price+= ($product->price * $product->stock);
        }
    }
    private function calculateTotalPurchase()
    {
        $this->total_purchase = $this->buying_stock_price;
    }
    private function calculatePurchaseToday()
    {
        $product_buy_today = $this->products->whereBetween('created_at',[Carbon::today()->startOfDay(), Carbon::today()->endOfDay()]);
        foreach ($product_buy_today as $product){
            $this->total_purchase_today += ($product->cost * $product->stock);
        }
    }
    private function calculatePossibleProfit()
    {
            $this->possible_profit+= $this->saleing_stock_price - $this->buying_stock_price;
     }

    private function getOrders(){
        $this->orders = (new Order())->getAllOrdersForReport($this->is_admin, $this->sales_admin_id);
    }
     private function calculateTotalSale()
     {
        $this->total_sale = $this->orders->sum('total');
     }
    private function calculateTotalSaleToday()
    {
        $this->total_sale_today = $this->orders->whereBetween('created_at', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->sum('total');
    }
}

