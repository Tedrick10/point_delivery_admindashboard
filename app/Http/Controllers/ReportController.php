<?php

namespace App\Http\Controllers;

use App\Exports\AdminReportExport;
use App\Exports\DeliverymanReportExport;
use App\Exports\OrderReportExport;
use App\Exports\ReportOfCityExport;
use App\Exports\ReportOfCountryExport;
use App\Exports\ReportOfUserExport;
use App\Exports\ReportOfDeliverymanExport;
use App\Models\City;
use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function adminEarning(Request $request)
    {
        $pageTitle = __('message.list_form_title_report', ['form' => __('message.admin_earning_report')]);

        $auth_user = authSession();

        $params = [ 'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date') ];
        $params["datatable_botton_style"] = true;
        $userIds = User::where('user_type', ['client', 'delivery_man'])->pluck('id')->toArray();

        $ordersQuery = Order::where(function ($query) use ($userIds) {
            $query->whereIn('client_id', $userIds)
                ->orWhereIn('delivery_man_id', $userIds);
        })
            ->whereHas('payment', function ($query) {
                $query->whereNotNull('total_amount');
            })
            ->with(['payment' => function ($query) {
                $query->whereNotNull('total_amount');
            }])
            ->with(['city' => function ($query) {
                $query->whereNotNull('commission_type');
            }])
            ->where('status', 'completed');

        if ($params['from_date']) {
            $ordersQuery->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($params['to_date']) {
            $ordersQuery->whereDate('created_at', '<=', $params['to_date']);
        }
        $orders = $ordersQuery->get();
        $totalAdminSum = $orders->sum(function ($order) {
            return $order->payment->admin_commission ?? 0;
        });
        $totaldeliverymanAmountSum = $orders->sum(function ($order) {
            return $order->payment->delivery_man_commission ?? 0;
        });
        $totalAmountorder = $orders->sum(function ($order) {
            return $order->payment->total_amount ?? 0;
        });
        return view('report.adminreport', compact('pageTitle', 'auth_user', 'totaldeliverymanAmountSum', 'totalAdminSum', 'totalAmountorder', 'params', 'orders'));
    }
    public function deliverymanEarning(Request $request)
    {
        $pageTitle = __('message.list_form_title_report', ['form' => __('message.deliveryman_earning_report')]);
        $auth_user = auth()->user();

        $params = [ 'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date') ];
        $params["datatable_botton_style"] = true;

        $deliverymenIds = User::where('user_type', 'delivery_man')->pluck('id')->toArray();

        $ordersQuery = Order::whereIn('delivery_man_id', $deliverymenIds)
            ->whereHas('payment', function ($query) {
                $query->whereNotNull('delivery_man_commission');
            })
            ->with(['payment' => function ($query) {
                $query->whereNotNull('delivery_man_commission');
            }])->where('status', 'completed');

        if ($params['from_date']) {
            $ordersQuery->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($params['to_date']) {
            $ordersQuery->whereDate('created_at', '<=', $params['to_date']);
        }

        $orders = $ordersQuery->get();
        $totaldeliverymanAmountSum = $orders->sum(function ($order) {
            return $order->payment->delivery_man_commission ?? 0;
        });
        $totalAdminSum = $orders->sum(function ($order) {
            return $order->payment->admin_commission ?? 0;
        });
        $totalAmountorder = $orders->sum(function ($order) {
            return $order->payment->total_amount ?? 0;
        });
        return view('report.deliverymanreport', compact('pageTitle', 'auth_user', 'totaldeliverymanAmountSum', 'totalAdminSum', 'totalAmountorder', 'orders', 'params'));
    }
    public function reportOfDeliveryman(Request $request)
    {
        $pageTitle = __('message.list_form_title_report', ['form' => __('message.delivery_man_wise_report')]);
        $client = Auth::user();

        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'delivery_man_id' => $request->input('delivery_man_id'),
        ];
        $params["datatable_botton_style"] = true;
        $ordersQuery = Order::where('delivery_man_id', $params['delivery_man_id'])->where('status', 'completed');

        if ($request->filled('delivery_man_id')) {
            $ordersQuery->where('delivery_man_id', $request->input('delivery_man_id'));
        }
        if ($request->filled('from_date')) {
            $ordersQuery->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($request->filled('to_date')) {
            $ordersQuery->whereDate('created_at', '<=', $params['to_date']);
        }
        $orders = $ordersQuery->get();
        $totaldeliverymanSum = $orders->sum(function ($order) {
            return $order->payment->delivery_man_commission ?? 0;
        });
        $totalAdminSum = $orders->sum(function ($order) {
            return $order->payment->admin_commission ?? 0;
        });
        $totalAmountorder = $orders->sum(function ($order) {
            return $order->payment->total_amount ?? 0;
        });
        $selectedDeliveryman = User::where('id', $params['delivery_man_id'])->pluck('name', 'id');

        return view('report.reportofdeliveryman', compact('pageTitle', 'orders', 'selectedDeliveryman', 'params', 'totaldeliverymanSum', 'totalAdminSum', 'totalAmountorder'));
    }

    public function reportOfuser(Request $request)
    {
        $pageTitle = __('message.list_form_title_report', ['form' => __('message.user_wise_report')]);
        $client = Auth::user();

        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'client_id' => $request->input('client_id'),
        ];

        $ordersQuery = Order::where('client_id', $params['client_id'])->where('status', 'completed');

        if ($request->filled('client_id')) {
            $ordersQuery->where('client_id', $request->input('client_id'));
        }
        if ($request->filled('from_date')) {
            $ordersQuery->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($request->filled('to_date')) {
            $ordersQuery->whereDate('created_at', '<=', $params['to_date']);
        }

        $orders = $ordersQuery->get();
        $totaldeliverymanSum = $orders->sum(function ($order) {
            return $order->payment->delivery_man_commission ?? 0;
        });
        $totalAdminSum = $orders->sum(function ($order) {
            return $order->payment->admin_commission ?? 0;
        });
        $totalAmountorder = $orders->sum(function ($order) {
            return $order->payment->total_amount ?? 0;
        });
        $selectedClients = User::where('id', $params['client_id'])->pluck('name', 'id');

        return view('report.reportofuser', compact('pageTitle', 'params', 'selectedClients', 'orders', 'totaldeliverymanSum', 'totalAdminSum', 'totalAmountorder', 'selectedClients'));
    }

    public function reportOfCity(Request $request)
    {
        $pageTitle = __('message.list_form_title_report', ['form' => __('message.city_wise_report')]);
        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'city_id' => $request->input('city_id'),
        ];

        $ordersQuery = Order::where('city_id', $params['city_id'])->where('status', 'completed');
        if ($request->filled('city_id')) {
            $ordersQuery->where('city_id', $request->input('city_id'));
        }
        if ($request->filled('from_date')) {
            $ordersQuery->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($request->filled('to_date')) {
            $ordersQuery->whereDate('created_at', '<=', $params['to_date']);
        }
        $orders = $ordersQuery->get();
        $totaldeliverymanSum = $orders->sum(function ($order) {
            return $order->payment->delivery_man_commission ?? 0;
        });
        $totalAdminSum = $orders->sum(function ($order) {
            return $order->payment->admin_commission ?? 0;
        });
        $totalAmountorder = $orders->sum(function ($order) {
            return $order->payment->total_amount ?? 0;
        });
        $selectedCity = City::where('id', $params['city_id'])->pluck('name', 'id');

        return view('report.reportofcity', compact('pageTitle', 'orders', 'selectedCity', 'params', 'totaldeliverymanSum', 'totalAdminSum', 'totalAmountorder'));
    }
    public function reportOfCountry(Request $request)
    {
        $pageTitle = __('message.list_form_title_report', ['form' => __('message.country_wise_report')]);
        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'country_id' => $request->input('country_id'),
        ];

        $ordersQuery = Order::where('country_id', $params['country_id'])->where('status', 'completed');

        if ($request->filled('country_id')) {
            $ordersQuery->where('country_id', $request->input('country_id'));
        }
        if ($request->filled('from_date')) {
            $ordersQuery->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($request->filled('to_date')) {
            $ordersQuery->whereDate('created_at', '<=', $params['to_date']);
        }
        $orders = $ordersQuery->get();
        $totaldeliverymanSum = $orders->sum(function ($order) {
            return $order->payment->delivery_man_commission ?? 0;
        });
        $totalAdminSum = $orders->sum(function ($order) {
            return $order->payment->admin_commission ?? 0;
        });
        $totalAmountorder = $orders->sum(function ($order) {
            return $order->payment->total_amount ?? 0;
        });
        $selectedCountry = Country::where('id', $params['country_id'])->pluck('name', 'id');

        return view('report.reportofcountry', compact('pageTitle', 'orders', 'selectedCountry', 'params', 'totalAdminSum', 'totaldeliverymanSum', 'totalAmountorder'));
    }

    public function orderreport(Request $request)
    {
        $pageTitle = __('message.list_form_title_report', ['form' => __('message.order_report')]);
        $auth_user = auth()->user();

        $params = [ 'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date') ];
        $params["datatable_botton_style"] = true;

        $userIds = User::where('user_type', ['client', 'delivery_man'])->pluck('id')->toArray();

        $ordersQuery = Order::where(function ($query) use ($userIds) {
            $query->whereIn('client_id', $userIds)->orWhereIn('delivery_man_id', $userIds);
        })
            ->whereHas('payment', function ($query) {
                $query->whereNotNull('total_amount');
            })
            ->with(['payment' => function ($query) {
                $query->whereNotNull('total_amount');
            }])
            ->with(['city' => function ($query) {
                $query->whereNotNull('commission_type');
            }])->where('status', 'completed');

        if ($params['from_date']) {
            $ordersQuery->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($params['to_date']) {
            $ordersQuery->whereDate('created_at', '<=', $params['to_date']);
        }

        $orders = $ordersQuery->get();
        $totaldeliverymanAmountSum = $orders->sum(function ($order) {
            return $order->payment->delivery_man_commission ?? 0;
        });
        $totalAdminSum = $orders->sum(function ($order) {
            return $order->payment->admin_commission ?? 0;
        });
        $totalAmountorder = $orders->sum(function ($order) {
            return $order->payment->total_amount ?? 0;
        });
        return view('report.orderreport', compact('pageTitle', 'auth_user', 'totaldeliverymanAmountSum', 'totalAdminSum', 'totalAmountorder', 'orders', 'params'));
    }

    public function downloadAdminEarning(Request $request, $fileType)
    {
        $selectedColumns = $request->input('columns', []);
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');

        $start = $startDate ? Carbon::parse($startDate)->format('d-m-Y') : '';
        $end = $endDate ? Carbon::parse($endDate)->format('d-m-Y') : '';

        $filename = ($startDate && $endDate) ? "admin-earning-report_{$start}_to_{$end}.{$fileType}" : "admin-earning-report_{$start}.{$fileType}";
        $export = new AdminReportExport($request, $selectedColumns);
        switch ($fileType) {
            case 'xlsx':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
            case 'xls':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLS);
            case 'ods':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::ODS);
            case 'csv':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'html':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::HTML);
            default:
                return back()->withErrors(['file_type' => 'Unsupported file type selected.']);
        }
    }

    public function downloadDeliverymanEarning(Request $request,$fileType)
    {
        $selectedColumns = $request->input('columns', []);
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');

        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');

        $filename = ($startDate && $endDate) ? "Deliveryman-earning-report_{$start}_to_{$end}.{$fileType}" : "Deliveryman-earning-report_{$start}.{$fileType}";
        $export = new DeliverymanReportExport($request, $selectedColumns);
        switch ($fileType) {
            case 'xlsx':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
            case 'xls':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLS);
            case 'ods':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::ODS);
            case 'csv':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'html':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::HTML);
            default:
                return back()->withErrors(['file_type' => 'Unsupported file type selected.']);
        }
    }

    public function reportOfDeliverymanExcel(Request $request,$fileType)
    {
        $selectedColumns = $request->input('columns', []);
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');
        $deliveryManId = $request->input('delivery_man_id');
        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');

        $deliveryMan = User::where('id', $deliveryManId)->first();
        $deliveryManName = $deliveryMan ? $deliveryMan->name : '-';

        $filename = ($startDate && $endDate) ? "report_of_{$deliveryManName}_{$start}_to_{$end}.{$fileType}" : "report_of_{$deliveryManName}_{$start}.{$fileType}";
        $export = new ReportOfDeliverymanExport($request, $selectedColumns);
        switch ($fileType) {
            case 'xlsx':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
            case 'xls':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLS);
            case 'ods':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::ODS);
            case 'csv':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'html':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::HTML);
            default:
                return back()->withErrors(['file_type' => 'Unsupported file type selected.']);
        }
    }

    public function reportOfuserExcel(Request $request,$fileType)
    {
        $selectedColumns = $request->input('columns', []);
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');
        $client_id = $request->input('client_id');

        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');

        $clientman = User::where('id', $client_id)->first();
        $clientmanName = $clientman ? $clientman->name : '-';

        $filename = ($startDate && $endDate) ? "report_of_{$clientmanName}_{$start}_to_{$end}.{$fileType}" : "report_of_{$clientmanName}_{$start}.{$fileType}";
        $export = new ReportOfUserExport($request, $selectedColumns);
        switch ($fileType) {
            case 'xlsx':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
            case 'xls':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLS);
            case 'ods':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::ODS);
            case 'csv':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'html':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::HTML);
            default:
                return back()->withErrors(['file_type' => 'Unsupported file type selected.']);
        }
    }

    public function reportOfcityExcel(Request $request,$fileType)
    {
        $selectedColumns = $request->input('columns', []);
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');
        $city_id = $request->input('city_id');

        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');

        $city = City::where('id', $city_id)->first();
        $cityName = $city ? $city->name : '-';

        $filename = ($startDate && $endDate) ? "report_of_{$cityName}_{$start}_to_{$end}.{$fileType}" : "report_of_{$cityName}_{$start}.{$fileType}";
        $export = new ReportOfCityExport($request, $selectedColumns);
        switch ($fileType) {
            case 'xlsx':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
            case 'xls':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLS);
            case 'ods':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::ODS);
            case 'csv':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'html':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::HTML);
            default:
                return back()->withErrors(['file_type' => 'Unsupported file type selected.']);
        }
    }

    public function reportOfcountryExcel(Request $request,$fileType)
    {
        $selectedColumns = $request->input('columns', []);
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');
        $country_id = $request->input('country_id');

        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');

        $country = Country::where('id', $country_id)->first();
        $countryName = $country ? $country->name : '-';

        $filename = ($startDate && $endDate) ? "report_of_{$countryName}_{$start}_to_{$end}.{$fileType}" : "report_of_{$countryName}_{$start}.{$fileType}";
        $export = new ReportOfCountryExport($request, $selectedColumns);
        switch ($fileType) {
            case 'xlsx':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
            case 'xls':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLS);
            case 'ods':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::ODS);
            case 'csv':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'html':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::HTML);
            default:
                return back()->withErrors(['file_type' => 'Unsupported file type selected.']);
        }
    }

    public function downloadOrderExcel(Request $request,$fileType)
    {
        $selectedColumns = $request->input('columns', []);
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');

        $start = Carbon::parse($startDate)->format('d-m-Y');
        $end = Carbon::parse($endDate)->format('d-m-Y');

        $filename = ($startDate && $endDate) ? "Order-report_{$start}_to_{$end}.{$fileType}" : "Order-report_{$start}.{$fileType}";
        $export = new OrderReportExport($request, $selectedColumns);
        switch ($fileType) {
            case 'xlsx':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLSX);
            case 'xls':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::XLS);
            case 'ods':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::ODS);
            case 'csv':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
            case 'html':
                return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::HTML);
            default:
                return back()->withErrors(['file_type' => 'Unsupported file type selected.']);
        }
    }

    public function downloadAdminEarningPdf(Request $request)
    {
        $export = new AdminReportExport($request, $request->input('columns', []));

        $mappedData = $export->collection()->map([$export, 'map']);
        $headings = $export->headings('pdf')[0];

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $to   = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from To Date: $to";
            $filenameDatePart = "_from_{$from}_to_{$to}";
        } elseif ($fromDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from";
            $filenameDatePart = "_from_{$from}";
        } elseif ($toDate) {
            $to = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "To Date: $to";
            $filenameDatePart = "_to_{$to}";
        }

        $pdf = Pdf::loadView('report.order-report-pdf', [
            'titleName' => __('message.admin_earning_report'),
            'mappedData' => $mappedData,
            'headings' => $headings,
            'dateFilterText' => $dateFilterText,
            'totalAmountSum' => getPriceFormat($export->getTotalAmountSum()),
            'totalAmountDeliveryman' => getPriceFormat($export->getTotaldeliverymanAmount()),
            'totalAmountOrder' => getPriceFormat($export->getTotalAmountorder()),
        ])->setPaper('a4', 'landscape');

        return $pdf->download( 'admin-earning-report' . $filenameDatePart . '.pdf' );
    }
    public function downloadDeliverymanEarningPdf(Request $request)
    {
        $export = new DeliverymanReportExport(
            $request,
            $request->input('columns', [])
        );

        $mappedData = $export->collection()->map([$export, 'map']);
        $headings = $export->headings('pdf')[0];

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $to   = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from To Date: $to";
            $filenameDatePart = "_from_{$from}_to_{$to}";
        } elseif ($fromDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from";
            $filenameDatePart = "_from_{$from}";
        } elseif ($toDate) {
            $to = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "To Date: $to";
            $filenameDatePart = "_to_{$to}";
        }

        $pdf = Pdf::loadView('report.order-report-pdf', [
            'titleName' => __('message.deliveryman_earning_report'),
            'mappedData' => $mappedData,
            'headings' => $headings,
            'dateFilterText' => $dateFilterText,

            'totalAmountSum' => getPriceFormat($export->getTotalAmountSum()),
            'totalAmountDeliveryman' => getPriceFormat($export->getTotaldeliverymanAmount()),
            'totalAmountOrder' => getPriceFormat($export->getTotalAmountorder()),
        ])->setPaper('a4', 'landscape');

        return $pdf->download( 'deliveryman-earning-report' . $filenameDatePart . '.pdf' );
    }

    public function reportOfuserPdf(Request $request)
    {
        $export = new ReportOfUserExport($request, $request->input('columns', []));

        $mappedData = $export->collection()->map([$export, 'map']);
        $headings = $export->headings('pdf')[0];

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $to   = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from To Date: $to";
            $filenameDatePart = "_from_{$from}_to_{$to}";
        } elseif ($fromDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from";
            $filenameDatePart = "_from_{$from}";
        } elseif ($toDate) {
            $to = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "To Date: $to";
            $filenameDatePart = "_to_{$to}";
        }

        $client = User::find($request->client_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'report.order-report-pdf',
            [
                'titleName' => $client?->name ?? '-',
                'mappedData' => $mappedData,
                'headings' => $headings,
                'dateFilterText' => $dateFilterText,
                'totalAmountSum' => getPriceFormat($export->getTotalAmountSum()),
                'totalAmountOrder' => getPriceFormat($export->getTotalAmountSumorder()),
                'totalAmountDeliveryman' => getPriceFormat($export->getTotaldeliverymanAmount()),
            ]
        )->setPaper('a4', 'landscape');

        return $pdf->download(
            'report_of_' . ($client->name ?? '-') . $filenameDatePart . '.pdf'
        );
    }

    public function reportOfDeliverymanPdf(Request $request)
    {
        $selectedColumns = $request->input('columns', []);
        $export = new ReportOfDeliverymanExport($request, $selectedColumns);

        $mappedData = $export->collection()->map([$export, 'map']);
        $headings = $export->headings('pdf')[0];

        $fromDate = $request->input('from_date');
        $toDate   = $request->input('to_date');

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $to   = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from To Date: $to";
            $filenameDatePart = "_from_{$from}_to_{$to}";
        } elseif ($fromDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from";
            $filenameDatePart = "_from_{$from}";
        } elseif ($toDate) {
            $to = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "To Date: $to";
            $filenameDatePart = "_to_{$to}";
        }

        $deliveryman = User::find($request->delivery_man_id);
        $deliverymanName = $deliveryman ? $deliveryman->name : '-';

        $data = [
            'mappedData' => $mappedData,
            'headings' => $headings,
            'dateFilterText' => $dateFilterText,
            'deliverymanName' => $deliverymanName,
            'totalAmountSum' => getPriceFormat($export->getTotalAmountSum()),
            'totalAmountOrder' => getPriceFormat($export->getTotalAmountSumorder()),
            'totalAmountDeliveryman' => getPriceFormat($export->getTotaldeliverymanAmount()),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'report.deliveryman-report-pdf',
            $data
        )->setPaper('a4', 'landscape');

        $filename = 'report_of_' . $deliverymanName . $filenameDatePart . '.pdf';

        return $pdf->download($filename);
    }

    public function reportOfcityPdf(Request $request)
    {
        $export = new ReportOfCityExport($request, $request->input('columns', []));

        $mappedData = $export->collection()->map([$export, 'map']);
        $headings = $export->headings('pdf')[0];

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $to   = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from To Date: $to";
            $filenameDatePart = "_from_{$from}_to_{$to}";
        } elseif ($fromDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from";
            $filenameDatePart = "_from_{$from}";
        } elseif ($toDate) {
            $to = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "To Date: $to";
            $filenameDatePart = "_to_{$to}";
        }

        $city = City::find($request->city_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'report.order-report-pdf',
            [
                'titleName' => $city?->name ?? '-',
                'mappedData' => $mappedData,
                'headings' => $headings,
                'dateFilterText' => $dateFilterText,
                'totalAmountSum' => $export->getTotalAmountSum(),
                'totalAmountOrder' => $export->getTotalAmountSumorder(),
                'totalAmountDeliveryman' => $export->getTotaldeliverymanAmount(),
            ]
        )->setPaper('a4', 'landscape');

        return $pdf->download( 'report_of_' . ($city->name ?? '-') . $filenameDatePart . '.pdf' );
    }

    public function reportOfcountryPdf(Request $request)
    {
        $export = new ReportOfCountryExport($request, $request->input('columns', []));

        $mappedData = $export->collection()->map([$export, 'map']);
        $headings = $export->headings('pdf')[0];

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $to   = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from To Date: $to";
            $filenameDatePart = "_from_{$from}_to_{$to}";
        } elseif ($fromDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from";
            $filenameDatePart = "_from_{$from}";
        } elseif ($toDate) {
            $to = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "To Date: $to";
            $filenameDatePart = "_to_{$to}";
        }

        $country = Country::find($request->country_id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
           'report.order-report-pdf',
            [
                'titleName' => $country?->name ?? '-',
                'mappedData' => $mappedData,
                'headings' => $headings,
                'dateFilterText' => $dateFilterText,
                'totalAmountSum' => $export->getTotalAmountSum(),
                'totalAmountOrder' => $export->getTotalAmountSumorder(),
                'totalAmountDeliveryman' => $export->getTotaldeliverymanAmount(),
            ]
        )->setPaper('a4', 'landscape');

        return $pdf->download( 'report_of_' . ($country->name ?? '-') . $filenameDatePart . '.pdf' );
    }

    public function downloadOrderReportPdf(Request $request)
    {
        $export = new OrderReportExport($request, $request->input('columns', []));

        $mappedData = $export->collection()->map([$export, 'map']);
        $headings = $export->headings('pdf')[0];

        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $to   = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from To Date: $to";
            $filenameDatePart = "_from_{$from}_to_{$to}";
        } elseif ($fromDate) {
            $from = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = "From Date: $from";
            $filenameDatePart = "_from_{$from}";
        } elseif ($toDate) {
            $to = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = "To Date: $to";
            $filenameDatePart = "_to_{$to}";
        }

        $pdf = Pdf::loadView('report.order-report-pdf', [
            'mappedData' => $mappedData,
            'headings' => $headings,
            'dateFilterText' => $dateFilterText,
            'totalAmountSum' => getPriceFormat($export->getTotalAmountSum()),
            'totalAmountDeliveryman' => getPriceFormat($export->getTotaldeliverymanAmount()),
            'totalAmountOrder' => getPriceFormat($export->getTotalAmountorder()),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('order-report' . $filenameDatePart . '.pdf');
    }
}
