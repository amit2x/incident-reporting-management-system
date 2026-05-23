const CACHE_NAME = 'irms-cache-v1';
const ASSETS_TO_CACHE = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/images/logo.png',
    '/offline.html'
];

// Install Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(ASSETS_TO_CACHE))
            .then(() => self.skipWaiting())
    );
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );
});

// Fetch Strategy: Network First, Cache Fallback
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests and API calls
    if (event.request.method !== 'GET' || event.request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                // Cache successful responses
                if (response.status === 200) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                return caches.match(event.request)
                    .then((cachedResponse) => {
                        return cachedResponse || caches.match('/offline.html');
                    });
            })
    );
});

// Push Notification Handling
self.addEventListener('push', (event) => {
    const data = event.data?.json() ?? {};
    
    const options = {
        body: data.body || 'New notification from IRMS',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/badge-72x72.png',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/',
            incident_id: data.incident_id,
            type: data.type,
        },
        actions: [
            {
                action: 'view',
                title: 'View Details',
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
            },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(
            data.title || 'IRMS Notification',
            options
        )
    );
});

// Notification Click Handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'view' || !event.action) {
        const url = event.notification.data.url || '/';
        
        event.waitUntil(
            clients.matchAll({ type: 'window' }).then((windowClients) => {
                // Check if there is already a window/tab open with the target URL
                for (const client of windowClients) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                // If not, open a new window/tab
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
        );
    }
});