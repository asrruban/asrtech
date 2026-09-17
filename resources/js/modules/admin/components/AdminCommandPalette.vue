<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Loader2, Search } from '@lucide/vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';
import {
    Dialog,
    DialogContent,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';

type SearchItem = { title: string; subtitle: string | null; url: string };
type SearchGroup = { label: string; items: SearchItem[] };

const open = ref(false);
const query = ref('');
const groups = ref<SearchGroup[]>([]);
const loading = ref(false);
const activeIndex = ref(0);
const inputRef = ref<HTMLInputElement | null>(null);
const searchError = ref('');

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let abortController: AbortController | null = null;

const flatItems = computed(() =>
    groups.value.flatMap((group) =>
        group.items.map((item) => ({ ...item, group: group.label })),
    ),
);

const openPalette = () => {
    open.value = true;
    nextTick(() => inputRef.value?.focus());
};

const closePalette = () => {
    open.value = false;
    query.value = '';
    groups.value = [];
    activeIndex.value = 0;
};

const onGlobalKeydown = (event: KeyboardEvent) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();

        if (open.value) {
            closePalette();
        } else {
            openPalette();
        }
    } else if (event.key === 'Escape' && open.value) {
        closePalette();
    }
};

const search = async (term: string) => {
    abortController?.abort();

    if (term.trim().length < 2) {
        groups.value = [];
        loading.value = false;

        return;
    }

    loading.value = true;
    searchError.value = '';
    abortController = new AbortController();

    try {
        const response = await fetch(
            `/admin/search?q=${encodeURIComponent(term)}`,
            {
                headers: { Accept: 'application/json' },
                signal: abortController.signal,
            },
        );

        if (response.ok) {
            const payload = (await response.json()) as {
                groups: SearchGroup[];
            };
            groups.value = payload.groups;
            activeIndex.value = 0;
        } else {
            searchError.value = 'Search is unavailable. Please try again.';
        }
    } catch (error) {
        if (!(error instanceof DOMException && error.name === 'AbortError')) {
            searchError.value = 'Could not connect. Please try again.';
        }
    } finally {
        loading.value = false;
    }
};

watch(query, (term) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => search(term), 200);
});

const move = (direction: 1 | -1) => {
    const count = flatItems.value.length;

    if (count === 0) {
        return;
    }

    activeIndex.value = (activeIndex.value + direction + count) % count;
};

const go = (item?: SearchItem) => {
    const target = item ?? flatItems.value[activeIndex.value];

    if (!target) {
        return;
    }

    closePalette();
    router.visit(target.url);
};

onMounted(() => window.addEventListener('keydown', onGlobalKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onGlobalKeydown);

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    abortController?.abort();
});

// Running index across groups for keyboard highlight.
const itemIndex = (groupIndex: number, index: number) => {
    let offset = 0;

    for (let i = 0; i < groupIndex; i++) {
        offset += groups.value[i].items.length;
    }

    return offset + index;
};
defineExpose({ openPalette });
</script>

<template>
    <Dialog
        :open="open"
        @update:open="
            (value) => {
                if (!value) closePalette();
            }
        "
    >
        <DialogContent
            class="gap-0 overflow-hidden p-0 sm:max-w-xl"
            @open-auto-focus.prevent="inputRef?.focus()"
        >
            <DialogTitle class="sr-only">Search administration</DialogTitle>
            <DialogDescription class="sr-only"
                >Search users, invoices, licenses, tickets, and products. Use
                arrow keys to select a result, then press
                Enter.</DialogDescription
            >
            <div class="flex items-center gap-3 border-b px-4">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <input
                    ref="inputRef"
                    v-model="query"
                    type="search"
                    aria-label="Search administration"
                    placeholder="Search users, invoices, licenses, tickets, products…"
                    class="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    @keydown.down.prevent="move(1)"
                    @keydown.up.prevent="move(-1)"
                    @keydown.enter.prevent="go()"
                />
                <Loader2
                    v-if="loading"
                    class="size-4 shrink-0 animate-spin text-muted-foreground"
                />
                <kbd
                    class="hidden shrink-0 rounded border bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground sm:inline"
                    >ESC</kbd
                >
            </div>

            <div class="max-h-80 overflow-y-auto p-2">
                <p
                    v-if="searchError"
                    role="alert"
                    class="px-3 py-5 text-sm text-destructive"
                >
                    {{ searchError }}
                </p>
                <p
                    v-if="
                        !searchError &&
                        query.trim().length >= 2 &&
                        !loading &&
                        flatItems.length === 0
                    "
                    class="px-3 py-8 text-center text-sm text-muted-foreground"
                >
                    No results for “{{ query }}”.
                </p>
                <p
                    v-else-if="query.trim().length < 2"
                    class="px-3 py-8 text-center text-sm text-muted-foreground"
                >
                    Type at least 2 characters to search.
                </p>

                <div
                    v-for="(group, groupIndex) in groups"
                    :key="group.label"
                    class="mb-1"
                >
                    <p
                        class="px-3 pt-2 pb-1 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ group.label }}
                    </p>
                    <button
                        v-for="(item, index) in group.items"
                        :key="item.url"
                        type="button"
                        class="flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-left text-sm transition"
                        :class="
                            itemIndex(groupIndex, index) === activeIndex
                                ? 'bg-primary/10 text-foreground'
                                : 'hover:bg-muted'
                        "
                        @mouseenter="activeIndex = itemIndex(groupIndex, index)"
                        @click="go(item)"
                    >
                        <span class="truncate font-medium">{{
                            item.title
                        }}</span>
                        <span
                            class="shrink-0 truncate text-xs text-muted-foreground"
                        >
                            {{ item.subtitle }}
                        </span>
                    </button>
                </div>
            </div>

            <div
                class="flex items-center gap-4 border-t px-4 py-2 text-[11px] text-muted-foreground"
            >
                <span>↑↓ navigate</span>
                <span>↵ open</span>
                <span>esc close</span>
            </div>
        </DialogContent>
    </Dialog>
</template>
