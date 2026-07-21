<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
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
const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const submit = () => {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Admin sign in">
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
    </Head>

    <main
        class="flex min-h-screen items-center justify-center bg-muted/40 px-4 py-12"
    >
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <div
                    class="flex size-12 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm"
                >
                    <ShieldCheck class="size-6" />
                </div>
            </div>

            <Card>
                <CardHeader class="text-center">
                    <CardTitle class="text-2xl">Admin console</CardTitle>
                    <CardDescription>
                        Sign in with your local administrator account. Client
                        accounts sign in on the storefront separately.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
                                autofocus
                                required
                            />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="current-password"
                                required
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <label
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="size-4 rounded border-input"
                            />
                            Keep me signed in
                        </label>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Signing in…' : 'Sign in' }}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    </main>
</template>
