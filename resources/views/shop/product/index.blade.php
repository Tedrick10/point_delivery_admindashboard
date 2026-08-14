<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-page-card">
                    <div class="card-header pds-page-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title pds-page-title mb-0">{{ $pageTitle }}</h4>
                        @can('shop-product-add')
                            <a href="{{ route('shop-product.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus-circle"></i> {{ __('message.add_form_title', ['form' => __('message.shop_product')]) }}
                            </a>
                        @endcan
                    </div>
                    <div class="card-body pds-page-body">
                        <div class="pds-table-shell">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ __('message.image') }}</th>
                                            <th>{{ __('message.name') }}</th>
                                            <th>{{ __('message.category') }}</th>
                                            <th>{{ __('message.price') }}</th>
                                            <th>Section</th>
                                            <th>{{ __('message.status') }}</th>
                                            <th class="text-right">{{ __('message.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $item)
                                            <tr>
                                                <td>
                                                    <img src="{{ getSingleMedia($item, 'product_image') ?: asset('images/default.png') }}"
                                                         height="48" width="48" class="rounded" style="object-fit:cover;">
                                                </td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ optional($item->category)->name ?? '—' }}</td>
                                                <td>{{ number_format($item->price) }}</td>
                                                <td>{{ $item->home_section && $item->home_section !== 'none' ? \App\Models\ShopProduct::formatHomeSectionLabel($item->home_section) : '—' }}</td>
                                                <td>
                                                    <span class="badge {{ $item->status ? 'badge-success' : 'badge-secondary' }}">
                                                        {{ $item->status ? __('message.enable') : __('message.disable') }}
                                                    </span>
                                                </td>
                                                <td class="text-right text-nowrap">
                                                    @can('shop-product-edit')
                                                        <a href="{{ route('shop-product.edit', $item->id) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                                                    @endcan
                                                    @can('shop-product-delete')
                                                        <form action="{{ route('shop-product.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('message.delete_msg') }}')">
                                                            @csrf @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4 text-muted">{{ __('message.no_record_found') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-3">{{ $items->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
