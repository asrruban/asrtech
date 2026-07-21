<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import {
    AtSign,
    CheckCircle2,
    EyeOff,
    Inbox,
    ListChecks,
    Mail,
    PlugZap,
    Save,
    Settings2,
    ShieldCheck,
    Users,
    Workflow,
} from '@lucide/vue';
import { computed, ref } from 'vue';
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
import DepartmentFieldForm from '@/modules/admin/support/components/DepartmentFieldForm.vue';
import type { DepartmentField } from '@/modules/admin/support/components/DepartmentFieldForm.vue';

export interface DepartmentData {
    id: number;
    name: string;
    description: string | null;
    email: string | null;
    clients_only: boolean;
    pipe_replies_only: boolean;
    no_autoresponder: boolean;
    feedback_request: boolean;
    prevent_client_closure: boolean;
    hidden: boolean;
    mail_provider: string;
    mail_hostname: string | null;
    mail_port: number;
    mail_email: string | null;
    mail_client_id: string | null;
    mail_password_configured: boolean;
    mail_client_secret_configured: boolean;
    assigned_admin_ids: number[];
}

interface AdminOption {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    department?: DepartmentData | null;
    admins: AdminOption[];
    mailProviders: Record<string, string>;
    fields?: DepartmentField[];
    fieldTypes?: Record<string, string>;
}>();

const activeTab = ref<'details' | 'fields'>('details');

const form = useForm({
    name: props.department?.name ?? '',
    description: props.department?.description ?? '',
    email: props.department?.email ?? '',
    assigned_admin_ids: [...(props.department?.assigned_admin_ids ?? [])],
    clients_only: props.department?.clients_only ?? false,
    pipe_replies_only: props.department?.pipe_replies_only ?? false,
    no_autoresponder: props.department?.no_autoresponder ?? false,
    feedback_request: props.department?.feedback_request ?? false,
    prevent_client_closure: props.department?.prevent_client_closure ?? false,
    hidden: props.department?.hidden ?? false,
    mail_provider: props.department?.mail_provider ?? 'pop3imap',
    mail_hostname: props.department?.mail_hostname ?? '',
    mail_port: props.department?.mail_port ?? 0,
    mail_email: props.department?.mail_email ?? '',
    mail_password: '',
    mail_client_id: props.department?.mail_client_id ?? '',
    mail_client_secret: '',
});

const flagRows = [
    {
        key: 'clients_only' as const,
        label: 'Clients only',
        text: 'Only registered clients and authorized contacts can use this department.',
        icon: ShieldCheck,
    },
    {
        key: 'pipe_replies_only' as const,
        label: 'Client-area submissions',
        text: 'Require new tickets to be opened through the secure client area.',
        icon: Inbox,
    },
    {
        key: 'no_autoresponder' as const,
        label: 'Disable autoresponder',
        text: 'Do not send the standard new-ticket acknowledgement email.',
        icon: Mail,
    },
    {
        key: 'feedback_request' as const,
        label: 'Request feedback',
        text: 'Invite the customer to rate the support experience after closure.',
        icon: CheckCircle2,
    },
    {
        key: 'prevent_client_closure' as const,
        label: 'Staff-controlled closure',
        text: 'Prevent customers from closing tickets in this department.',
        icon: Users,
    },
    {
        key: 'hidden' as const,
        label: 'Hide from storefront',
        text: 'Keep this department internal and remove it from public selection.',
        icon: EyeOff,
    },
];

const assignedAdminCount = computed(() => form.assigned_admin_ids.length);
const publicStatus = computed(() =>
    form.hidden ? 'Internal only' : 'Publicly visible',
);

const submit = () => {
    if (props.department) {
        form.put(`/admin/support/departments/${props.department.id}`, {
            preserveScroll: true,
        });
    } else {
        form.post('/admin/support/departments');
    }
};

const testForm = useForm({});
const testErrors = computed(() =>
    Object.values(testForm.errors as Record<string, string>),
);
const testConfiguration = () => {
    testForm
        .transform(() => ({
            department_id: props.department?.id ?? null,
            mail_hostname: form.mail_hostname,
            mail_port: form.mail_port,
            mail_email: form.mail_email,
            mail_password: form.mail_password,
        }))
        .post('/admin/support/departments/test-mail', {
            preserveScroll: true,
        });
};
</script>

<template>
    <div class="space-y-6">
        <div
            class="inline-flex rounded-xl border bg-muted/40 p-1 shadow-sm"
            role="tablist"
            aria-label="Department configuration"
        >
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'details'"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                :class="
                    activeTab === 'details'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'details'"
            >
                <Settings2 class="size-4" /> Configuration
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeTab === 'fields'"
                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                :class="
                    activeTab === 'fields'
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeTab = 'fields'"
            >
                <ListChecks class="size-4" /> Custom fields
                <span
                    v-if="(props.fields ?? []).length"
                    class="rounded-full bg-blue-100 px-1.5 py-0.5 text-[10px] font-extrabold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300"
                >
                    {{ props.fields?.length }}
                </span>
            </button>
        </div>

        <form
            v-show="activeTab === 'details'"
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
            @submit.prevent="submit"
        >
            <div class="space-y-6">
                <Card class="overflow-hidden">
                    <CardHeader class="border-b bg-muted/20">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300"
                            >
                                <Inbox class="size-5" />
                            </span>
                            <div>
                                <CardTitle>Department identity</CardTitle>
                                <CardDescription class="mt-1">
                                    Give customers a clear reason to choose this
                                    team.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-5 pt-6 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label for="dept-name">Department name</Label>
                            <Input
                                id="dept-name"
                                v-model="form.name"
                                required
                                placeholder="General Support"
                                class="h-11"
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="dept-description"
                                >Customer-facing description</Label
                            >
                            <textarea
                                id="dept-description"
                                v-model="form.description"
                                rows="4"
                                maxlength="255"
                                placeholder="Explain which questions and services this team handles."
                                class="w-full resize-y rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            ></textarea>
                            <div class="flex items-start justify-between gap-3">
                                <InputError
                                    :message="form.errors.description"
                                />
                                <span
                                    class="ml-auto text-xs text-muted-foreground"
                                >
                                    {{ form.description.length }}/255
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="dept-email">Department email</Label>
                            <div class="relative">
                                <AtSign
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                />
                                <Input
                                    id="dept-email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="support@example.com"
                                    class="h-11 pl-10"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Used as the public contact and reply identity
                                when configured.
                            </p>
                            <InputError :message="form.errors.email" />
                        </div>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden">
                    <CardHeader class="border-b bg-muted/20">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300"
                            >
                                <Users class="size-5" />
                            </span>
                            <div>
                                <CardTitle>Team assignment</CardTitle>
                                <CardDescription class="mt-1">
                                    Route new conversations to the
                                    administrators responsible for this queue.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="pt-6">
                        <p
                            v-if="props.admins.length === 0"
                            class="rounded-xl border border-dashed px-5 py-8 text-center text-sm text-muted-foreground"
                        >
                            No administrator accounts are available for
                            assignment.
                        </p>
                        <div v-else class="grid gap-3 md:grid-cols-2">
                            <label
                                v-for="admin in props.admins"
                                :key="admin.id"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 transition hover:border-blue-300 hover:bg-blue-50/40 dark:hover:bg-blue-500/5"
                                :class="
                                    form.assigned_admin_ids.includes(admin.id)
                                        ? 'border-blue-300 bg-blue-50/60 dark:border-blue-500/40 dark:bg-blue-500/10'
                                        : ''
                                "
                            >
                                <input
                                    v-model="form.assigned_admin_ids"
                                    type="checkbox"
                                    :value="admin.id"
                                    class="size-4 rounded border-slate-300 text-blue-600"
                                />
                                <span
                                    class="flex size-9 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-extrabold text-white"
                                >
                                    {{ admin.name.slice(0, 1).toUpperCase() }}
                                </span>
                                <span class="min-w-0">
                                    <span
                                        class="block truncate text-sm font-bold"
                                        >{{ admin.name }}</span
                                    >
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                    >
                                        {{ admin.email }}
                                    </span>
                                </span>
                            </label>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.assigned_admin_ids"
                        />
                    </CardContent>
                </Card>

                <Card class="overflow-hidden">
                    <CardHeader class="border-b bg-muted/20">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                            >
                                <Workflow class="size-5" />
                            </span>
                            <div>
                                <CardTitle>Access and automation</CardTitle>
                                <CardDescription class="mt-1">
                                    Control visibility, submission rules,
                                    follow-up, and ticket closure.
                                </CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="grid gap-3 pt-6 md:grid-cols-2">
                        <label
                            v-for="flag in flagRows"
                            :key="flag.key"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition hover:border-blue-300"
                            :class="
                                form[flag.key]
                                    ? 'border-blue-300 bg-blue-50/50 dark:border-blue-500/40 dark:bg-blue-500/5'
                                    : ''
                            "
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                            >
                                <component :is="flag.icon" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold">{{
                                    flag.label
                                }}</span>
                                <span
                                    class="mt-1 block text-xs leading-5 text-muted-foreground"
                                >
                                    {{ flag.text }}
                                </span>
                            </span>
                            <span class="relative mt-0.5 inline-flex shrink-0">
                                <input
                                    v-model="form[flag.key]"
                                    type="checkbox"
                                    class="peer sr-only"
                                />
                                <span
                                    class="h-5 w-9 rounded-full bg-slate-200 transition peer-checked:bg-blue-600 after:absolute after:top-0.5 after:left-0.5 after:size-4 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:after:translate-x-4 dark:bg-slate-700"
                                ></span>
                            </span>
                        </label>
                    </CardContent>
                </Card>

                <Card class="overflow-hidden">
                    <CardHeader class="border-b bg-muted/20">
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                >
                                    <Mail class="size-5" />
                                </span>
                                <div>
                                    <CardTitle>Mail importing</CardTitle>
                                    <CardDescription class="mt-1">
                                        Optional inbound mailbox connection for
                                        automated ticket imports.
                                    </CardDescription>
                                </div>
                            </div>
                            <span
                                class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold text-slate-500 dark:bg-white/5"
                            >
                                Optional
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6 pt-6">
                        <div class="space-y-2">
                            <Label>Mail provider</Label>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <button
                                    v-for="(
                                        label, value
                                    ) in props.mailProviders"
                                    :key="value"
                                    type="button"
                                    class="rounded-xl border px-4 py-3 text-sm font-bold transition"
                                    :class="
                                        form.mail_provider === value
                                            ? 'border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-600/15'
                                            : 'text-muted-foreground hover:border-blue-300 hover:text-foreground'
                                    "
                                    @click="form.mail_provider = value"
                                >
                                    {{ label }}
                                </button>
                            </div>
                        </div>

                        <template v-if="form.mail_provider === 'pop3imap'">
                            <div
                                class="grid gap-5 md:grid-cols-[minmax(0,1fr)_150px]"
                            >
                                <div class="space-y-2">
                                    <Label for="mail-hostname"
                                        >Mail hostname</Label
                                    >
                                    <Input
                                        id="mail-hostname"
                                        v-model="form.mail_hostname"
                                        placeholder="mail.example.com"
                                    />
                                    <InputError
                                        :message="form.errors.mail_hostname"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="mail-port">Server port</Label>
                                    <Input
                                        id="mail-port"
                                        v-model.number="form.mail_port"
                                        type="number"
                                        min="0"
                                        max="65535"
                                        placeholder="993"
                                    />
                                    <InputError
                                        :message="form.errors.mail_port"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="mail-email"
                                        >Mailbox email</Label
                                    >
                                    <Input
                                        id="mail-email"
                                        v-model="form.mail_email"
                                        type="email"
                                        placeholder="tickets@example.com"
                                    />
                                    <InputError
                                        :message="form.errors.mail_email"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="mail-password">Password</Label>
                                    <Input
                                        id="mail-password"
                                        v-model="form.mail_password"
                                        type="password"
                                        autocomplete="new-password"
                                        :placeholder="
                                            props.department
                                                ?.mail_password_configured
                                                ? 'Configured - leave blank to keep'
                                                : 'Mailbox password'
                                        "
                                    />
                                    <InputError
                                        :message="form.errors.mail_password"
                                    />
                                </div>
                            </div>
                            <div
                                class="rounded-xl border border-dashed bg-muted/20 p-4"
                            >
                                <Button
                                    type="button"
                                    variant="outline"
                                    :disabled="testForm.processing"
                                    @click="testConfiguration"
                                >
                                    <PlugZap class="size-4" />
                                    {{
                                        testForm.processing
                                            ? 'Testing connection...'
                                            : 'Test connection'
                                    }}
                                </Button>
                                <InputError
                                    v-for="message in testErrors"
                                    :key="message"
                                    class="mt-2"
                                    :message="message"
                                />
                            </div>
                        </template>

                        <template v-else>
                            <div class="grid gap-5 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="mail-client-id"
                                        >OAuth client ID</Label
                                    >
                                    <Input
                                        id="mail-client-id"
                                        v-model="form.mail_client_id"
                                    />
                                    <InputError
                                        :message="form.errors.mail_client_id"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <Label for="mail-client-secret"
                                        >OAuth client secret</Label
                                    >
                                    <Input
                                        id="mail-client-secret"
                                        v-model="form.mail_client_secret"
                                        type="password"
                                        autocomplete="new-password"
                                        :placeholder="
                                            props.department
                                                ?.mail_client_secret_configured
                                                ? 'Configured - leave blank to keep'
                                                : 'Client secret'
                                        "
                                    />
                                    <InputError
                                        :message="
                                            form.errors.mail_client_secret
                                        "
                                    />
                                </div>
                            </div>
                            <p
                                class="rounded-xl bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200"
                            >
                                Create the OAuth application in
                                {{
                                    form.mail_provider === 'google'
                                        ? 'Google Cloud Console'
                                        : 'Microsoft Azure'
                                }}. Mailbox authorization is completed when
                                ticket importing is enabled.
                            </p>
                        </template>
                    </CardContent>
                </Card>
            </div>

            <aside class="space-y-5 xl:sticky xl:top-24">
                <div
                    class="overflow-hidden rounded-2xl bg-[radial-gradient(circle_at_80%_10%,rgba(103,232,249,.24),transparent_32%),linear-gradient(145deg,#082f6b,#0874d1)] p-5 text-white shadow-xl"
                >
                    <div class="flex items-center justify-between gap-3">
                        <p
                            class="text-xs font-extrabold tracking-wider text-cyan-200 uppercase"
                        >
                            Storefront preview
                        </p>
                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-extrabold"
                            :class="
                                form.hidden
                                    ? 'bg-white/10 text-slate-300'
                                    : 'bg-emerald-400/15 text-emerald-200'
                            "
                        >
                            {{ publicStatus }}
                        </span>
                    </div>
                    <div
                        class="mt-8 rounded-2xl bg-white p-5 text-slate-900 shadow-2xl"
                    >
                        <span
                            class="flex size-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-500"
                        >
                            <Inbox class="size-7" />
                        </span>
                        <p class="mt-5 text-lg font-extrabold">
                            {{ form.name || 'Department name' }}
                        </p>
                        <p
                            class="mt-2 min-h-15 text-sm leading-5 text-slate-500"
                        >
                            {{
                                form.description ||
                                'Your customer-facing description will appear here.'
                            }}
                        </p>
                        <span
                            class="mt-5 inline-flex items-center gap-2 text-xs font-extrabold text-blue-600"
                        >
                            Choose department →
                        </span>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base"
                            >Configuration summary</CardTitle
                        >
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-muted-foreground"
                                >Visibility</span
                            >
                            <span class="font-bold">{{ publicStatus }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-muted-foreground"
                                >Assigned staff</span
                            >
                            <span class="font-bold">{{
                                assignedAdminCount
                            }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-muted-foreground">Access</span>
                            <span class="font-bold">
                                {{
                                    form.clients_only
                                        ? 'Clients only'
                                        : 'All visitors'
                                }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-muted-foreground"
                                >Mail provider</span
                            >
                            <span class="font-bold">
                                {{ props.mailProviders[form.mail_provider] }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <div class="grid gap-2">
                    <Button type="submit" size="lg" :disabled="form.processing">
                        <Save class="size-4" />
                        {{
                            form.processing
                                ? 'Saving...'
                                : props.department
                                  ? 'Save department'
                                  : 'Create department'
                        }}
                    </Button>
                    <Button type="button" variant="outline" as-child>
                        <Link href="/admin/support/departments">Cancel</Link>
                    </Button>
                </div>
            </aside>
        </form>

        <div v-show="activeTab === 'fields'" class="space-y-6">
            <template v-if="props.department && props.fieldTypes">
                <Card>
                    <CardHeader>
                        <CardTitle>Department custom fields</CardTitle>
                        <CardDescription>
                            Collect structured information before a customer
                            submits a ticket.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <p
                            v-if="(props.fields ?? []).length === 0"
                            class="rounded-xl border border-dashed px-5 py-8 text-center text-sm text-muted-foreground"
                        >
                            No custom fields yet. Add the first field below.
                        </p>
                        <DepartmentFieldForm
                            v-for="field in props.fields ?? []"
                            :key="field.id"
                            :department-id="props.department.id"
                            :field="field"
                            :field-types="props.fieldTypes"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Add custom field</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DepartmentFieldForm
                            :department-id="props.department.id"
                            :field-types="props.fieldTypes"
                        />
                    </CardContent>
                </Card>
            </template>
            <Card v-else>
                <CardContent
                    class="flex flex-col items-center px-6 py-14 text-center"
                >
                    <ListChecks class="size-10 text-muted-foreground" />
                    <p class="mt-4 font-bold">Create the department first</p>
                    <p class="mt-2 max-w-md text-sm text-muted-foreground">
                        Once the department exists, return to this tab to add
                        custom fields.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
