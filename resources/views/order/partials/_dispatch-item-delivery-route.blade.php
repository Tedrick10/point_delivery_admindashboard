<section class="pds-dispatch-item-section">
    <header class="pds-dispatch-item-section-head">
        <i class="fas fa-route"></i>
        <span>{{ __('message.delivery_route') }}</span>
    </header>
    <div class="pds-dispatch-item-section-body">
        <div class="pds-dispatch-grid pds-dispatch-grid-3 pds-dispatch-item-grid">
            <div class="pds-dispatch-field">
                <label for="item_received_date">{{ __('message.received_date') }}</label>
                <input type="text" name="received_date" id="item_received_date"
                       class="pds-dispatch-input dispatch-item-datepicker" value="{{ $receivedDate }}" required>
            </div>
            <div class="pds-dispatch-field">
                <label for="from_branch_id">{{ __('message.from') }}</label>
                <select name="from_branch_id" id="from_branch_id" class="pds-dispatch-input dispatch-item-select2" required>
                    <option value="">{{ __('message.select_name', ['select' => __('message.from')]) }}</option>
                    @foreach($branchCities as $city)
                        <option value="{{ $city->id }}" @selected((string) $fromBranchId === (string) $city->id)>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pds-dispatch-field">
                <div class="pds-dispatch-field-label-row">
                    <label for="to_branch_id">{{ __('message.to') }}</label>
                    <button type="button" class="pds-dispatch-field-add"
                            data-dispatch-add-target="#to_branch_id"
                            data-dispatch-add-type="branch"
                            data-dispatch-add-prompt="{{ __('message.enter_name', ['name' => __('message.to')]) }}">
                        + {{ __('message.add') }}
                    </button>
                </div>
                <select name="to_branch_id" id="to_branch_id" class="pds-dispatch-input dispatch-item-select2" required>
                    <option value="">{{ __('message.select_name', ['select' => __('message.to')]) }}</option>
                    @foreach($branchCities as $city)
                        <option value="{{ $city->id }}" @selected((string) $toBranchId === (string) $city->id)>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="pds-dispatch-grid pds-dispatch-grid-2 pds-dispatch-item-grid">
            <div class="pds-dispatch-field">
                <div class="pds-dispatch-field-label-row">
                    <label for="item_delivery_city">{{ __('message.city') }}</label>
                    <button type="button" class="pds-dispatch-field-add"
                            data-dispatch-add-target="#item_delivery_city"
                            data-dispatch-add-type="city"
                            data-dispatch-add-prompt="{{ __('message.enter_name', ['name' => __('message.city')]) }}">
                        + {{ __('message.add') }}
                    </button>
                </div>
                <select name="delivery_city" id="item_delivery_city" class="pds-dispatch-input dispatch-item-select2" required>
                    <option value="">{{ __('message.select_name', ['select' => __('message.city')]) }}</option>
                    @foreach($deliveryCities as $routeCity)
                        <option value="{{ $routeCity['name'] }}"
                                data-name="{{ $routeCity['name'] }}"
                                data-nrc-state="{{ $routeCity['nrc_state'] }}"
                                @selected((string) $deliveryCity === (string) $routeCity['name'])>{{ $routeCity['name_mm'] ?? $routeCity['name'] }}</option>
                    @endforeach
                    @if($hasCustomDeliveryCity)
                        <option value="{{ $deliveryCity }}"
                                data-name="{{ $deliveryCity }}"
                                data-nrc-state=""
                                selected>{{ $deliveryCity }}</option>
                    @endif
                </select>
            </div>
            <div class="pds-dispatch-field">
                <div class="pds-dispatch-field-label-row">
                    <label for="item_township">{{ __('message.township') }}</label>
                    <button type="button" class="pds-dispatch-field-add"
                            data-dispatch-add-target="#item_township"
                            data-dispatch-add-type="township"
                            data-dispatch-add-prompt="{{ __('message.enter_name', ['name' => __('message.township')]) }}">
                        + {{ __('message.add') }}
                    </button>
                </div>
                <select name="township" id="item_township" class="pds-dispatch-input dispatch-item-select2" required>
                    <option value="">{{ __('message.select_name', ['select' => __('message.township')]) }}</option>
                    @if($township)
                        <option value="{{ $township }}" selected>{{ $township }}</option>
                    @endif
                </select>
            </div>
        </div>
    </div>
</section>
