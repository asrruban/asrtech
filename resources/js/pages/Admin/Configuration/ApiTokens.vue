<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Check, Copy, KeyRound, Plus, Trash2 } from '@lucide/vue';
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

const props = defineProps<{
    tokens: {
        id: number;
        name: string;
        created_by: string | null;
        last_used_at: string | null;
        last_used_ip: string | null;
        revoked: boolean;
        created_at: string | null;
    }[];
    newToken: string | null;
}>();

const createForm = useForm({ name: '' });
const revokeForm = useForm({});
const copied = ref(false);

const create = () =>
    createForm.post('/admin/settings/api-tokens', {
        onSuccess: () => createForm.reset(),
    });

const revoke = (id: number) => {
    if (
        !confirm(
            'Revoke this API token? Integrations using it will stop working immediately.',
        )
    ) {
        return;
    }

    revokeForm.delete(`/admin/settings/api-tokens/${id}`);
};

const copyToken = async () => {
    if (!props.newToken) {
        return;
    }

    await navigator.clipboard.writeText(props.newToken);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const formatDate = (value: string | null) =>
    value ? new Date(value).toLocaleString() : '—';
</script>

<template>
    <Head title="API tokens" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">API tokens</h1>
            <p class="text-sm text-muted-foreground">
                Bearer tokens for the REST API (<code class="font-mono"
                    >/api/v1/*</code
                >). Send as
                <code class="font-mono"
                    >Authorization: Bearer &lt;token&gt;</code
                >.
            </p>
        </div>

        <Card
            v-if="props.newToken"
            class="border-emerald-500/40 bg-emerald-500/5"
        >
            <CardHeader>
                <CardTitle class="text-base">Your new API token</CardTitle>
                <CardDescription>
                    Copy it now — for security it is shown only once.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex items-center gap-2">
                    <code
                        class="flex-1 rounded-md border bg-background px-3 py-2 font-mono text-sm break-all"
                    >
                        {{ props.newToken }}
                    </code>
                    <Button
                        size="icon"
                        variant="outline"
                        aria-label="Copy token"
                        @click="copyToken"
                    >
                        <Check v-if="copied" class="size-4 text-emerald-500" />
                        <Copy v-else class="size-4" />
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Create a token</CardTitle>
            </CardHeader>
            <CardContent>
                <form
                    class="flex max-w-md items-end gap-3"
                    @submit.prevent="create"
                >
                    <div class="flex-1 space-y-1.5">
                        <Label for="token-name">Name</Label>
                        <Input
                            id="token-name"
                            v-model="createForm.name"
                            placeholder="e.g. Zapier integration"
                            required
                        />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <Button type="submit" :disabled="createForm.processing">
                        <Plus class="size-4" /> Create
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="props.tokens.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No API tokens yet.
                </div>
                <table v-else class="w-full text-left text-sm">
                    <thead>
                        <tr
                            class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            <th class="px-5 py-3.5">Name</th>
                            <th class="px-5 py-3.5">Created by</th>
                            <th class="px-5 py-3.5">Last used</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="token in props.tokens"
                            :key="token.id"
                            class="border-b last:border-0"
                        >
                            <td class="px-5 py-4">
                                <div
                                    class="flex items-center gap-2 font-medium"
                                >
                                    <KeyRound
                                        class="size-4 text-muted-foreground"
                                    />
                                    {{ token.name }}
                                </div>
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ token.created_by }}
                            </td>
                            <td class="px-5 py-4 text-muted-foreground">
                                {{ formatDate(token.last_used_at) }}
                                <span v-if="token.last_used_ip" class="text-xs"
                                    >({{ token.last_used_ip }})</span
                                >
                            </td>
                            <td class="px-5 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        token.revoked
                                            ? 'bg-red-500/10 text-red-500'
                                            : 'bg-emerald-500/10 text-emerald-500'
                                    "
                                >
                                    {{ token.revoked ? 'Revoked' : 'Active' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <Button
                                    v-if="!token.revoked"
                                    size="sm"
                                    variant="outline"
                                    @click="revoke(token.id)"
                                >
                                    <Trash2 class="size-4" /> Revoke
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
