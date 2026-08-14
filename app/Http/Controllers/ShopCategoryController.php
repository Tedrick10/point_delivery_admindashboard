<?php

namespace App\Http\Controllers;

use App\Models\ShopCategory;
use Illuminate\Http\Request;

class ShopCategoryController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('shop-category-list')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.shop_categories');
        $items = ShopCategory::orderBy('sort_order')->orderBy('name')->paginate(20);

        return view('shop.category.index', compact('pageTitle', 'items'));
    }

    public function create()
    {
        if (!auth()->user()->can('shop-category-add')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.shop_category')]);

        return view('shop.category.form', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_group' => 'required|in:quick_link,product_type',
            'status' => 'required|in:0,1',
        ]);

        $data = $request->only(['name', 'category_group', 'status']);
        $data['sort_order'] = (ShopCategory::max('sort_order') ?? 0) + 1;

        $item = ShopCategory::create($data);
        uploadMediaFile($item, $request->category_image, 'category_image');

        return redirect()->route('shop-category.index')->withSuccess(__('message.save_form', ['form' => __('message.shop_category')]));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('shop-category-edit')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $data = ShopCategory::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.shop_category')]);

        return view('shop.category.form', compact('data', 'pageTitle', 'id'));
    }

    public function update(Request $request, $id)
    {
        $item = ShopCategory::findOrFail($id);
        $item->update($request->only(['name', 'category_group', 'status']));
        uploadMediaFile($item, $request->category_image, 'category_image');

        return redirect()->route('shop-category.index')->withSuccess(__('message.update_form', ['form' => __('message.shop_category')]));
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('shop-category-delete')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        ShopCategory::findOrFail($id)->delete();

        return redirect()->route('shop-category.index')->withSuccess(__('message.delete_form', ['form' => __('message.shop_category')]));
    }
}
