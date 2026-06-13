import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: 'km' | 'en';
            translations: Record<string, string>;
            current_station: { id: number; name: string; business_id: number } | null;
            stations: Array<{ id: number; name: string; business_id: number }>;
            has_business: boolean;
            has_station: boolean;
            is_business_owner: boolean;
            [key: string]: unknown;
        };
    }
}
