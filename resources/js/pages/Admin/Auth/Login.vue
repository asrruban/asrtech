<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
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
import AuthPanel from '@/modules/client/components/AuthPanel.vue';
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

    <main class="min-h-screen bg-[var(--client-surface-soft)]">
        <AuthPanel
            :features="false"
            eyebrow="Administration"
            title="The workspace behind the work."
            description="Manage the ASR Tech catalog, customer accounts, billing, content, and support."
        >
            <Card
                class="rounded-2xl border-[var(--client-border)] bg-[var(--client-surface)] py-5 shadow-sm"
            >
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
                                :aria-invalid="Boolean(form.errors.email)"
                                :aria-describedby="
                                    form.errors.email
                                        ? 'form-email-error'
                                        : undefined
                                "
                                type="email"
                                autocomplete="username"
                                autofocus
                                required
                            />
                            <InputError
                                id="form-email-error"
                                :message="form.errors.email"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="password">Password</Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                :aria-invalid="Boolean(form.errors.password)"
                                :aria-describedby="
                                    form.errors.password
                                        ? 'form-password-error'
                                        : undefined
                                "
                                type="password"
                                autocomplete="current-password"
                                required
                            />
                            <InputError
                                id="form-password-error"
                                :message="form.errors.password"
                            />
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
        </AuthPanel>
    </main>
</template>
