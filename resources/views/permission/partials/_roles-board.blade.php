{{-- Shared Roles & Permissions board --}}
@php
    $canManageRoles = $canManageRoles ?? (isSuperAdmin($auth_user) || $auth_user->can('role-add'));
    $embedReturn = $embedReturn ?? null;
    $formAction = $formAction ?? route('permission.store');
    $addRoleUrl = $addRoleUrl ?? route('permission.add', ['type' => 'role']);
    $pageTitle = $pageTitle ?? __('message.roles_and_permission');
    $storeRoleUrl = $storeRoleUrl ?? null;
    $togglePermissionUrl = $togglePermissionUrl ?? null;
    $usePopups = !empty($embedReturn);
@endphp
<div class="container-fluid pds-page-wrap pds-motion-enter pds-roles-page{{ !empty($embedReturn) ? ' is-embed' : '' }}"
     style="padding:0;margin:0;"
     @if($usePopups)
        data-store-role-url="{{ $storeRoleUrl }}"
        data-toggle-url="{{ $togglePermissionUrl }}"
        data-csrf="{{ csrf_token() }}"
     @endif
>
        @if(empty($embedReturn))
        <div class="pds-roles-hero">
            <div class="pds-roles-hero__copy">
                <div class="pds-roles-hero__eyebrow">
                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                    <span>{{ __('message.account_creation') }}</span>
                </div>
                <h4 class="pds-roles-hero__title">{{ $pageTitle }}</h4>
                <p class="pds-roles-hero__subtitle">{{ __('message.roles_permission_subtitle') }}</p>
            </div>
            <div class="pds-roles-hero__actions">
                @if($canManageRoles)
                    <a href="{{ $addRoleUrl }}" class="btn btn-primary">
                        <i class="fa fa-plus-circle"></i> {{ __('message.add_form_title', ['form' => __('message.role')]) }}
                    </a>
                @endif
            </div>
        </div>
        @endif

        @php
            $protectedRoles = ['admin'];
        @endphp

        {{ html()->form('POST', $formAction)->id('rolesPermissionForm')->open() }}
            @if(!empty($embedReturn))
                <input type="hidden" name="embed_return" value="{{ $embedReturn }}">
            @endif
            <div class="pds-roles-shell">
                <aside class="pds-roles-aside">
                    <div class="pds-roles-aside__title">{{ __('message.role') }}</div>
                    <div class="pds-roles-tabs" id="roleTabs">
                        @foreach($roles as $index => $role)
                            <button type="button"
                                    class="pds-roles-tab{{ $index === 0 ? ' is-active' : '' }}"
                                    data-role-id="{{ $role->id }}"
                                    data-role-name="{{ $role->name }}">
                                <span class="pds-roles-tab__avatar">{{ strtoupper(substr(str_replace('_', '', $role->name), 0, 1)) }}</span>
                                <span class="pds-roles-tab__name">{{ ucwords(str_replace('_', ' ', $role->name)) }}</span>
                            </button>
                        @endforeach
                    </div>
                </aside>

                <section class="pds-roles-main">
                    <div class="pds-roles-main__toolbar">
                        <div>
                            <div class="pds-roles-main__label">{{ __('message.permission') }}</div>
                            <div class="pds-roles-main__current" id="currentRoleLabel">
                                {{ $roles->isNotEmpty() ? ucwords(str_replace('_', ' ', $roles->first()->name)) : '-' }}
                            </div>
                        </div>
                        <div class="pds-roles-main__buttons">
                            @if($canManageRoles)
                                @if($usePopups)
                                    <button type="button" class="pds-roles-add-btn js-pds-role-add">
                                        <i class="fa fa-plus"></i> {{ __('message.add_form_title', ['form' => __('message.role')]) }}
                                    </button>
                                @else
                                    <a href="{{ $addRoleUrl }}" class="pds-roles-add-btn loadRemoteModel">
                                        <i class="fa fa-plus"></i> {{ __('message.add_form_title', ['form' => __('message.role')]) }}
                                    </a>
                                @endif
                                @foreach($roles as $index => $role)
                                    @if(! in_array($role->name, $protectedRoles, true))
                                        @if($usePopups)
                                            <button type="button"
                                                    class="btn btn-outline-danger pds-roles-delete-btn js-pds-role-delete{{ $index === 0 ? ' is-visible' : '' }}"
                                                    data-role-panel="{{ $role->id }}"
                                                    data-delete-url="{{ route('super-admin.roles-permissions.destroy-role', $role->id) }}"
                                                    data-role-label="{{ ucwords(str_replace('_', ' ', $role->name)) }}">
                                                <i class="fas fa-trash-alt"></i> {{ __('message.delete') }}
                                            </button>
                                        @else
                                            <a href="javascript:void(0)"
                                               class="btn btn-outline-danger pds-roles-delete-btn{{ $index === 0 ? ' is-visible' : '' }}"
                                               data-role-panel="{{ $role->id }}"
                                               data--submit="role{{ $role->id }}"
                                               data--confirmation="true"
                                               data-title="{{ __('message.delete_form_title', ['form' => __('message.role')]) }}"
                                               data-message="{{ __('message.delete_msg') }}"
                                               title="{{ __('message.delete_form_title', ['form' => __('message.role')]) }}">
                                                <i class="fas fa-trash-alt"></i> {{ __('message.delete') }}
                                            </a>
                                        @endif
                                    @endif
                                @endforeach
                            @endif
                            @if(! $usePopups)
                            <button type="submit" class="btn btn-primary" @disabled($roles->isEmpty())>
                                <i class="fas fa-save"></i> {{ __('message.save') }}
                            </button>
                            @endif
                        </div>
                    </div>

                    <div class="pds-roles-modules">
                        @forelse($modules as $module)
                            <article class="pds-roles-module">
                                <header class="pds-roles-module__header">
                                    <div class="pds-roles-module__icon"><i class="{{ $module['icon'] }}"></i></div>
                                    <div>
                                        <h5>{{ $module['label'] }}</h5>
                                        <p>{{ $module['hint'] }}</p>
                                    </div>
                                </header>
                                <div class="pds-roles-module__actions">
                                    @foreach($module['permissions'] as $perm)
                                        @foreach($roles as $index => $role)
                                            <label class="pds-roles-chip{{ $index === 0 ? ' is-visible' : '' }}"
                                                   data-role-panel="{{ $role->id }}"
                                                   for="permission-{{ $role->id }}-{{ $perm['id'] }}">
                                                <input class="permission_check"
                                                       id="permission-{{ $role->id }}-{{ $perm['id'] }}"
                                                       type="checkbox"
                                                       name="permission[{{ $perm['name'] }}][]"
                                                       value="{{ $role->name }}"
                                                       data-role-name="{{ $role->name }}"
                                                       data-permission-name="{{ $perm['name'] }}"
                                                       {{ checkRolePermission($role, $perm['name']) ? 'checked' : '' }}
                                                       @if($role->is_hidden) disabled @endif>
                                                <span>{{ $perm['action'] }}</span>
                                            </label>
                                        @endforeach
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <div class="pds-roles-empty">{{ __('message.no_record_found') }}</div>
                        @endforelse
                    </div>
                </section>
            </div>
        {{ html()->form()->close() }}

        @if($canManageRoles && ! $usePopups)
            @foreach($roles as $role)
                @if(! in_array($role->name, $protectedRoles, true))
                    {{ html()->form('DELETE', route('role.destroy', $role->id))->attribute('data--submit', 'role' . $role->id)->class('d-none')->open() }}
                        <input type="hidden" name="redirect_to" value="{{ !empty($embedReturn) ? 'super-admin' : 'permission' }}">
                    {{ html()->form()->close() }}
                @endif
            @endforeach
        @endif

    <style>
        .pds-roles-page { --rp-gap: 16px; --site-color: #FE6F07; --brand-rgb: 254, 111, 7; }
        .pds-roles-hero {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
            background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.08) 0%, #ffffff 55%);
            border: 1px solid #ffe4cc; border-radius: 20px; padding: 22px 24px; margin-bottom: 18px;
        }
        .pds-roles-hero__eyebrow {
            display: inline-flex; align-items: center; gap: 8px; color: var(--site-color); font-weight: 600; font-size: 13px; margin-bottom: 6px;
        }
        .pds-roles-hero__title { margin: 0; font-size: 26px; font-weight: 700; color: #0f172a; }
        .pds-roles-hero__subtitle { margin: 6px 0 0; color: #64748b; }
        .pds-roles-shell {
            display: grid; grid-template-columns: 260px 1fr; gap: var(--rp-gap); align-items: start;
        }
        .pds-roles-aside, .pds-roles-main {
            background: #fff; border: 1px solid #e8edf5; border-radius: 20px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }
        .pds-roles-aside { padding: 16px; position: sticky; top: 90px; }
        .pds-roles-aside__title {
            font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; font-weight: 700; margin-bottom: 12px;
        }
        .pds-roles-tabs { display: grid; gap: 8px; }
        .pds-roles-tab {
            display: flex; align-items: center; gap: 12px; width: 100%; border: 1px solid transparent;
            background: #f8fafc; border-radius: 14px; padding: 12px 14px; text-align: left; transition: .2s ease;
        }
        .pds-roles-tab:hover { background: rgba(var(--brand-rgb), 0.08); border-color: #ffd8b0; }
        .pds-roles-tab.is-active {
            background: linear-gradient(135deg, var(--site-color), var(--site-color)); color: #fff; box-shadow: 0 8px 20px rgba(var(--brand-rgb), .28);
        }
        .pds-roles-tab__avatar {
            width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center;
            background: rgba(255,255,255,.75); color: var(--site-color); font-weight: 700;
        }
        .pds-roles-tab.is-active .pds-roles-tab__avatar { background: rgba(255,255,255,.2); color: #fff; }
        .pds-roles-tab__name { font-weight: 600; }
        .pds-roles-main { padding: 18px; min-height: 520px; }
        .pds-roles-main__toolbar {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid #eef2f7;
        }
        .pds-roles-main__label { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; }
        .pds-roles-main__current { font-size: 20px; font-weight: 700; color: #0f172a; }
        .pds-roles-main__buttons { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .pds-roles-delete-btn { display: none; }
        .pds-roles-delete-btn.is-visible { display: inline-flex; align-items: center; gap: 6px; }
        .pds-roles-add-btn { display: none; }
        .pds-roles-page.is-embed .pds-roles-hero { display: none !important; }
        .pds-roles-page.is-embed .pds-roles-add-btn {
            display: inline-flex; align-items: center; gap: 6px;
            border-radius: 999px; padding: 0.5rem 0.9rem; font-weight: 700;
            text-decoration: none; border: 1px solid #FFD4B0; background: #FFF4EC; color: #FE6F07;
        }
        .pds-roles-page.is-embed .pds-roles-add-btn:hover { background: #FE6F07; color: #fff; border-color: #FE6F07; }
        .pds-roles-page.is-embed .pds-roles-delete-btn.is-visible:hover,
        .pds-roles-page.is-embed .pds-roles-delete-btn:hover {
            background: #E11D48 !important;
            color: #fff !important;
            border-color: #E11D48 !important;
        }
        .pds-roles-modules { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .pds-roles-module {
            border: 1px solid #eef2f7; border-radius: 18px; padding: 16px; background: #fbfdff;
        }
        .pds-roles-module__header { display: flex; gap: 12px; margin-bottom: 14px; }
        .pds-roles-module__icon {
            width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center;
            background: rgba(var(--brand-rgb), 0.08); color: var(--site-color); flex: 0 0 auto;
        }
        .pds-roles-module__header h5 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
        .pds-roles-module__header p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
        .pds-roles-module__actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .pds-roles-chip {
            display: none; align-items: center; gap: 8px; border: 1px solid #e2e8f0; border-radius: 999px;
            padding: 8px 12px 8px 10px; background: #fff; cursor: pointer; user-select: none; margin: 0; font-weight: 600; color: #334155;
        }
        .pds-roles-chip.is-visible { display: inline-flex; }
        .pds-roles-chip:has(input:checked) {
            background: #FFF4EC; border-color: #FE6F07; color: #C2410C;
        }
        .pds-roles-chip input {
            position: absolute; opacity: 0; width: 0; height: 0; margin: 0; pointer-events: none;
        }
        .pds-roles-chip span {
            display: inline-flex; align-items: center; gap: 8px;
        }
        .pds-roles-chip span::before {
            content: "";
            width: 18px; height: 18px; flex: 0 0 18px;
            border-radius: 6px;
            border: 1.5px solid #cbd5e1;
            background: #fff;
            box-sizing: border-box;
            transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
        }
        .pds-roles-chip:has(input:checked) span::before {
            border-color: #FE6F07;
            background: #FE6F07
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12' fill='none'%3E%3Cpath d='M2.4 6.2 4.8 8.5 9.6 3.5' stroke='%23fff' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")
                center / 11px 11px no-repeat;
            box-shadow: 0 2px 6px rgba(254, 111, 7, 0.28);
        }
        .pds-roles-empty { grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b; }
        @media (max-width: 720px) {
            .pds-roles-shell { grid-template-columns: 1fr; }
            .pds-roles-aside { position: static; }
            .pds-roles-modules { grid-template-columns: 1fr; }
            .pds-roles-tabs { grid-template-columns: 1fr; }
        }
        .pds-roles-modal[hidden] { display: none !important; }
        .pds-roles-modal.is-open { display: grid !important; }
        .pds-roles-modal {
            position: fixed; inset: 0; z-index: 1080;
            display: grid; place-items: center;
            padding: 1.25rem;
        }
        .pds-roles-modal__backdrop {
            position: absolute; inset: 0;
            background: rgba(20, 17, 15, 0.42);
            backdrop-filter: blur(8px);
        }
        .pds-roles-modal__dialog {
            position: relative; z-index: 1;
            width: min(400px, 100%);
            background: #fff;
            border-radius: 24px;
            border: 1px solid #f3efe9;
            box-shadow: 0 28px 64px rgba(20, 17, 15, 0.18);
            padding: 1.5rem 1.4rem 1.25rem;
            text-align: left;
        }
        .pds-roles-modal__dialog.is-delete { width: min(380px, 100%); text-align: center; }
        .pds-roles-modal__close {
            position: absolute; top: 12px; right: 12px;
            width: 32px; height: 32px; border: 0; border-radius: 999px;
            background: #f6f3ef; color: #8a8178; cursor: pointer;
            display: grid; place-items: center;
        }
        .pds-roles-modal__close:hover { background: #efe8e0; color: #14110F; }
        .pds-roles-modal__icon {
            width: 52px; height: 52px; border-radius: 16px;
            display: grid; place-items: center;
            margin: 0 auto 1rem;
            background: #FFF4EC; color: #FE6F07; font-size: 1.15rem;
        }
        .pds-roles-modal__icon i { line-height: 1; }
        .pds-roles-modal__dialog.is-add h3 { text-align: center; }
        .pds-roles-modal__dialog.is-delete .pds-roles-modal__icon {
            background: #FFF1F2; color: #E11D48;
        }
        .pds-roles-modal__dialog h3 {
            margin: 0 0 0.35rem;
            font-size: 1.22rem;
            font-weight: 800;
            color: #14110F;
            letter-spacing: -0.02em;
        }
        .pds-roles-modal__dialog p {
            margin: 0 0 1.15rem;
            color: #7a726b;
            font-size: 0.92rem;
            line-height: 1.45;
        }
        .pds-roles-modal__dialog p strong { color: #14110F; font-weight: 700; }
        .pds-roles-modal .sa-field { margin-bottom: 0.9rem; }
        .pds-roles-modal .sa-field label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 0.4rem;
        }
        .pds-roles-modal .sa-field input {
            width: 100%;
            height: 46px;
            border: 1px solid #eee8e1;
            border-radius: 12px;
            padding: 0 0.9rem;
            background: #fafaf8;
            color: #14110F;
        }
        .pds-roles-modal .sa-field input:focus {
            background: #fff;
            border-color: #FE6F07;
            box-shadow: 0 0 0 3px rgba(254,111,7,0.14);
            outline: none;
        }
        .pds-roles-modal__actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            margin-top: 0.15rem;
        }
        .pds-roles-modal__actions .sa-btn {
            width: 100%;
            justify-content: center;
            height: 42px;
            border-radius: 12px;
            font-weight: 700;
        }
        .pds-roles-modal__dialog.is-delete .pds-roles-modal__danger {
            background: #E11D48;
            color: #fff !important;
            border: 0;
            box-shadow: 0 8px 18px rgba(225, 29, 72, 0.22);
        }
        .pds-roles-modal__dialog.is-delete .pds-roles-modal__danger:hover {
            background: #be123c;
            color: #fff !important;
        }
        .pds-roles-modal__error { color: #e11d48; font-size: 0.8rem; margin: 0 0 0.7rem; }
    </style>

    @if($usePopups && $canManageRoles)
        <div class="pds-roles-modal" id="pdsRoleAddModal" hidden>
            <div class="pds-roles-modal__backdrop" data-close-modal></div>
            <div class="pds-roles-modal__dialog is-add" role="dialog" aria-modal="true" aria-labelledby="pdsRoleAddTitle">
                <button type="button" class="pds-roles-modal__close" data-close-modal aria-label="{{ __('message.close') }}">
                    <i class="fas fa-times"></i>
                </button>
                <div class="pds-roles-modal__icon"><i class="fas fa-user-plus"></i></div>
                <h3 id="pdsRoleAddTitle">{{ __('message.add_form_title', ['form' => __('message.role')]) }}</h3>
                <form id="pdsRoleAddForm">
                    <div class="sa-field">
                        <label>{{ __('message.name') }}</label>
                        <input type="text" name="name" required autocomplete="off">
                    </div>
                    <div class="sa-field">
                        <label>{{ __('message.employee_type') }}</label>
                        <input type="text" name="employee_type_name" required autocomplete="off"
                               placeholder="{{ __('message.new_employee_type_placeholder') }}">
                    </div>
                    <p class="pds-roles-modal__error" id="pdsRoleAddError" hidden></p>
                    <div class="pds-roles-modal__actions">
                        <button type="submit" class="sa-btn sa-btn-primary">{{ __('message.save') }}</button>
                        <button type="button" class="sa-btn sa-btn-ghost" data-close-modal>{{ __('message.cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="pds-roles-modal" id="pdsRoleDeleteModal" hidden>
            <div class="pds-roles-modal__backdrop" data-close-modal></div>
            <div class="pds-roles-modal__dialog is-delete" role="dialog" aria-modal="true" aria-labelledby="pdsRoleDeleteTitle">
                <button type="button" class="pds-roles-modal__close" data-close-modal aria-label="{{ __('message.close') }}">
                    <i class="fas fa-times"></i>
                </button>
                <div class="pds-roles-modal__icon"><i class="fas fa-trash-alt"></i></div>
                <h3 id="pdsRoleDeleteTitle">{{ __('message.delete_form_title', ['form' => __('message.role')]) }}</h3>
                <p id="pdsRoleDeleteMessage">{{ __('message.delete_msg') }}</p>
                <p class="pds-roles-modal__error" id="pdsRoleDeleteError" hidden></p>
                <div class="pds-roles-modal__actions">
                    <button type="button" class="sa-btn sa-btn-ghost" data-close-modal>{{ __('message.cancel') }}</button>
                    <button type="button" class="sa-btn pds-roles-modal__danger" id="pdsRoleDeleteConfirm">{{ __('message.delete') }}</button>
                </div>
            </div>
        </div>
    @endif

</div>
