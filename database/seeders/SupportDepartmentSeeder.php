<?php

namespace Database\Seeders;

use App\Models\TicketDepartment;
use Illuminate\Database\Seeder;

class SupportDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->departments() as $department) {
            TicketDepartment::query()->firstOrCreate(
                ['name' => $department['name']],
                $department,
            );
        }
    }

    /** @return list<array<string, bool|int|string|null>> */
    private function departments(): array
    {
        return [
            [
                'name' => 'Product Pre-Sales',
                'description' => 'Questions about products, compatibility, licensing, discounts, and the best option for your business.',
                'email' => config('asrtech.support_email'),
                'clients_only' => false,
                'hidden' => false,
                'sort_order' => 10,
                'mail_provider' => 'pop3imap',
                'mail_port' => 0,
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Help with installation, configuration, licensing, updates, errors, and active ASRTech products.',
                'email' => config('asrtech.support_email'),
                'clients_only' => true,
                'hidden' => false,
                'sort_order' => 20,
                'mail_provider' => 'pop3imap',
                'mail_port' => 0,
            ],
            [
                'name' => 'Billing & Accounts',
                'description' => 'Assistance with invoices, payments, renewals, account access, and order-related questions.',
                'email' => config('asrtech.support_email'),
                'clients_only' => true,
                'hidden' => false,
                'sort_order' => 30,
                'mail_provider' => 'pop3imap',
                'mail_port' => 0,
            ],
            [
                'name' => 'Custom Development',
                'description' => 'Discuss a Laravel, Vue, WHMCS, API integration, automation, or custom software project.',
                'email' => config('asrtech.support_email'),
                'clients_only' => false,
                'hidden' => false,
                'sort_order' => 40,
                'mail_provider' => 'pop3imap',
                'mail_port' => 0,
            ],
        ];
    }
}
