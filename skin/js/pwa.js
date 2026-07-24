(() => {
  const installButton = document.querySelector('#pwaInstall');
  const installRegion = installButton?.closest('[data-pwa-install-region]');
  let installPrompt = null;

  const setInstallVisible = visible => {
    if (installButton) installButton.hidden = !visible;
    if (installRegion) installRegion.hidden = !visible;
  };

  const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  if (installButton && isStandalone) setInstallVisible(false);

  window.addEventListener('beforeinstallprompt', event => {
    if (!installButton || isStandalone) return;
    event.preventDefault();
    installPrompt = event;
    setInstallVisible(true);
  });

  installButton?.addEventListener('click', async () => {
    if (!installPrompt) return;
    installButton.disabled = true;
    try {
      await installPrompt.prompt();
      await installPrompt.userChoice;
    } finally {
      installPrompt = null;
      setInstallVisible(false);
      installButton.disabled = false;
    }
  });

  window.addEventListener('appinstalled', () => {
    installPrompt = null;
    setInstallVisible(false);
  });

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
    });
  }
})();
