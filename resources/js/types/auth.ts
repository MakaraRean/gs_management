export type Role = 'admin' | 'cashier' | 'gas_jockey';

export type Permission =
    | 'sales'
    | 'fuel_management'
    | 'cash_flow'
    | 'sales_analysis'
    | 'reports'
    | 'stations'
    | 'manage_team'
    | 'customers'
    | 'delete_records';

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: Role | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions: Permission[];
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
