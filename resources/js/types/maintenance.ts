export interface MaintenancePlan {
    id: number;
    name: string;
    slug: string;
    platform: string;
    summary: string;
    scope: string;
    exclusions: string | null;
    support_arrangements: string | null;
    product_price_id: number | null;
    published: boolean;
    available: boolean;
    version: string;
    billing: {
        price_id: number;
        product_id: number;
        amount: string;
        currency: string;
        cycle: string;
        setup_fee: string;
    } | null;
}
export interface MaintenanceRequest {
    id: number;
    plan: MaintenancePlan;
    website: string | null;
    requirements: string;
    status: string;
    client_update: string | null;
    internal_notes?: string | null;
    created_at: string;
    scope_acknowledged_at: string;
    subscription: { id: number; status: string; url: string } | null;
    quote: { id: number; number: string; status: string; url: string } | null;
    checkout_url: string | null;
    checkout_order: { order_number: string; status: string } | null;
    checkout_resume_url: string | null;
    subscription_id?: number | null;
    quote_id?: number | null;
    user?: { id: number; name: string; email: string };
}
