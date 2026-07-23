<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Check,
    ExternalLink,
    EyeOff,
    RotateCcw,
    Search,
    Star,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

interface Review {
    id: number;
    rating: number;
    title?: string | null;
    content: string;
    status: string;
    moderation_note?: string | null;
    moderated_at?: string | null;
    created_at: string;
    user: { id: number; name: string; email: string };
    product: { id: number; name: string; url: string };
    moderator?: { id: number; name: string } | null;
}

const props = defineProps<{
    filters: { status: string; search: string };
    statusOptions: string[];
    counts: Record<string, number>;
    reviews: Record<string, any>;
}>();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? 'pending');
const notes = ref<Record<number, string>>(
    Object.fromEntries(
        (props.reviews.data as Review[]).map((review) => [
            review.id,
            review.moderation_note ?? '',
        ]),
    ),
);
const busyId = ref<number | null>(null);
watch(
    () => props.reviews.data,
    (reviews: Review[]) => {
        for (const review of reviews) {
            notes.value[review.id] = review.moderation_note ?? '';
        }
    },
);

const filter = () =>
    router.get(
        '/admin/product-reviews',
        { search: search.value, status: status.value },
        { preserveState: true, replace: true },
    );
const setStatus = (value: string) => {
    status.value = value;
    filter();
};
const moderate = (review: Review, nextStatus: string) => {
    busyId.value = review.id;
    router.patch(
        `/admin/product-reviews/${review.id}`,
        {
            status: nextStatus,
            moderation_note: notes.value[review.id] || null,
        },
        {
            preserveScroll: true,
            onFinish: () => (busyId.value = null),
        },
    );
};
const label = (value: string) =>
    value
        .split('_')
        .map((part) => part[0].toUpperCase() + part.slice(1))
        .join(' ');
const date = (value: string) =>
    new Intl.DateTimeFormat('en', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
const pageLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
const statusClass = (value: string) =>
    ({
        pending: 'bg-amber-100 text-amber-800',
        approved: 'bg-emerald-100 text-emerald-800',
        hidden: 'bg-slate-200 text-slate-700',
    })[value] ?? 'bg-muted text-muted-foreground';
</script>

<template>
    <Head title="Product reviews" />
    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Product reviews
            </h1>
            <p class="mt-1 text-muted-foreground">
                Moderate customer-authored reviews. Admins cannot write or
                rewrite customer feedback.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button
                v-for="option in statusOptions"
                :key="option"
                type="button"
                size="sm"
                :variant="status === option ? 'default' : 'outline'"
                @click="setStatus(option)"
            >
                {{ label(option) }}
                <span class="rounded-full bg-black/10 px-1.5 text-xs">
                    {{ counts[option] ?? 0 }}
                </span>
            </Button>
            <Button
                type="button"
                size="sm"
                :variant="status === '' ? 'default' : 'outline'"
                @click="setStatus('')"
            >
                All
            </Button>
        </div>

        <form class="flex max-w-2xl gap-3" @submit.prevent="filter">
            <Input
                v-model="search"
                class="min-w-56 flex-1"
                placeholder="Customer, email, product, or review text"
            />
            <Button type="submit" variant="outline">
                <Search class="size-4" /> Search
            </Button>
        </form>

        <div
            v-if="reviews.data.length === 0"
            class="rounded-xl border bg-card p-12 text-center text-sm text-muted-foreground"
        >
            No reviews found for this filter.
        </div>

        <div v-else class="space-y-4">
            <Card v-for="review in reviews.data" :key="review.id">
                <CardContent class="space-y-5 p-5 sm:p-6">
                    <div
                        class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start"
                    >
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="flex gap-0.5 text-amber-500">
                                    <Star
                                        v-for="number in 5"
                                        :key="number"
                                        class="size-4"
                                        :class="
                                            number <= review.rating
                                                ? 'fill-current'
                                                : 'text-muted'
                                        "
                                    />
                                </div>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-bold"
                                    :class="statusClass(review.status)"
                                >
                                    {{ label(review.status) }}
                                </span>
                                <span class="text-xs text-muted-foreground">
                                    {{ date(review.created_at) }}
                                </span>
                            </div>
                            <h2 class="text-lg font-semibold">
                                {{ review.title || 'Verified customer review' }}
                            </h2>
                            <p
                                class="max-w-4xl text-sm leading-6 text-muted-foreground"
                            >
                                {{ review.content }}
                            </p>
                        </div>
                        <a
                            :href="review.product.url"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-primary hover:underline"
                        >
                            View product <ExternalLink class="size-3.5" />
                        </a>
                    </div>

                    <div
                        class="grid gap-4 border-t pt-5 md:grid-cols-[220px_1fr]"
                    >
                        <div class="text-sm">
                            <p class="font-semibold">{{ review.user.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ review.user.email }}
                            </p>
                            <p class="mt-3 text-xs font-medium">
                                {{ review.product.name }}
                            </p>
                            <p
                                v-if="review.moderator"
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                Last moderated by
                                {{ review.moderator.name }}
                            </p>
                        </div>
                        <div class="space-y-3">
                            <textarea
                                v-model="notes[review.id]"
                                rows="2"
                                maxlength="2000"
                                placeholder="Optional private moderation note"
                                class="w-full rounded-md border bg-transparent px-3 py-2 text-sm"
                            />
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    :disabled="busyId === review.id"
                                    @click="moderate(review, 'approved')"
                                >
                                    <Check class="size-4" /> Approve
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    :disabled="busyId === review.id"
                                    @click="moderate(review, 'hidden')"
                                >
                                    <EyeOff class="size-4" /> Hide
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    :disabled="busyId === review.id"
                                    @click="moderate(review, 'pending')"
                                >
                                    <RotateCcw class="size-4" /> Return to
                                    pending
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="reviews.last_page > 1"
            class="flex flex-wrap items-center justify-between gap-3 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ reviews.from }}–{{ reviews.to }} of
                {{ reviews.total }}
            </p>
            <div class="flex gap-2">
                <template v-for="link in reviews.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        as-child
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                    >
                        <Link :href="link.url">{{
                            pageLabel(link.label)
                        }}</Link>
                    </Button>
                    <Button v-else size="sm" variant="outline" disabled>
                        {{ pageLabel(link.label) }}
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>
