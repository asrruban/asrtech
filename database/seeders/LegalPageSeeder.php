<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $data) {
            $page = Page::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'content' => $data['content'],
                    'template' => 'legal',
                    'status' => true,
                    'sort_order' => $data['sort_order'],
                ],
            );

            $page->seo()->updateOrCreate([], [
                'meta_title' => $data['meta_title'],
                'meta_description' => $data['meta_description'],
                'canonical_url' => route('legal.show', $slug),
                'robots' => 'index,follow',
                'twitter_card' => 'summary_large_image',
                'schema_json' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => $data['title'],
                    'url' => route('legal.show', $slug),
                ],
            ]);
        }
    }

    /** @return array<string, array<string, int|string>> */
    private function pages(): array
    {
        return [
            'terms-of-service' => [
                'title' => 'Terms of Service',
                'excerpt' => 'The rules and responsibilities that apply when you use ASRTech products, services, and websites.',
                'meta_title' => 'Terms of Service | ASRTech',
                'meta_description' => 'Read the terms governing ASRTech products, software licenses, services, accounts, and website use.',
                'sort_order' => 90,
                'content' => <<<'TEXT'
1. Agreement

By accessing an ASRTech website, creating an account, purchasing a product, or using a service, you agree to these terms and any product-specific terms shown at purchase.

2. Accounts and security

You are responsible for accurate account information, safeguarding credentials, and activity performed through your account. Contact us promptly if you believe an account or license has been compromised.

3. Products and licenses

Software is licensed, not sold. The license type, installation limit, update period, support period, and other entitlements are described on the applicable product page or order. You may not resell, redistribute, reverse engineer, or remove ownership notices except where applicable law expressly permits it.

4. Payments and renewals

Prices, billing cycles, setup fees, and taxes are presented before checkout. Recurring services may renew until cancelled. Failure to pay may suspend access to updates, support, hosted services, or license validation.

5. Custom development

The scope, milestones, deliverables, acceptance process, ownership, and payment schedule for custom work are governed by the written proposal or statement of work agreed for that project.

6. Acceptable use

You may not use our products or services to break the law, compromise systems, distribute malware, infringe intellectual property, evade licensing, or interfere with other users.

7. Availability and support

We work to keep our services reliable but do not guarantee uninterrupted availability. Support response times and included services depend on the product, plan, and active support entitlement.

8. Liability

To the maximum extent permitted by law, ASRTech is not liable for indirect, incidental, special, or consequential loss, including lost profit, data, or business opportunity. You are responsible for backups and testing changes before production use.

9. Suspension and termination

We may restrict or terminate access for material breach, unlawful activity, security risk, abusive conduct, or unpaid balances. Obligations that should reasonably survive termination will continue.

10. Changes and contact

We may update these terms as products and laws evolve. Material changes will be reflected on this page. Questions may be sent through the Support Center.
TEXT,
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'excerpt' => 'How ASRTech collects, uses, protects, and manages information across our website, products, and services.',
                'meta_title' => 'Privacy Policy | ASRTech',
                'meta_description' => 'Learn how ASRTech collects, uses, stores, and protects account, order, support, and website information.',
                'sort_order' => 91,
                'content' => <<<'TEXT'
1. Information we collect

We may collect account and contact information, order and transaction details, license and product usage data, support communications, technical logs, and information submitted through forms.

2. How we use information

We use information to provide accounts, process orders, validate licenses, deliver downloads and services, respond to support requests, secure our systems, improve products, meet legal obligations, and communicate important service updates.

3. Payments

Payments may be processed by third-party payment providers. We receive transaction status and necessary billing references but do not intentionally store complete payment card numbers on ASRTech systems.

4. Cookies and session data

Our website uses essential cookies for authentication, security, preferences, cart state, and core functionality. Optional analytics or marketing technologies should only be used where enabled and legally permitted.

5. Sharing and service providers

We share information only as needed with service providers that support hosting, email, payments, monitoring, storage, and business operations; when you direct us to; or when required to protect rights, safety, or comply with law.

6. Retention

We retain information for as long as needed to provide services, maintain transaction and license records, resolve disputes, enforce agreements, and satisfy legal or security requirements. Retention periods vary by data type and purpose.

7. Security

We use reasonable administrative, technical, and organizational safeguards. No online system can be guaranteed completely secure, so you should use a unique password and avoid sending credentials or secrets in support messages.

8. Your choices and rights

Depending on your location, you may have rights to access, correct, delete, restrict, or receive a copy of personal information, and to object to certain processing. Some records must be retained for legal, fraud-prevention, or transaction purposes.

9. International processing

Our providers and systems may process information in countries other than your own. Where required, appropriate contractual or legal safeguards are used for international transfers.

10. Updates and contact

We may update this policy when our practices, products, or legal obligations change. Privacy questions and requests may be submitted through the Support Center.
TEXT,
            ],
            'refund-policy' => [
                'title' => 'Refund Policy',
                'excerpt' => 'How refund requests are evaluated for digital products, subscriptions, installation services, and custom development.',
                'meta_title' => 'Refund Policy | ASRTech',
                'meta_description' => 'Review ASRTech refund conditions for software licenses, subscriptions, services, and custom development.',
                'sort_order' => 92,
                'content' => <<<'TEXT'
1. Overview

ASRTech sells digital software and professional services. Refund eligibility depends on the product, delivery status, license activity, work already performed, the stated order terms, and applicable law.

2. Digital products

Except where applicable law requires otherwise, downloaded software, delivered source code, activated licenses, and products that have received installation, configuration, or support work are generally non-refundable.

3. Duplicate or incorrect charges

If you were charged more than once for the same order or a billing error occurred, contact support with the invoice number so the transaction can be reviewed and corrected.

4. Technical issues

Before requesting a refund for a technical issue, please open a support ticket and provide the environment details needed to reproduce the problem. We may offer troubleshooting, a fix, an update, or another reasonable solution.

5. Subscriptions and renewals

Cancelling a subscription prevents future renewals but does not automatically refund a completed billing period. Access and entitlements continue or end according to the applicable plan terms.

6. Installation and professional services

Fees for completed installation, configuration, consulting, or other professional work are non-refundable. If work has not started, contact support promptly so we can review whether cancellation is possible.

7. Custom development

Deposits, completed milestones, reserved capacity, third-party costs, and accepted deliverables are non-refundable unless the signed proposal or statement of work says otherwise.

8. How to request a review

Open a billing support ticket and include the order or invoice number, product or service, reason for the request, and relevant technical details. Do not submit payment credentials or passwords.

9. Payment disputes

Please contact us before initiating a chargeback so we can investigate. Fraudulent or abusive disputes may result in account, license, or service suspension where permitted by law.

10. Statutory rights

Nothing in this policy limits rights that cannot legally be waived in your jurisdiction. If a mandatory consumer protection rule applies, it will take precedence over conflicting policy language.
TEXT,
            ],
        ];
    }
}
