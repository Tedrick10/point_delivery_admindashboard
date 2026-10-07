{{-- Super Admin Account Creation board --}}
<section class="sa-module-panel sa-account-board">
    <header class="sa-module-panel__head">
        <h3>{{ __('message.sa_account_creation_list_title') }}</h3>
        <a href="{{ route('super-admin.account-creation.create') }}" class="sa-module-hero__btn">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <span>{{ __('message.add_form_title', ['form' => __('message.sub_admin')]) }}</span>
        </a>
    </header>

    <form method="GET" action="{{ route('super-admin.screens.show', ['screen' => 'account-creation']) }}" class="sa-account-filter">
        <label class="sa-account-filter__search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $accountCreation['filterQ'] ?? '' }}" placeholder="{{ __('message.search') }}">
        </label>
        <select name="role">
            <option value="">{{ __('message.role') }}</option>
            @foreach(($accountCreation['roleOptions'] ?? []) as $value => $label)
                <option value="{{ $value }}" @selected(($accountCreation['filterRole'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="sa-module-hero__btn">{{ __('message.filter') }}</button>
    </form>

    <div class="sa-module-table-wrap">
        <table class="sa-module-table sa-account-table">
            <thead>
                <tr>
                    <th class="sa-account-table__num">#</th>
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
                    @php
                        $on = (bool) $member->isRiderWorkOn();
                        $roleKey = strtolower((string) $member->user_type);
                    @endphp
                    <tr data-user-id="{{ $member->id }}">
                        <td class="sa-account-table__num">{{ ($accountCreation['users']->firstItem() ?? 1) + $i }}</td>
                        <td>
                            <div class="sa-person">
                                <span class="sa-avatar">{{ $initials((string) $member->name) }}</span>
                                <strong>{{ $member->name }}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="sa-role-pill sa-role-pill--{{ preg_replace('/[^a-z0-9]+/', '-', $roleKey) }}">
                                {{ ucwords(str_replace('_', ' ', (string) $member->user_type)) }}
                            </span>
                        </td>
                        <td class="sa-muted">{{ $member->email }}</td>
                        <td class="sa-muted">{{ $member->contact_number }}</td>
                        <td>
                            <label class="sa-work-switch{{ $on ? ' is-on' : ' is-off' }}">
                                <input type="checkbox"
                                       class="js-sa-employee-work-toggle"
                                       data-id="{{ $member->id }}"
                                       {{ $on ? 'checked' : '' }}>
                                <span class="sa-work-switch__track" aria-hidden="true"></span>
                                <span class="js-sa-work-label">{{ $on ? __('message.rider_work_on') : __('message.rider_work_off') }}</span>
                            </label>
                        </td>
                        <td>
                            <div class="sa-account-actions">
                                <a href="{{ route('super-admin.account-creation.edit', $member->id) }}" class="sa-account-edit">
                                    <i class="fas fa-pen"></i> {{ __('message.edit') }}
                                </a>
                                <form method="POST"
                                      action="{{ route('super-admin.account-creation.destroy', $member->id) }}"
                                      onsubmit="return confirm(@json(__('message.delete_msg')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="sa-account-delete">{{ __('message.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="sa-account-empty">{{ __('message.no_record_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(($accountCreation['users'] ?? null) && method_exists($accountCreation['users'], 'links'))
        <div class="sa-account-pager">{{ $accountCreation['users']->links() }}</div>
    @endif
</section>
