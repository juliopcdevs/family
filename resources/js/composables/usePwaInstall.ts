import { ref, computed } from 'vue';

const DISMISS_KEY = 'pwa-install-dismissed';
const deferredPrompt = ref<Event | null>(null);
const dismissed = ref(localStorage.getItem(DISMISS_KEY) === '1');

function isStandalone(): boolean {
  if (window.matchMedia('(display-mode: standalone)').matches) return true;
  if ('standalone' in navigator && (navigator as unknown as { standalone: boolean }).standalone) return true;
  return false;
}

const isAndroid = /Android/i.test(navigator.userAgent);
const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent);

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt.value = e;
});

window.addEventListener('appinstalled', () => {
  deferredPrompt.value = null;
  dismissed.value = true;
  localStorage.setItem(DISMISS_KEY, '1');
});

export function usePwaInstall() {
  const standalone = isStandalone();

  const showAndroidBanner = computed(() =>
    isAndroid && !!deferredPrompt.value && !dismissed.value && !standalone
  );

  const showIosBanner = computed(() =>
    isIos && !standalone && !dismissed.value
  );

  const install = async () => {
    const prompt = deferredPrompt.value as unknown as { prompt: () => void; userChoice: Promise<{ outcome: string }> };
    if (!prompt) return;
    prompt.prompt();
    const { outcome } = await prompt.userChoice;
    if (outcome === 'accepted') {
      deferredPrompt.value = null;
    }
    dismissed.value = true;
    localStorage.setItem(DISMISS_KEY, '1');
  };

  const dismiss = () => {
    dismissed.value = true;
    localStorage.setItem(DISMISS_KEY, '1');
  };

  return { showAndroidBanner, showIosBanner, install, dismiss };
}
