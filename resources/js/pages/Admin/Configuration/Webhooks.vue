<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Check, Copy, Plus, Trash2, Webhook } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    endpoints: {
        id: number;
        name: string;
        url: string;
        secret: string;
        events: string[];
        enabled: boolean;
        successful_deliveries: number;
        failed_deliveries: number;
        created_at: string | null;
    }[];
    availableEvents: string[];
}>();

const createForm = useForm({
    name: '',
    url: '',
    events: [] as string[],
});

const copied = ref<number | null>(null);

const create = () =>
    createForm.post('/admin/settings/webhooks', {
        onSuccess: () => createForm.reset(),
    });

const toggle = (endpoint: { id: number; enabled: boolean }) =>
    useForm({ enabled: !endpoint.enabled }).patch(
        `/admin/settings/webhooks/${endpoint.id}`,
    );

const remove = (id: number) => {
    if (
        !confirm(
            'Delete this webhook endpoint? Its delivery history is removed too.',
        )
    ) {
        return;
    }

    useForm({}).delete(`/admin/settings/webhooks/${id}`);
};

const copySecret = async (endpoint: { id: number; secret: string }) => {
    await navigator.clipboard.writeText(endpoint.secret);
    copied.value = endpoint.id;
    setTimeout(() => (copied.value = null), 2000);
};
</script>

<template>
    <Head title="Webhooks" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Outbound webhooks</h1>
            <p class="text-sm text-muted-foreground">
                POST signed JSON events to external endpoints. Verify with the
                <code class="font-mono">X-ASRTech-Signature</code> HMAC-SHA256
                header.
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Add an endpoint</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="create">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="wh-name">Name</Label>
                            <Input
                                id="wh-name"
                                v-model="createForm.name"
                                placeholder="e.g. Zapier"
                                required
                            />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="wh-url">Endpoint URL</Label>
                            <Input
                                id="wh-url"
                                v-model="createForm.url"
                                type="url"
                                placeholder="https://example.com/hooks/asrtech"
                                required
                            />
                            <InputError :message="createForm.errors.url" />
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Events</Label>
                        <div class="flex flex-wrap gap-3">
                            <label
                                v-for="event in props.availableEvents"
                                :key="event"
                                class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                            >
                                <input
                                    v-model="createForm.events"
                                    type="checkbox"
                                    :value="event"
                                    class="size-4 rounded"
                                />
                                <code class="font-mono text-xs">{{
                                    event
                                }}</code>
                            </label>
                        </div>
                        <InputError :message="createForm.errors.events" />
                    </div>
                    <Button type="submit" :disabled="createForm.processing">
                        <Plus class="size-4" /> Create endpoint
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.endpoints.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No webhook endpoints yet.
                </div>
                <div v-else class="divide-y">
                    <div
                        v-for="endpoint in props.endpoints"
                        :key="endpoint.id"
                        class="flex flex-wrap items-center gap-4 px-5 py-4"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <Webhook
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <p class="font-semibold">{{ endpoint.name }}</p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="
                                        endpoint.enabled
                                            ? 'bg-emerald-500/10 text-emerald-500'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{
                                        endpoint.enabled
                                            ? 'Enabled'
                                            : 'Disabled'
                                    }}
                                </span>
                            </div>
                            <p
                                class="mt-1 truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ endpoint.url }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <code
                                    v-for="event in endpoint.events"
                                    :key="event"
                                    class="rounded bg-muted px-1.5 py-0.5 font-mono text-[11px]"
                                >
                                    {{ event }}
                                </code>
                            </div>
                            <button
                                type="button"
                                class="mt-2 inline-flex items-center gap-1.5 font-mono text-[11px] text-muted-foreground transition hover:text-foreground"
                                @click="copySecret(endpoint)"
                            >
                                <Check
                                    v-if="copied === endpoint.id"
                                    class="size-3 text-emerald-500"
                                />
                                <Copy v-else class="size-3" />
                                {{ endpoint.secret }}
                            </button>
                        </div>
                        <div class="text-right text-xs text-muted-foreground">
                            <p class="text-emerald-500">
                                {{ endpoint.successful_deliveries }} delivered
                            </p>
                            <p
                                :class="
                                    endpoint.failed_deliveries > 0
                                        ? 'text-red-500'
                                        : ''
                                "
                            >
                                {{ endpoint.failed_deliveries }} failed
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                @click="toggle(endpoint)"
                            >
                                {{ endpoint.enabled ? 'Disable' : 'Enable' }}
                            </Button>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="remove(endpoint.id)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
