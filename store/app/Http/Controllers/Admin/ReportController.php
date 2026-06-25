<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Services\Reporting\SalesReportService;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly SalesReportService $reports) {}

    /**
     * Halaman utama Laporan Penjualan — summary + charts + tabel.
     */
    public function index(ReportFilterRequest $request): View
    {
        $from = $request->dateFrom();
        $to = $request->dateTo();

        $rawSummary = $this->reports->revenueSummary($from, $to);
        $dailyRevenue = $this->reports->dailyRevenue($from, $to);
        $topProducts = $this->reports->topProducts($from, $to, 10);
        $statusBreakdown = $this->reports->orderStatusBreakdown($from, $to);
        $rawPaymentSummary = $this->reports->paymentSummary($from, $to);

        // Map service output ke struktur yang dipakai view.
        $summary = [
            'total_revenue' => $rawSummary['revenue'],
            'transactions_count' => $rawSummary['verified_payments_count'],
            'avg_transaction' => $rawSummary['verified_payments_count'] > 0
                ? $rawSummary['revenue'] / $rawSummary['verified_payments_count']
                : 0.0,
            'orders_count' => $rawSummary['orders_count'],
        ];

        // Transform payment summary dari [status => [count, total]]
        // ke ['counts' => [status => count], 'totals' => [status => total]]
        $paymentSummary = [
            'counts' => [
                'verified' => $rawPaymentSummary['verified']['count'],
                'pending' => $rawPaymentSummary['pending']['count'],
                'rejected' => $rawPaymentSummary['rejected']['count'],
            ],
            'totals' => [
                'verified' => $rawPaymentSummary['verified']['total'],
                'pending' => $rawPaymentSummary['pending']['total'],
                'rejected' => $rawPaymentSummary['rejected']['total'],
            ],
        ];

        return view('admin.reports.index', [
            'summary' => $summary,
            'dailyRevenue' => $dailyRevenue,
            'topProducts' => $topProducts,
            'orderStatusBreakdown' => $statusBreakdown,
            'paymentSummary' => $paymentSummary,
            'filters' => [
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
        ]);
    }

    /**
     * Export laporan penjualan ke CSV (StreamedResponse).
     */
    public function export(ReportFilterRequest $request)
    {
        $from = $request->dateFrom();
        $to = $request->dateTo();

        $filename = 'laporan-penjualan-'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];

        $callback = function () use ($from, $to) {
            $out = fopen('php://output', 'w');

            // BOM for Excel auto-detect UTF-8
            fprintf($out, "\xEF\xBB\xBF");

            foreach ($this->reports->csvRows($from, $to) as $row) {
                fwrite($out, $row."\n");
            }

            fclose($out);
        };

        return Response::stream($callback, 200, $headers);
    }
}
