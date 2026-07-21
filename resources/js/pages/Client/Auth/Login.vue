<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { KeyRound } from '@lucide/vue';
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

    <section
        class="flex min-h-[70vh] items-center justify-center bg-muted/40 px-4 py-16"
    >
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <div
                    class="flex size-12 items-center justify-center rounded-xl bg-[#4fb250] text-white shadow-sm"
                >
                    <KeyRound class="size-6" />
                </div>
            </div>

            <Card>
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
                            class="w-full bg-[#4fb250] hover:bg-[#439c45]"
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
                            class="font-semibold text-[#4fb250] hover:text-[#439c45]"
                        >
                            Create an account
                        </Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    </section>
</template>
