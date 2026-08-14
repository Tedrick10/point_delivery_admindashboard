<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('message.list') }}</h5>
                            <a href="{{ route('useraddress.create') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ __('message.add_form_title', ['form' => __('message.address')]) }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @foreach($userAddresses as $item)
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div>
                                        <strong>{{ $item->address }}</strong><br>
                                        <strong>{{ $item->contact_number }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @section('bottom_script')
    @endsection
</x-master-layout>
