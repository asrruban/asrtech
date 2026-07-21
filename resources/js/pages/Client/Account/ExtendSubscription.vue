<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, Package, ShieldCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface SubscriptionDetail {
    id: number;
    billing_cycle: string;
    currency: string;
    amount: string;
    product: { name: string; featured_image: string | null };
}

interface PaymentGateway {
    key: string;
    name: string;
    description: string;
}

const props = defineProps<{
    subscription: SubscriptionDetail;
    paymentGateways: PaymentGateway[];
}>();

const label = (value: string) =>
    value
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

const money = (currency: string, amount: string) =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(Number(amount));

const form = useForm({
    gateway: props.paymentGateways[0]?.key ?? '',
});

const submit = () => {
    form.post(`/client-area/subscriptions/${props.subscription.id}/extend`);
};
</script>

<template>
    <SeoHead
        :title="`Extend ${props.subscription.product.name} subscription`"
        description="Choose a payment method to extend your subscription."
    />

    <ClientAreaHero
        title="Extend Subscription"
        subtitle="Upgrade your free trial to a regular paid billing cycle."
    />

    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <Link
            :href="`/client-area/subscriptions/${props.subscription.id}`"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" /> Back to subscription details
        </Link>

        <div class="mt-6 grid gap-8 md:grid-cols-[1.2fr_1fr]">
            <!-- Payment Methods Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-foreground">
                        Select Payment Method
                    </h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Choose how you would like to pay for your recurring
                        subscription.
                    </p>

                    <div class="mt-6 space-y-3">
                        <label
                            v-for="gateway in props.paymentGateways"
                            :key="gateway.key"
                            class="relative flex cursor-pointer items-start gap-4 rounded-xl border p-4 transition hover:bg-muted/30"
                            :class="
                                form.gateway === gateway.key
                                    ? 'border-blue-500 bg-blue-50/10'
                                    : 'border-border'
                            "
                        >
                            <input
                                v-model="form.gateway"
                                type="radio"
                                name="gateway"
                                :value="gateway.key"
                                class="mt-1 size-4 border-border text-blue-600 focus:ring-blue-500"
                            />
                            <div class="flex-1">
                                <span
                                    class="block text-sm font-bold text-foreground"
                                    >{{ gateway.name }}</span
                                >
                                <span
                                    class="mt-1 block text-xs text-muted-foreground"
                                    >{{ gateway.description }}</span
                                >
                            </div>
                        </label>
                    </div>
                    <InputError :message="form.errors.gateway" class="mt-2" />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-blue-600 font-bold text-white shadow-lg shadow-blue-600/10 transition hover:bg-blue-700 disabled:opacity-50"
                >
                    <CreditCard class="mr-2 size-5" /> Pay & Activate
                    Subscription
                </button>
            </form>

            <!-- Order Summary Card -->
            <div class="space-y-6">
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-foreground">
                        Subscription Details
                    </h3>

                    <div class="mt-6 flex gap-4">
                        <img
                            v-if="props.subscription.product.featured_image"
                            :src="props.subscription.product.featured_image"
                            alt=""
                            class="size-12 rounded-lg object-cover"
                        />
                        <div
                            v-else
                            class="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Package class="size-5" />
                        </div>
                        <div>
                            <span class="block font-bold text-foreground">{{
                                props.subscription.product.name
                            }}</span>
                            <span
                                class="mt-0.5 block text-xs text-muted-foreground"
                                >Recurring License</span
                            >
                        </div>
                    </div>

                    <div class="my-6 space-y-4 border-t border-border/60 pt-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Billing Cycle</span
                            >
                            <span
                                class="font-semibold text-foreground capitalize"
                                >{{
                                    label(props.subscription.billing_cycle)
                                }}</span
                            >
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-muted-foreground"
                                >Regular Price</span
                            >
                            <span class="font-semibold text-foreground">{{
                                money(
                                    props.subscription.currency,
                                    props.subscription.amount,
                                )
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t border-border/60 pt-4"
                        >
                            <span class="font-bold text-foreground"
                                >Total Due Now</span
                            >
                            <span
                                class="text-lg font-extrabold text-blue-600"
                                >{{
                                    money(
                                        props.subscription.currency,
                                        props.subscription.amount,
                                    )
                                }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="flex items-center gap-2 rounded-xl bg-muted/20 p-3 text-xs text-muted-foreground"
                    >
                        <ShieldCheck class="size-4 shrink-0 text-emerald-600" />
                        <span
                            >Secure payments encrypted with industry
                            standards.</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
