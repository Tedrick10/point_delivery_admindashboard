<?php

namespace App\Http\Controllers;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Http\Request;

class ShopProductController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('shop-product-list')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.shop_products');
        $items = ShopProduct::with('category')->orderBy('sort_order')->orderByDesc('id')->paginate(20);

        return view('shop.product.index', compact('pageTitle', 'items'));
    }

    public function create()
    {
        if (!auth()->user()->can('shop-product-add')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $pageTitle = __('message.add_form_title', ['form' => __('message.shop_product')]);
        $categories = ShopCategory::active()->pluck('name', 'id');
        $homeSections = ShopProduct::homeSectionOptions();

        return view('shop.product.form', compact('pageTitle', 'categories', 'homeSections'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:shop_categories,id',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
            'home_section' => 'required|string',
        ]);

        $data = $request->only([
            'category_id', 'name', 'description', 'price', 'status',
        ]);
        $data['home_section'] = ShopProduct::normalizeHomeSection($request->home_section);
        $data['stock_status'] = 'in_stock';
        $data['sort_order'] = (ShopProduct::max('sort_order') ?? 0) + 1;

        $item = ShopProduct::create($data);
        uploadMediaFile($item, $request->product_image, 'product_image');
        if ($request->hasFile('product_gallery')) {
            uploadMediaFile($item, $request->file('product_gallery'), 'product_gallery');
        }

        return redirect()->route('shop-product.index')->withSuccess(__('message.save_form', ['form' => __('message.shop_product')]));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('shop-product-edit')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        $data = ShopProduct::findOrFail($id);
        $pageTitle = __('message.update_form_title', ['form' => __('message.shop_product')]);
        $categories = ShopCategory::active()->pluck('name', 'id');
        $homeSections = ShopProduct::homeSectionOptions();

        return view('shop.product.form', compact('data', 'pageTitle', 'id', 'categories', 'homeSections'));
    }

    public function update(Request $request, $id)
    {
        $item = ShopProduct::findOrFail($id);
        $data = $request->only([
            'category_id', 'name', 'description', 'price', 'status',
        ]);
        $data['home_section'] = ShopProduct::normalizeHomeSection($request->home_section);

        $item->update($data);
        uploadMediaFile($item, $request->product_image, 'product_image');
        if ($request->hasFile('product_gallery')) {
            foreach ($request->file('product_gallery') as $file) {
                uploadMediaFile($item, $file, 'product_gallery');
            }
        }

        return redirect()->route('shop-product.index')->withSuccess(__('message.update_form', ['form' => __('message.shop_product')]));
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('shop-product-delete')) {
            return redirect()->back()->withErrors(__('message.permission_denied_for_account'));
        }

        ShopProduct::findOrFail($id)->delete();

        return redirect()->route('shop-product.index')->withSuccess(__('message.delete_form', ['form' => __('message.shop_product')]));
    }
}
