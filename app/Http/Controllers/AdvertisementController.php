<?php

namespace App\Http\Controllers;

use App\DataTables\AdvertisementDataTable;
use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function index(AdvertisementDataTable $dataTable)
    {
        if (!auth()->user()->can('advertisement-list')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        $pageTitle = __('message.list_form_title', ['form' => __('message.advertisement')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $button = $auth_user->can('advertisement-add')
            ? '<a href="' . route('advertisement.create') . '" class="float-right btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> ' . __('message.add_form_title', ['form' => __('message.advertisement')]) . '</a>'
            : '';
        return $dataTable->render('global.datatable', compact('pageTitle', 'button', 'auth_user', 'assets'));
    }

    public function create()
    {
        if (!auth()->user()->can('advertisement-add')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        $pageTitle = __('message.add_form_title', ['form' => __('message.advertisement')]);
        $clients = User::where('user_type', 'client')->where('status', 1)->pluck('name', 'id');
        return view('advertisement.form', compact('pageTitle', 'clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'placement' => 'required|string',
            'target_app' => 'required|string',
        ]);

        $data = $request->all();
        $data['approval_status'] = $request->has('client_id') && $request->client_id ? 'pending' : 'approved';
        if ($data['approval_status'] === 'approved') {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        $ad = Advertisement::create($data);
        uploadMediaFile($ad, $request->ad_image, 'ad_image');

        if (request()->is('api/*')) {
            return json_message_response(__('message.save_form', ['form' => __('message.advertisement')]));
        }
        return redirect()->route('advertisement.index')->withSuccess(__('message.save_form', ['form' => __('message.advertisement')]));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('advertisement-edit')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        $data = Advertisement::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.advertisement')]);
        $clients = User::where('user_type', 'client')->where('status', 1)->pluck('name', 'id');
        return view('advertisement.form', compact('data', 'pageTitle', 'id', 'clients'));
    }

    public function update(Request $request, $id)
    {
        $ad = Advertisement::findOrFail($id);
        $data = $request->all();
        $ad->update($data);
        uploadMediaFile($ad, $request->ad_image, 'ad_image');

        if (request()->is('api/*')) {
            return json_message_response(__('message.update_form', ['form' => __('message.advertisement')]));
        }
        return redirect()->route('advertisement.index')->withSuccess(__('message.update_form', ['form' => __('message.advertisement')]));
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('advertisement-delete')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }
        Advertisement::findOrFail($id)->delete();
        $message = __('message.delete_form', ['form' => __('message.advertisement')]);
        if (request()->is('api/*')) {
            return json_message_response($message);
        }
        return redirect()->route('advertisement.index')->withSuccess($message);
    }

    public function approve($id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return redirect()->route('advertisement.index')->withSuccess(__('message.advertisement_approved'));
    }

    public function reject($id)
    {
        $ad = Advertisement::findOrFail($id);
        $ad->update(['approval_status' => 'rejected']);
        return redirect()->route('advertisement.index')->withSuccess(__('message.advertisement_rejected'));
    }
}
