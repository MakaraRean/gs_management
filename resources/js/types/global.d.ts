import type { Auth } from '@/types/auth';
import type { Currency, CurrentStation } from '@/types/currency';

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
            version: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: 'km' | 'en';
            translations: Record<string, string>;
            current_station: CurrentStation | null;
            currencies: Currency[];
            stations: Array<{ id: number; name: string; business_id: number }>;
            has_business: boolean;
            has_station: boolean;
            is_business_owner: boolean;
            pending_deletion_requests: number;
            [key: string]: unknown;
        };
    }
}
