<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter">
        <div class="row">
            <div class="col-lg-12 mb-3">
                <div class="pds-dashboard-hero pds-hero-animate">
                    <div class="pds-dashboard-hero-content">
                        <p class="pds-dashboard-eyebrow mb-1">{{ __('message.dashboard') }}</p>
                        <h2 class="pds-dashboard-title mb-0">
                            {{ __('message.dashboard') }}
                            @if(isBranchAdmin() && auth()->user()->branch)
                                <span class="text-muted" style="font-size:0.55em;font-weight:600;">· {{ auth()->user()->branch->name }}</span>
                            @endif
                        </h2>
                    </div>
                    <div class="pds-dashboard-hero-glow" aria-hidden="true"></div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-dashboard-panel pds-panel-animate">
                    <div class="row align-items-center pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.today_order_counts') }}</h5>
                        <div class="ml-auto d-flex justify-content-end pds-section-actions">
                            <a href="{{ route('dashboard.filter.data', $params) }}" class="btn btn-sm btn-primary loadRemoteModel">
                                <i class="fas fa-filter"></i> {{ __('message.filter')}}
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-primary">
                                <i class="ri-repeat-line"></i> {{ __('message.reset_filter')}}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row pds-stats-grid">
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_order_today'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16">
                                                    <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.pending_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_order_today_peding'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16">
                                                    <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.24.54-.555 1.038zm-.964 1.205q.183-.183.35-.378l.758.653a8 8 0 0 1-.401.432z"/>
                                                    <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0z"/>
                                                    <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.inprogress_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_order_today_inprogress'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
                                                    <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41m-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9"/>
                                                    <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5 5 0 0 0 8 3M3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.completed_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_order_today_completed'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-cart-check" viewBox="0 0 16 16">
                                                    <path d="M11.354 6.354a.5.5 0 0 0-.708-.708L8 8.293 6.854 7.146a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z"/>
                                                    <path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1zm3.915 10L3.102 4h10.796l-1.313 7zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0m7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.cancel_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_order_today_cancelled'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-calendar2-x" viewBox="0 0 16 16">
                                                    <path d="M6.146 8.146a.5.5 0 0 1 .708 0L8 9.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 10l1.147 1.146a.5.5 0 0 1-.708.708L8 10.707l-1.146 1.147a.5.5 0 0 1-.708-.708L7.293 10 6.146 8.854a.5.5 0 0 1 0-.708"/>
                                                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M2 2a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1z"/>
                                                    <path d="M2.5 4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-dashboard-panel pds-panel-animate">
                    <div class="row pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.order_detail') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row pds-stats-grid">
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-ui-checks-grid" viewBox="0 0 16 16">
                                                    <path d="M2 10h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1m9-9h3a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-3a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1m0 9a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1zm0-10a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM2 9a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h3a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2zm7 2a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2zM0 2a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm5.354.854a.5.5 0 1 0-.708-.708L3 3.793l-.646-.647a.5.5 0 1 0-.708.708l1 1a.5.5 0 0 0 .708 0z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.created_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_create_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-file-earmark-plus" viewBox="0 0 16 16">
                                                    <path d="M8 6.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V11a.5.5 0 0 1-1 0V9.5H6a.5.5 0 0 1 0-1h1.5V7a.5.5 0 0 1 .5-.5"/>
                                                    <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.assigned_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_assigned_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-person-check" viewBox="0 0 16 16">
                                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0M8 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                                                    <path d="M8.256 14a4.5 4.5 0 0 1-.229-1.004H3c.001-.246.154-.986.832-1.664C4.484 10.68 5.711 10 8 10q.39 0 .74.025c.226-.341.496-.65.804-.918Q8.844 9.002 8 9c-5 0-6 3-6 4s1 1 1 1z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.accepted_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_accepetd_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-file-check" viewBox="0 0 16 16">
                                                    <path d="M10.854 6.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 8.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                                                    <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.arrived_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_arrived_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-file-arrow-down" viewBox="0 0 16 16">
                                                    <path d="M8 5a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 1 1 .708-.708L7.5 9.293V5.5A.5.5 0 0 1 8 5"/>
                                                    <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.picked_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_pickup_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-truck" viewBox="0 0 16 16">
                                                    <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.departed_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_departed_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16">
                                                    <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576zm6.787-8.201L1.591 6.602l4.339 2.76z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.delivered_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_delivered_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-bag-check" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd" d="M10.854 8.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 0 1 .708-.708L7.5 10.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                                                    <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.cancel_order') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_cancelled_order'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-file-earmark-excel" viewBox="0 0 16 16">
                                                    <path d="M5.884 6.68a.5.5 0 1 0-.768.64L7.349 10l-2.233 2.68a.5.5 0 0 0 .768.64L8 10.781l2.116 2.54a.5.5 0 0 0 .768-.641L8.651 10l2.233-2.68a.5.5 0 0 0-.768-.64L8 9.219l-2.116-2.54z"/>
                                                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_user') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_user'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_delivery_person') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ $data['dashboard']['total_delivery_person'] }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $dash = $data['dashboard'] ?? [];
            $todayPieSeries = [
                (int) ($dash['total_order_today_peding'] ?? 0),
                (int) ($dash['total_order_today_inprogress'] ?? 0),
                (int) ($dash['total_order_today_completed'] ?? 0),
                (int) ($dash['total_order_today_cancelled'] ?? 0),
            ];
            $pipelineLabels = [
                __('message.created_order'),
                __('message.assigned_order'),
                __('message.accepted_order'),
                __('message.arrived_order'),
                __('message.picked_order'),
                __('message.departed_order'),
                __('message.delivered_order'),
                __('message.cancel_order'),
            ];
            $pipelineSeries = [
                (int) ($dash['total_create_order'] ?? 0),
                (int) ($dash['total_assigned_order'] ?? 0),
                (int) ($dash['total_accepetd_order'] ?? 0),
                (int) ($dash['total_arrived_order'] ?? 0),
                (int) ($dash['total_pickup_order'] ?? 0),
                (int) ($dash['total_departed_order'] ?? 0),
                (int) ($dash['total_delivered_order'] ?? 0),
                (int) ($dash['total_cancelled_order'] ?? 0),
            ];
            $weeklyLabels = collect($data['weekly_order_count'] ?? [])->pluck('day')->map(function ($day) {
                return \Illuminate\Support\Str::substr((string) $day, 0, 3);
            })->values()->all();
            if (empty($weeklyLabels)) {
                $weeklyLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            }
            $cityChartData = collect($cityData ?? [])->take(8)->values()->all();
        @endphp

        <div class="row pds-dash-charts">
            <div class="col-lg-4 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.today_status_chart') }}</h5>
                        <span class="pds-chart-panel__badge">{{ __('message.today_order') }}</span>
                    </div>
                    <div class="card-body">
                        <div id="pds-today-status-pie" class="pds-chart-canvas"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.order_pipeline_chart') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="pds-order-pipeline-bar" class="pds-chart-canvas pds-chart-canvas--tall"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.weekly_order_count') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="pds-weekly-order-bar" class="pds-chart-canvas"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.weekly_order_share') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="dash-count-chart-pie" class="pds-chart-canvas"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.monthly_order_count') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="wave-chart" class="pds-chart-canvas pds-chart-canvas--wide"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.monthly_payment_count') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="dash-payment-chart-bar" class="pds-chart-canvas pds-chart-canvas--wide"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.orders_by_city') }}</h5>
                    </div>
                    <div class="card-body">
                        <div id="city-package-chart" class="pds-chart-canvas"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 mb-3">
                <div class="card pds-dashboard-panel pds-chart-panel pds-panel-animate">
                    <div class="pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.number_of_packages') }}</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive pds-dash-city-table">
                            <table id="basic-table" class="table mb-0 text-center" role="grid">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('message.city_name') }}</th>
                                        <th scope="col">{{ __('message.total_number') }}</th>
                                        <th scope="col">{{ __('message.parcel_in_progress') }}</th>
                                        <th scope="col">{{ __('message.delivered_package') }}</th>
                                        <th scope="col">{{ __('message.cancelled_package') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($cityData ?? []) as $cityList)
                                        <tr>
                                            <td>{{ $cityList['city'] }}</td>
                                            <td>{{ $cityList['count'] }}</td>
                                            <td>{{ $cityList['in_progress'] }}</td>
                                            <td>{{ $cityList['delivered'] }}</td>
                                            <td>{{ $cityList['cancelled'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">{{ __('message.no_record_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card pds-dashboard-panel pds-panel-animate">
                    <div class="row align-items-center pds-section-toolbar">
                        <h5 class="pds-section-title mb-0">{{ __('message.total_collect') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row pds-stats-grid">
                            <div class="col-lg-3 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_collection') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ getPriceFormat($data['dashboard']['total_collection_by_order']) }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="42" height="44" fill="currentColor" class="bi bi-piggy-bank" viewBox="0 0 16 16">
                                                    <path d="M5 6.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0m1.138-1.496A6.6 6.6 0 0 1 7.964 4.5c.666 0 1.303.097 1.893.273a.5.5 0 0 0 .286-.958A7.6 7.6 0 0 0 7.964 3.5c-.734 0-1.441.103-2.102.292a.5.5 0 1 0 .276.962"/>
                                                    <path fill-rule="evenodd" d="M7.964 1.527c-2.977 0-5.571 1.704-6.32 4.125h-.55A1 1 0 0 0 .11 6.824l.254 1.46a1.5 1.5 0 0 0 1.478 1.243h.263c.3.513.688.978 1.145 1.382l-.729 2.477a.5.5 0 0 0 .48.641h2a.5.5 0 0 0 .471-.332l.482-1.351c.635.173 1.31.267 2.011.267.707 0 1.388-.095 2.028-.272l.543 1.372a.5.5 0 0 0 .465.316h2a.5.5 0 0 0 .478-.645l-.761-2.506C13.81 9.895 14.5 8.559 14.5 7.069q0-.218-.02-.431c.261-.11.508-.266.705-.444.315.306.815.306.815-.417 0 .223-.5.223-.461-.026a1 1 0 0 0 .09-.255.7.7 0 0 0-.202-.645.58.58 0 0 0-.707-.098.74.74 0 0 0-.375.562c-.024.243.082.48.32.654a2 2 0 0 1-.259.153c-.534-2.664-3.284-4.595-6.442-4.595M2.516 6.26c.455-2.066 2.667-3.733 5.448-3.733 3.146 0 5.536 2.114 5.536 4.542 0 1.254-.624 2.41-1.67 3.248a.5.5 0 0 0-.165.535l.66 2.175h-.985l-.59-1.487a.5.5 0 0 0-.629-.288c-.661.23-1.39.359-2.157.359a6.6 6.6 0 0 1-2.157-.359.5.5 0 0 0-.635.304l-.525 1.471h-.979l.633-2.15a.5.5 0 0 0-.17-.534 4.65 4.65 0 0 1-1.284-1.541.5.5 0 0 0-.446-.275h-.56a.5.5 0 0 1-.492-.414l-.254-1.46h.933a.5.5 0 0 0 .488-.393m12.621-.857a.6.6 0 0 1-.098.21l-.044-.025c-.146-.09-.157-.175-.152-.223a.24.24 0 0 1 .117-.173c.049-.027.08-.021.113.012a.2.2 0 0 1 .064.199"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_admin') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ getPriceFormat($data['dashboard']['total_admin_comission']) }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                                                    <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <div class="card card-block card-stretch card-height">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_delivery_boy') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ getPriceFormat($data['dashboard']['total_delivery_comission']) }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-cash-coin" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8m5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0"/>
                                                    <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195z"/>
                                                    <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083q.088-.517.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1z"/>
                                                    <path d="M9.998 5.083 10 5a2 2 0 1 0-3.132 1.65 6 6 0 0 1 3.13-1.567"/>
                                                  </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.total_wallet') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ getPriceFormat ($data['dashboard']['total_wallet_balance']) }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-wallet2" viewBox="0 0 16 16">
                                                    <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-4">
                                <div class="card card-block card-stretch card-height shadow-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="mm-cart-text">
                                                <p class="mb-0">{{ __('message.monthly_payment_count') }}</p>
                                                <br>
                                                <h5 class="font-weight-700">{{ getPriceFormat ($data['dashboard']['monthly_payment_count']) }}</h5>
                                            </div>
                                            <div class="mm-cart-image text-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="42" fill="currentColor" class="bi bi-credit-card" viewBox="0 0 16 16">
                                                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z"/>
                                                    <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card pds-dashboard-panel pds-panel-animate">
                            <div class="row align-items-center pds-section-toolbar">
                                <h5 class="pds-section-title mb-0">{{ __('message.recent_order')}}</h5>
                                <div class="ml-auto pds-section-actions">
                                    <a href="{{ route('order.index') }}" class="btn btn-sm btn-primary">{{ __('message.view_all') }}</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive pds-dash-city-table">
                                    <table class="table mb-0 text-center" role="grid">
                                        <thead>
                                            <tr>
                                                <th scope='col'>{{ __('message.id') }}</th>
                                                <th scope='col'>{{ __('message.name') }}</th>
                                                <th scope='col'>{{ __('message.delivery_man') }}</th>
                                                <th scope='col'>{{ __('message.pickup_date') }}</th>
                                                <th scope='col'>{{ __('message.created_at') }}</th>
                                                <th scope='col'>{{ __('message.status') }}</th>
                                                <th scope='col'>{{ __('message.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if( count($data['recent_order']) > 0 )
                                                @foreach ($data['recent_order'] as $order)
                                                    @php
                                                        $status = 'primary';
                                                        $order_status = $order->status;
                                                        switch ($order->status) {
                                                            case 'draft':
                                                                $status = 'light';
                                                                $status_name = __('message.draft');
                                                                break;
                                                            case 'create':
                                                                $status = 'primary';
                                                                $status_name = __('message.create');
                                                                break;
                                                            case 'completed':
                                                                $status = 'success';
                                                                $status_name = __('message.delivered');
                                                                break;
                                                            case 'courier_assigned':
                                                                $status = 'warning';
                                                                $status_name = __('message.assigned');
                                                                break;
                                                            case 'active':
                                                                $status = 'info';
                                                                $status_name = __('message.active');
                                                                break;
                                                            case 'courier_departed':
                                                                $status = 'info';
                                                                $status_name = __('message.departed');
                                                                break;
                                                            case 'courier_picked_up':
                                                                $status = 'warning';
                                                                $status_name = __('message.picked_up');
                                                                break;
                                                            case 'courier_arrived':
                                                                $status = 'warning';
                                                                $status_name = __('message.arrived');
                                                                break;
                                                            case 'cancelled':
                                                                $status = 'danger';
                                                                $status_name = __('message.cancelled');
                                                                break;
                                                        }
                                                    @endphp
                                                    <tr>
                                                        <td>{{ optional($order)->id }}</td>
                                                        <td>{{ optional($order->client)->name ?? '-' }}</td>
                                                        <td>{{ optional($order->delivery_man)->name ?? '-' }}</td>
                                                        <td>{{ dateAgoFormate($order->pickup_datetime) ?? '-' }}</td>
                                                        <td>{{  dateAgoFormate($order->created_at , true)  }}</td>
                                                        <td><span class="badge bg-{{$status}}">{{ __('message.'.$order->status) }}</span></td>
                                                        <td><a class="mr-2" href="{{ route('order.show',$order->id) }}"><i class="fas fa-eye text-secondary"></i></a></td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="8">{{ __('message.no_record_found') }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card pds-dashboard-panel pds-panel-animate">
                            <div class="row align-items-center pds-section-toolbar">
                                <h5 class="pds-section-title mb-0">{{ __('message.recent_withdrawrequest')}}</h5>
                                <div class="ml-auto pds-section-actions">
                                    <a href="{{ route('withdrawrequest.index') }}" class="btn btn-sm btn-primary">{{ __('message.view_all') }}</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive pds-dash-city-table">
                                    <table class="table mb-0 text-center" role="grid">
                                        <thead>
                                            <tr>
                                                <th scope='col'>{{ __('message.no') }}</th>
                                                <th scope='col'>{{ __('message.name') }}</th>
                                                <th scope='col'>{{ __('message.amount') }}</th>
                                                <th scope='col'>{{ __('message.created_at') }}</th>
                                                <th scope='col'>{{ __('message.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if( count($data['recent_withdrawrequest']) > 0 )
                                                @php
                                                    $i = 1;
                                                @endphp
                                                @foreach ($data['recent_withdrawrequest'] as $withdrawrequest)
                                                    <tr>
                                                        <td>{{ $i }}</td>
                                                        <td>{{ optional($withdrawrequest->user)->name ?? '-' }}</td>
                                                        <td>{{ getPriceFormat($withdrawrequest->amount) ?? '-' }}</td>
                                                        <td>{{ dateAgoFormate($withdrawrequest->created_at , true) }}</td>
                                                        <td>
                                                            <span class="
                                                                @if(optional($withdrawrequest)->status == 'requested')
                                                                    text-primary
                                                                @elseif(optional($withdrawrequest)->status == 'decline')
                                                                    text-danger
                                                                @elseif(optional($withdrawrequest)->status == 'approved')
                                                                    text-success
                                                                @elseif(optional($withdrawrequest)->status == 'completed')
                                                                    text-warning
                                                                @endif
                                                            ">{{ ucfirst(optional($withdrawrequest)->status) ?? '-' }}</span>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $i++;
                                                    @endphp
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="5">{{ __('message.no_record_found') }}</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('bottom_script')
        <script>
            (function () {
                if (typeof ApexCharts === 'undefined') {
                    return;
                }

                if (window.jQuery && $.fn.DataTable && document.getElementById('basic-table')) {
                    $('#basic-table').DataTable({
                        dom: '<"row align-items-center"<"col-md-2"><"col-md-6" B><"col-md-4"f>><"table-responsive my-3" rt><"d-flex" <"flex-grow-1" l><"p-2" i><"mt-4" p>><"clear">',
                        order: [[1, 'desc']]
                    });
                }

                var palette = {
                    orange: (window.pdsBrandColor || '#FE6F07'),
                    orangeSoft: (window.pdsBrandColor || '#FE6F07'),
                    orangePale: '#FFB347',
                    cream: '#FFF8F1',
                    ink: '#14110F',
                    muted: '#78716c',
                    green: '#16a34a',
                    blue: '#0ea5e9',
                    amber: (window.pdsBrandColor || '#FE6F07'),
                    red: '#ef4444',
                    slate: '#64748b'
                };

                var monthList = @json($data['monthlist'] ?? []);
                var categories = [];
                if (monthList.month_start && monthList.month_end) {
                    var startDate = new Date(monthList.month_start);
                    var endDate = new Date(monthList.month_end);
                    for (var currentDate = new Date(startDate); currentDate <= endDate; currentDate.setDate(currentDate.getDate() + 1)) {
                        categories.push(currentDate.toISOString().split('T')[0]);
                    }
                }

                var chartDefaults = {
                    fontFamily: (window.pdsBrandFontFamily || "Outfit, 'Noto Sans Myanmar', sans-serif"),
                    foreColor: palette.muted,
                    toolbar: { show: false },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 650
                    }
                };

                var todaySeries = @json($todayPieSeries);
                var todayLabels = [
                    @json(__('message.pending_order')),
                    @json(__('message.inprogress_order')),
                    @json(__('message.completed_order')),
                    @json(__('message.cancel_order'))
                ];
                var todayTotal = todaySeries.reduce(function (sum, n) { return sum + Number(n || 0); }, 0);

                if (document.querySelector('#pds-today-status-pie')) {
                    new ApexCharts(document.querySelector('#pds-today-status-pie'), {
                        series: todayTotal > 0 ? todaySeries : [1],
                        labels: todayTotal > 0 ? todayLabels : [@json(__('message.no_record_found'))],
                        colors: todayTotal > 0
                            ? [palette.amber, palette.blue, palette.green, palette.red]
                            : ['#e7e5e4'],
                        chart: Object.assign({ type: 'donut', height: 320 }, chartDefaults),
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%',
                                    labels: {
                                        show: true,
                                        name: { show: true, fontSize: '13px', color: palette.muted },
                                        value: { show: true, fontSize: '22px', fontWeight: 700, color: palette.ink },
                                        total: {
                                            show: true,
                                            label: @json(__('message.total_order')),
                                            fontSize: '12px',
                                            color: palette.muted,
                                            formatter: function () { return String(todayTotal); }
                                        }
                                    }
                                }
                            }
                        },
                        dataLabels: { enabled: false },
                        legend: { position: 'bottom', fontSize: '12px' },
                        stroke: { width: 2, colors: ['#fff'] },
                        tooltip: { y: { formatter: function (val) { return val; } } }
                    }).render();
                }

                var pipelineLabels = @json($pipelineLabels);
                var pipelineSeries = @json($pipelineSeries);
                if (document.querySelector('#pds-order-pipeline-bar')) {
                    new ApexCharts(document.querySelector('#pds-order-pipeline-bar'), {
                        series: [{ name: @json(__('message.total_order')), data: pipelineSeries }],
                        chart: Object.assign({ type: 'bar', height: 320 }, chartDefaults),
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 8,
                                barHeight: '62%',
                                distributed: true
                            }
                        },
                        colors: [
                            (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'),
                            '#EA580C', (window.pdsBrandColor || '#FE6F07'), '#16a34a', '#ef4444'
                        ],
                        dataLabels: {
                            enabled: true,
                            style: { fontSize: '11px', fontWeight: 700, colors: ['#fff'] }
                        },
                        xaxis: {
                            categories: pipelineLabels,
                            labels: { style: { fontSize: '11px' } }
                        },
                        yaxis: {
                            labels: { style: { fontSize: '12px', fontWeight: 600 } }
                        },
                        grid: { borderColor: '#f5f5f4', strokeDashArray: 4 },
                        legend: { show: false },
                        tooltip: { theme: 'light' }
                    }).render();
                }

                var weeklyCount = @json($weekly_count);
                var weeklyLabels = @json($weeklyLabels);
                if (document.querySelector('#pds-weekly-order-bar')) {
                    new ApexCharts(document.querySelector('#pds-weekly-order-bar'), {
                        series: [{ name: @json(__('message.total_order')), data: weeklyCount }],
                        chart: Object.assign({ type: 'bar', height: 320 }, chartDefaults),
                        plotOptions: {
                            bar: {
                                borderRadius: 10,
                                columnWidth: '48%',
                                distributed: true
                            }
                        },
                        colors: [
                            (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'),
                            '#EA580C', (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07')
                        ],
                        dataLabels: { enabled: false },
                        xaxis: {
                            categories: weeklyLabels,
                            labels: { style: { fontWeight: 600 } }
                        },
                        yaxis: {
                            labels: { formatter: function (val) { return Math.round(val); } }
                        },
                        grid: { borderColor: '#f5f5f4', strokeDashArray: 4 },
                        legend: { show: false },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'light',
                                type: 'vertical',
                                shadeIntensity: 0.25,
                                opacityFrom: 1,
                                opacityTo: 0.85
                            }
                        }
                    }).render();
                }

                if (document.querySelector('#dash-count-chart-pie')) {
                    new ApexCharts(document.querySelector('#dash-count-chart-pie'), {
                        series: weeklyCount,
                        labels: weeklyLabels,
                        colors: [
                            (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'),
                            '#EA580C', (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07')
                        ],
                        chart: Object.assign({ type: 'pie', height: 320 }, chartDefaults),
                        legend: { position: 'bottom', fontSize: '12px' },
                        dataLabels: {
                            enabled: true,
                            style: { fontSize: '11px', fontWeight: 700 }
                        },
                        stroke: { width: 2, colors: ['#fff'] }
                    }).render();
                }

                var monthlyTotals = @json(array_column($data['monthly_order_count'] ?? [], 'total'));
                if (document.querySelector('#wave-chart')) {
                    new ApexCharts(document.querySelector('#wave-chart'), {
                        series: [{ name: @json(__('message.total_order')), data: monthlyTotals }],
                        chart: Object.assign({ type: 'area', height: 340 }, chartDefaults),
                        colors: [palette.orange],
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 3 },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.45,
                                opacityTo: 0.05,
                                stops: [0, 90, 100]
                            }
                        },
                        xaxis: {
                            categories: categories,
                            labels: {
                                rotate: -45,
                                rotateAlways: categories.length > 14,
                                style: { fontSize: '10px' }
                            }
                        },
                        yaxis: {
                            title: { text: @json(__('message.total_order')) },
                            labels: { formatter: function (val) { return Math.round(val); } }
                        },
                        grid: { borderColor: '#f5f5f4', strokeDashArray: 4 },
                        tooltip: { theme: 'light' }
                    }).render();
                }

                var completedPayments = @json(array_column($data['completed'] ?? [], 'total_amount'));
                var cancelledPayments = @json(array_column($data['cancelled'] ?? [], 'total_amount'));
                if (document.querySelector('#dash-payment-chart-bar')) {
                    new ApexCharts(document.querySelector('#dash-payment-chart-bar'), {
                        series: [
                            { name: @json(__('message.completed')), data: completedPayments },
                            { name: @json(__('message.cancelled')), data: cancelledPayments }
                        ],
                        chart: Object.assign({ type: 'bar', height: 340, stacked: false }, chartDefaults),
                        colors: [palette.orange, '#1c1917'],
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '52%',
                                borderRadius: 6
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: { show: true, width: 2, colors: ['transparent'] },
                        xaxis: {
                            categories: categories,
                            labels: {
                                rotate: -45,
                                rotateAlways: categories.length > 14,
                                style: { fontSize: '10px' }
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: function (val) {
                                    return Number(val || 0).toLocaleString();
                                }
                            }
                        },
                        grid: { borderColor: '#f5f5f4', strokeDashArray: 4 },
                        legend: { position: 'top', horizontalAlign: 'right' },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return Number(val || 0).toLocaleString();
                                }
                            }
                        }
                    }).render();
                }

                var cityData = @json($cityChartData);
                var cityNames = cityData.map(function (row) { return row.city; });
                var cityCounts = cityData.map(function (row) { return Number(row.count || 0); });
                var cityColors = [
                    (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'), (window.pdsBrandColor || '#FE6F07'),
                    '#EA580C', '#0ea5e9', '#16a34a', '#64748b'
                ];
                var cityTotal = cityCounts.reduce(function (sum, n) { return sum + n; }, 0);

                if (document.querySelector('#city-package-chart')) {
                    new ApexCharts(document.querySelector('#city-package-chart'), {
                        series: cityTotal > 0 ? cityCounts : [1],
                        labels: cityTotal > 0 ? cityNames : [@json(__('message.no_record_found'))],
                        colors: cityTotal > 0 ? cityColors : ['#e7e5e4'],
                        chart: Object.assign({ type: 'donut', height: 340 }, chartDefaults),
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '64%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: @json(__('message.total_number')),
                                            fontSize: '12px',
                                            color: palette.muted,
                                            formatter: function () { return String(cityTotal); }
                                        }
                                    }
                                }
                            }
                        },
                        legend: { position: 'bottom', fontSize: '11px' },
                        dataLabels: { enabled: false },
                        stroke: { width: 2, colors: ['#fff'] }
                    }).render();
                }
            })();
        </script>
    @endpush
</x-master-layout>
