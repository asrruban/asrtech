<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { UserRoundPlus } from '@lucide/vue';
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

    <section
        class="flex min-h-[70vh] items-center justify-center bg-muted/40 px-4 py-16"
    >
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <div
                    class="flex size-12 items-center justify-center rounded-xl bg-[#4fb250] text-white shadow-sm"
                >
                    <UserRoundPlus class="size-6" />
                </div>
            </div>

            <Card>
                <CardHeader class="text-center">
                    <CardTitle class="text-2xl">Create your account</CardTitle>
                    <CardDescription>
                        Buy products and get license keys instantly.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-5" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="name">Full name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                autocomplete="name"
                                autofocus
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="email">Email address</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                autocomplete="username"
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
                                autocomplete="new-password"
                                required
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="space-y-2">
                            <Label for="password_confirmation"
                                >Confirm password</Label
                            >
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                            />
                            <InputError
                                :message="form.errors.password_confirmation"
                            />
                        </div>

                        <div v-if="site.requireTosAccept" class="space-y-2">
                            <label class="flex items-start gap-2 text-sm">
                                <input
                                    v-model="form.terms"
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
                                        class="font-semibold text-[#4fb250] hover:text-[#439c45]"
                                        >Terms of Service</a
                                    >
                                    <span v-else>Terms of Service</span>
                                </span>
                            </label>
                            <InputError :message="form.errors.terms" />
                        </div>

                        <Button
                            type="submit"
                            class="w-full bg-[#4fb250] hover:bg-[#439c45]"
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
                            class="font-semibold text-[#4fb250] hover:text-[#439c45]"
                        >
                            Sign in
                        </Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    </section>
</template>
