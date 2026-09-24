// Service Worker para PWA
const CACHE_NAME = 'agrocontrol-v1.0.0';
const urlsToCache = [
  '/AgroControl/',
  '/AgroControl/index.php',
  '/AgroControl/modules/auth/login.php',
  '/AgroControl/modules/auth/register.php',
  '/AgroControl/assets/css/style.css',
  '/AgroControl/assets/js/script.js',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css'
];

// Instalação do Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache aberto');
        return cache.addAll(urlsToCache);
      })
  );
});

// Busca em cache primeiro (offline first)
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Cache hit - return response
        if (response) {
          return response;
        }
        return fetch(event.request).then(
          response => {
            // Check if we received a valid response
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            
            // Clone the response
            const responseToCache = response.clone();
            
            caches.open(CACHE_NAME)
              .then(cache => {
                cache.put(event.request, responseToCache);
              });
            
            return response;
          }
        );
      })
  );
});

// Atualização do cache
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Notificações push (opcional)
self.addEventListener('push', event => {
  const options = {
    body: event.data.text(),
    icon: '/AgroControl/assets/img/icon-192x192.png',
    badge: '/AgroControl/assets/img/badge-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Ver agora',
        icon: '/AgroControl/assets/img/checkmark.png'
      },
      {
        action: 'close',
        title: 'Fechar',
        icon: '/AgroControl/assets/img/xmark.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('AgroControl', options)
  );
});