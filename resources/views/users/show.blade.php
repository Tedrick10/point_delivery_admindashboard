<x-master-layout>
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-os-profile-page">
        <div class="pds-os-profile-hero">
                <div class="pds-os-profile-hero__copy">
                    <div class="pds-os-profile-hero__eyebrow">
                        <span>{{ __('message.online_shop') }}</span>
                    </div>
                    <h4 class="pds-os-profile-hero__title">{{ $pageTitle }}</h4>
                </div>
            <a href="{{ route('users.index') }}" class="pds-os-profile-back">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>{{ __('message.back') }}</span>
            </a>
        </div>

        <div class="pds-os-profile-screen">
            <nav class="pds-os-profile-tabs" aria-label="{{ __('message.online_shop') }}">
                <a href="{{ route('users-view.show', $data->id) }}" class="pds-os-profile-tab {{ $type == 'detail' ? 'is-active': '' }}">{{ __('message.profile') }}</a>
                <a href="{{ route('users-view.show', [ 'id' => $data->id, 'type' => 'wallethistory']) }}" class="pds-os-profile-tab {{ $type == 'wallethistory' ? 'is-active': '' }}">{{ __('message.wallet') }}</a>
                <a href="{{ route('users-view.show', [ 'id' => $data->id, 'type' => 'orderhistory']) }}" class="pds-os-profile-tab {{ $type == 'orderhistory' ? 'is-active': '' }}">{{ __('message.order') }}</a>
                <a href="{{ route('users-view.show', [ 'id' => $data->id, 'type' => 'withdrawrequest']) }}" class="pds-os-profile-tab {{ $type == 'withdrawrequest' ? 'is-active': '' }}">{{ __('message.withdrawrequest') }}</a>
                <a href="{{ route('users-view.show', [ 'id' => $data->id, 'type' => 'useraddress']) }}" class="pds-os-profile-tab {{ $type == 'useraddress' ? 'is-active': '' }}">{{ __('message.address') }}</a>
                <a href="{{ route('users-view.show', [ 'id' => $data->id, 'type' => 'claimsinfo']) }}" class="pds-os-profile-tab {{ $type == 'claimsinfo' ? 'is-active': '' }}">{{ __('message.claimsinfo') }}</a>
            </nav>

            <div class="pds-os-profile-body">
                @if( $type == 'detail' )
                    @php
                        $approval = $data->approval_status ?? 'approved';
                        $approvalTone = match ($approval) {
                            'pending' => 'pending',
                            'rejected' => 'rejected',
                            default => 'approved',
                        };
                    @endphp
                    <div class="pds-os-profile-grid">
                        <aside class="pds-os-profile-card">
                            <div class="pds-os-profile-card__banner"></div>
                            <div class="pds-os-profile-card__avatar-wrap">
                                <img class="pds-os-profile-card__avatar profile_image_preview" src="{{ getSingleMedia($data,'profile_image', null) }}" alt="">
                            </div>
                            <h3 class="pds-os-profile-card__name">{{ optional($data)->name }}</h3>
                            <span class="pds-os-approval-pill is-{{ $approvalTone }}">{{ __('message.'.$approval) }}</span>

                            <ul class="pds-os-profile-meta">
                                <li>
                                    <span class="pds-os-profile-meta__icon"><i class="fas fa-envelope"></i></span>
                                    <span>{{ auth()->user()->hasRole('admin') ? maskSensitiveInfo('email',optional($data)->email) : maskSensitiveInfo('email',optional($data)->email) }}</span>
                                </li>
                                <li>
                                    <span class="pds-os-profile-meta__icon"><i class="fas fa-phone-alt"></i></span>
                                    <span>{{ auth()->user()->hasRole('admin') ? maskSensitiveInfo('contact_number',optional($data)->contact_number) : maskSensitiveInfo('contact_number',optional($data)->contact_number) }}</span>
                                </li>
                                <li>
                                    <span class="pds-os-profile-meta__icon"><i class="fas fa-map-marker-alt"></i></span>
                                    <span>
                                        {{ auth()->user()->hasRole('admin') ? (optional($data->city)->name ?: '—') : maskSensitiveInfo('city',optional($data->city)->name) }}
                                        ·
                                        {{ auth()->user()->hasRole('admin') ? (optional($data->country)->name ?: '—') : maskSensitiveInfo('country',optional($data->country)->name) }}
                                    </span>
                                </li>
                                <li>
                                    <span class="pds-os-profile-meta__icon"><i class="fas fa-clock"></i></span>
                                    <span>{{ __('message.last_active') }} · {{ auth()->user()->hasRole('admin') ? (dateAgoFormate($data->last_actived_at) ?: '—') : '—' }}</span>
                                </li>
                            </ul>

                            @if(auth()->user()->can('users-edit'))
                                <div class="pds-os-profile-card__approve">
                                    <label for="osProfileApproval">{{ __('message.status') }}</label>
                                    <select
                                        id="osProfileApproval"
                                        class="pds-os-approval-select js-os-approval-status"
                                        data-id="{{ $data->id }}"
                                        data-url="{{ route('users.approval-status', $data->id) }}"
                                        data-tone="{{ $approval }}"
                                    >
                                        <option value="pending" @selected($approval === 'pending')>{{ __('message.pending') }}</option>
                                        <option value="approved" @selected($approval === 'approved')>{{ __('message.approved') }}</option>
                                        <option value="rejected" @selected($approval === 'rejected')>{{ __('message.rejected') }}</option>
                                    </select>
                                </div>
                            @endif
                        </aside>

                        <div class="pds-os-profile-panels">
                            <section class="pds-os-profile-panel">
                                <header class="pds-os-profile-panel__head">
                                    <h5>{{ __('message.verification_detail')}}</h5>
                                </header>
                                <div class="table-responsive">
                                    <table class="table pds-os-profile-table mb-0" role="grid">
                                        <thead>
                                            <tr>
                                                <th>{{ __('message.type') }}</th>
                                                <th>{{ __('message.is_auto_verified') }}</th>
                                                <th>{{ __('message.verified_date') }}</th>
                                                <th>{{ __('message.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{__('message.email')}}</td>
                                                <td>
                                                    <span class="pds-os-verify-chip {{ $user->is_autoverified_email == 1 ? 'is-yes' : 'is-no' }}">
                                                        {{ $user->is_autoverified_email == 1 ? __('message.yes') : __('message.no') }}
                                                    </span>
                                                </td>
                                                <td>{{ dateAgoFormate($user->email_verified_at) ?: '—' }}</td>
                                                <td>
                                                    @if($user->email_verified_at !=null)
                                                        <button type="button" class="pds-os-reverify-btn update-verification" data-type="email" data-id="{{ $user->id }}">{{__('message.re_verification')}}</button>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>{{__('message.mobile')}}</td>
                                                <td>
                                                    <span class="pds-os-verify-chip {{ $user->is_autoverified_mobile == 1 ? 'is-yes' : 'is-no' }}">
                                                        {{ $user->is_autoverified_mobile == 1 ? __('message.yes') : __('message.no') }}
                                                    </span>
                                                </td>
                                                <td>{{ dateAgoFormate($user->otp_verify_at) ?: '—' }}</td>
                                                <td>
                                                    @if($user->otp_verify_at !=null)
                                                        <button type="button" class="pds-os-reverify-btn update-verification" data-type="mobile" data-id="{{ $user->id }}">{{__('message.re_verification')}}</button>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <form id="update-form" action="{{ route('update-verification', ['user' => $user]) }}" method="POST" style="display: none;">
                                        @csrf
                                        <input type="hidden" name="type" id="update-type">
                                        <input type="hidden" name="id" id="update-id">
                                        <input type="hidden" name="confirm" id="update-confirm">
                                    </form>
                                </div>
                            </section>

                            <section class="pds-os-profile-panel">
                                <header class="pds-os-profile-panel__head">
                                    <h5>{{ __('message.bank_details')}}</h5>
                                </header>
                                @forelse ($bank_detail as $value)
                                    <div class="pds-os-bank-grid">
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.bank_name') }}</span>
                                            <strong>{{ optional($value)->bank_name ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.bank_account_holder_name') }}</span>
                                            <strong>{{ optional($value)->account_holder_name ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.account_number') }}</span>
                                            <strong>{{ optional($value)->account_number ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.bank_ifsc_code') }}</span>
                                            <strong>{{ optional($value)->bank_code ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.bank_address') }}</span>
                                            <strong>{{ optional($value)->bank_address ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.routing_number') }}</span>
                                            <strong>{{ optional($value)->routing_number ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.bank_iban') }}</span>
                                            <strong>{{ optional($value)->bank_iban ?: '—' }}</strong>
                                        </div>
                                        <div>
                                            <span class="pds-os-bank-label">{{ __('message.bank_swift') }}</span>
                                            <strong>{{ optional($value)->bank_swift ?: '—' }}</strong>
                                        </div>
                                    </div>
                                @empty
                                    <p class="pds-os-profile-empty">{{ __('message.no_record_found') }}</p>
                                @endforelse
                            </section>
                        </div>
                    </div>
                @else
                    <div class="row">
                                @if( $type == 'wallethistory' )
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="card card-block ml-1">
                                                    <div class="card-body">
                                                        <div class="top-block-one">
                                                            <p class="mb-1">{{ __('message.wallet_total_balance') }}</p>
                                                            <h5>{{ getPriceFormat($earning_detail->user_wallet_sum_total_amount) }} </h5>
                                                        </div>

                                                    </div>
                                                </div>
                                                <div class="card card-block pl-3 ml-1">
                                                    <div class="card-header d-flex justify-content-between">
                                                        <div class="header-title">
                                                            <h4 class="card-title mb-0">{{ __('message.add_form_title', [ 'form' => __('message.wallet') ]) }}</h4>
                                                        </div>
                                                    </div>
                                                    <div class="card-body mr-2">
                                                        {!! html()->form('POST', route('savewallet-save', $data->id))->open() !!}
                                                        <div class="row">
                                                            <div class="form-group col-md-6">
                                                                {!! html()->label(__('message.type') . ' <span class="text-danger">*</span>')->class('form-control-label') !!}
                                                                {!! html()->select('type', ['credit' => __('message.credit'),'debit' => __('message.debit')], old('type'))->class('form-control select2js')->required() !!}
                                                            </div>
                                                            <div class="form-group col-md-6">
                                                                {!! html()->label(__('message.amount') . ' <span class="text-danger">*</span>')->class('form-control-label') !!}
                                                                {!! html()->number('amount', old('amount'))->class('form-control')->attribute('min', 0)->attribute('step', 'any')->required()->placeholder(__('message.amount')) !!}
                                                            </div>
                                                        </div>
                                                            {!! html()->button(__('message.save'))->class('btn btn-md btn-primary float-right')->type('submit') !!}
                                                        {!! html()->form()->close() !!}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card card-block mr-1 ml-1">
                                                    <div class="card-header d-flex justify-content-between flex-wrap">
                                                        <div class="header-title">
                                                            <h4 class="card-title">{{ __('message.wallethistory')}}</h4>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-2 mr-2">
                                                        <div class="table-responsive mt-4">
                                                            <table id="basic-table" class="table mb-1  text-center" role="grid">
                                                                <thead>
                                                                    <tr>
                                                                        <th scope='col'>{{ __('message.order_id') }}</th>
                                                                        <th scope='col'>{{ __('message.transaction_type') }}</th>
                                                                        <th scope='col'>{{ __('message.amount') }}</th>
                                                                        <th scope='col'>{{ __('message.date') }}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>

                                                                    @if(count($wallet_history) > 0)
                                                                        @foreach ( $wallet_history  as $value)
                                                                            <tr>
                                                                                <td><a href="{{ route('order-view.show', $value->order_id) }}">{{ optional($value)->order_id }}</a></td>
                                                                                @php
                                                                                $key = str_replace('_', ' ', ucwords($value->transaction_type ?? '-', '_'));
                                                                                @endphp
                                                                                <td>{{$key}}</td>
                                                                                <td>{{ getPriceFormat($value->amount) ?? '-' }}</td>
                                                                                <td>{{ dateAgoFormate($value->created_at) ?? '-' }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    @else
                                                                        <tr>
                                                                            <td colspan="9">{{ __('message.no_record_found') }}</td>
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
                                @endif
                                @if( $type == 'orderhistory' )
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="card card-block mr-1 ml-1">
                                                    <div class="card-body p-2 mr-2">
                                                        <table id="basic-table" class="table mb-3  text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th scope='col'>{{ __('message.order_id') }}</th>
                                                                    <th scope='col'>{{ __('message.pickup_address') }}</th>
                                                                    <th scope='col'>{{ __('message.delivery_address') }}</th>
                                                                    <th scope='col'>{{ __('message.delivery_man') }}</th>
                                                                    <th scope='col'>{{ __('message.pickup_date') }}</th>
                                                                    <th scope='col'>{{ __('message.delivery_date') }}</th>
                                                                    <th scope='col'>{{ __('message.invoice') }}</th>
                                                                    <th scope='col'>{{ __('message.created_at') }}</th>
                                                                    <th scope='col'>{{ __('message.status') }}</th>
                                                                    <th scope='col'>{{ __('message.is_return') }}</th>
                                                                    <th scope='col'>{{ __('message.assign') }}</th>
                                                                    <th scope='col'>{{ __('message.action') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @if($order->count() > 0)
                                                                    @foreach ( $order as $orders )
                                                                        @php
                                                                            $status = 'primary';
                                                                                $order_status = $orders->status;
                                                                            switch ($order_status) {
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
                                                                                $status_name = __('message.completed');
                                                                                break;
                                                                            case 'courier_assigned':
                                                                                $status = 'warning';
                                                                                $status_name = __('message.courier_assigned');
                                                                                break;
                                                                            case 'active':
                                                                                $status = 'info';
                                                                                $status_name = __('message.active');
                                                                                break;
                                                                            case 'courier_departed':
                                                                                $status = 'info';
                                                                                $status_name = __('message.courier_departed');
                                                                                break;
                                                                            case 'courier_picked_up':
                                                                                $status = 'warning';
                                                                                $status_name = __('message.pickup');
                                                                                break;
                                                                            case 'courier_arrived':
                                                                                $status = 'warning';
                                                                                $status_name = __('message.arrived');
                                                                                break;
                                                                            case 'cancellled':
                                                                                $status = 'danger';
                                                                                $status_name = __('message.cancellled');
                                                                                break;
                                                                            case 'delayed':
                                                                                $status = 'secondary';
                                                                                $status_name = __('message.delayed');
                                                                                break;
                                                                            case 'failed':
                                                                                $status = 'danger';
                                                                                $status_name = __('message.failed');
                                                                                break;
                                                                            }
                                                                        @endphp

                                                                        <tr>
                                                                            <td><a href="{{ route('order.show', $orders) }}">{{ optional($orders)->id }}</a></td>
                                                                            <td>
                                                                                <?php
                                                                                    $pickup_address = $orders->pickup_point['address'];
                                                                                    echo (isset($pickup_address)) ?'<span data-toggle="tooltip" title="'.$pickup_address.'">'.stringLong($pickup_address, 'title',20).'</span>' : '-';
                                                                                ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                    $delivery_address = $orders->delivery_point['address'];
                                                                                    echo (isset($delivery_address)) ?'<span data-toggle="tooltip" title="'.$delivery_address.'">'.stringLong($delivery_address, 'title',20).'</span>' : '-';
                                                                                ?>
                                                                            </td>
                                                                            <td><a href="{{ route('deliveryman.show', $orders->delivery_man_id ?? '') }}">{{ optional($orders->delivery_man)->name ?? '-' }}</a></td>
                                                                            <td>{{ dateAgoFormate($orders->pickup_datetime) ?? '-' }}</td>
                                                                            <td>{{ dateAgoFormate($orders->delivery_datetime) ?? '-' }}</td>
                                                                            <td>
                                                                                @if(optional($orders)->status == 'completed')
                                                                                    <a href="{{ route('order-invoice', $orders->id) }}"><i class="fa fa-download"></i></a>
                                                                                @else
                                                                                N/A
                                                                                @endif
                                                                            </td>
                                                                            <td>{{ dateAgoFormate($orders->created_at) ?? '-' }}</td>
                                                                            <td><span class="badge bg-{{$status}}">{{ __('message.'.$order_status) }}</span></td>
                                                                            <td>
                                                                                @php
                                                                                $parentOrderIds = $orders->pluck('parent_order_id')->toArray();
                                                                                @endphp
                                                                                    @if (in_array($orders->id, $parentOrderIds))
                                                                                        <i class="fa-solid fa-right-left text-primary"></i>
                                                                                    @else
                                                                                       {{'-'}}
                                                                                    @endif
                                                                            </td>
                                                                            <td>
                                                                                @if($orders->deleted_at)
                                                                                    <span style="color: red">{{ __('message.order_deleted') }}</span>
                                                                                @elseif($orders->status === 'cancelled')
                                                                                    <span class='text-primary'>{{ __('message.order_cancelled') }}</span>
                                                                                @elseif($orders->status === 'draft')
                                                                                    <span class='text-primary'>{{ __('message.order_draft') }}</span>
                                                                                @elseif($orders->status === 'completed')
                                                                                    <span class='text-primary'>{{ __('message.order_completed') }}</span>
                                                                                @elseif($orders->delivery_man_id === null)
                                                                                    <a href="{{ route('order-assign', ['id' => $orders->id]) }}" class="btn btn-sm btn-outline-primary loadRemoteModel">{{ __('message.assign') }}</a>
                                                                                @else
                                                                                    <a href="{{ route('order-assign', ['id' => $orders->id]) }}" class="btn btn-sm btn-outline-primary loadRemoteModel">{{ __('message.transfer') }}</a>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex justify-content-end align-items-center">
                                                                                    {!! html()->form('DELETE', route('order.destroy', $orders->id))->attribute('data--submit', 'order' . $orders->id)->open() !!}
                                                                                        <a class="mr-2 text-danger" href="javascript:void(0)" data--submit="order{{$orders->id}}"
                                                                                            data--confirmation='true' data-title="{{ __('message.delete_form_title',['form'=> __('message.order') ]) }}"
                                                                                            title="{{ __('message.delete_form_title',['form'=>  __('message.order') ]) }}"
                                                                                            data-message='{{ __("message.delete_msg") }}'>
                                                                                            <i class="fas fa-trash-alt"></i>
                                                                                        </a>
                                                                                    {!! html()->form()->close() !!}
                                                                                    @if( auth()->user()->hasRole('admin') && $orders->status != 'draft' )
                                                                                        <a class="mr-2" href="{{ route('order.show',$orders->id) }}"><i class="fas fa-eye text-secondary"></i></a>
                                                                                    @endif
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                @else
                                                                    <tr>
                                                                        <td colspan="9">{{ __('message.no_record_found') }}</td>
                                                                    </tr>
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if ($type == 'withdrawrequest')
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="card card-block mr-1 ml-1">
                                                    <div class="card-body p-2 mr-2">
                                                        <table id="basic-table" class="table mb-3  text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th scope='col'>{{ __('message.no') }}</th>
                                                                    <th scope='col'>{{ __('message.amount') }}</th>
                                                                    <th scope='col'>{{ __('message.available_balnce') }}</th>
                                                                    <th scope='col'>{{ __('message.request_at') }}</th>
                                                                    <th scope='col'>{{ __('message.action_at') }}</th>
                                                                    <th scope='col'>{{ __('message.bank_details') }}</th>
                                                                    <th scope='col'>{{ __('message.status') }}</th>
                                                                    <th scope='col'>{{ __('message.action') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @if($withdraw->count() > 0)
                                                                    @php
                                                                        $counter = 1;
                                                                    @endphp
                                                                    @foreach($withdraw as $value)
                                                                        <tr>
                                                                            <td>{{ $counter }}</td>
                                                                            <td>{{ getPriceFormat($value->amount) ?? 0 }}</td>
                                                                            <td>
                                                                            {{ $value->status == 'requested' ? ($wallte ? getPriceFormat($wallte->total_amount) : 0) : '-' }}
                                                                            </td>
                                                                            <td>{{ dateAgoFormate($value->created_at) ?? '-' }}</td>
                                                                            <td>{{ dateAgoFormate($value->updated_at) ?? '-' }}</td>
                                                                            <td>
                                                                                <a class="mr-2 loadRemoteModel" href="{{ route('withdrawrequest.show',$value->user_id) }}"><i class="fas fa-eye text-secondary"></i></a>
                                                                            </td>
                                                                            <td>
                                                                                @php
                                                                                    $status = 'danger';
                                                                                    $status_name = 'requested';
                                                                                    switch ($value->status) {
                                                                                        case 'requested':
                                                                                            $status = 'indigo';
                                                                                            $status_name = __('message.pending');
                                                                                            break;
                                                                                        case 'decline':
                                                                                            $status = 'danger';
                                                                                            $status_name = __('message.declined');
                                                                                            break;
                                                                                        case 'approved':
                                                                                            $status = 'success';
                                                                                            $status_name = __('message.approved');
                                                                                            break;
                                                                                    }
                                                                                @endphp
                                                                            <span class="text-capitalize badge bg-{{$status}}">{{$status_name  ?? ''}} </span>
                                                                            </td>
                                                                            <td>
                                                                                @if($value->status == 'requested')
                                                                                    <a class="mr-2" href="{{ route('approvedWithdrawRequest', ['id' => $value->id]) }}"><i class="fas fa-check"></i></a>
                                                                                    <a class="mr-2" href="{{ route('declineWithdrawRequest', ['id' => $value->id]) }}"><i class="fa fa-times text icon-color"></i></a>
                                                                                @else
                                                                                    {{'-'}}
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                        @php
                                                                            $counter++;
                                                                        @endphp
                                                                    @endforeach
                                                                @else
                                                                    <tr>
                                                                        <td colspan="9">{{ __('message.no_record_found') }}</td>
                                                                    </tr>
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($type == 'useraddress')
                                    <div class="col-md-12">
                                        @foreach($userAddresses as $item)
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div>
                                                                <strong>{{ $item->address }}</strong><br>
                                                                <strong>{{ auth()->user()->hasRole('admin') ? maskSensitiveInfo('contact_number',optional($item)->contact_number) : maskSensitiveInfo('contact_number',optional($item)->contact_number) }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if($type == 'claimsinfo')
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="card card-block mr-1 ml-1">
                                                <div class="card-body p-2 mr-2">
                                                    <table id="basic-table" class="table mb-3  text-center">
                                                        <thead>
                                                            <tr>
                                                                <th scope='col'>{{ __('message.id') }}</th>
                                                                <th scope='col'>{{ __('message.traking_no') }}</th>
                                                                <th scope='col'>{{ __('message.prof_value') }}</th>
                                                                <th scope='col'>{{ __('message.detail') }}</th>
                                                                <th scope='col'>{{ __('message.status') }}</th>
                                                                <th scope='col'>{{ __('message.action') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ( $claims as $value )
                                                                @php
                                                                    $status = 'primary';
                                                                    $status_name = 'pending';
                                                                    switch ($value->status) {
                                                                        case 'pending':
                                                                            $status = 'primary';
                                                                            $status_name = __('message.pending');
                                                                            break;
                                                                        case 'approved':
                                                                            $status = 'success';
                                                                            $status_name = __('message.approved');
                                                                            break;
                                                                        case 'reject':
                                                                            $status = 'warning';
                                                                            $status_name = __('message.reject');
                                                                            break;
                                                                        case 'close':
                                                                            $status = 'danger';
                                                                            $status_name = __('message.close');
                                                                            break;
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td>{{ $value->id ?? '-'}}</td>
                                                                    <td>{{ $value->traking_no ?? '-' }}</td>
                                                                    @php
                                                                        $shortText = strlen($value->prof_value) > 30 ? substr($value->prof_value, 0, 30) . '...' : $value->prof_value;
                                                                    @endphp

                                                                    <td>
                                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $value->prof_value }}">
                                                                            {{ $shortText ?? '-' }}
                                                                        </span>
                                                                    </td>
                                                                    @php
                                                                    $shortdetails = strlen($value->detail) > 30 ? substr($value->detail, 0, 30) . '...' : $value->detail;
                                                                    @endphp
                                                                    <td>
                                                                        <span data-toggle="tooltip" data-placement="top" title="{{ $value->prof_value }}">
                                                                            {{ $shortdetails ?? '-' }}
                                                                        </span>
                                                                    </td>
                                                                    <td><span class="badge bg-{{ $status }}">{{ $status_name }}</span></td>
                                                                    <td>
                                                                        <div class="d-flex justify-content-end align-items-center">
                                                                            <a class="mr-2" href="{{ route('claims.show',$value->id) }}"><i class="fas fa-eye text-secondary"></i></a>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
    @section('bottom_script')
    <script>
           $("#basic-table").DataTable({
                "dom":  '<"row align-items-center"<"col-md-2"><"col-md-6" B><"col-md-4"f>><"table-responsive my-3" rt><"d-flex" <"flex-grow-1" l><"p-2" i><"mt-4" p>><"clear">',
                "order": [[0, "desc"]]
            });
            $(document).ready(function() {
                $('[data-toggle="tooltip"]').tooltip();
                $('.update-verification').click(function() {
                    var type = $(this).data('type');
                    var id = $(this).data('id');
                    var message = 'Are you sure you want to re-verify ' + type + '?';
                    Swal.fire({
                        title: message,
                        showDenyButton: true,
                        confirmButtonText: 'Yes',
                        denyButtonText: 'No'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#update-type').val(type);
                            $('#update-id').val(id);
                            $('#update-confirm').val('yes');
                            $('#update-form').submit();
                        } else if (result.isDenied) {
                            $('#update-confirm').val('no');
                        }
                    });
                });

                $(document).on('change', '.js-os-approval-status', function () {
                    var $el = $(this);
                    $el.prop('disabled', true);
                    $el.attr('data-tone', $el.val());
                    $.ajax({
                        url: $el.data('url'),
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            approval_status: $el.val()
                        }
                    }).done(function (res) {
                        if (typeof SnackBar === 'function') {
                            SnackBar({ message: res.message || 'Updated', status: 'success' });
                        }
                        var tone = res.approval_status || $el.val();
                        $('.pds-os-approval-pill')
                            .removeClass('is-pending is-approved is-rejected')
                            .addClass('is-' + tone)
                            .text(res.label || tone);
                    }).fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Update failed';
                        if (typeof SnackBar === 'function') {
                            SnackBar({ message: msg, status: 'error' });
                        } else {
                            alert(msg);
                        }
                    }).always(function () {
                        $el.prop('disabled', false);
                    });
                });
            });
        </script>
    @endsection
</x-master-layout>
