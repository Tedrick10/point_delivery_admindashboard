{{-- Super Admin Account Creation board --}}
<section class="sa-module-panel sa-late-fine-staff-panel">
    <header class="sa-module-panel__head">
        <h3>{{ __('message.sa_account_creation_list_title') }}</h3>
        <a href="{{ route('super-admin.account-creation.create') }}" class="sa-module-hero__btn">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <span>{{ __('message.add_form_title', ['form' => __('message.sub_admin')]) }}</span>
        </a>
    </header>
    <p class="sa-fuel-default-panel__hint">{{ __('message.sa_account_creation_list_hint') }}</p>

    <form method="GET" action="{{ route('super-admin.screens.show', ['screen' => 'account-creation']) }}" class="sa-account-filter">
        <input type="search" name="q" value="{{ $accountCreation['filterQ'] ?? '' }}" placeholder="{{ __('message.search') }}">
        <select name="role">
            <option value="">{{ __('message.hr_extra_list_filter_all_groups') }} / {{ __('message.role') }}</option>
            @foreach(($accountCreation['roleOptions'] ?? []) as $value => $label)
                <option value="{{ $value }}" @selected(($accountCreation['filterRole'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="sa-module-hero__btn">{{ __('message.filter') }}</button>
    </form>

    <div class="sa-module-table-wrap">
        <table class="sa-module-table sa-late-fine-staff-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('message.name') }}</th>
                    <th>{{ __('message.role') }}</th>
                    <th>{{ __('message.email') }}</th>
                    <th>{{ __('message.contact_number') }}</th>
                    <th>On/Off</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse(($accountCreation['users'] ?? []) as $i => $member)
                    @php $on = (bool) $member->isRiderWorkOn(); @endphp
                    <tr data-user-id="{{ $member->id }}">
                        <td>{{ ($accountCreation['users']->firstItem() ?? 1) + $i }}</td>
                        <td><strong>{{ $member->name }}</strong></td>
                        <td>{{ ucwords(str_replace('_', ' ', (string) $member->user_type)) }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->contact_number }}</td>
                        <td>
                            <label class="sa-work-switch{{ $on ? ' is-on' : ' is-off' }}">
                                <input type="checkbox"
                                       class="js-sa-employee-work-toggle"
                                       data-id="{{ $member->id }}"
                                       {{ $on ? 'checked' : '' }}>
                                <span class="js-sa-work-label">{{ $on ? __('message.rider_work_on') : __('message.rider_work_off') }}</span>
                            </label>
                        </td>
                        <td class="sa-account-actions">
                            <a href="{{ route('super-admin.account-creation.edit', $member->id) }}" class="sa-module-panel__link">
                                <i class="fas fa-edit"></i> {{ __('message.edit') }}
                            </a>
                            <form method="POST"
                                  action="{{ route('super-admin.account-creation.destroy', $member->id) }}"
                                  class="d-inline"
                                  onsubmit="return confirm(@json(__('message.delete_msg')));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sa-account-delete">{{ __('message.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">{{ __('message.no_record_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(($accountCreation['users'] ?? null) && method_exists($accountCreation['users'], 'links'))
        <div class="sa-account-pager">{{ $accountCreation['users']->links() }}</div>
    @endif
</section>

<style>
    .sa-account-filter {
        display: flex; flex-wrap: wrap; gap: 10px; margin: 0 0 14px;
    }
    .sa-account-filter input,
    .sa-account-filter select {
        min-width: 180px; flex: 1; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: 0.55rem 0.8rem; background: #fff;
    }
    .sa-account-actions {
        display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: flex-end;
    }
    .sa-account-delete {
        border: 0; background: transparent; color: #b91c1c; font-weight: 700; cursor: pointer; padding: 0;
    }
    .sa-work-switch {
        display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 700; font-size: 13px;
    }
    .sa-work-switch.is-on { color: #FE6F07; }
    .sa-work-switch.is-off { color: #64748b; }
    .sa-account-pager { margin-top: 14px; }
</style>
