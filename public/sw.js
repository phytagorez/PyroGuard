const CACHE_NAME = 'pyroguard-shell-v1';
const TILES_CACHE = 'pyroguard-map-tiles-v1';
const DATA_CACHE = 'pyroguard-data-v1';

const CORE_ASSETS = [
    '/',
    '/evakuasi',
    '/metode',
    '/lapor',
    '/notifikasi',
    '/demo',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon.svg',
];

// Install Event: Pra-cache shell aset penting
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(CORE_ASSETS).catch((err) => {
                console.warn('Gagal memuat beberapa shell asset PyroGuard ke cache:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate Event: Bersihkan cache versi lama
self.addEventListener('activate', (event) => {
    const validCaches = [CACHE_NAME, TILES_CACHE, DATA_CACHE];
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (!validCaches.includes(key)) {
                        return caches.delete(key);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch Event: Penanganan pintar luring (Offline-First untuk peta, Network-First untuk data zona)
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // 1. Tangani OpenStreetMap Map Tiles (Cache First / Stale-While-Revalidate)
    if (url.hostname.includes('tile.openstreetmap.org')) {
        event.respondWith(
            caches.open(TILES_CACHE).then((cache) => {
                return cache.match(request).then((cachedResponse) => {
                    const fetchPromise = fetch(request).then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            cache.put(request, networkResponse.clone());
                        }
                        return networkResponse;
                    }).catch(() => cachedResponse);

                    return cachedResponse || fetchPromise;
                });
            })
        );
        return;
    }

    // 2. Tangani API Zona & Prediksi Risiko (Network First -> Fallback Cache)
    if (url.pathname.startsWith('/api/v1/')) {
        event.respondWith(
            fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const copy = networkResponse.clone();
                    caches.open(DATA_CACHE).then((cache) => cache.put(request, copy));
                }
                return networkResponse;
            }).catch(() => {
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return new Response(JSON.stringify({
                        status: 'luring',
                        pesan: 'Anda sedang luring. Menampilkan status tersimpan terakhir.',
                        offline: true
                    }), {
                        headers: { 'Content-Type': 'application/json' }
                    });
                });
            })
        );
        return;
    }

    // 3. Tangani Navigasi Halaman HTML
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => {
                return caches.match(request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return caches.match('/');
                });
            })
        );
        return;
    }

    // 4. Default: Stale While Revalidate untuk aset statis internal
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            const fetchPromise = fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 && request.method === 'GET') {
                    const copy = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
                }
                return networkResponse;
            }).catch(() => cachedResponse);

            return cachedResponse || fetchPromise;
        })
    );
});
