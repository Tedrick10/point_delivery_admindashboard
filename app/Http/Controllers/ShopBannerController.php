<?php

namespace App\Http\Controllers;

use App\Models\ShopBanner;
use Illuminate\Http\Request;

class ShopBannerController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('shop-banner-list')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.shop_banners');
        $items = ShopBanner::orderBy('sort_order')->orderByDesc('id')->paginate(20);

        return view('shop.banner.index', compact('pageTitle', 'items'));
    }

    public function create()
    {
        if (!auth()->user()->can('shop-banner-add')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.shop_banner')]);

        return view('shop.banner.form', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'banner_type' => 'required|in:carousel,middle',
            'status' => 'required|in:0,1',
        ]);

        $data = $request->only(['title', 'banner_type', 'status']);
        $data['link_type'] = 'none';
        $data['sort_order'] = (ShopBanner::max('sort_order') ?? 0) + 1;

        $item = ShopBanner::create($data);
        uploadMediaFile($item, $request->banner_image, 'banner_image');

        return redirect()->route('shop-banner.index')->withSuccess(__('message.save_form', ['form' => __('message.shop_banner')]));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('shop-banner-edit')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $data = ShopBanner::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.shop_banner')]);

        return view('shop.banner.form', compact('data', 'pageTitle', 'id'));
    }

    public function update(Request $request, $id)
    {
        $item = ShopBanner::findOrFail($id);
        $item->update($request->only(['title', 'banner_type', 'status']));
        uploadMediaFile($item, $request->banner_image, 'banner_image');

        return redirect()->route('shop-banner.index')->withSuccess(__('message.update_form', ['form' => __('message.shop_banner')]));
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('shop-banner-delete')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        ShopBanner::findOrFail($id)->delete();

        return redirect()->route('shop-banner.index')->withSuccess(__('message.delete_form', ['form' => __('message.shop_banner')]));
    }
}
