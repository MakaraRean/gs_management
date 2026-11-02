export type Currency = {
    id: number;
    code: string;
    name: string;
    name_km: string;
    symbol: string;
    round_digits: number;
    is_active: boolean;
};

export type CurrentStation = {
    id: number;
    name: string;
    business_id: number;
    currency: Currency;
};
