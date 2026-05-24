importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

// Your Firebase configuration
firebase.initializeApp({
    apiKey: "AIzaSyDD-HXWZhDy1KbGyYD_EZrlbwWh-1gMPeY",
    authDomain: "irms-22afa.firebaseapp.com",
    projectId: "irms-22afa",
    storageBucket: "irms-22afa.firebasestorage.app",
    messagingSenderId: "798628568501",
    appId: "1:798628568501:web:d8731cacaa99908c0fcdfc",
    measurementId: "G-ZPJ7NJ7T6B"
});


const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message:', payload);

    const notification = payload.notification || {};
    const data = payload.data || {};

    const notificationOptions = {
        body: notification.body || 'You have a new notification',
        icon: '/images/logo.png',
        badge: '/images/logo.png',
        data: {
            url: data.click_action || '/notifications',
            type: data.type || 'general',
            incident_id: data.incident_id || '',
        },
        actions: [
            { action: 'view', title: 'View Details' },
            { action: 'close', title: 'Dismiss' },
        ],
        requireInteraction: true,
        vibrate: [200, 100, 200],
        tag: data.incident_id || 'notification',
    };

    return self.registration.showNotification(
        notification.title || 'IRMSystem',
        notificationOptions
    );
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    let url = '/notifications';

    if (event.notification.data && event.notification.data.url) {
        url = event.notification.data.url;
    } else if (event.action === 'view') {
        url = event.notification.data?.url || '/notifications';
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Check if there is already a window/tab open with the target URL
                for (const client of windowClients) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // If not, open a new window/tab
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Handle push events (for when app is not open)
self.addEventListener('push', (event) => {
    if (event.data) {
        const payload = event.data.json();

        const options = {
            body: payload.notification?.body || 'New notification',
            icon: '/images/logo.png',
            badge: '/images/logo.png',
            data: {
                url: payload.data?.click_action || '/notifications',
            },
        };

        event.waitUntil(
            self.registration.showNotification(
                payload.notification?.title || 'IRMSystem',
                options
            )
        );
    }
});
