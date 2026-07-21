<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    BookOpen,
    Check,
    ChevronRight,
    Clipboard,
    Code2,
    Globe2,
    KeyRound,
    Server,
    ShieldCheck,
    Terminal,
    TriangleAlert,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type ApiField = {
    name: string;
    required: boolean;
    description: string;
};

type ApiStatus = {
    value: string;
    meaning: string;
};

type GuideKey = 'php' | 'whmcs' | 'website';

const props = defineProps<{
    licenseApi: {
        endpoint: string;
        method: string;
        rate_limit: string;
        fields: ApiField[];
        statuses: ApiStatus[];
    };
}>();

const activeGuide = ref<GuideKey>('php');
const copiedSnippet = ref<string | null>(null);

const guides = [
    {
        key: 'php' as const,
        label: 'PHP script',
        description: 'Protect a standalone PHP application.',
        icon: Terminal,
    },
    {
        key: 'whmcs' as const,
        label: 'WHMCS module',
        description: 'Validate an addon module license.',
        icon: Server,
    },
    {
        key: 'website' as const,
        label: 'Website',
        description: 'Add server-side Laravel middleware.',
        icon: Globe2,
    },
];

const replaceEndpoint = (snippet: string) =>
    snippet.replaceAll('__LICENSE_ENDPOINT__', props.licenseApi.endpoint);

const phpSnippet = computed(() =>
    replaceEndpoint(
        [
            '<?php',
            '',
            'function verifyAsrTechLicense(string $licenseKey): array',
            '{',
            '    $payload = json_encode([',
            "        'license_key' => $licenseKey,",
            "        'domain' => $_SERVER['SERVER_NAME'] ?? null,",
            "        'ip' => $_SERVER['SERVER_ADDR'] ?? null,",
            "        'path' => __DIR__,",
            '    ], JSON_THROW_ON_ERROR);',
            '',
            "    $handle = curl_init('__LICENSE_ENDPOINT__');",
            '    curl_setopt_array($handle, [',
            '        CURLOPT_POST => true,',
            '        CURLOPT_POSTFIELDS => $payload,',
            '        CURLOPT_RETURNTRANSFER => true,',
            '        CURLOPT_CONNECTTIMEOUT => 5,',
            '        CURLOPT_TIMEOUT => 10,',
            '        CURLOPT_SSL_VERIFYPEER => true,',
            "        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],",
            '    ]);',
            '',
            '    $body = curl_exec($handle);',
            '    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);',
            '    $error = curl_error($handle);',
            '    curl_close($handle);',
            '',
            '    if ($body === false || $httpCode !== 200) {',
            "        return ['status' => 'unavailable', 'message' => $error ?: 'License server unavailable'];",
            '    }',
            '',
            '    return json_decode($body, true, 512, JSON_THROW_ON_ERROR);',
            '}',
            '',
            "$result = verifyAsrTechLicense((string) getenv('ASRTECH_LICENSE_KEY'));",
            '',
            "if (($result['status'] ?? null) !== 'active') {",
            '    http_response_code(403);',
            "    exit('License validation failed. Please contact support.');",
            '}',
            '',
            '// Continue booting the licensed application.',
        ].join('\n'),
    ),
);

const whmcsSnippet = computed(() =>
    replaceEndpoint(
        [
            '<?php',
            '',
            "if (!defined('WHMCS')) {",
            "    exit('This file cannot be accessed directly.');",
            '}',
            '',
            'function asrtechmodule_config(): array',
            '{',
            '    return [',
            "        'name' => 'ASRTech Licensed Module',",
            "        'description' => 'Example licensed WHMCS addon.',",
            "        'version' => '1.0.0',",
            "        'author' => 'Your Company',",
            "        'fields' => [",
            "            'license_key' => [",
            "                'FriendlyName' => 'License Key',",
            "                'Type' => 'text',",
            "                'Size' => '48',",
            "                'Description' => 'Enter the key from your client account.',",
            '            ],',
            '        ],',
            '    ];',
            '}',
            '',
            'function asrtechmodule_verify(string $licenseKey): array',
            '{',
            '    $payload = json_encode([',
            "        'license_key' => $licenseKey,",
            "        'domain' => $_SERVER['HTTP_HOST'] ?? null,",
            "        'ip' => $_SERVER['SERVER_ADDR'] ?? null,",
            "        'path' => __DIR__,",
            '    ], JSON_THROW_ON_ERROR);',
            '',
            "    $handle = curl_init('__LICENSE_ENDPOINT__');",
            '    curl_setopt_array($handle, [',
            '        CURLOPT_POST => true,',
            '        CURLOPT_POSTFIELDS => $payload,',
            '        CURLOPT_RETURNTRANSFER => true,',
            '        CURLOPT_CONNECTTIMEOUT => 5,',
            '        CURLOPT_TIMEOUT => 10,',
            '        CURLOPT_SSL_VERIFYPEER => true,',
            "        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],",
            '    ]);',
            '',
            '    $body = curl_exec($handle);',
            '    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);',
            '    curl_close($handle);',
            '',
            '    if (!is_string($body) || $httpCode !== 200) {',
            "        return ['status' => 'unavailable'];",
            '    }',
            '',
            '    return json_decode($body, true) ?: [];',
            '}',
            '',
            'function asrtechmodule_output(array $vars): void',
            '{',
            "    $result = asrtechmodule_verify((string) ($vars['license_key'] ?? ''));",
            '',
            "    if (($result['status'] ?? null) !== 'active') {",
            '        echo \'<div class="alert alert-danger">License validation failed. Contact the module vendor.</div>\';',
            '        return;',
            '    }',
            '',
            "    echo '<h2>ASRTech Licensed Module</h2>';",
            "    echo '<p>Your module is licensed and ready.</p>';",
            '}',
        ].join('\n'),
    ),
);

const websiteSnippet = computed(() =>
    replaceEndpoint(
        [
            '<?php',
            '',
            'namespace App\\Http\\Middleware;',
            '',
            'use Closure;',
            'use Illuminate\\Http\\Request;',
            'use Illuminate\\Support\\Facades\\Cache;',
            'use Illuminate\\Support\\Facades\\Http;',
            'use Symfony\\Component\\HttpFoundation\\Response;',
            '',
            'class VerifyProductLicense',
            '{',
            '    public function handle(Request $request, Closure $next): Response',
            '    {',
            "        $licenseKey = (string) config('services.asrtech_license.key');",
            "        $cacheKey = 'asrtech-license:'.hash('sha256', $licenseKey);",
            '',
            '        $result = Cache::remember($cacheKey, now()->addHours(6), function () use ($licenseKey) {',
            '            return Http::acceptJson()',
            '                ->connectTimeout(5)',
            '                ->timeout(10)',
            "                ->post('__LICENSE_ENDPOINT__', [",
            "                    'license_key' => $licenseKey,",
            "                    'domain' => request()->getHost(),",
            "                    'ip' => request()->server('SERVER_ADDR'),",
            "                    'path' => base_path(),",
            '                ])',
            '                ->throw()',
            '                ->json();',
            '        });',
            '',
            "        abort_unless(($result['status'] ?? null) === 'active', 403, 'Product license is not active.');",
            '',
            '        return $next($request);',
            '    }',
            '}',
            '',
            '// config/services.php',
            "'asrtech_license' => [",
            "    'key' => env('ASRTECH_LICENSE_KEY'),",
            '],',
            '',
            '// .env (never commit this value)',
            'ASRTECH_LICENSE_KEY=ASR-XXXXX-XXXXX-XXXXX',
        ].join('\n'),
    ),
);

const requestSnippet = computed(() =>
    replaceEndpoint(
        [
            "curl --request POST '__LICENSE_ENDPOINT__' \\",
            "  --header 'Accept: application/json' \\",
            "  --header 'Content-Type: application/json' \\",
            "  --data '{",
            '    \"license_key\": \"ASR-XXXXX-XXXXX-XXXXX\",',
            '    \"domain\": \"example.com\",',
            '    \"ip\": \"203.0.113.10\",',
            '    \"path\": \"/var/www/example\"',
            "  }'",
        ].join('\n'),
    ),
);

const activeSnippet = computed(
    () =>
        ({
            php: phpSnippet.value,
            whmcs: whmcsSnippet.value,
            website: websiteSnippet.value,
        })[activeGuide.value],
);

const activeGuideDetails = computed(
    () => guides.find((guide) => guide.key === activeGuide.value) ?? guides[0],
);

const copy = async (value: string, key: string) => {
    try {
        await navigator.clipboard.writeText(value);
        copiedSnippet.value = key;
        window.setTimeout(() => {
            if (copiedSnippet.value === key) {
                copiedSnippet.value = null;
            }
        }, 1800);
    } catch {
        copiedSnippet.value = null;
    }
};

const statusClass = (status: string) =>
    ({
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        invalid: 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        suspended:
            'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        expired:
            'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300',
        terminated:
            'bg-slate-200 text-slate-700 dark:bg-white/10 dark:text-slate-300',
    })[status] ?? 'bg-muted text-muted-foreground';
</script>

<template>
    <Head title="License integration docs" />

    <div class="w-full min-w-0 flex-1 space-y-6 p-4 sm:p-6 lg:p-8">
        <section
            class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-8 text-white shadow-2xl shadow-slate-200 sm:px-8 lg:px-10 dark:shadow-none"
        >
            <div
                class="pointer-events-none absolute -top-28 -right-16 size-80 rounded-full bg-cyan-400/20 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-32 left-1/3 size-72 rounded-full bg-blue-600/20 blur-3xl"
            />
            <div class="relative max-w-3xl">
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-cyan-200"
                >
                    <BookOpen class="size-3.5" />
                    Developer documentation
                </div>
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    License verification integration
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base"
                >
                    Connect PHP products, WHMCS modules, and server-rendered
                    websites to your ASRTech license server.
                </p>
                <div class="mt-6 flex flex-wrap gap-2 text-xs font-medium">
                    <span
                        class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5"
                    >
                        {{ licenseApi.method }} JSON
                    </span>
                    <span
                        class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5"
                    >
                        {{ licenseApi.rate_limit }}
                    </span>
                    <span
                        class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1.5 text-emerald-200"
                    >
                        Server-to-server
                    </span>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300"
                >
                    <KeyRound class="size-5" />
                </div>
                <h2 class="mt-4 font-semibold">First check activates</h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    The first successful request records the supplied domain, IP
                    address, and installation path.
                </p>
            </div>
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                    <ShieldCheck class="size-5" />
                </div>
                <h2 class="mt-4 font-semibold">Installation is matched</h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    Later checks must match the recorded restrictions. An
                    administrator or customer can reissue an installation.
                </p>
            </div>
            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-slate-900"
            >
                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300"
                >
                    <Code2 class="size-5" />
                </div>
                <h2 class="mt-4 font-semibold">Every check is audited</h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    Domain, IP, directory, result, and check time appear in the
                    license activity log.
                </p>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900"
        >
            <div
                class="border-b border-slate-100 px-5 py-5 sm:px-6 dark:border-white/10"
            >
                <p
                    class="text-xs font-semibold tracking-widest text-cyan-600 uppercase"
                >
                    Integration guides
                </p>
                <h2 class="mt-1 text-xl font-semibold">
                    Choose your application type
                </h2>
            </div>

            <div class="grid lg:grid-cols-[280px_minmax(0,1fr)]">
                <nav
                    class="border-b border-slate-100 p-3 lg:border-r lg:border-b-0 dark:border-white/10"
                    aria-label="License integration guides"
                >
                    <button
                        v-for="guide in guides"
                        :key="guide.key"
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition"
                        :class="
                            activeGuide === guide.key
                                ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950'
                                : 'hover:bg-slate-50 dark:hover:bg-white/5'
                        "
                        :aria-current="
                            activeGuide === guide.key ? 'page' : undefined
                        "
                        @click="activeGuide = guide.key"
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                            :class="
                                activeGuide === guide.key
                                    ? 'bg-white/10 dark:bg-slate-950/10'
                                    : 'bg-slate-100 dark:bg-white/10'
                            "
                        >
                            <component :is="guide.icon" class="size-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold">
                                {{ guide.label }}
                            </span>
                            <span
                                class="mt-0.5 block text-xs"
                                :class="
                                    activeGuide === guide.key
                                        ? 'text-slate-300 dark:text-slate-600'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{ guide.description }}
                            </span>
                        </span>
                        <ChevronRight class="size-4 shrink-0 opacity-60" />
                    </button>
                </nav>

                <div class="min-w-0 p-4 sm:p-6">
                    <div
                        class="mb-4 flex flex-wrap items-start justify-between gap-3"
                    >
                        <div>
                            <h3 class="text-lg font-semibold">
                                {{ activeGuideDetails.label }} example
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Replace the environment license key, then run
                                this check during your server-side bootstrap.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center gap-2 rounded-lg border px-3 text-sm font-medium transition hover:bg-muted"
                            @click="copy(activeSnippet, 'guide-' + activeGuide)"
                        >
                            <Check
                                v-if="copiedSnippet === 'guide-' + activeGuide"
                                class="size-4 text-emerald-600"
                            />
                            <Clipboard v-else class="size-4" />
                            {{
                                copiedSnippet === 'guide-' + activeGuide
                                    ? 'Copied'
                                    : 'Copy code'
                            }}
                        </button>
                    </div>

                    <div
                        class="max-w-full overflow-hidden rounded-xl border border-slate-800 bg-slate-950"
                    >
                        <div
                            class="flex items-center justify-between border-b border-white/10 px-4 py-2.5"
                        >
                            <span class="text-xs font-medium text-slate-400">
                                {{ activeGuide }} integration
                            </span>
                            <span class="flex gap-1.5">
                                <span
                                    class="size-2 rounded-full bg-red-400/80"
                                />
                                <span
                                    class="size-2 rounded-full bg-amber-400/80"
                                />
                                <span
                                    class="size-2 rounded-full bg-emerald-400/80"
                                />
                            </span>
                        </div>
                        <pre
                            class="max-h-[620px] overflow-auto p-4 text-[13px] leading-6 text-slate-200"
                        ><code>{{ activeSnippet }}</code></pre>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]"
        >
            <div
                class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-white/10 dark:bg-slate-900"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-6 dark:border-white/10"
                >
                    <div>
                        <h2 class="font-semibold">Test the endpoint</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Make a direct request from a terminal.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-9 items-center gap-2 rounded-lg border px-3 text-sm font-medium transition hover:bg-muted"
                        @click="copy(requestSnippet, 'request')"
                    >
                        <Check
                            v-if="copiedSnippet === 'request'"
                            class="size-4 text-emerald-600"
                        />
                        <Clipboard v-else class="size-4" />
                        {{ copiedSnippet === 'request' ? 'Copied' : 'Copy' }}
                    </button>
                </div>
                <div class="p-4 sm:p-6">
                    <pre
                        class="overflow-auto rounded-xl bg-slate-950 p-4 text-[13px] leading-6 text-slate-200"
                    ><code>{{ requestSnippet }}</code></pre>
                    <div
                        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
                    >
                        <p
                            class="text-xs font-semibold text-emerald-700 uppercase dark:text-emerald-300"
                        >
                            Active response
                        </p>
                        <pre
                            class="mt-2 overflow-auto text-xs leading-5 text-emerald-950 dark:text-emerald-100"
                        ><code>{
  "status": "active",
  "message": "Valid",
  "product": "Your Product",
  "registered_at": "2026-07-21",
  "expires_at": "2027-07-21"
}</code></pre>
                    </div>
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 dark:border-white/10 dark:bg-slate-900"
            >
                <h2 class="font-semibold">API reference</h2>
                <div class="mt-4 rounded-xl bg-slate-50 p-4 dark:bg-white/5">
                    <p
                        class="text-xs font-semibold text-muted-foreground uppercase"
                    >
                        Endpoint
                    </p>
                    <code class="mt-2 block text-sm font-semibold break-all">
                        {{ licenseApi.endpoint }}
                    </code>
                </div>

                <div class="mt-5">
                    <h3 class="text-sm font-semibold">Request fields</h3>
                    <div class="mt-2 divide-y dark:divide-white/10">
                        <div
                            v-for="field in licenseApi.fields"
                            :key="field.name"
                            class="py-3 first:pt-1"
                        >
                            <div class="flex items-center gap-2">
                                <code class="text-xs font-semibold">
                                    {{ field.name }}
                                </code>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                    :class="
                                        field.required
                                            ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300'
                                            : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-400'
                                    "
                                >
                                    {{
                                        field.required ? 'required' : 'optional'
                                    }}
                                </span>
                            </div>
                            <p
                                class="mt-1 text-xs leading-5 text-muted-foreground"
                            >
                                {{ field.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <h3 class="text-sm font-semibold">Response statuses</h3>
                    <div class="mt-3 space-y-3">
                        <div
                            v-for="status in licenseApi.statuses"
                            :key="status.value"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="mt-0.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                :class="statusClass(status.value)"
                            >
                                {{ status.value }}
                            </span>
                            <p class="text-xs leading-5 text-muted-foreground">
                                {{ status.meaning }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/10"
        >
            <TriangleAlert
                class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-300"
            />
            <div>
                <h2 class="font-semibold text-amber-950 dark:text-amber-100">
                    Production security checklist
                </h2>
                <ul
                    class="mt-2 grid gap-x-8 gap-y-2 text-sm leading-6 text-amber-900 sm:grid-cols-2 dark:text-amber-100/80"
                >
                    <li>Use HTTPS and keep TLS verification enabled.</li>
                    <li>
                        Store the license key in server environment variables.
                    </li>
                    <li>
                        Never place a license key in JavaScript or browser
                        storage.
                    </li>
                    <li>
                        Allow the product to run only when status is active.
                    </li>
                    <li>
                        Use a short cache to avoid checking on every page
                        request.
                    </li>
                    <li>Do not write full license keys to application logs.</li>
                </ul>
            </div>
        </section>
    </div>
</template>
