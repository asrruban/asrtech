<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
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

type Delivery = {
    id: number;
    inquiry_id: number;
    kind: string;
    recipient: string;
    status: string;
    attempts: number;
    sent_at: string | null;
    last_error: string | null;
};
const props = defineProps<{
    configuration: {
        enabled: boolean;
        acknowledgements: boolean;
        sender: string;
        recipient: string;
        transport: string;
        ready: boolean;
        issue: string | null;
    };
    deliveries: {
        data: Delivery[];
        prev_page_url: string | null;
        next_page_url: string | null;
        current_page: number;
        last_page: number;
    };
}>();
const form = useForm({
    enabled: props.configuration.enabled,
    acknowledgements: props.configuration.acknowledgements,
    recipient: props.configuration.recipient,
    verified: false,
});
const retryForm = useForm({});
const retrying = { id: 0 };
const retry = (id: number) => {
    retrying.id = id;
    retryForm.post(`/admin/settings/inquiry-notifications/${id}/retry`, {
        preserveScroll: true,
    });
};
const submit = () =>
    form.put('/admin/settings/inquiry-notifications', {
        preserveScroll: true,
        onSuccess: () => {
            form.verified = false;
        },
    });
</script>

<template>
    <Head title="Inquiry notifications" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Configuration</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Inquiry notifications
            </h1>
            <p class="mt-2 max-w-3xl text-muted-foreground">
                Receive new inquiries in your inbox and optionally acknowledge
                the customer’s submission. Inquiries are always saved in the
                admin inbox.
            </p>
        </div>
        <div
            v-if="configuration.issue"
            role="status"
            class="rounded-xl border bg-muted/40 p-4 text-sm"
        >
            {{ configuration.issue }}
            <Link
                href="/admin/settings/general"
                class="font-semibold text-primary underline"
                >Open General Configuration</Link
            >
        </div>
        <Card>
            <CardHeader
                ><CardTitle>Email delivery</CardTitle
                ><CardDescription
                    >Notifications start disabled. Configure and verify your
                    mail provider before enabling them. Saving settings does not
                    send a test email.</CardDescription
                ></CardHeader
            >
            <CardContent>
                <form class="max-w-2xl space-y-5" @submit.prevent="submit">
                    <div class="rounded-lg bg-muted/40 p-4 text-sm">
                        <p>
                            <strong>Sending address:</strong>
                            {{ configuration.sender || 'Not configured' }}
                        </p>
                        <p class="mt-1">
                            <strong>Mail transport:</strong>
                            {{ configuration.transport || 'Not configured' }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="inquiry-recipient"
                            >Your notification inbox</Label
                        ><Input
                            id="inquiry-recipient"
                            v-model="form.recipient"
                            type="email"
                            autocomplete="email"
                            :required="form.enabled"
                            :aria-invalid="Boolean(form.errors.recipient)"
                            aria-describedby="inquiry-recipient-error"
                        /><InputError
                            id="inquiry-recipient-error"
                            :message="form.errors.recipient"
                        />
                    </div>
                    <div>
                        <label class="flex items-start gap-3"
                            ><input
                                v-model="form.enabled"
                                type="checkbox"
                                class="mt-1 size-4 accent-primary"
                            /><span class="text-sm font-medium"
                                >Email me when a new inquiry is submitted</span
                            ></label
                        ><InputError :message="form.errors.enabled" />
                    </div>
                    <div>
                        <label class="flex items-start gap-3"
                            ><input
                                v-model="form.acknowledgements"
                                type="checkbox"
                                class="mt-1 size-4 accent-primary"
                            /><span class="text-sm"
                                ><span class="block font-medium"
                                    >Send a customer acknowledgement</span
                                ><span class="text-muted-foreground"
                                    >A short receipt with an inquiry reference.
                                    It does not include the message or promise a
                                    response time.</span
                                ></span
                            ></label
                        ><InputError :message="form.errors.acknowledgements" />
                    </div>
                    <div v-if="form.enabled" class="rounded-lg border p-4">
                        <label class="flex items-start gap-3"
                            ><input
                                v-model="form.verified"
                                type="checkbox"
                                required
                                class="mt-1 size-4 accent-primary"
                                :aria-invalid="Boolean(form.errors.verified)"
                                aria-describedby="inquiry-verified-error"
                            /><span class="text-sm"
                                >I have verified this sending address with our
                                mail provider and confirmed that I control the
                                notification inbox.</span
                            ></label
                        ><InputError
                            id="inquiry-verified-error"
                            :message="form.errors.verified"
                        />
                    </div>
                    <Button type="submit" :disabled="form.processing">{{
                        form.processing
                            ? 'Saving…'
                            : 'Save notification settings'
                    }}</Button>
                </form>
            </CardContent>
        </Card>
        <Card>
            <CardHeader
                ><CardTitle>Recent deliveries</CardTitle
                ><CardDescription
                    >Pending deliveries need a running queue worker. Failed
                    deliveries retry automatically, up to five attempts. Review
                    held or failed deliveries before retrying
                    them.</CardDescription
                ></CardHeader
            >
            <CardContent class="space-y-4">
                <InputError
                    :message="
                        (retryForm.errors as Record<string, string>).delivery
                    "
                />
                <div
                    v-if="!deliveries.data.length"
                    class="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    No notification deliveries yet. New inquiries appear here
                    after you enable notifications.
                </div>
                <div v-else class="overflow-x-auto rounded-xl border">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <caption class="sr-only">
                            Inquiry notification delivery history
                        </caption>
                        <thead class="bg-muted/40">
                            <tr>
                                <th scope="col" class="p-4">Inquiry</th>
                                <th scope="col" class="p-4">Recipient</th>
                                <th scope="col" class="p-4">Delivery</th>
                                <th scope="col" class="p-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="delivery in deliveries.data"
                                :key="delivery.id"
                            >
                                <td class="p-4">
                                    <Link
                                        href="/admin/inquiries"
                                        class="font-semibold text-primary"
                                        >#{{ delivery.inquiry_id }}</Link
                                    >
                                    <p
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        {{
                                            delivery.kind === 'admin'
                                                ? 'Admin notification'
                                                : 'Customer receipt'
                                        }}
                                    </p>
                                </td>
                                <td class="max-w-xs p-4 break-words">
                                    {{ delivery.recipient }}
                                </td>
                                <td class="p-4">
                                    <span class="font-medium capitalize">{{
                                        delivery.status
                                    }}</span>
                                    <p class="text-xs text-muted-foreground">
                                        {{ delivery.attempts }}
                                        {{
                                            delivery.attempts === 1
                                                ? 'attempt'
                                                : 'attempts'
                                        }}
                                    </p>
                                    <p
                                        v-if="delivery.last_error"
                                        class="mt-1 max-w-sm text-xs text-muted-foreground"
                                    >
                                        {{ delivery.last_error }}
                                    </p>
                                </td>
                                <td class="p-4">
                                    <Button
                                        v-if="
                                            ['failed', 'held'].includes(
                                                delivery.status,
                                            )
                                        "
                                        variant="outline"
                                        size="sm"
                                        :disabled="retryForm.processing"
                                        :aria-label="`Retry ${delivery.kind} delivery for inquiry ${delivery.inquiry_id}`"
                                        @click="retry(delivery.id)"
                                        >{{
                                            retryForm.processing &&
                                            retrying.id === delivery.id
                                                ? 'Queueing…'
                                                : 'Retry'
                                        }}</Button
                                    ><span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <nav
                    v-if="deliveries.last_page > 1"
                    aria-label="Delivery pages"
                    class="flex items-center justify-between gap-4 text-sm"
                >
                    <Link
                        v-if="deliveries.prev_page_url"
                        :href="deliveries.prev_page_url"
                        class="font-medium text-primary"
                        >Previous</Link
                    ><span
                        >Page {{ deliveries.current_page }} of
                        {{ deliveries.last_page }}</span
                    ><Link
                        v-if="deliveries.next_page_url"
                        :href="deliveries.next_page_url"
                        class="font-medium text-primary"
                        >Next</Link
                    >
                </nav>
            </CardContent>
        </Card>
    </div>
</template>
