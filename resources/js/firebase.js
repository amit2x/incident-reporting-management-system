import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, onMessage } from 'firebase/messaging';

// Your Firebase configuration - Replace with your actual config
// Get this from Firebase Console → Project Settings → General → Your apps → Web App
const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);

// Initialize Messaging
let messaging = null;
try {
    messaging = getMessaging(app);
    console.log('Firebase Messaging initialized');
} catch (error) {
    console.warn('Firebase Messaging not available:', error.message);
}

/**
 * Request notification permission and get FCM token
 */
export async function requestFCMToken() {
    if (!messaging) {
        console.warn('Messaging not initialized');
        return null;
    }

    try {
        // Request permission
        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            console.log('Notification permission denied');
            return null;
        }

        console.log('Notification permission granted');

        // Get token with VAPID key
        const currentToken = await getToken(messaging, {
            vapidKey: import.meta.env.VITE_FIREBASE_VAPID_KEY,
        });

        if (currentToken) {
            console.log('FCM Token:', currentToken);

            // Send token to backend
            await sendTokenToServer(currentToken);

            return currentToken;
        } else {
            console.log('No registration token available.');
            return null;
        }
    } catch (error) {
        console.error('Error getting FCM token:', error);
        return null;
    }
}

/**
 * Send FCM token to backend
 */
async function sendTokenToServer(token) {
    try {
        const response = await fetch('/api/v1/user/fcm-token', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                fcm_token: token,
                device_type: getDeviceType(),
            }),
        });

        const data = await response.json();
        console.log('Token saved to server:', data);
        return data;
    } catch (error) {
        console.error('Error saving token to server:', error);
    }
}

/**
 * Detect device type
 */
function getDeviceType() {
    const ua = navigator.userAgent;
    if (/android/i.test(ua)) return 'android';
    if (/iphone|ipad|ipod/i.test(ua)) return 'ios';
    return 'web';
}

/**
 * Listen for incoming messages when app is in foreground
 */
export function onMessageListener() {
    if (!messaging) return;

    onMessage(messaging, (payload) => {
        console.log('Message received in foreground:', payload);

        // Show notification
        const { title, body } = payload.notification || {};
        const { type, incident_id } = payload.data || {};

        if (Notification.permission === 'granted') {
            new Notification(title || 'IRMSystem', {
                body: body || 'You have a new notification',
                icon: '/images/logo.png',
                badge: '/images/logo.png',
                data: { type, incident_id },
            });
        }

        // Show toast if available
        if (typeof toastr !== 'undefined') {
            toastr.info(body || 'New notification', title || 'IRMSystem');
        }
    });
}
