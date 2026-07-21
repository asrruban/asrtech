<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    FileText,
    Globe,
    Mail,
    RefreshCw,
    Save,
    Settings2,
    ShoppingCart,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import SectionSidebar from '@/modules/admin/components/SectionSidebar.vue';

const props = defineProps(['settings']);

const activeSection = ref('general');
const sections = [
    { value: 'general', label: 'General' },
    { value: 'branding', label: 'Branding' },
    { value: 'links', label: 'Social Links' },
    { value: 'localisation', label: 'Localisation' },
    { value: 'ordering', label: 'Ordering' },
    { value: 'invoices', label: 'Invoices' },
    { value: 'subscriptions', label: 'Subscriptions' },
    { value: 'mail', label: 'Mail' },
    { value: 'social', label: 'Social Login' },
    { value: 'security', label: 'Security' },
];

const dateFormats = ['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD', 'DD.MM.YYYY'];

const form = useForm({
    site_url: props.settings.site_url ?? '',
    company_name: props.settings.company_name ?? '',
    app_name: props.settings.app_name ?? '',
    support_email: props.settings.support_email ?? '',
    tagline: props.settings.tagline ?? '',
    phone: props.settings.phone ?? '',
    address: props.settings.address ?? '',
    logo_url: props.settings.logo_url ?? '',
    currency: props.settings.currency ?? 'USD',
    timezone: props.settings.timezone ?? '',
    facebook_url: props.settings.facebook_url ?? '',
    linkedin_url: props.settings.linkedin_url ?? '',
    github_url: props.settings.github_url ?? '',
    activity_log_limit: props.settings.activity_log_limit,
    mail_disable: props.settings.mail_disable,
    mail_bcc: props.settings.mail_bcc ?? '',
    email_header_content: props.settings.email_header_content ?? '',
    email_footer_content: props.settings.email_footer_content ?? '',
    allow_registration: props.settings.allow_registration,
    maintenance_mode: props.settings.maintenance_mode,
    maintenance_message: props.settings.maintenance_message ?? '',
    records_per_page: props.settings.records_per_page,
    auto_provision_licenses: props.settings.auto_provision_licenses,
    send_invoice_reminders: props.settings.send_invoice_reminders,
    invoice_reminder_days: props.settings.invoice_reminder_days,
    send_subscription_reminders: props.settings.send_subscription_reminders,
    subscription_reminder_days: props.settings.subscription_reminder_days,
    subscription_grace_days: props.settings.subscription_grace_days,
    mail_signature: props.settings.mail_signature ?? '',
    login_max_attempts: props.settings.login_max_attempts,
    login_decay_minutes: props.settings.login_decay_minutes,
    email_otp_ttl_minutes: props.settings.email_otp_ttl_minutes,
    date_format: props.settings.date_format,
    default_country: props.settings.default_country,
    currency_symbol: props.settings.currency_symbol,
    require_tos_accept: props.settings.require_tos_accept,
    terms_url: props.settings.terms_url ?? '',
    invoice_number_prefix: props.settings.invoice_number_prefix,
    invoice_due_days: props.settings.invoice_due_days,
    invoice_pay_to: props.settings.invoice_pay_to ?? '',
    invoice_footer_note: props.settings.invoice_footer_note ?? '',
    mail_from_name: props.settings.mail_from_name ?? '',
    mail_from_address: props.settings.mail_from_address ?? '',
    smtp_host: props.settings.smtp_host ?? '',
    smtp_port: props.settings.smtp_port ?? '',
    smtp_username: props.settings.smtp_username ?? '',
    smtp_password: '',
    google_client_id: props.settings.google_client_id ?? '',
    google_client_secret: '',
    github_client_id: props.settings.github_client_id ?? '',
    github_client_secret: '',
});

const brandingForm = useForm<{
    logo: File | null;
    logo_light: File | null;
    logo_dark: File | null;
    favicon: File | null;
}>({
    logo: null,
    logo_light: null,
    logo_dark: null,
    favicon: null,
});

const submitBranding = () =>
    brandingForm.post('/admin/settings/branding', {
        forceFormData: true,
        onSuccess: () => brandingForm.reset(),
    });

const pickFile = (
    field: 'logo' | 'logo_light' | 'logo_dark' | 'favicon',
    event: Event,
) => {
    brandingForm[field] = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const sectionFor = (field: string) => {
    if (['facebook_url', 'linkedin_url', 'github_url'].includes(field)) {
        return 'links';
    }

    if (field === 'logo_url') {
        return 'branding';
    }

    if (['currency', 'timezone'].includes(field)) {
        return 'localisation';
    }

    if (
        ['app_name', 'support_email', 'tagline', 'phone', 'address'].includes(
            field,
        )
    ) {
        return 'general';
    }

    if (field.startsWith('google_') || field.startsWith('github_')) {
        return 'social';
    }

    if (
        ['email_header_content', 'email_footer_content', 'mail_bcc'].includes(
            field,
        )
    ) {
        return 'mail';
    }

    if (
        ['company_name', 'activity_log_limit', 'mail_disable'].includes(field)
    ) {
        return field === 'mail_disable' ? 'mail' : 'general';
    }

    if (
        [
            'login_max_attempts',
            'login_decay_minutes',
            'email_otp_ttl_minutes',
        ].includes(field)
    ) {
        return 'security';
    }

    if (field.startsWith('smtp_') || field.startsWith('mail_')) {
        return 'mail';
    }

    if (field.startsWith('invoice_') || field === 'send_invoice_reminders') {
        return 'invoices';
    }

    if (
        field.startsWith('subscription_') ||
        field === 'send_subscription_reminders'
    ) {
        return 'subscriptions';
    }

    if (
        ['require_tos_accept', 'terms_url', 'auto_provision_licenses'].includes(
            field,
        )
    ) {
        return 'ordering';
    }

    if (['date_format', 'default_country', 'currency_symbol'].includes(field)) {
        return 'localisation';
    }

    return 'general';
};

const submit = () =>
    form.put('/admin/settings/general', {
        onError: (errors) => {
            const first = Object.keys(errors)[0];

            if (first) {
                activeSection.value = sectionFor(first);
            }
        },
    });
</script>

<template>
    <Head title="General configuration" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Configuration</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                General configuration
            </h1>
            <p class="mt-1 text-muted-foreground">
                Platform behavior for registration, localisation, ordering,
                invoicing, and outgoing mail.
            </p>
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8">
                <SectionSidebar
                    v-model="activeSection"
                    title="Configuration"
                    :items="sections"
                />

                <div class="min-w-0 space-y-6">
                    <Card v-show="activeSection === 'general'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Settings2 class="size-5" />
                                <CardTitle>General</CardTitle>
                            </div>
                            <CardDescription>
                                Core platform behavior.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-5">
                            <div class="grid gap-5 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label>Company name</Label>
                                    <Input
                                        v-model="form.company_name"
                                        placeholder="ASRHost"
                                        required
                                    />
                                    <InputError
                                        :message="form.errors.company_name"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>System email address</Label>
                                    <Input
                                        v-model="form.mail_from_address"
                                        type="email"
                                        placeholder="noreply@asrhost.bd"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Default sender for all outgoing email.
                                    </p>
                                    <InputError
                                        :message="form.errors.mail_from_address"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>Application name</Label>
                                    <Input
                                        v-model="form.app_name"
                                        placeholder="ASRTech"
                                        required
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Shown in browser titles and system
                                        emails.
                                    </p>
                                    <InputError
                                        :message="form.errors.app_name"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>Support email</Label>
                                    <Input
                                        v-model="form.support_email"
                                        type="email"
                                        placeholder="support@asrhost.bd"
                                    />
                                    <InputError
                                        :message="form.errors.support_email"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label>Phone</Label>
                                    <Input
                                        v-model="form.phone"
                                        placeholder="+880 1XXX-XXXXXX"
                                    />
                                    <InputError :message="form.errors.phone" />
                                </div>
                                <div class="space-y-2">
                                    <Label>Tagline</Label>
                                    <Input
                                        v-model="form.tagline"
                                        placeholder="WHMCS modules, templates, and web development."
                                    />
                                    <InputError
                                        :message="form.errors.tagline"
                                    />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label>Address</Label>
                                <textarea
                                    v-model="form.address"
                                    rows="3"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                    placeholder="Dhaka, Bangladesh"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Public company address shown in the footer
                                    and on invoices.
                                </p>
                                <InputError :message="form.errors.address" />
                            </div>
                            <div class="space-y-2">
                                <Label>Domain (System URL)</Label>
                                <Input
                                    v-model="form.site_url"
                                    type="url"
                                    placeholder="https://asrtech.example"
                                    required
                                />
                                <InputError :message="form.errors.site_url" />
                            </div>
                            <div class="space-y-2">
                                <Label>Pay To text</Label>
                                <textarea
                                    v-model="form.invoice_pay_to"
                                    rows="3"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                    placeholder="ASR Tech Ltd&#10;Dhaka, Bangladesh"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Shown on invoices and invoice PDFs.
                                </p>
                                <InputError
                                    :message="form.errors.invoice_pay_to"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Limit activity log</Label>
                                <Input
                                    v-model.number="form.activity_log_limit"
                                    type="number"
                                    min="10"
                                    max="100000"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    Maximum log entries kept per license across
                                    the website.
                                </p>
                                <InputError
                                    :message="form.errors.activity_log_limit"
                                />
                            </div>
                            <label class="flex items-start gap-3 text-sm">
                                <input
                                    v-model="form.allow_registration"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded"
                                />
                                <span>
                                    <span class="font-semibold"
                                        >Allow client registration</span
                                    >
                                    <span class="block text-muted-foreground">
                                        When off, the register page and social
                                        sign-up are disabled; existing customers
                                        can still sign in.
                                    </span>
                                </span>
                            </label>
                            <InputError
                                :message="form.errors.allow_registration"
                            />
                            <label class="flex items-start gap-3 text-sm">
                                <input
                                    v-model="form.maintenance_mode"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded"
                                />
                                <span>
                                    <span class="font-semibold"
                                        >Maintenance mode</span
                                    >
                                    <span class="block text-muted-foreground">
                                        The storefront shows a maintenance page;
                                        admins, the admin panel, and
                                        gateway/license APIs keep working.
                                    </span>
                                </span>
                            </label>
                            <InputError
                                :message="form.errors.maintenance_mode"
                            />
                            <div class="space-y-2">
                                <Label>Maintenance message</Label>
                                <textarea
                                    v-model="form.maintenance_message"
                                    rows="2"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                    placeholder="We'll be right back."
                                />
                                <InputError
                                    :message="form.errors.maintenance_message"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Records to display per page</Label>
                                <select
                                    v-model.number="form.records_per_page"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    <option :value="10">10</option>
                                    <option :value="15">15</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                                <InputError
                                    :message="form.errors.records_per_page"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'localisation'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Globe class="size-5" />
                                <CardTitle>Localisation</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Date format</Label>
                                <select
                                    v-model="form.date_format"
                                    class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                >
                                    <option
                                        v-for="format in dateFormats"
                                        :key="format"
                                        :value="format"
                                    >
                                        {{ format }}
                                    </option>
                                </select>
                                <InputError
                                    :message="form.errors.date_format"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Default country (ISO code)</Label>
                                <Input
                                    v-model="form.default_country"
                                    maxlength="2"
                                    placeholder="BD"
                                    class="uppercase"
                                    required
                                />
                                <InputError
                                    :message="form.errors.default_country"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Currency symbol</Label>
                                <Input
                                    v-model="form.currency_symbol"
                                    maxlength="8"
                                    placeholder="$"
                                    required
                                />
                                <InputError
                                    :message="form.errors.currency_symbol"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Currency (ISO code)</Label>
                                <Input
                                    v-model="form.currency"
                                    maxlength="3"
                                    placeholder="USD"
                                    class="uppercase"
                                    required
                                />
                                <InputError :message="form.errors.currency" />
                            </div>
                            <div class="space-y-2">
                                <Label>Timezone</Label>
                                <Input
                                    v-model="form.timezone"
                                    placeholder="Asia/Dhaka"
                                    required
                                />
                                <InputError :message="form.errors.timezone" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'ordering'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <ShoppingCart class="size-5" />
                                <CardTitle>Ordering</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-5">
                            <label class="flex items-start gap-3 text-sm">
                                <input
                                    v-model="form.require_tos_accept"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded"
                                />
                                <span>
                                    <span class="font-semibold"
                                        >Require Terms of Service
                                        acceptance</span
                                    >
                                    <span class="block text-muted-foreground">
                                        New customers must accept the terms
                                        before creating an account.
                                    </span>
                                </span>
                            </label>
                            <div class="space-y-2">
                                <Label>Terms of Service URL</Label>
                                <Input
                                    v-model="form.terms_url"
                                    type="url"
                                    placeholder="https://asrtech.example/pages/terms"
                                />
                                <InputError :message="form.errors.terms_url" />
                            </div>
                            <label class="flex items-start gap-3 text-sm">
                                <input
                                    v-model="form.auto_provision_licenses"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded"
                                />
                                <span>
                                    <span class="font-semibold"
                                        >Automatically provision licenses</span
                                    >
                                    <span class="block text-muted-foreground">
                                        Generate a license the moment an order
                                        is paid. When off, assign licenses
                                        manually from the client profile.
                                    </span>
                                </span>
                            </label>
                            <InputError
                                :message="form.errors.auto_provision_licenses"
                            />
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'invoices'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <FileText class="size-5" />
                                <CardTitle>Invoices</CardTitle>
                            </div>
                            <CardDescription>
                                Applied to newly generated invoices.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Invoice number prefix</Label>
                                <Input
                                    v-model="form.invoice_number_prefix"
                                    maxlength="8"
                                    class="uppercase"
                                    placeholder="INV"
                                    required
                                />
                                <InputError
                                    :message="form.errors.invoice_number_prefix"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Payment due (days)</Label>
                                <Input
                                    v-model="form.invoice_due_days"
                                    type="number"
                                    min="0"
                                    max="365"
                                    required
                                />
                                <InputError
                                    :message="form.errors.invoice_due_days"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label>Invoice footer note</Label>
                                <textarea
                                    v-model="form.invoice_footer_note"
                                    rows="2"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                    placeholder="Thank you for your business."
                                />
                                <InputError
                                    :message="form.errors.invoice_footer_note"
                                />
                            </div>
                            <label
                                class="flex items-start gap-3 text-sm md:col-span-2"
                            >
                                <input
                                    v-model="form.send_invoice_reminders"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded"
                                />
                                <span>
                                    <span class="font-semibold"
                                        >Send payment reminders</span
                                    >
                                    <span class="block text-muted-foreground">
                                        Emails unpaid invoices approaching their
                                        due date (runs daily via the scheduler).
                                    </span>
                                </span>
                            </label>
                            <InputError
                                :message="form.errors.send_invoice_reminders"
                            />
                            <div class="space-y-2">
                                <Label>Remind (days before due)</Label>
                                <Input
                                    v-model.number="form.invoice_reminder_days"
                                    type="number"
                                    min="1"
                                    max="60"
                                    required
                                />
                                <InputError
                                    :message="form.errors.invoice_reminder_days"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'subscriptions'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <RefreshCw class="size-5" />
                                <CardTitle>Subscriptions</CardTitle>
                            </div>
                            <CardDescription>
                                Renewal notices and failed-payment access
                                policy.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <label
                                class="flex items-start gap-3 text-sm md:col-span-2"
                            >
                                <input
                                    v-model="form.send_subscription_reminders"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded"
                                />
                                <span>
                                    <span class="font-semibold"
                                        >Send renewal reminders</span
                                    >
                                    <span class="block text-muted-foreground">
                                        Email customers once before each
                                        upcoming automatic renewal.
                                    </span>
                                </span>
                            </label>
                            <InputError
                                :message="
                                    form.errors.send_subscription_reminders
                                "
                            />
                            <div class="space-y-2">
                                <Label>Reminder lead time (days)</Label>
                                <Input
                                    v-model.number="
                                        form.subscription_reminder_days
                                    "
                                    type="number"
                                    min="1"
                                    max="60"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    The daily scheduler sends one message per
                                    billing period.
                                </p>
                                <InputError
                                    :message="
                                        form.errors.subscription_reminder_days
                                    "
                                />
                            </div>
                            <div class="space-y-2">
                                <Label
                                    >Failed-payment grace period (days)</Label
                                >
                                <Input
                                    v-model.number="
                                        form.subscription_grace_days
                                    "
                                    type="number"
                                    min="0"
                                    max="60"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    Licenses remain valid for this many days
                                    after the paid period ends. Use 0 for no
                                    grace period.
                                </p>
                                <InputError
                                    :message="
                                        form.errors.subscription_grace_days
                                    "
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'mail'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Mail class="size-5" />
                                <CardTitle>Mail</CardTitle>
                            </div>
                            <CardDescription>
                                Sender identity and SMTP delivery. Leave the
                                host blank to keep the server default mailer.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>From name</Label>
                                <Input
                                    v-model="form.mail_from_name"
                                    placeholder="ASRTech Billing"
                                />
                                <InputError
                                    :message="form.errors.mail_from_name"
                                />
                            </div>
                            <label class="flex items-start gap-3 text-sm">
                                <input
                                    v-model="form.mail_disable"
                                    type="checkbox"
                                    class="mt-0.5 size-4 rounded"
                                />
                                <span>
                                    <span class="font-semibold"
                                        >Disable email sending</span
                                    >
                                    <span class="block text-muted-foreground">
                                        All outgoing email is written to the log
                                        instead of being delivered.
                                    </span>
                                </span>
                            </label>
                            <div class="space-y-2">
                                <Label>SMTP host</Label>
                                <Input
                                    v-model="form.smtp_host"
                                    placeholder="smtp.mailgun.org"
                                />
                                <InputError :message="form.errors.smtp_host" />
                            </div>
                            <div class="space-y-2">
                                <Label>SMTP port</Label>
                                <Input
                                    v-model="form.smtp_port"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    placeholder="587"
                                />
                                <InputError :message="form.errors.smtp_port" />
                            </div>
                            <div class="space-y-2">
                                <Label>SMTP username</Label>
                                <Input v-model="form.smtp_username" />
                                <InputError
                                    :message="form.errors.smtp_username"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>SMTP password</Label>
                                <Input
                                    v-model="form.smtp_password"
                                    type="password"
                                    :placeholder="
                                        settings.smtp_password_configured
                                            ? 'Configured — leave blank to keep it'
                                            : ''
                                    "
                                />
                                <p class="text-xs text-muted-foreground">
                                    Stored encrypted and never returned to the
                                    browser.
                                </p>
                                <InputError
                                    :message="form.errors.smtp_password"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label>Global email signature</Label>
                                <textarea
                                    v-model="form.mail_signature"
                                    rows="3"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                                    placeholder="---&#10;ASRHost&#10;https://www.asrhost.bd"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Appended to the footer of all outgoing
                                    emails.
                                </p>
                                <InputError
                                    :message="form.errors.mail_signature"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label>BCC messages</Label>
                                <Input
                                    v-model="form.mail_bcc"
                                    placeholder="records@asrhost.bd,archive@asrhost.bd"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Copies of all system emails go to these
                                    addresses. Separate multiple with a comma.
                                </p>
                                <InputError :message="form.errors.mail_bcc" />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label>Client email header content</Label>
                                <textarea
                                    v-model="form.email_header_content"
                                    rows="4"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 font-mono text-xs"
                                    placeholder="Custom HTML for the top of every client email. Leave blank to use the modern built-in header with your logo."
                                />
                                <InputError
                                    :message="form.errors.email_header_content"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label>Client email footer content</Label>
                                <textarea
                                    v-model="form.email_footer_content"
                                    rows="4"
                                    class="w-full rounded-md border bg-transparent px-3 py-2 font-mono text-xs"
                                    placeholder="Custom HTML for the bottom of every client email. Leave blank to use the modern built-in footer with your signature and company details."
                                />
                                <InputError
                                    :message="form.errors.email_footer_content"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'branding'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Globe class="size-5" />
                                <CardTitle>Branding</CardTitle>
                            </div>
                            <CardDescription>
                                Upload your logos and favicon. The light logo
                                shows on dark backgrounds, the dark logo on
                                light backgrounds; the main logo is the fallback
                                for both.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-6 md:grid-cols-2">
                                <div
                                    v-for="slot in [
                                        {
                                            field: 'logo',
                                            label: 'Main logo',
                                            current: settings.branding_logo_url,
                                            hint: 'PNG or SVG, up to 2 MB',
                                        },
                                        {
                                            field: 'logo_light',
                                            label: 'Light (lite) logo',
                                            current:
                                                settings.branding_logo_light_url,
                                            hint: 'For dark backgrounds',
                                        },
                                        {
                                            field: 'logo_dark',
                                            label: 'Dark logo',
                                            current:
                                                settings.branding_logo_dark_url,
                                            hint: 'For light backgrounds',
                                        },
                                        {
                                            field: 'favicon',
                                            label: 'Favicon',
                                            current:
                                                settings.branding_favicon_url,
                                            hint: 'ICO, PNG, or SVG, up to 512 KB',
                                        },
                                    ]"
                                    :key="slot.field"
                                    class="space-y-2 rounded-lg border p-4"
                                >
                                    <Label>{{ slot.label }}</Label>
                                    <div
                                        class="flex h-20 items-center justify-center rounded-md border border-dashed bg-muted/40 p-3"
                                    >
                                        <img
                                            v-if="slot.current"
                                            :src="slot.current"
                                            :alt="slot.label"
                                            class="max-h-full max-w-full object-contain"
                                        />
                                        <span
                                            v-else
                                            class="text-xs text-muted-foreground"
                                            >Not uploaded</span
                                        >
                                    </div>
                                    <input
                                        type="file"
                                        accept="image/*,.ico"
                                        class="block w-full text-xs file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-xs file:font-semibold"
                                        @change="
                                            pickFile(
                                                slot.field as
                                                    | 'logo'
                                                    | 'logo_light'
                                                    | 'logo_dark'
                                                    | 'favicon',
                                                $event,
                                            )
                                        "
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        {{ slot.hint }}
                                    </p>
                                    <InputError
                                        :message="
                                            (
                                                brandingForm.errors as Record<
                                                    string,
                                                    string
                                                >
                                            )[slot.field]
                                        "
                                    />
                                </div>
                            </div>
                            <Button
                                type="button"
                                class="mt-5"
                                :disabled="brandingForm.processing"
                                @click="submitBranding"
                            >
                                <Save class="size-4" />
                                {{
                                    brandingForm.processing
                                        ? 'Uploading…'
                                        : 'Upload branding'
                                }}
                            </Button>
                            <div class="mt-6 space-y-2 border-t pt-5">
                                <Label>External logo URL (optional)</Label>
                                <Input
                                    v-model="form.logo_url"
                                    type="url"
                                    placeholder="https://cdn.example.com/logo.svg"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Used when no logo has been uploaded above.
                                    Saved with the main configuration below.
                                </p>
                                <InputError :message="form.errors.logo_url" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'links'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Globe class="size-5" />
                                <CardTitle>Social Links</CardTitle>
                            </div>
                            <CardDescription>
                                Public profile links shown in the storefront
                                footer.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Facebook URL</Label>
                                <Input
                                    v-model="form.facebook_url"
                                    type="url"
                                    placeholder="https://facebook.com/asrtech"
                                />
                                <InputError
                                    :message="form.errors.facebook_url"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>LinkedIn URL</Label>
                                <Input
                                    v-model="form.linkedin_url"
                                    type="url"
                                    placeholder="https://linkedin.com/company/asrtech"
                                />
                                <InputError
                                    :message="form.errors.linkedin_url"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label>GitHub URL</Label>
                                <Input
                                    v-model="form.github_url"
                                    type="url"
                                    placeholder="https://github.com/asrtech"
                                />
                                <InputError :message="form.errors.github_url" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'social'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Globe class="size-5" />
                                <CardTitle>Social Login</CardTitle>
                            </div>
                            <CardDescription>
                                OAuth credentials for "continue with" sign-in.
                                Buttons appear on the login and register pages
                                as soon as a provider is configured. Callback
                                URLs: /auth/google/callback and
                                /auth/github/callback.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Google client ID</Label>
                                <Input
                                    v-model="form.google_client_id"
                                    placeholder="....apps.googleusercontent.com"
                                />
                                <InputError
                                    :message="form.errors.google_client_id"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Google client secret</Label>
                                <Input
                                    v-model="form.google_client_secret"
                                    type="password"
                                    :placeholder="
                                        settings.google_client_secret_configured
                                            ? 'Configured — leave blank to keep it'
                                            : 'GOCSPX-...'
                                    "
                                />
                                <InputError
                                    :message="form.errors.google_client_secret"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>GitHub client ID</Label>
                                <Input
                                    v-model="form.github_client_id"
                                    placeholder="Iv1...."
                                />
                                <InputError
                                    :message="form.errors.github_client_id"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>GitHub client secret</Label>
                                <Input
                                    v-model="form.github_client_secret"
                                    type="password"
                                    :placeholder="
                                        settings.github_client_secret_configured
                                            ? 'Configured — leave blank to keep it'
                                            : ''
                                    "
                                />
                                <InputError
                                    :message="form.errors.github_client_secret"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-show="activeSection === 'security'">
                        <CardHeader>
                            <div class="flex items-center gap-3">
                                <Settings2 class="size-5" />
                                <CardTitle>Security</CardTitle>
                            </div>
                            <CardDescription>
                                Sign-in throttling and verification code
                                lifetime.
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="grid gap-5 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label>Max sign-in attempts</Label>
                                <Input
                                    v-model.number="form.login_max_attempts"
                                    type="number"
                                    min="3"
                                    max="100"
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    Failed attempts allowed before the ban kicks
                                    in.
                                </p>
                                <InputError
                                    :message="form.errors.login_max_attempts"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Ban length (minutes)</Label>
                                <Input
                                    v-model.number="form.login_decay_minutes"
                                    type="number"
                                    min="1"
                                    max="60"
                                    required
                                />
                                <InputError
                                    :message="form.errors.login_decay_minutes"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label>Email code expiry (minutes)</Label>
                                <Input
                                    v-model.number="form.email_otp_ttl_minutes"
                                    type="number"
                                    min="5"
                                    max="60"
                                    required
                                />
                                <InputError
                                    :message="form.errors.email_otp_ttl_minutes"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex items-center justify-end gap-3">
                        <p
                            v-if="form.wasSuccessful"
                            class="text-sm font-medium text-emerald-600"
                        >
                            Configuration saved.
                        </p>
                        <Button type="submit" :disabled="form.processing">
                            <Save class="size-4" /> Save configuration
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>
