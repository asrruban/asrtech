<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import MaintenanceScope from '@/modules/client/components/MaintenanceScope.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import type { MaintenancePlan } from '@/types/maintenance';
const props = defineProps<{ plan: MaintenancePlan }>();
const page = usePage();
const signedIn = computed(() => Boolean(page.props.auth?.user));
const form = useForm({
    website: '',
    requirements: '',
    acknowledge_scope: false,
    version: props.plan.version,
});
const submit = () =>
    form.post(`/maintenance/${props.plan.slug}/request`, {
        preserveScroll: true,
    });
</script>
<template>
    <SeoHead :title="plan.name" :description="plan.summary" />
    <section class="site-container py-10 md:py-16">
        <Link
            href="/maintenance"
            class="text-sm font-semibold text-[var(--client-accent-dark)]"
            >← All maintenance plans</Link
        >
        <h1 class="display-title mt-6 max-w-4xl">
            Care for the technology<br />you rely on.
        </h1>
        <div class="mt-12 grid items-start gap-10 lg:grid-cols-[1.2fr_1fr]">
            <MaintenanceScope :plan="plan" />
            <div class="surface-card p-6 md:p-8">
                <h2 class="text-2xl font-semibold">Request this plan</h2>
                <p class="body-copy mt-3">
                    We review your requirements before arranging billing or
                    starting work. A request does not create a subscription.
                </p>
                <p
                    v-if="!plan.available"
                    class="mt-6 rounded-xl bg-amber-50 p-4 text-amber-900"
                >
                    This plan is currently unavailable.
                    <Link href="/contact" class="underline"
                        >Contact us about maintenance.</Link
                    >
                </p>
                <template v-else-if="!signedIn"
                    ><p class="body-copy mt-6">
                        Sign in to keep your scope, updates and billing
                        together.
                    </p>
                    <Link href="/login" class="button-primary mt-5"
                        >Sign in to request</Link
                    ><Link href="/register" class="mt-4 block text-sm underline"
                        >Create an account</Link
                    ></template
                >
                <form v-else class="mt-6 space-y-5" @submit.prevent="submit">
                    <div
                        v-if="form.errors.version"
                        role="alert"
                        class="rounded-lg bg-red-50 p-4 text-sm text-red-800"
                    >
                        {{ form.errors.version }}
                    </div>
                    <div>
                        <label
                            for="maintenance-website"
                            class="text-sm font-semibold"
                            >Website URL
                            <span class="font-normal">(optional)</span></label
                        ><input
                            id="maintenance-website"
                            v-model="form.website"
                            type="url"
                            maxlength="2048"
                            placeholder="https://"
                            class="mt-2 w-full rounded-lg border bg-background px-3 py-3"
                            :aria-invalid="Boolean(form.errors.website)"
                            aria-describedby="website-error"
                        />
                        <p id="website-error" class="mt-1 text-sm text-red-600">
                            {{ form.errors.website }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="maintenance-requirements"
                            class="text-sm font-semibold"
                            >What needs maintaining?</label
                        ><textarea
                            id="maintenance-requirements"
                            v-model="form.requirements"
                            rows="6"
                            required
                            minlength="20"
                            maxlength="10000"
                            class="mt-2 w-full rounded-lg border bg-background px-3 py-3"
                            :aria-invalid="Boolean(form.errors.requirements)"
                            aria-describedby="requirements-help requirements-error"
                        />
                        <p
                            id="requirements-help"
                            class="body-copy mt-2 text-xs"
                        >
                            Describe your setup and priorities. Do not include
                            passwords, access keys or other credentials.
                        </p>
                        <p
                            id="requirements-error"
                            role="alert"
                            class="mt-1 text-sm text-red-600"
                        >
                            {{ form.errors.requirements }}
                        </p>
                    </div>
                    <label class="flex items-start gap-3 text-sm leading-6"
                        ><input
                            v-model="form.acknowledge_scope"
                            type="checkbox"
                            required
                            class="mt-1 size-4 shrink-0"
                        />I have reviewed this scope and understand that ASR
                        Tech will confirm the arrangements before work
                        starts.</label
                    >
                    <p
                        v-if="form.errors.acknowledge_scope"
                        role="alert"
                        class="text-sm text-red-600"
                    >
                        {{ form.errors.acknowledge_scope }}
                    </p>
                    <button
                        class="button-primary w-full"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? 'Sending request…'
                                : 'Request maintenance'
                        }}</button
                    ><Link
                        href="/client-area/maintenance"
                        class="block text-center text-sm underline"
                        >View existing requests</Link
                    >
                </form>
            </div>
        </div>
    </section>
</template>
