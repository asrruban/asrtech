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
const image = computed(
    () => props.seo?.open_graph_image || props.image || site.value.seo.image,
);
const schemaJson = computed(() =>
    props.seo?.schema_json ? JSON.stringify(props.seo.schema_json) : null,
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
        <meta
            head-key="robots"
            name="robots"
            :content="seo?.robots || 'index,follow'"
        />
        <link
            v-if="seo?.canonical_url"
            head-key="canonical"
            rel="canonical"
            :href="seo.canonical_url"
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
            :content="seo?.twitter_card || 'summary_large_image'"
        />
        <component
            :is="'script'"
            v-if="schemaJson"
            head-key="schema-json"
            type="application/ld+json"
            >{{ schemaJson }}</component
        >
    </Head>
</template>
