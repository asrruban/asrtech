<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps(['title', 'description', 'image', 'seo', 'type']);
const page = usePage();
const site = computed(() => page.props.site);
const title = computed(
    () => props.seo?.meta_title || props.title || site.value.seo.title,
);
const description = computed(
    () =>
        props.seo?.meta_description ||
        props.description ||
        site.value.seo.description,
);
const image = computed(() => {
    const source =
        props.seo?.open_graph_image || props.image || site.value.seo.image;

    if (!source) {
        return null;
    }

    if (/^https?:\/\//i.test(source)) {
        return source;
    }

    try {
        return new URL(source, `${page.props.business.website}/`).href;
    } catch {
        return null;
    }
});
const canonical = computed(
    () =>
        props.seo?.canonical_url ||
        `${page.props.business.website}${page.url.split('?')[0]}`,
);
const privatePage = computed(() =>
    /^\/(client-area|dashboard|settings|login|register|forgot-password|reset-password|verify-email|two-factor-challenge|checkout|cart|admin)(\/|\?|$)/.test(
        page.url,
    ),
);
const robots = computed(() =>
    privatePage.value
        ? 'noindex,nofollow'
        : props.seo?.robots || 'index,follow',
);
const schemaJson = computed(() =>
    props.seo?.schema_json ? JSON.stringify(props.seo.schema_json) : null,
);
const businessSchema = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: page.props.business.name,
        url: page.props.business.website,
        founder: { '@type': 'Person', name: page.props.business.owner },
        address: page.props.business.address,
        sameAs: [page.props.business.facebook],
    }),
);
</script>

<template>
    <Head :title="title">
        <meta
            head-key="description"
            name="description"
            :content="description"
        />
        <meta
            v-if="seo?.keywords"
            head-key="keywords"
            name="keywords"
            :content="seo.keywords"
        />
        <meta head-key="robots" name="robots" :content="robots" />
        <link head-key="canonical" rel="canonical" :href="canonical" />
        <meta head-key="og:url" property="og:url" :content="canonical" />
        <meta
            head-key="og:site_name"
            property="og:site_name"
            :content="page.props.business.name"
        />
        <meta head-key="twitter:title" name="twitter:title" :content="title" />
        <meta
            head-key="twitter:description"
            name="twitter:description"
            :content="description"
        />
        <meta
            v-if="image"
            head-key="twitter:image"
            name="twitter:image"
            :content="image"
        />
        <meta
            head-key="og:type"
            property="og:type"
            :content="type || 'website'"
        />
        <meta
            head-key="og:title"
            property="og:title"
            :content="seo?.open_graph_title || title"
        />
        <meta
            head-key="og:description"
            property="og:description"
            :content="seo?.open_graph_description || description"
        />
        <meta
            v-if="image"
            head-key="og:image"
            property="og:image"
            :content="image"
        />
        <meta
            head-key="twitter:card"
            name="twitter:card"
            :content="
                seo?.twitter_card || (image ? 'summary_large_image' : 'summary')
            "
        />
        <component
            :is="'script'"
            v-if="schemaJson"
            head-key="schema-json"
            type="application/ld+json"
            >{{ schemaJson }}</component
        >
        <component
            :is="'script'"
            head-key="business-schema"
            type="application/ld+json"
            >{{ businessSchema }}</component
        >
    </Head>
</template>
