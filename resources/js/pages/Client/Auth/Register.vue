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
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <SeoHead
        title="Create account"
        description="Create an account to buy products and manage licenses."
    />

    <AuthPanel
        title="Start with an account."
        description="Keep purchases, product access, and support together as you build with ASR Tech."
    >
        <Card
            class="rounded-2xl border-[var(--client-border)] bg-[var(--client-surface)] py-3 shadow-sm sm:py-5"
        >
            <CardHeader class="text-center">
                <CardTitle class="text-2xl">Create your account</CardTitle>
                <CardDescription>
                    Manage your purchases, licenses, and support requests.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="space-y-2">
                        <Label for="name">Full name</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            :aria-invalid="Boolean(form.errors.name)"
                            :aria-describedby="
                                form.errors.name ? 'form-name-error' : undefined
                            "
                            type="text"
                            autocomplete="name"
                            autofocus
                            required
                        />
                        <InputError
                            id="form-name-error"
                            :message="form.errors.name"
                        />
                    </div>

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
                            autocomplete="new-password"
                            required
                        />
                        <InputError
                            id="form-password-error"
                            :message="form.errors.password"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation"
                            >Confirm password</Label
                        >
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :aria-invalid="
                                Boolean(form.errors.password_confirmation)
                            "
                            :aria-describedby="
                                form.errors.password_confirmation
                                    ? 'form-password_confirmation-error'
                                    : undefined
                            "
                            type="password"
                            autocomplete="new-password"
                            required
                        />
                        <InputError
                            id="form-password_confirmation-error"
                            :message="form.errors.password_confirmation"
                        />
                    </div>

                    <div v-if="site.requireTosAccept" class="space-y-2">
                        <label class="flex items-start gap-2 text-sm">
                            <input
                                v-model="form.terms"
                                :aria-invalid="Boolean(form.errors.terms)"
                                :aria-describedby="
                                    form.errors.terms
                                        ? 'form-terms-error'
                                        : undefined
                                "
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-input"
                            />
                            <span>
                                I accept the
                                <a
                                    v-if="site.termsUrl"
                                    :href="site.termsUrl"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="font-semibold text-[var(--client-accent)] hover:text-[var(--client-accent-dark)]"
                                    >Terms of Service</a
                                >
                                <span v-else>Terms of Service</span>
                            </span>
                        </label>
                        <InputError
                            id="form-terms-error"
                            :message="form.errors.terms"
                        />
                    </div>

                    <Button
                        type="submit"
                        class="w-full bg-[#087f75] hover:bg-[#06655e]"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? 'Creating account…'
                                : 'Create account'
                        }}
                    </Button>
                </form>

                <div class="mt-5">
                    <SocialAuthButtons />
                </div>

                <p class="mt-5 text-center text-sm text-muted-foreground">
                    Already have an account?
                    <Link
                        href="/login"
                        class="font-semibold text-[var(--client-accent)] hover:text-[var(--client-accent-dark)]"
                    >
                        Sign in
                    </Link>
                </p>
            </CardContent>
        </Card>
    </AuthPanel>
</template>
