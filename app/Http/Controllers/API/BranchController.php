<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function getList(Request $request)
    {
        $branches = Branch::query()->where('status', 1);

        $branches->when($request->filled('search'), function ($q) use ($request) {
            return $q->where('name', 'LIKE', '%'.$request->search.'%');
        });

        $perPage = config('constant.PER_PAGE_LIMIT');
        if ($request->has('per_page') && ! empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $perPage = $request->per_page;
            }
            if ((int) $request->per_page === -1) {
                $perPage = max($branches->count(), 1);
            }
        }

        $paginated = $branches->orderBy('name')->paginate($perPage);
        $items = $paginated->getCollection()->map(function (Branch $branch) {
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'status' => (int) $branch->status,
            ];
        })->values();

        return json_custom_response([
            'pagination' => json_pagination_response($paginated),
            'data' => $items,
        ]);
    }
}
