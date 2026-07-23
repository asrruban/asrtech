<?php

namespace App\Services;

use App\Models\EmailTemplate;

/**
 * Resolves admin-editable email templates and substitutes
 * {{merge_field}} placeholders, WHMCS merge-tag style.
 */
class EmailTemplateService
{
    /** Merge fields available to every template. */
    public const GLOBAL_MERGE_FIELDS = ['company_name', 'support_email', 'site_url'];

    /** @var array<string, list<string>> Template-specific merge fields, keyed by slug. */
    public const MERGE_FIELDS = [
        'email-otp' => ['client_name', 'otp_code', 'otp_expiry_minutes'],
        'invoice-notification' => [
            'client_name',
            'invoice_number',
            'invoice_total',
            'invoice_due_date',
            'invoice_status',
            'product_name',
        ],
        'subscription-renewed' => [
            'client_name',
            'product_name',
            'subscription_amount',
            'billing_cycle',
            'next_renewal_date',
            'order_number',
            'invoice_number',
            'invoice_url',
            'subscriptions_url',
        ],
        'subscription-payment-failed' => [
            'client_name',
            'product_name',
            'subscription_amount',
            'billing_cycle',
            'invoice_reference',
            'payment_method_url',
            'subscriptions_url',
        ],
        'subscription-cancellation-scheduled' => [
            'client_name',
            'product_name',
            'service_end_date',
            'subscriptions_url',
        ],
        'subscription-renewal-reminder' => [
            'client_name',
            'product_name',
            'subscription_amount',
            'billing_cycle',
            'renewal_date',
            'subscriptions_url',
        ],
        'refund-request-received' => [
            'client_name', 'request_number', 'request_amount', 'invoice_number', 'invoice_url',
        ],
        'refund-request-decision' => [
            'client_name', 'request_number', 'request_amount', 'request_status',
            'decision_note', 'invoice_number', 'invoice_url',
        ],
        'product-release-published' => [
            'client_name', 'product_name', 'version', 'release_title',
            'release_notes', 'release_date', 'downloads_url',
        ],
    ];

    /**
     * Render the enabled template for a slug, or null when the template
     * is missing or disabled (callers fall back to their blade view).
     *
     * @param  array<string, string>  $fields
     * @return array{subject: string, html: string}|null
     */
    public function render(string $slug, array $fields = []): ?array
    {
        $template = EmailTemplate::query()
            ->where('slug', $slug)
            ->where('enabled', true)
            ->first();

        if ($template === null) {
            return null;
        }

        $replacements = [];

        foreach ([...$this->globalFields(), ...$fields] as $key => $value) {
            $replacements['{{'.$key.'}}'] = e($value);
        }

        return [
            'subject' => strip_tags(strtr($template->subject, $replacements)),
            'html' => strtr($template->body, $replacements),
        ];
    }

    /** @return array<string, string> */
    private function globalFields(): array
    {
        return [
            'company_name' => (string) config('asrtech.company_name', config('app.name')),
            'support_email' => (string) config('asrtech.support_email', ''),
            'site_url' => (string) config('app.url'),
        ];
    }
}
