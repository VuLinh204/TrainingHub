/**
 * Service Worker cho Nền tảng Đào tạo PWA
 * Đặt file này tại thư mục gốc: sw.js
 */

const CACHE_NAME = 'training-platform-v1.0.0';
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    '/',
    '/Training/assets/css/style.css',
    '/Training/assets/css/animations.css',
    '/Training/assets/js/main.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
];

// Install event - cache static assets một cách an toàn
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Install');
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Caching static assets');
                // Thêm từng asset một để xử lý lỗi partial response
                return Promise.all(
                    STATIC_ASSETS.map((url) => {
                        return fetch(url)
                            .then((response) => {
                                // Bỏ qua nếu là partial response (206)
                                if (response.ok && response.status !== 206) {
                                    return cache.put(url, response);
                                }
                                return Promise.resolve(); // Bỏ qua asset này
                            })
                            .catch((error) => {
                                console.warn('[ServiceWorker] Failed to cache', url, error);
                                return Promise.resolve();
                            });
                    })
                );
            })
            .then(() => {
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activate');
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[ServiceWorker] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Skip cross-origin requests
    if (!request.url.startsWith(self.location.origin)) {
        return;
    }

    // Skip API calls (always fetch from network)
    if (request.url.includes('/api/')) {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response(
                    JSON.stringify({
                        error: 'Network error',
                        offline: true,
                    }),
                    {
                        headers: { 'Content-Type': 'application/json' },
                    }
                );
            })
        );
        return;
    }

    // Không cache các request có Range header (thường cho video)
    if (request.headers.has('range')) {
        event.respondWith(fetch(request));
        return;
    }

    // Network first for HTML pages
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful full responses (skip partial content và media)
                    if (response.ok && response.status !== 206) {
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.startsWith('video/')) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                    }
                    return response;
                })
                .catch(() => {
                    // Fallback to cache, then offline page
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || caches.match(OFFLINE_URL);
                    });
                })
        );
        return;
    }

    // Cache first for static assets
    event.respondWith(
        caches
            .match(request)
            .then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }

                return fetch(request).then((response) => {
                    // Cache valid full responses (skip partial content và media)
                    if (response.ok && response.status !== 206) {
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.startsWith('video/')) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                    }
                    return response;
                });
            })
            .catch(() => {
                // Return offline page for navigation requests
                if (request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }
            })
    );
});

// Background sync for offline exam submissions
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-exam') {
        event.waitUntil(syncExamData());
    }
});

async function syncExamData() {
    const cache = await caches.open(CACHE_NAME);
    const requests = await cache.keys();

    for (const request of requests) {
        if (request.url.includes('/api/exam/submit')) {
            try {
                const response = await fetch(request);
                if (response.ok) {
                    await cache.delete(request);
                }
            } catch (error) {
                console.error('Sync failed:', error);
            }
        }
    }
}

// Push notification support
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Training Platform';
    const options = {
        body: data.body || 'You have a new notification',
        icon: '/assets/img/icon-192.png',
        badge: '/assets/img/badge-72.png',
        vibrate: [200, 100, 200],
        tag: data.tag || 'notification',
        data: data.url ? { url: data.url } : {},
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(clients.openWindow(event.notification.data.url));
    }
});

// Message handler for cache updates
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(CACHE_NAME).then((cache) => {
                return Promise.all(
                    event.data.urls.map((url) => {
                        return fetch(url)
                            .then((response) => {
                                if (response.ok && response.status !== 206) {
                                    return cache.put(url, response);
                                }
                                return Promise.resolve();
                            })
                            .catch(() => Promise.resolve());
                    })
                );
            })
        );
    }
});
