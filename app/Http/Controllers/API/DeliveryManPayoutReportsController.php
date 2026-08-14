<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\DeliveryManPayoutReportResource;
use App\Models\DeliveryManPayoutReport;

class DeliveryManPayoutReportsController extends Controller
{
    public function getList(Request $request)
    {
        $baseQuery = DeliveryManPayoutReport::where('delivery_man_id', $request->delivery_man_id);

        // Clone query for listing (so totals are not affected)
        $query = clone $baseQuery;

        $query->when( $request->filled('status') && $request->status !== 'null',
            function ($q) use ($request) {
                $q->where('status', $request->status);
            }
        );

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $baseQuery->count();
            }
        }

        $reports = $query->orderByDesc('id')->paginate($per_page);
        $items   = DeliveryManPayoutReportResource::collection($reports);

        $totalPayout = (clone $baseQuery)->where('status', 'paid')->sum('payout_amount');

        $totalPayoutCount = (clone $baseQuery)->where('status', 'paid')->count();

        $totalPendingPayout = (clone $baseQuery)->where('status', 'pending')->sum('payout_amount');

        $totalPendingPayoutCount = (clone $baseQuery)->where('status', 'pending')->count();

        $totalCount = (clone $baseQuery)->count();

        return json_custom_response([
            'total_payout'                => $totalPayout,
            'total_payout_count'          => $totalPayoutCount,
            'total_pending_payout'        => $totalPendingPayout,
            'total_pending_payout_count'  => $totalPendingPayoutCount,
            'total_count'                 => $totalCount,
            'pagination'                  => json_pagination_response($items),
            'data'                        => $items,
        ]);
    }
}
