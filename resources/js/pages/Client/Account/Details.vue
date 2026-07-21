<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import AccountCard from '@/modules/client/components/AccountCard.vue';
import AccountSettingsTabs from '@/modules/client/components/AccountSettingsTabs.vue';
import ClientAreaHero from '@/modules/client/components/ClientAreaHero.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';

interface Details {
    first_name: string;
    last_name: string;
    company_name: string | null;
    address_1: string | null;
    city: string | null;
    state: string | null;
    postcode: string | null;
    country: string | null;
    phone: string | null;
    email: string;
    vat_number: string | null;
    newsletter: boolean;
    account_id: number;
}

const props = defineProps<{
    account: { name: string; email: string; address: string[] };
    totalDue: string;
    currency: string;
    details: Details;
}>();

const form = useForm({
    first_name: props.details.first_name,
    last_name: props.details.last_name,
    company_name: props.details.company_name ?? '',
    address_1: props.details.address_1 ?? '',
    city: props.details.city ?? '',
    state: props.details.state ?? '',
    postcode: props.details.postcode ?? '',
    country: props.details.country ?? 'BD',
    phone: props.details.phone ?? '',
    email: props.details.email,
    vat_number: props.details.vat_number ?? '',
    newsletter: props.details.newsletter,
});

const submit = () =>
    form.patch('/client-area/account-details', { preserveScroll: true });

// ISO 3166-1 alpha-2 codes; names resolved by the browser.
const ISO_CODES =
    'AD AE AF AG AI AL AM AO AR AT AU AW AZ BA BB BD BE BF BG BH BI BJ BM BN BO BR BS BT BW BY BZ CA CD CF CG CH CI CK CL CM CN CO CR CU CV CY CZ DE DJ DK DM DO DZ EC EE EG ER ES ET FI FJ FK FM FO FR GA GB GD GE GF GH GI GL GM GN GP GQ GR GT GU GW GY HK HN HR HT HU ID IE IL IM IN IQ IR IS IT JE JM JO JP KE KG KH KI KM KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MG MH MK ML MM MN MO MQ MR MS MT MU MV MW MX MY MZ NA NC NE NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SI SK SL SM SN SO SR SS ST SV SY SZ TC TD TG TH TJ TL TM TN TO TR TT TV TW TZ UA UG US UY UZ VC VE VG VI VN VU WS YE ZA ZM ZW'.split(
        ' ',
    );

const regionNames = new Intl.DisplayNames(['en'], { type: 'region' });

const countries = computed(() =>
    ISO_CODES.map((code) => ({
        code,
        name: regionNames.of(code) ?? code,
    })).sort((a, b) => a.name.localeCompare(b.name)),
);

const inputClass =
    'h-11 w-full rounded-md border bg-transparent px-3 text-sm';
const labelClass = 'mb-1.5 block text-[13px] font-medium text-muted-foreground';
</script>

<template>
    <SeoHead
        title="My account details"
        description="Edit your billing and account details."
    />

    <ClientAreaHero title="My Account" overlap />

    <section class="mx-auto max-w-7xl px-4 pb-14 sm:px-6 lg:px-8">
        <div class="-mt-24 grid items-start gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
            <AccountCard
                :account="props.account"
                :total-due="props.totalDue"
                :currency="props.currency"
            />

            <div>
                <AccountSettingsTabs />

                <form
                    class="rounded-xl bg-card p-6 shadow-lg sm:p-8"
                    @submit.prevent="submit"
                >
                    <h2 class="font-bold tracking-tight">Billing Details</h2>

                    <div class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                        <div>
                            <label :class="labelClass" for="first-name">
                                First Name (required)
                            </label>
                            <input
                                id="first-name"
                                v-model="form.first_name"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div>
                            <label :class="labelClass" for="last-name">
                                Last Name (required)
                            </label>
                            <input
                                id="last-name"
                                v-model="form.last_name"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.last_name" />
                        </div>
                        <div>
                            <label :class="labelClass" for="company">
                                Company
                            </label>
                            <input
                                id="company"
                                v-model="form.company_name"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.company_name" />
                        </div>
                        <div>
                            <label :class="labelClass" for="address">
                                Address (required)
                            </label>
                            <input
                                id="address"
                                v-model="form.address_1"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.address_1" />
                        </div>
                        <div>
                            <label :class="labelClass" for="city">
                                City (required)
                            </label>
                            <input
                                id="city"
                                v-model="form.city"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.city" />
                        </div>
                        <div>
                            <label :class="labelClass" for="country">
                                Country (required)
                            </label>
                            <select
                                id="country"
                                v-model="form.country"
                                :class="inputClass"
                            >
                                <option
                                    v-for="country in countries"
                                    :key="country.code"
                                    :value="country.code"
                                >
                                    {{ country.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.country" />
                        </div>
                        <div>
                            <label :class="labelClass" for="state">
                                State
                            </label>
                            <input
                                id="state"
                                v-model="form.state"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.state" />
                        </div>
                        <div>
                            <label :class="labelClass" for="postcode">
                                ZIP Code (required)
                            </label>
                            <input
                                id="postcode"
                                v-model="form.postcode"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.postcode" />
                        </div>
                        <div>
                            <label :class="labelClass" for="phone">
                                Phone Number (required)
                            </label>
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>
                    </div>

                    <h2 class="mt-9 font-bold tracking-tight">
                        Account Details
                    </h2>

                    <div class="mt-5 grid gap-x-6 gap-y-5 sm:grid-cols-2">
                        <div>
                            <label :class="labelClass" for="email">
                                Email Address (required)
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                :class="inputClass"
                            />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div>
                            <label :class="labelClass" for="account-id">
                                Account ID
                            </label>
                            <input
                                id="account-id"
                                :value="props.details.account_id"
                                type="text"
                                disabled
                                class="h-11 w-full rounded-md border bg-muted/50 px-3 text-sm text-muted-foreground"
                            />
                        </div>
                        <div>
                            <label :class="labelClass" for="vat">
                                VAT Number
                            </label>
                            <input
                                id="vat"
                                v-model="form.vat_number"
                                type="text"
                                :class="inputClass"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                (EU customers only)
                            </p>
                            <InputError :message="form.errors.vat_number" />
                        </div>
                    </div>

                    <h2 class="mt-9 font-bold tracking-tight">
                        Weekly Newsletter
                    </h2>

                    <label
                        class="mt-4 flex items-start gap-2.5 text-sm"
                        for="newsletter"
                    >
                        <input
                            id="newsletter"
                            v-model="form.newsletter"
                            type="checkbox"
                            class="mt-0.5 size-4 rounded accent-[#4fb250]"
                        />
                        Subscribe to receive the most important news about our
                        promotions, products and services.
                    </label>
                    <InputError :message="form.errors.newsletter" />

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-8 w-full rounded-md bg-[#5cb85c] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#4cae4c] disabled:opacity-60"
                    >
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                </form>
            </div>
        </div>
    </section>
</template>
