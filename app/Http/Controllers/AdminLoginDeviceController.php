<?php

namespace App\Http\Controllers;

use App\DataTables\AdminLoginDeviceDataTable;
use App\DataTables\AdminLoginHistoryDataTable;
use App\Models\AdminLoginDevice;
use Illuminate\Support\Facades\Auth;

class AdminLoginDeviceController extends Controller
{
    public function index(AdminLoginDeviceDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.active_sessions')] );
        $auth_user = authSession();
        $assets = ['datatable'];

        return $dataTable->render('global.datatable', compact('assets','pageTitle','auth_user'));
    }
    public function logoutDevice($id)
    {
        if(env('APP_DEMO')){
            $message = __('message.demo_permission_denied');
            if(request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->route('admin-login-device.index')->withErrors($message);
        }
        $device = AdminLoginDevice::where('id', $id)->where('user_id', auth()->id())->first();

        if (!$device) {
            return redirect()->back()->withErrors(__('message.device_not_found'));
        }
        $device->update([ 'is_active' => false,'logout_at' => now() ]);

        if ($device->session_id === session()->getId()) {            
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect()->route('login');
        }
        
        return redirect()->back()->with('success', __('message.device_logged_out'));  
    }
    public function show(AdminLoginHistoryDataTable $dataTable, $user_id)
    {     
        if (env('APP_DEMO')) {
            $message = __('message.demo_permission_denied');
            if (request()->ajax()) {
                return response()->json(['status' => false, 'message' => $message, 'event' => 'validation']);
            }
            return redirect()->route('admin-login-device.index')->withErrors($message);
        }
        $pageTitle = __('message.list_form_title', ['form' => __('message.where_You_are_logged_in')]);
        $auth_user = authSession();
        $assets = ['datatable'];

        return $dataTable->with('user_id', $user_id)->render('global.datatable', compact('assets', 'pageTitle', 'auth_user'));
    }
}
