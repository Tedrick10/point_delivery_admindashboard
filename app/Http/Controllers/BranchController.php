<?php

namespace App\Http\Controllers;

use App\DataTables\BranchDataTable;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(BranchDataTable $dataTable)
    {
        if (isBranchAdmin()) {
            return redirect()->route('home')->withErrors(__('message.access_denied'));
        }

        if (! auth()->user()->can('branch-list')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.list_form_title', ['form' => __('message.branch')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $multi_checkbox_delete = $auth_user->can('branch-delete')
            ? '<button id="deleteSelectedBtn" checked-title="branch-checked" class="float-left btn btn-sm ">'.__('message.delete_selected').'</button>'
            : '';
        $button = $auth_user->can('branch-add')
            ? '<a href="'.route('branch.create').'" class="float-right btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title', ['form' => __('message.branch')]).'</a>'
            : '';

        return $dataTable->render('global.datatable', compact('pageTitle', 'button', 'auth_user', 'multi_checkbox_delete'));
    }

    public function create()
    {
        if (! auth()->user()->can('branch-add')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.branch')]);

        return view('branch.form', compact('pageTitle'));
    }

    public function store(BranchRequest $request)
    {
        if (! auth()->user()->can('branch-add')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        Branch::create($request->only(['name', 'status']));
        $message = __('message.save_form', ['form' => __('message.branch')]);

        return redirect()->route('branch.index')->withSuccess($message);
    }

    public function edit($id)
    {
        if (! auth()->user()->can('branch-edit')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.update_form_title', ['form' => __('message.branch')]);
        $data = Branch::findOrFail($id);

        return view('branch.form', compact('data', 'pageTitle', 'id'));
    }

    public function update(BranchRequest $request, $id)
    {
        if (! auth()->user()->can('branch-edit')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        $branch = Branch::findOrFail($id);
        $branch->update($request->only(['name', 'status']));
        $message = __('message.update_form', ['form' => __('message.branch')]);

        return redirect()->route('branch.index')->withSuccess($message);
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('branch-delete')) {
            $message = __('message.demo_permission_denied');

            return redirect()->back()->withErrors($message);
        }

        if (env('APP_DEMO')) {
            $message = __('message.demo_permission_denied');
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }

            return redirect()->route('branch.index')->withErrors($message);
        }

        $branch = Branch::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' => __('message.branch')]);

        if ($branch) {
            $branch->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.branch')]);
        }

        if (request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->back()->with($status, $message);
    }

    public function action(Request $request)
    {
        $id = $request->id;
        $branch = Branch::withTrashed()->where('id', $id)->first();
        $message = __('message.not_found_entry', ['name' => __('message.branch')]);

        if (! $branch) {
            return redirect()->route('branch.index')->withErrors($message);
        }

        if ($request->type === 'restore') {
            $branch->restore();
            $message = __('message.msg_restored', ['name' => __('message.branch')]);
        }

        if ($request->type === 'forcedelete') {
            if (env('APP_DEMO')) {
                $message = __('message.demo_permission_denied');

                return redirect()->route('branch.index')->withErrors($message);
            }
            $branch->forceDelete();
            $message = __('message.msg_forcedelete', ['name' => __('message.branch')]);
        }

        return redirect()->route('branch.index')->withSuccess($message);
    }
}
