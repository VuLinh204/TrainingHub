/**
 * Service Worker cho Nền tảng Đào tạo PWA
 * FIXED: Không cache POST requests
 */

const CACHE_NAME = 'training-platform-v1.0.1';
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    '/',
    '/Training/assets/css/style.css',
    '/Training/assets/css/animations.css',
    '/Training/assets/js/main.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Install');
    event.waitUntil(
        caches
            .open(CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Caching static assets');
                return Promise.all(
                    STATIC_ASSETS.map((url) => {
                        return fetch(url)
                            .then((response) => {
                                // Only cache successful GET responses
                                if (response.ok && response.status !== 206) {
                                    return cache.put(url, response);
                                }
                                return Promise.resolve();
                            })
                            .catch((error) => {
                                console.warn('[ServiceWorker] Failed to cache', url, error);
                                return Promise.resolve();
                            });
                    })
                );
            })
            .then(() => self.skipWaiting())
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
            .then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Skip cross-origin requests
    if (!request.url.startsWith(self.location.origin)) {
        return;
    }

    // CRITICAL FIX: Skip caching for POST/PUT/DELETE requests
    if (request.method !== 'GET') {
        console.log('[ServiceWorker] Skipping cache for', request.method, request.url);
        event.respondWith(fetch(request));
        return;
    }

    // Skip API calls (always fetch from network for GET APIs)
    if (request.url.includes('/')) {
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

    // Skip requests with Range header (usually for video)
    if (request.headers.has('range')) {
        event.respondWith(fetch(request));
        return;
    }

    // Network first for HTML pages
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful GET responses (skip partial content and media)
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

    // Cache first for static assets (GET only, already filtered above)
    event.respondWith(
        caches
            .match(request)
            .then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }

                return fetch(request).then((response) => {
                    // Cache valid GET responses (skip partial content and media)
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
    // This would sync any queued exam data when connection is restored
    console.log('[ServiceWorker] Syncing exam data...');
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
