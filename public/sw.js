/* ASRTech client portal — offline shell.
   Strategy: network-first for documents/API, cache-first for static assets. */
const VERSION = 'asrtech-v1';
const STATIC_CACHE = `${VERSION}-static`;

const STATIC_EXTENSIONS = /\.(?:js|css|png|jpg|jpeg|svg|webp|woff2?|ttf|ico)$/;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(STATIC_CACHE)
            .then((cache) => cache.addAll(['/icons/icon-192.png', '/icons/icon-512.png']))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => !key.startsWith(VERSION))
                        .map((key) => caches.delete(key)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only GET over http(s); never touch gateway callbacks or API mutations.
    if (request.method !== 'GET' || !request.url.startsWith('http')) {
        return;
    }

    const url = new URL(request.url);

    if (STATIC_EXTENSIONS.test(url.pathname)) {
        // Cache-first for hashed static assets.
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ??
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const clone = response.clone();
                            caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
                        }
                        return response;
                    }),
            ),
        );
        return;
    }

    if (request.mode === 'navigate') {
        // Network-first for pages; fall back to a simple offline notice.
        event.respondWith(
            fetch(request).catch(
                () =>
                    new Response(
                        '<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head>' +
                            '<body style="font-family:system-ui;background:#041f15;color:#fff;display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center">' +
                            '<div><h1 style="font-size:1.4rem">You are offline</h1><p style="opacity:.75">Reconnect to continue using the ASRTech portal.</p></div></body></html>',
                        { headers: { 'Content-Type': 'text/html' } },
                    ),
            ),
        );
    }
});
