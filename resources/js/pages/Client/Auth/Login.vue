<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import SeoHead from '@/modules/client/components/SeoHead.vue';
import SocialAuthButtons from '@/modules/client/components/SocialAuthButtons.vue';

const page = usePage();
const site = computed(() => page.props.site as Record<string, any>);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <SeoHead
        title="Sign in"
        description="Sign in to manage your licenses and orders."
    />

    <AuthPanel>
        <Card
            class="rounded-2xl border-[var(--client-border)] bg-[var(--client-surface)] py-3 shadow-sm sm:py-5"
        >
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">Welcome back</CardTitle>
                <CardDescription>
                    Sign in to access your licenses and orders.
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
                        <div class="flex items-center justify-between gap-3">
                            <Label for="password">Password</Label>
                            <Link
                                href="/forgot-password"
                                class="text-sm font-semibold text-[var(--client-accent)]"
                                >Forgot password?</Link
                            >
                        </div>
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
                        class="w-full bg-[#087f75] hover:bg-[#06655e]"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Signing in…' : 'Sign in' }}
                    </Button>
                </form>

                <div class="mt-5">
                    <SocialAuthButtons />
                </div>

                <p
                    v-if="site.allowRegistration"
                    class="mt-5 text-center text-sm text-muted-foreground"
                >
                    New here?
                    <Link
                        href="/register"
                        class="font-semibold text-[var(--client-accent)] hover:text-[var(--client-accent-dark)]"
                    >
                        Create an account
                    </Link>
                </p>
            </CardContent>
        </Card>
    </AuthPanel>
</template>
