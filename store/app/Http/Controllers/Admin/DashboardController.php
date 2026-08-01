<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\VideoTestimonial;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin dashboard — landing setelah login. Quick stats untuk M2.
     */
    public function index(): View
    {
        $stats = [
            'orders_pending' => Order::where('status', 'pending')->count(),
            'orders_partial_paid' => Order::where('status', 'partial_paid')->count(),
            'orders_paid' => Order::where('status', 'paid')->count(),
            'orders_total' => Order::count(),
            'payments_to_verify' => OrderPayment::where('status', 'pending')->count(),
            'products_active' => Product::where('status', 'active')->count(),
            'video_testimonials_homepage' => VideoTestimonial::visibleOnHomepage()->count(),
        ];

        $recentOrders = Order::latest()->limit(5)->get();

        $revenueTotal = Order::whereIn('status', ['paid', 'shipped', 'completed'])->sum('total');
        $ordersThisMonth = Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        $months = [];
        $counts = [];
        for ($i = 0; $i < 6; $i++) {
            $date = Carbon::now()->startOfMonth()->subMonths(5 - $i);
            $months[] = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][$date->month];
            $counts[] = Order::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count();
        }
        $chartData = [
            'categories' => $months,
            'series' => [['name' => 'Pesanan', 'data' => $counts]],
        ];

        return view('admin.dashboard', compact('stats', 'recentOrders', 'revenueTotal', 'ordersThisMonth', 'chartData'));
    }
}
