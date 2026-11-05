/**
 * Service Worker TAMUIN PWA (SRS Bab V & SDD Bab III.B)
 * Menyimpan cache aset statis dan halaman Kiosk agar tetap dapat dibuka saat koneksi terputus.
 */
const CACHE_NAME = 'tamuin-pwa-v1';
const PRECACHE_URLS = [
    '/kiosk',
    '/manifest.json',
    '/js/kiosk-app.js',
    'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
    'https://cdn.tailwindcss.com'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return Promise.allSettled(
                PRECACHE_URLS.map((url) => cache.add(url).catch(() => null))
            );
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            )
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Jangan intercept request POST atau request API sinkronisasi
    if (req.method !== 'GET' || req.url.includes('/kiosk/api/')) {
        return;
    }

    event.respondWith(
        fetch(req)
            .then((networkResp) => {
                if (networkResp && networkResp.status === 200) {
                    const respClone = networkResp.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(req, respClone));
                }
                return networkResp;
            })
            .catch(() => {
                return caches.match(req).then((cachedResp) => {
                    if (cachedResp) {
                        return cachedResp;
                    }
                    if (req.mode === 'navigate') {
                        return caches.match('/kiosk');
                    }
                });
            })
    );
});
