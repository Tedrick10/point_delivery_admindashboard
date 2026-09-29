<x-master-layout :assets="$assets ?? []">
    @include('permission.partials._roles-board', [
        'auth_user' => $auth_user,
        'roles' => $roles,
        'modules' => $modules,
        'pageTitle' => $pageTitle ?? __('message.roles_and_permission'),
        'canManageRoles' => isSuperAdmin($auth_user) || $auth_user->can('role-add'),
    ])

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
