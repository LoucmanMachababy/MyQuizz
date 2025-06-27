// Service Worker pour les notifications push
const CACHE_NAME = 'myquiz-v1';
const urlsToCache = [
    '/',
    '/quiz',
    '/quiz/leaderboard',
    '/account/profile'
];

// Installation du service worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache ouvert');
                return cache.addAll(urlsToCache);
            })
    );
});

// Interception des requêtes
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Retourner la réponse du cache si elle existe
                if (response) {
                    return response;
                }
                return fetch(event.request);
            }
        )
    );
});

// Gestion des notifications push
self.addEventListener('push', event => {
    const options = {
        body: event.data ? event.data.text() : 'Nouvelle notification de MyQuiz !',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Voir le quiz',
                icon: '/favicon.ico'
            },
            {
                action: 'close',
                title: 'Fermer',
                icon: '/favicon.ico'
            }
        ]
    };

    event.waitUntil(
        self.registration.showNotification('MyQuiz', options)
    );
});

// Gestion des clics sur les notifications
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/quiz')
        );
    }
});

// Mise à jour du service worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
}); 