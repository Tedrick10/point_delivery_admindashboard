<x-master-layout :assets="$assets ?? []">
    <div class="container-fluid pds-page-wrap pds-motion-enter pds-roles-page">
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
                @if($auth_user->can('role-add'))
                    <a href="{{ route('permission.add', ['type' => 'role']) }}" class="btn btn-primary loadRemoteModel">
                        <i class="fa fa-plus-circle"></i> {{ __('message.add_form_title', ['form' => __('message.role')]) }}
                    </a>
                @endif
            </div>
        </div>

        @php
            $protectedRoles = ['user', 'agent', 'admin', 'client', 'delivery_man', 'demo_admin', 'super_admin'];
        @endphp

        {{ html()->form('POST', route('permission.store'))->id('rolesPermissionForm')->open() }}
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
                            @if($auth_user->can('role-add'))
                                @foreach($roles as $index => $role)
                                    @if(! in_array($role->name, $protectedRoles, true))
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
                                @endforeach
                            @endif
                            <button type="submit" class="btn btn-primary" @disabled($roles->isEmpty())>
                                <i class="fas fa-save"></i> {{ __('message.save') }}
                            </button>
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

        @if($auth_user->can('role-add'))
            @foreach($roles as $role)
                @if(! in_array($role->name, $protectedRoles, true))
                    {{ html()->form('DELETE', route('role.destroy', $role->id))->attribute('data--submit', 'role' . $role->id)->class('d-none')->open() }}
                        <input type="hidden" name="redirect_to" value="permission">
                    {{ html()->form()->close() }}
                @endif
            @endforeach
        @endif
    </div>

    <style>
        .pds-roles-page { --rp-gap: 16px; }
        .pds-roles-hero {
            display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%);
            border: 1px solid #ffe4cc; border-radius: 20px; padding: 22px 24px; margin-bottom: 18px;
        }
        .pds-roles-hero__eyebrow {
            display: inline-flex; align-items: center; gap: 8px; color: #FE6F07; font-weight: 600; font-size: 13px; margin-bottom: 6px;
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
        .pds-roles-tab:hover { background: #fff7ed; border-color: #ffd8b0; }
        .pds-roles-tab.is-active {
            background: linear-gradient(135deg, #FE6F07, #ff8f3d); color: #fff; box-shadow: 0 8px 20px rgba(254, 111, 7, .28);
        }
        .pds-roles-tab__avatar {
            width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center;
            background: rgba(255,255,255,.75); color: #FE6F07; font-weight: 700;
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
        .pds-roles-modules { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .pds-roles-module {
            border: 1px solid #eef2f7; border-radius: 18px; padding: 16px; background: #fbfdff;
        }
        .pds-roles-module__header { display: flex; gap: 12px; margin-bottom: 14px; }
        .pds-roles-module__icon {
            width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center;
            background: #fff7ed; color: #FE6F07; flex: 0 0 auto;
        }
        .pds-roles-module__header h5 { margin: 0; font-size: 16px; font-weight: 700; color: #0f172a; }
        .pds-roles-module__header p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
        .pds-roles-module__actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .pds-roles-chip {
            display: none; align-items: center; gap: 8px; border: 1px solid #e2e8f0; border-radius: 999px;
            padding: 8px 12px; background: #fff; cursor: pointer; user-select: none; margin: 0; font-weight: 600; color: #334155;
        }
        .pds-roles-chip.is-visible { display: inline-flex; }
        .pds-roles-chip:has(input:checked) {
            background: #fff7ed; border-color: #FE6F07; color: #c2410c;
        }
        .pds-roles-chip input { width: 16px; height: 16px; accent-color: #FE6F07; margin: 0; }
        .pds-roles-empty { grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b; }
        @media (max-width: 991px) {
            .pds-roles-shell { grid-template-columns: 1fr; }
            .pds-roles-aside { position: static; }
            .pds-roles-modules { grid-template-columns: 1fr; }
            .pds-roles-tabs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    @section('bottom_script')
        <script>
            (function ($) {
                'use strict';
                $(document).on('click', '.pds-roles-tab', function () {
                    var roleId = $(this).data('role-id');
                    var roleName = $(this).data('role-name') || '';
                    $('.pds-roles-tab').removeClass('is-active');
                    $(this).addClass('is-active');
                    $('#currentRoleLabel').text(String(roleName).replace(/_/g, ' ').replace(/\b\w/g, function (c) {
                        return c.toUpperCase();
                    }));
                    $('.pds-roles-chip').removeClass('is-visible');
                    $('.pds-roles-chip[data-role-panel="' + roleId + '"]').addClass('is-visible');
                    $('.pds-roles-delete-btn').removeClass('is-visible');
                    $('.pds-roles-delete-btn[data-role-panel="' + roleId + '"]').addClass('is-visible');
                });

                var $active = $('.pds-roles-tab.is-active');
                if ($active.length) {
                    $('.pds-roles-delete-btn').removeClass('is-visible');
                    $('.pds-roles-delete-btn[data-role-panel="' + $active.data('role-id') + '"]').addClass('is-visible');
                }
            })(jQuery);
        </script>
    @endsection
</x-master-layout>
