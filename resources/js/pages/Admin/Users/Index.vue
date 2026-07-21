<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { BadgeCheck, Search, UserRound, UserRoundPlus } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps(['filters', 'users']);

const search = ref(props.filters.search ?? '');
const createOpen = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    verified: true,
});

const createUser = () =>
    createForm.post('/admin/users', {
        onSuccess: () => {
            createOpen.value = false;
            createForm.reset();
        },
    });

const applyFilters = () =>
    router.get(
        '/admin/users',
        { search: search.value },
        { preserveState: true, replace: true },
    );

const formatDate = (date: string) =>
    new Intl.DateTimeFormat('en', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));

const paginationLabel = (value: string) =>
    value.replace('&laquo;', '').replace('&raquo;', '').trim();
</script>

<template>
    <Head title="Users" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <div>
            <p class="text-sm font-medium text-primary">Customers</p>
            <h1 class="text-3xl font-semibold tracking-tight">Users</h1>
            <p class="mt-1 text-muted-foreground">
                Registered customers with their orders and licenses.
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <form class="flex max-w-md flex-1 gap-3" @submit.prevent="applyFilters">
                <Input v-model="search" placeholder="Search by name or email" />
                <Button type="submit" variant="outline">
                    <Search class="size-4" /> Search
                </Button>
            </form>

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button><UserRoundPlus class="size-4" /> Add user</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add a user</DialogTitle>
                        <DialogDescription>
                            Create a client account. Verified accounts can buy
                            immediately without an email code.
                        </DialogDescription>
                    </DialogHeader>
                    <form class="space-y-5" @submit.prevent="createUser">
                        <div class="space-y-2">
                            <Label for="new-user-name">Full name</Label>
                            <Input
                                id="new-user-name"
                                v-model="createForm.name"
                                required
                            />
                            <InputError :message="createForm.errors.name" />
                        </div>
                        <div class="space-y-2">
                            <Label for="new-user-email">Email address</Label>
                            <Input
                                id="new-user-email"
                                v-model="createForm.email"
                                type="email"
                                required
                            />
                            <InputError :message="createForm.errors.email" />
                        </div>
                        <div class="space-y-2">
                            <Label for="new-user-password">Password</Label>
                            <Input
                                id="new-user-password"
                                v-model="createForm.password"
                                type="password"
                                autocomplete="new-password"
                                required
                            />
                            <InputError
                                :message="createForm.errors.password"
                            />
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="createForm.verified"
                                type="checkbox"
                                class="size-4 rounded"
                            />
                            Mark email as verified
                        </label>
                        <DialogFooter>
                            <Button
                                type="submit"
                                :disabled="createForm.processing"
                            >
                                <UserRoundPlus class="size-4" />
                                {{
                                    createForm.processing
                                        ? 'Creating…'
                                        : 'Create user'
                                }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="users.data.length === 0"
                    class="p-10 text-center text-sm text-muted-foreground"
                >
                    No users found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead>
                            <tr
                                class="border-b text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-5 py-3.5">Customer</th>
                                <th class="px-5 py-3.5">Verified</th>
                                <th class="px-5 py-3.5">Sign in</th>
                                <th class="px-5 py-3.5">Orders</th>
                                <th class="px-5 py-3.5">Licenses</th>
                                <th class="px-5 py-3.5">Joined</th>
                                <th class="px-5 py-3.5"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="border-b last:border-b-0 hover:bg-muted/40"
                            >
                                <td class="px-5 py-4">
                                    <p class="font-semibold">{{ user.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ user.email }}
                                    </p>
                                </td>
                                <td class="px-5 py-4">
                                    <BadgeCheck
                                        v-if="user.email_verified_at"
                                        class="size-4 text-emerald-600"
                                    />
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                        >Pending</span
                                    >
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ user.social_provider ?? 'Email' }}
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    {{ user.orders_count }}
                                </td>
                                <td class="px-5 py-4 font-semibold">
                                    {{ user.licenses_count }}
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <Button as-child size="sm" variant="outline">
                                        <Link :href="`/admin/users/${user.id}`">
                                            <UserRound class="size-4" /> Manage
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div
            v-if="users.last_page > 1"
            class="flex items-center justify-between gap-4 text-sm"
        >
            <p class="text-muted-foreground">
                Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
            </p>
            <div class="flex gap-2">
                <template v-for="link in users.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        as-child
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                    >
                        <Link :href="link.url">{{
                            paginationLabel(link.label)
                        }}</Link>
                    </Button>
                    <Button v-else size="sm" variant="outline" disabled>
                        {{ paginationLabel(link.label) }}
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>
