import { usePage } from '@inertiajs/react';
import type { Auth, Permission } from '@/types';

export function usePermissions() {
    const { auth } = usePage().props;
    const { permissions } = auth as Auth;

    const can = (permission: Permission): boolean =>
        permissions.includes(permission);

    return { can };
}
