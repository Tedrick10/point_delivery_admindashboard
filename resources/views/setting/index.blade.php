<x-master-layout :assets="$assets ?? []">
    <div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('message.list') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12 col-lg-12">
                                <ul class="nav flex-column nav-pills me-3 tabslink" id="tabs-text" role="tablist">
                                    <li class="nav-item">
                                        <a href="javascript:void(0)"
                                            data-href="{{ route('layout_page') }}?page=profile_form"
                                            data-target=".paste_here"
                                            class="nav-link {{ $page == 'profile_form' ? 'active' : '' }}"
                                            data-toggle="tabajax" rel="tooltip"> {{ __('message.profile') }} </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="javascript:void(0)"
                                            data-href="{{ route('layout_page') }}?page=password_form"
                                            data-target=".paste_here"
                                            class="nav-link {{ $page == 'password_form' ? 'active' : '' }}"
                                            data-toggle="tabajax" rel="tooltip">
                                            {{ __('message.change_password') }} </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="javascript:void(0)"
                                            data-href="{{ route('layout_page') }}?page=verify-old-password"
                                            data-target=".paste_here"
                                            class="nav-link {{ $page == 'verify-old-password' ? 'active' : '' }}"
                                            data-toggle="tabajax" rel="tooltip">
                                            {{ __('message.change_email') }} </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12 col-lg-12">
                                <div class="tab-content" id="pills-tabContent-1">
                                    <div class="tab-pane active p-1">
                                        <div class="paste_here"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        <script>
            $(document).ready(function(event) {
                var $this = $('.nav-item').find('a.active');
                loadurl = "{{ route('layout_page') }}?page={{ $page }}";

                targ = $this.attr('data-target');

                id = this.id || '';

                $.post(loadurl, {
                    '_token': $('meta[name=csrf-token]').attr('content')
                }, function(data) {
                    $(targ).html(data);
                });

                $this.tab('show');
                return false;
            });
        </script>
        @if (request('page') == 'change_email_form')
            <script>
                $(document).ready(function() {
                    const page = '{{ request('page') }}';
                    const target = $('.paste_here');

                    $.ajax({
                        url: '{{ route('layout_page') }}?page=' + page,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            target.html(data);
                        },
                        error: function() {
                            target.html('<div class="alert alert-danger">Failed to load Email form.</div>');
                        }
                    });
                });
            </script>
        @endif
        @if (request('page') == 'otp_form')
            <script>
                $(document).ready(function() {
                    const page = '{{ request('page') }}';
                    const target = $('.paste_here');

                    $.ajax({
                        url: '{{ route('layout_page') }}?page=' + page,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            target.html(data);
                        },
                        error: function() {
                            target.html('<div class="alert alert-danger">Failed to load OTP form.</div>');
                        }
                    });
                });
            </script>
        @endif
    @endsection
</x-master-layout>
