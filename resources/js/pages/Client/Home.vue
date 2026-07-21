<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, ShieldCheck } from '@lucide/vue';
import { Blocks, Code2, LayoutTemplate } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import ProductCard from '@/modules/client/components/ProductCard.vue';
import SeoHead from '@/modules/client/components/SeoHead.vue';
defineProps(['featuredProducts', 'navigationPages']);
const page = usePage();
const site = computed(() => page.props.site);
const services = [
    {
        title: 'WHMCS Modules',
        text: 'Production-ready automation and integrations for your hosting business.',
        icon: Blocks,
        type: 'whmcs_module',
    },
    {
        title: 'Templates',
        text: 'Fast, conversion-focused templates engineered for WHMCS and modern brands.',
        icon: LayoutTemplate,
        type: 'template',
    },
    {
        title: 'Web Development',
        text: 'Custom Laravel, Vue, and business platforms built for long-term growth.',
        icon: Code2,
        type: 'web_development',
    },
];
</script>

<template>
    <SeoHead
        :title="site.seo.home?.title || site.seo.title"
        :description="site.seo.home?.description || site.seo.description"
        :image="site.seo.home?.image || site.seo.image"
        :seo="
            site.seo.home?.keywords
                ? { keywords: site.seo.home.keywords }
                : undefined
        "
    />

    <section class="relative overflow-hidden bg-slate-950 text-white">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_70%_20%,rgba(59,130,246,0.28),transparent_36%)]"
        />
        <div
            class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-24 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-32"
        >
            <div>
                <p
                    class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm text-slate-300"
                >
                    <ShieldCheck class="size-4 text-blue-400" /> Reliable
                    digital solutions for hosting businesses
                </p>
                <h1 class="text-5xl font-bold tracking-tight sm:text-6xl">
                    Technology built to move your business forward.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">
                    {{ site.tagline }}
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <Button
                        as-child
                        size="lg"
                        class="bg-white text-slate-950 hover:bg-slate-200"
                        ><Link href="/products"
                            >Explore products
                            <ArrowRight class="size-4" /></Link
                    ></Button>
                    <Button
                        as-child
                        size="lg"
                        variant="outline"
                        class="border-white/25 bg-transparent text-white hover:bg-white/10 hover:text-white"
                        ><a :href="`mailto:${site.supportEmail || ''}`"
                            >Discuss a project</a
                        ></Button
                    >
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div
                    class="rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur"
                >
                    <p class="text-4xl font-bold">WHMCS</p>
                    <p class="mt-2 text-sm text-slate-400">
                        Modules and automation
                    </p>
                </div>
                <div
                    class="mt-10 rounded-3xl border border-blue-400/20 bg-blue-500/10 p-8 backdrop-blur"
                >
                    <p class="text-4xl font-bold">Vue 3</p>
                    <p class="mt-2 text-sm text-slate-400">
                        Modern client experiences
                    </p>
                </div>
                <div
                    class="-mt-10 rounded-3xl border border-emerald-400/20 bg-emerald-500/10 p-8 backdrop-blur"
                >
                    <p class="text-4xl font-bold">Laravel</p>
                    <p class="mt-2 text-sm text-slate-400">
                        Secure business platforms
                    </p>
                </div>
                <div
                    class="rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur"
                >
                    <p class="text-4xl font-bold">Support</p>
                    <p class="mt-2 text-sm text-slate-400">
                        Professional and dependable
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p
                class="text-sm font-semibold tracking-widest text-blue-600 uppercase"
            >
                What we provide
            </p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                Focused products. Professional delivery.
            </h2>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <Link
                v-for="service in services"
                :key="service.type"
                :href="`/products?type=${service.type}`"
                class="group rounded-2xl border p-7 transition hover:border-slate-400 hover:shadow-lg"
            >
                <component :is="service.icon" class="size-8" />
                <h3 class="mt-5 text-xl font-semibold">{{ service.title }}</h3>
                <p class="mt-3 text-sm leading-6 text-muted-foreground">
                    {{ service.text }}
                </p>
                <span
                    class="mt-5 inline-flex items-center gap-1 text-sm font-semibold"
                    >View solutions
                    <ArrowRight
                        class="size-4 transition group-hover:translate-x-1"
                /></span>
            </Link>
        </div>
    </section>

    <section v-if="featuredProducts.length" class="bg-muted/50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between gap-6">
                <div>
                    <p
                        class="text-sm font-semibold tracking-widest text-blue-600 uppercase"
                    >
                        Featured
                    </p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight">
                        Popular ASRTech products
                    </h2>
                </div>
                <Link
                    href="/products"
                    class="hidden items-center gap-1 text-sm font-semibold sm:flex"
                    >View all <ArrowRight class="size-4"
                /></Link>
            </div>
            <div class="mt-10 grid gap-7 md:grid-cols-2 lg:grid-cols-3">
                <ProductCard
                    v-for="product in featuredProducts"
                    :key="product.slug"
                    :product="product"
                />
            </div>
        </div>
    </section>

    <section
        v-if="navigationPages.length"
        class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8"
    >
        <div
            class="flex flex-wrap justify-center gap-x-6 gap-y-3 text-sm text-muted-foreground"
        >
            <Link
                v-for="managedPage in navigationPages"
                :key="managedPage.slug"
                :href="`/pages/${managedPage.slug}`"
                class="hover:text-foreground"
                >{{ managedPage.title }}</Link
            >
        </div>
    </section>
</template>
