/**
 * Service Worker cho Nền tảng Đào tạo PWA
 * Đặt tệp này tại thư mục gốc: sw.js
 */

const CACHE_NAME = 'training-platform-v1.0.0';
const OFFLINE_URL = '/offline.html';
const MAX_CACHE_ITEMS = 100; // Giới hạn số lượng mục trong bộ nhớ đệm động
const CACHE_EXPIRE_DAYS = 30; // Thời gian hết hạn của bộ nhớ đệm động (ngày)

const STATIC_ASSETS = [
    '/',
    '/assets/css/style.css',
    '/assets/css/animations.css',
    '/assets/js/main.js',
    '/offline.html',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
    '/assets/img/icon-192.png',
    '/assets/img/icon-512.png'
];

// Sự kiện cài đặt - lưu trữ tài nguyên tĩnh
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Cài đặt');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[ServiceWorker] Lưu trữ tài nguyên tĩnh');
            return cache.addAll(STATIC_ASSETS);
        }).then(() => {
            return self.skipWaiting();
        }).catch((error) => {
            console.error('[ServiceWorker] Cài đặt thất bại:', error);
        })
    );
});

// Sự kiện kích hoạt - xóa bộ nhớ đệm cũ
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Kích hoạt');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[ServiceWorker] Xóa bộ nhớ đệm cũ:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            // Dọn dẹp bộ nhớ đệm động để tránh phình to
            return caches.open(CACHE_NAME).then((cache) => {
                return cache.keys().then((keys) => {
                    if (keys.length > MAX_CACHE_ITEMS) {
                        return Promise.all(
                            keys.slice(0, keys.length - MAX_CACHE_ITEMS).map((key) => cache.delete(key))
                        );
                    }
                });
            });
        }).then(() => {
            return self.clients.claim();
        }).catch((error) => {
            console.error('[ServiceWorker] Kích hoạt thất bại:', error);
        })
    );
});

// Sự kiện fetch - xử lý yêu cầu từ bộ nhớ đệm hoặc mạng
self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Bỏ qua yêu cầu cross-origin
    if (!request.url.startsWith(self.location.origin)) {
        return;
    }

    // Xử lý yêu cầu API (luôn lấy từ mạng)
    if (request.url.includes('/api/')) {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response(JSON.stringify({
                    error: 'Lỗi mạng',
                    offline: true
                }), {
                    status: 503,
                    headers: { 'Content-Type': 'application/json' }
                });
            })
        );
        return;
    }

    // Ưu tiên mạng cho yêu cầu điều hướng (trang HTML)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).then((response) => {
                if (response.ok) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            }).catch(() => {
                return caches.match(request).then((cachedResponse) => {
                    return cachedResponse || caches.match(OFFLINE_URL);
                });
            })
        );
        return;
    }

    // Ưu tiên bộ nhớ đệm cho các tài nguyên khác (CSS, JS, hình ảnh)
    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                // Kiểm tra thời gian hết hạn của bộ nhớ đệm
                return cachedResponse.headers.get('date') &&
                    (Date.now() - new Date(cachedResponse.headers.get('date')).getTime()) / (1000 * 60 * 60 * 24) < CACHE_EXPIRE_DAYS
                    ? cachedResponse
                    : fetchAndCache(request);
            }
            return fetchAndCache(request);
        })
    );
});

// Hàm hỗ trợ để lấy và lưu trữ phản hồi
function fetchAndCache(request) {
    return fetch(request).then((response) => {
        if (response.ok) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
                cache.put(request, responseClone);
            });
        }
        return response;
    }).catch(() => {
        return caches.match(OFFLINE_URL);
    });
}

// Giữ chỗ cho đồng bộ nền (tùy chọn, cho các yêu cầu API ngoại tuyến)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-progress') {
        event.waitUntil(
            // Triển khai logic đồng bộ tại đây (ví dụ: thử lại các yêu cầu API thất bại)
            console.log('[ServiceWorker] Kích hoạt đồng bộ nền')
            // Ví dụ: syncProgressData();
        );
    }
});

// Giữ chỗ cho thông báo đẩy (tùy chọn)
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : { title: 'Nền tảng Đào tạo', message: 'Có nội dung mới!' };
    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.message,
            icon: '/assets/icon.png',
            badge: '/assets/icon.png'
        })
    );
});