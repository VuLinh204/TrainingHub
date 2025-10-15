/**
 * Service Worker for Training Platform PWA
 * Place this file at the root: sw.js
 */

const CACHE_NAME = 'training-platform-v1.0.0';
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    '/',
    '/assets/css/style.css',
    '/assets/css/animations.css',
    '/assets/js/main.js',
    '/offline.html',
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
                return cache.addAll(STATIC_ASSETS);
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

    // Network first for HTML pages
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful responses
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
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
                    // Cache valid responses
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
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
                return cache.addAll(event.data.urls);
            })
        );
    }
});
