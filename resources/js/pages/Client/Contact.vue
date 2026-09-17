<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CheckCircle2,
    Globe,
    LoaderCircle,
    MapPin,
    MessageCircle,
} from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
import { services } from '@/modules/client/data/services';

const props = defineProps<{
    seo: Record<string, unknown>;
    selectedService: string;
    inquiryReceived: boolean;
}>();
const page = usePage();
const business = computed(() => page.props.business);
const successMessage = ref<HTMLElement | null>(null);
watch(
    successMessage,
    (message) => {
        if (message) {
            message.focus();
            message.scrollIntoView({ block: 'center' });
        }
    },
    { flush: 'post' },
);
const form = useForm({
    name: page.props.auth.user?.name ?? '',
    email: page.props.auth.user?.email ?? '',
    service: props.selectedService,
    message: '',
    submission: '',
});
function showSubmissionError(message: string) {
    form.setError('submission', message);
    void nextTick(() => document.getElementById('inquiry-errors')?.focus());
}
function submit() {
    form.clearErrors();
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => form.reset('message'),
        onError: () => {
            void nextTick(() =>
                document.getElementById('inquiry-errors')?.focus(),
            );
        },
        onHttpException: (response) => {
            showSubmissionError(
                response.status === 429
                    ? 'Too many inquiries have been submitted. Please wait a few minutes before trying again.'
                    : response.status === 419
                      ? 'Your session has expired. Refresh this page before submitting again.'
                      : 'Your inquiry could not be sent. Please try again or contact us through Facebook.',
            );

            return false;
        },
        onNetworkError: () => {
            showSubmissionError(
                'We could not connect. Check your connection and try again.',
            );

            return false;
        },
    });
}
</script>

<template>
    <SeoHead :seo="seo" />
    <section class="page-intro border-b border-border/70">
        <div class="site-container py-14 md:py-20">
            <p class="section-kicker">Contact / Let’s make a start</p>
            <h1 class="display-title mt-5">What can we help you build?</h1>
            <p class="body-copy mt-6 max-w-2xl">
                A new project, a platform improvement, or a technical problem.
                Tell us what you have in mind and where you need help.
            </p>
        </div>
    </section>
    <section
        class="site-container grid gap-12 py-14 lg:grid-cols-[0.75fr_1.25fr] lg:gap-20 lg:py-20"
    >
        <aside class="order-2 lg:order-1">
            <p class="section-kicker">Connect with ASR Tech</p>
            <div class="mt-8 flex gap-4">
                <MapPin class="mt-1 size-5 shrink-0 text-primary" />
                <div>
                    <h2 class="font-semibold">Our address</h2>
                    <address
                        class="mt-3 max-w-sm text-base leading-7 text-muted-foreground not-italic"
                    >
                        {{ business.address }}
                    </address>
                </div>
            </div>
            <div class="mt-8 flex gap-4">
                <Globe class="mt-1 size-5 shrink-0 text-primary" />
                <div>
                    <h2 class="font-semibold">On the web</h2>
                    <a
                        :href="business.website"
                        class="mt-3 inline-flex items-center gap-2 text-sm text-primary underline underline-offset-4"
                        >www.asrtech.bd <ArrowUpRight class="size-4"
                    /></a>
                </div>
            </div>
            <div class="mt-8 flex gap-4">
                <MessageCircle class="mt-1 size-5 shrink-0 text-primary" />
                <div>
                    <h2 class="font-semibold">Facebook</h2>
                    <a
                        :href="business.facebook"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex items-center gap-2 text-sm text-primary underline underline-offset-4"
                        >ASR Tech on Facebook
                        <ArrowUpRight class="size-4" /><span class="sr-only"
                            >(opens in a new tab)</span
                        ></a
                    >
                </div>
            </div>
            <div
                v-if="page.props.site.supportEmail || page.props.site.phone"
                class="mt-8 space-y-3 border-t border-border pt-7"
            >
                <p v-if="page.props.site.supportEmail">
                    <a
                        :href="`mailto:${page.props.site.supportEmail}`"
                        class="break-all text-primary underline underline-offset-4"
                        >{{ page.props.site.supportEmail }}</a
                    >
                </p>
                <p v-if="page.props.site.phone">
                    <a
                        :href="`tel:${page.props.site.phone}`"
                        class="text-primary underline underline-offset-4"
                        >{{ page.props.site.phone }}</a
                    >
                </p>
            </div>
            <div class="mt-10 rounded-2xl border border-teal-100 bg-accent p-6">
                <h2 class="font-semibold text-primary">Already a customer?</h2>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    For an existing product or account issue, keep your
                    conversation together in the Client Area.
                </p>
                <Link
                    href="/client-area/tickets"
                    class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-primary"
                    >Go to support tickets <ArrowUpRight class="size-4"
                /></Link>
            </div>
        </aside>
        <div class="surface-card order-1 p-6 sm:p-9 lg:order-2">
            <div
                v-if="inquiryReceived && !form.processing && !form.hasErrors"
                ref="successMessage"
                tabindex="-1"
                role="status"
                class="mb-8 flex gap-4 rounded-2xl border border-teal-200 bg-accent p-5 text-primary"
            >
                <CheckCircle2 class="mt-0.5 size-6 shrink-0" />
                <div>
                    <h2 class="font-semibold">
                        Your inquiry has been received.
                    </h2>
                    <p class="mt-2 text-sm leading-6">
                        Thank you for sharing the details. Your message is saved
                        for ASR Tech to review, along with the email address you
                        provided.
                    </p>
                </div>
            </div>
            <h2 class="text-2xl font-semibold tracking-tight">
                Tell us about your project
            </h2>
            <p class="mt-3 text-sm leading-6 text-muted-foreground">
                All fields are required. Please leave out passwords, payment
                details, and other sensitive information.
            </p>
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div
                    v-if="form.hasErrors"
                    id="inquiry-errors"
                    tabindex="-1"
                    role="alert"
                    class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm leading-6 text-red-800"
                >
                    <p class="font-semibold">
                        {{
                            form.errors.submission ||
                            'Please check the highlighted fields and try again.'
                        }}
                    </p>
                </div>
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label
                            for="inquiry-name"
                            class="block text-sm font-semibold text-foreground"
                            >Your name</label
                        ><input
                            id="inquiry-name"
                            v-model="form.name"
                            required
                            autocomplete="name"
                            maxlength="120"
                            :aria-invalid="Boolean(form.errors.name)"
                            :aria-describedby="
                                form.errors.name ? 'name-error' : undefined
                            "
                            class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-base outline-none focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20"
                        />
                        <p
                            v-if="form.errors.name"
                            id="name-error"
                            class="mt-2 text-sm text-red-700"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            for="inquiry-email"
                            class="block text-sm font-semibold text-foreground"
                            >Email address</label
                        ><input
                            id="inquiry-email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            maxlength="255"
                            :aria-invalid="Boolean(form.errors.email)"
                            :aria-describedby="
                                form.errors.email ? 'email-error' : undefined
                            "
                            class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-base outline-none focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20"
                        />
                        <p
                            v-if="form.errors.email"
                            id="email-error"
                            class="mt-2 text-sm text-red-700"
                        >
                            {{ form.errors.email }}
                        </p>
                    </div>
                </div>
                <div>
                    <label
                        for="inquiry-service"
                        class="block text-sm font-semibold text-foreground"
                        >What do you need help with?</label
                    ><select
                        id="inquiry-service"
                        v-model="form.service"
                        required
                        :aria-invalid="Boolean(form.errors.service)"
                        :aria-describedby="
                            form.errors.service ? 'service-error' : undefined
                        "
                        class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-base outline-none focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20"
                    >
                        <option disabled value="">Choose a service</option>
                        <option
                            v-for="service in services"
                            :key="service.id"
                            :value="service.slug"
                        >
                            {{ service.title }}
                        </option>
                        <option value="not-sure">I’d like help choosing</option>
                    </select>
                    <p
                        v-if="form.errors.service"
                        id="service-error"
                        class="mt-2 text-sm text-red-700"
                    >
                        {{ form.errors.service }}
                    </p>
                </div>
                <div>
                    <label
                        for="inquiry-message"
                        class="block text-sm font-semibold text-foreground"
                        >Project or issue details</label
                    ><textarea
                        id="inquiry-message"
                        v-model="form.message"
                        rows="6"
                        required
                        minlength="20"
                        maxlength="10000"
                        :aria-invalid="Boolean(form.errors.message)"
                        :aria-describedby="
                            form.errors.message
                                ? 'message-error message-hint'
                                : 'message-hint'
                        "
                        placeholder="What are you working on? What would you like to build, change, or fix?"
                        class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-base leading-7 outline-none focus:border-teal-700 focus:ring-2 focus:ring-teal-700/20"
                    ></textarea>
                    <p
                        id="message-hint"
                        class="mt-2 text-xs leading-5 text-muted-foreground"
                    >
                        Include your current platform, relevant versions, and
                        any requirements. At least 20 characters.
                    </p>
                    <p
                        v-if="form.errors.message"
                        id="message-error"
                        class="mt-2 text-sm text-red-700"
                    >
                        {{ form.errors.message }}
                    </p>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="button-primary w-full sm:w-auto"
                    :aria-busy="form.processing"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="size-4 animate-spin motion-reduce:animate-none"
                    /><span>{{
                        form.processing ? 'Sending inquiry…' : 'Send inquiry'
                    }}</span
                    ><ArrowUpRight v-if="!form.processing" class="size-4" />
                </button>
            </form>
        </div>
    </section>
</template>
