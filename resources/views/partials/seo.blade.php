@php
    // Inertia replaces these keyed server-rendered tags after navigation.
    $business = config('asrtech.business');
    $initialSeo = data_get($page, 'props.seo')
        ?: data_get($page, 'props.product.seo')
        ?: data_get($page, 'props.landing.seo')
        ?: data_get($page, 'props.managedPage.seo')
        ?: [];
    $privatePage = request()->is('client-area*', 'dashboard', 'settings*', 'login', 'register', 'forgot-password', 'reset-password*', 'verify-email', 'two-factor-challenge', 'checkout*', 'cart*', 'admin*');
    $defaultDescription = 'Web and mobile development, WHMCS solutions, WordPress plugins, and technical maintenance from ASR Tech in Bangladesh.';
    $pageName = data_get($page, 'props.product.name') ?: data_get($page, 'props.managedPage.title') ?: data_get($page, 'props.landing.name');
    $initialTitle = $privatePage ? 'Client Area | '.$business['name'] : (data_get($initialSeo, 'meta_title') ?: ($pageName ? $pageName.' | '.$business['name'] : $business['name'].' — Development & technical care'));
    $initialDescription = $privatePage ? 'Manage your ASR Tech account.' : (data_get($initialSeo, 'meta_description') ?: data_get($page, 'props.product.short_description') ?: $defaultDescription);
    $initialCanonical = data_get($initialSeo, 'canonical_url') ?: $business['website'].(request()->path() === '/' ? '/' : '/'.request()->path());
    $initialImage = data_get($initialSeo, 'open_graph_image') ?: data_get($page, 'props.product.featured_image') ?: config('asrtech.seo.image');
    if ($initialImage && ! preg_match('~^https?://~i', $initialImage)) {
        try {
            $initialImage = (string) \GuzzleHttp\Psr7\UriResolver::resolve(
                new \GuzzleHttp\Psr7\Uri(rtrim($business['website'], '/').'/'),
                new \GuzzleHttp\Psr7\Uri($initialImage),
            );
        } catch (\InvalidArgumentException) {
            $initialImage = null;
        }
    }
    $initialRobots = $privatePage ? 'noindex,nofollow' : (data_get($initialSeo, 'robots') ?: 'index,follow');
    $businessSchema = ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $business['name'], 'url' => $business['website'], 'founder' => ['@type' => 'Person', 'name' => $business['owner']], 'address' => $business['address'], 'sameAs' => [$business['facebook']]];
@endphp
<title data-inertia="">{{ $initialTitle }}</title>
<meta data-inertia="description" name="description" content="{{ strip_tags($initialDescription) }}">
<meta data-inertia="robots" name="robots" content="{{ $initialRobots }}">
<link data-inertia="canonical" rel="canonical" href="{{ $initialCanonical }}">
<meta data-inertia="og:type" property="og:type" content="website">
<meta data-inertia="og:title" property="og:title" content="{{ data_get($initialSeo, 'open_graph_title') ?: $initialTitle }}">
<meta data-inertia="og:description" property="og:description" content="{{ strip_tags(data_get($initialSeo, 'open_graph_description') ?: $initialDescription) }}">
<meta data-inertia="og:url" property="og:url" content="{{ $initialCanonical }}">
<meta data-inertia="og:site_name" property="og:site_name" content="{{ $business['name'] }}">
<meta data-inertia="twitter:card" name="twitter:card" content="{{ $initialImage ? 'summary_large_image' : 'summary' }}">
<meta data-inertia="twitter:title" name="twitter:title" content="{{ $initialTitle }}">
<meta data-inertia="twitter:description" name="twitter:description" content="{{ strip_tags($initialDescription) }}">
@if ($initialImage)
    <meta data-inertia="og:image" property="og:image" content="{{ $initialImage }}">
    <meta data-inertia="twitter:image" name="twitter:image" content="{{ $initialImage }}">
@endif
@unless ($privatePage)
    <script data-inertia="business-schema" type="application/ld+json">{!! json_encode($businessSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
    @if (data_get($initialSeo, 'schema_json'))
        <script data-inertia="schema-json" type="application/ld+json">{!! json_encode(data_get($initialSeo, 'schema_json'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
@endunless
