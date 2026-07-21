<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    ChevronDown,
    Clock,
    Copy,
    CreditCard,
    Link2,
    Power,
    PowerOff,
    Save,
    TriangleAlert,
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

interface GatewayField {
    name: string;
    label: string;
    type: 'text' | 'password' | 'yesno' | 'select';
    description?: string | null;
    options?: Record<string, string> | null;
    required: boolean;
    value?: string | null;
    configured: boolean;
}

interface CallbackUrl {
    label: string;
    url: string;
}

interface GatewayModule {
    key: string;
    name: string;
    moduleName: string;
    description: string;
    implemented: boolean;
    ready: boolean;
    configured: boolean;
    active: boolean;
    live: boolean;
    callbackUrls: CallbackUrl[];
    webhookInstructions: string | null;
    fields: GatewayField[];
}

const props = defineProps<{ gateways: GatewayModule[] }>();

const page = usePage();
const gatewayError = computed(() => page.props.errors.gateway);

const activeGateways = computed(() =>
    props.gateways.filter((gateway) => gateway.active),
);
const availableGateways = computed(() =>
    props.gateways.filter((gateway) => !gateway.active),
);

const expanded = ref<string | null>(
    props.gateways.find(
        (gateway) => gateway.active && !gateway.configured && gateway.implemented,
    )?.key ?? null,
);

const toggle = (key: string) => {
    expanded.value = expanded.value === key ? null : key;
};

const configForms = Object.fromEntries(
    props.gateways.map((gateway) => [
        gateway.key,
        useForm({
            config: Object.fromEntries(
                gateway.fields.map((field) => [field.name, field.value ?? '']),
            ) as Record<string, string>,
        }),
    ]),
);

const saveConfig = (gateway: GatewayModule) =>
    configForms[gateway.key].put(`/admin/settings/gateways/${gateway.key}`);

const activate = (gateway: GatewayModule) =>
    router.post(`/admin/settings/gateways/${gateway.key}/activate`);

const deactivate = (gateway: GatewayModule) => {
    if (confirm(`Deactivate ${gateway.name}? It will no longer be offered at checkout.`)) {
        router.delete(`/admin/settings/gateways/${gateway.key}`);
    }
};

const copiedUrl = ref<string | null>(null);

const copyUrl = async (url: string) => {
    try {
        await navigator.clipboard.writeText(url);
        copiedUrl.value = url;
        setTimeout(() => (copiedUrl.value = null), 1500);
    } catch {
        // Clipboard unavailable (non-secure context) — ignore.
    }
};

const configError = (gateway: GatewayModule, field: string) =>
    (configForms[gateway.key].errors as Record<string, string>)[
        `config.${field}`
    ] ?? (configForms[gateway.key].errors as Record<string, string>).config;
</script>

<template>
    <Head title="Payment gateways" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Configuration</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Payment gateways
            </h1>
            <p class="mt-1 text-muted-foreground">
                Activate a gateway first, then configure its credentials.
                Gateways are offered at checkout once they are active and
                fully configured.
            </p>
        </div>

        <InputError :message="gatewayError" />

        <section class="space-y-4">
            <h2 class="text-lg font-semibold">Active gateways</h2>

            <Card v-for="gateway in activeGateways" :key="gateway.key">
                <CardHeader>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <span class="flex flex-wrap items-center gap-2">
                                <CardTitle>{{ gateway.name }}</CardTitle>
                                <span
                                    v-if="gateway.name !== gateway.moduleName"
                                    class="text-xs text-muted-foreground"
                                >
                                    ({{ gateway.moduleName }} module)
                                </span>
                                <span
                                    v-if="gateway.live"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    <BadgeCheck class="size-3" /> Live at
                                    checkout
                                </span>
                                <span
                                    v-else-if="!gateway.configured"
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    <TriangleAlert class="size-3" /> Needs
                                    configuration
                                </span>
                                <span
                                    v-if="
                                        gateway.fields.length > 1 &&
                                        gateway.configured
                                    "
                                    class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-500/10 dark:text-sky-300"
                                >
                                    <CreditCard class="size-3" /> Configured
                                </span>
                            </span>
                            <CardDescription class="mt-1">
                                {{ gateway.description }}
                            </CardDescription>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-semibold transition hover:bg-muted"
                                @click="toggle(gateway.key)"
                            >
                                Configure
                                <ChevronDown
                                    class="size-3.5 transition"
                                    :class="
                                        expanded === gateway.key
                                            ? 'rotate-180'
                                            : ''
                                    "
                                />
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border border-destructive/40 px-3 py-1.5 text-xs font-semibold text-destructive transition hover:bg-destructive/10"
                                @click="deactivate(gateway)"
                            >
                                <PowerOff class="size-3.5" /> Deactivate
                            </button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent
                    v-if="expanded === gateway.key"
                    class="space-y-6 border-t pt-5"
                >
                    <form
                        class="space-y-5"
                        @submit.prevent="saveConfig(gateway)"
                    >
                        <div class="grid gap-5 md:grid-cols-2">
                            <div
                                v-for="field in gateway.fields"
                                :key="field.name"
                                class="space-y-2"
                                :class="
                                    field.type === 'yesno'
                                        ? 'flex items-end md:col-span-2'
                                        : ''
                                "
                            >
                                <template v-if="field.type === 'yesno'">
                                    <label
                                        class="flex items-center gap-2 text-sm font-medium"
                                    >
                                        <input
                                            type="checkbox"
                                            class="size-4 rounded"
                                            :checked="
                                                configForms[gateway.key].config[
                                                    field.name
                                                ] === '1'
                                            "
                                            @change="
                                                configForms[gateway.key].config[
                                                    field.name
                                                ] = (
                                                    $event.target as HTMLInputElement
                                                ).checked
                                                    ? '1'
                                                    : '0'
                                            "
                                        />
                                        {{ field.label }}
                                    </label>
                                </template>
                                <template v-else-if="field.type === 'select'">
                                    <Label>{{ field.label }}</Label>
                                    <select
                                        v-model="
                                            configForms[gateway.key].config[
                                                field.name
                                            ]
                                        "
                                        class="h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    >
                                        <option value="">—</option>
                                        <option
                                            v-for="(optionLabel, value) in field.options"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ optionLabel }}
                                        </option>
                                    </select>
                                </template>
                                <template v-else>
                                    <Label>{{ field.label }}</Label>
                                    <Input
                                        v-model="
                                            configForms[gateway.key].config[
                                                field.name
                                            ]
                                        "
                                        :type="field.type"
                                        :placeholder="
                                            field.type === 'password' &&
                                            field.configured
                                                ? 'Configured — leave blank to keep it'
                                                : (field.description ?? '')
                                        "
                                    />
                                    <p
                                        v-if="
                                            field.description &&
                                            field.type !== 'password'
                                        "
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ field.description }}
                                    </p>
                                </template>
                                <InputError
                                    :message="configError(gateway, field.name)"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Button
                                type="submit"
                                :disabled="configForms[gateway.key].processing"
                            >
                                <Save class="size-4" /> Save configuration
                            </Button>
                            <p
                                v-if="configForms[gateway.key].wasSuccessful"
                                class="text-sm font-medium text-emerald-600"
                            >
                                Saved.
                            </p>
                        </div>
                    </form>

                    <div
                        v-if="gateway.callbackUrls.length"
                        class="space-y-3 rounded-lg border bg-muted/30 p-4"
                    >
                        <p
                            class="flex items-center gap-2 text-sm font-semibold"
                        >
                            <Link2 class="size-4" /> Webhook &amp; return URLs
                        </p>
                        <p
                            v-if="gateway.webhookInstructions"
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ gateway.webhookInstructions }}
                        </p>
                        <div
                            v-for="callback in gateway.callbackUrls"
                            :key="callback.label"
                            class="space-y-1"
                        >
                            <Label class="text-xs">{{ callback.label }}</Label>
                            <div class="flex items-center gap-2">
                                <code
                                    class="min-w-0 flex-1 truncate rounded-md border bg-background px-3 py-2 text-xs"
                                >
                                    {{ callback.url }}
                                </code>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="copyUrl(callback.url)"
                                >
                                    <Copy class="size-3.5" />
                                    {{
                                        copiedUrl === callback.url
                                            ? 'Copied!'
                                            : 'Copy'
                                    }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section v-if="availableGateways.length" class="space-y-4">
            <h2 class="text-lg font-semibold">Available gateways</h2>

            <Card v-for="gateway in availableGateways" :key="gateway.key">
                <CardHeader>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <span class="flex flex-wrap items-center gap-2">
                                <CardTitle>{{ gateway.name }}</CardTitle>
                                <span
                                    v-if="!gateway.implemented"
                                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground"
                                >
                                    <Clock class="size-3" /> Coming soon
                                </span>
                            </span>
                            <CardDescription class="mt-1">
                                {{ gateway.description }}
                            </CardDescription>
                        </div>

                        <Button
                            v-if="gateway.implemented"
                            type="button"
                            @click="activate(gateway)"
                        >
                            <Power class="size-4" /> Activate
                        </Button>
                    </div>
                </CardHeader>
            </Card>
        </section>
    </div>
</template>
