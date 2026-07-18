const CACHE_PREFIX = 'dragon-maiden-wallpaper-';
const CACHE_NAME = `${CACHE_PREFIX}v5`;
const APP_SHELL = [
  '/',
  '/index.php',
  '/search.php',
  '/collection.php',
  '/ranking.php',
  '/ai-skill.php',
  '/android-app.php',
  '/api-docs.php',
  '/offline.php',
  '/skin/css/styles.css',
  '/skin/css/mobile-preview.css',
  '/skin/css/wallpaper-details.css',
  '/skin/css/vendor/all.min.css',
  '/skin/css/vendor/bootstrap.min.css',
  '/skin/js/vendor/jquery-3.7.1.min.js',
  '/skin/js/site.js',
  '/skin/js/visitor-counter.js',
  '/skin/js/home.js',
  '/skin/js/catalog.js',
  '/skin/js/collection.js',
  '/skin/js/ranking.js',
  '/skin/js/skill.js',
  '/skin/js/pwa.js',
  '/manifest.webmanifest',
  '/skin/img/assets/logo.svg',
  '/skin/img/assets/logo-dragon-tail.png'
];

self.addEventListener('install', event => {
  event.waitUntil((async () => {
    try {
      const cache = await caches.open(CACHE_NAME);
      const results = await Promise.allSettled(APP_SHELL.map(async url => {
        const request = new Request(url, { cache: 'reload' });
        const response = await fetch(request);
        if (!response.ok) throw new Error(`${url} returned ${response.status}`);
        await cache.put(request, response);
      }));
      const skipped = results.reduce((count, result) => count + (result.status === 'rejected' ? 1 : 0), 0);
      if (skipped) console.warn(`[Service Worker] Skipped ${skipped} unavailable precache resource(s).`);
    } catch (error) {
      console.warn('[Service Worker] Precache was unavailable; network mode remains active.', error);
    }
    await self.skipWaiting();
  })());
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(keys.filter(key => key.startsWith(CACHE_PREFIX) && key !== CACHE_NAME).map(key => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

async function networkFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  try {
    const response = await fetch(request);
    if (response.ok) cache.put(request, response.clone());
    return response;
  } catch {
    return (await cache.match(request))
      || (await cache.match('/offline.php'))
      || new Response('目前無法連線，請恢復網路後再試一次。', {
        status: 503,
        headers: { 'Content-Type': 'text/plain; charset=utf-8' }
      });
  }
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);
  const fresh = fetch(request).then(response => {
    if (response.ok) cache.put(request, response.clone());
    return response;
  }).catch(() => cached || Response.error());
  return cached || fresh;
}

async function cacheFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) cache.put(request, response.clone());
    return response;
  } catch {
    return Response.error();
  }
}

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (url.pathname === '/api/catalog.php') {
    event.respondWith(staleWhileRevalidate(request));
    return;
  }
  if (url.pathname.startsWith('/api/')) return;
  if (request.mode === 'navigate') {
    event.respondWith(networkFirst(request));
    return;
  }
  if (request.destination === 'image') {
    event.respondWith(staleWhileRevalidate(request));
    return;
  }
  if (['style', 'script', 'manifest', 'font'].includes(request.destination)) {
    event.respondWith(cacheFirst(request));
  }
});
