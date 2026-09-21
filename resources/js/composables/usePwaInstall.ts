import { ref, computed } from 'vue';

const DISMISS_KEY = 'pwa-install-dismissed';
const deferredPrompt = ref<Event | null>(null);
const dismissed = ref(localStorage.getItem(DISMISS_KEY) === '1');

const isAndroid = /Android/i.test(navigator.userAgent);
const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent);

function checkStandalone(): boolean {
  if (window.matchMedia('(display-mode: standalone)').matches) return true;
  if ('standalone' in navigator && (navigator as unknown as { standalone: boolean }).standalone) return true;
  return false;
}

const standalone = ref(checkStandalone());

window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
  standalone.value = e.matches;
});

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt.value = e;
});

window.addEventListener('appinstalled', () => {
  deferredPrompt.value = null;
  standalone.value = true;
  dismissed.value = true;
  localStorage.setItem(DISMISS_KEY, '1');
});

export function usePwaInstall() {
  const showAndroidBanner = computed(() =>
    isAndroid && !!deferredPrompt.value && !dismissed.value && !standalone.value
  );

  const showIosBanner = computed(() =>
    isIos && !standalone.value && !dismissed.value
  );

  // El navegador tiene un prompt de instalacion nativo disponible (Android/Chrome/Edge de escritorio)
  const canPromptInstall = computed(() => !!deferredPrompt.value);

  // Mostrar el boton de instalar mientras no este ya instalada y sea instalable
  // (Android/escritorio con prompt nativo, o iOS via instrucciones manuales).
  const canInstall = computed(() =>
    !standalone.value && (canPromptInstall.value || isIos)
  );

  const install = async (): Promise<'accepted' | 'dismissed' | 'unavailable'> => {
    const prompt = deferredPrompt.value as unknown as { prompt: () => void; userChoice: Promise<{ outcome: string }> } | null;
    if (!prompt) return 'unavailable';
    prompt.prompt();
    const { outcome } = await prompt.userChoice;
    if (outcome === 'accepted') {
      deferredPrompt.value = null;
    }
    dismissed.value = true;
    localStorage.setItem(DISMISS_KEY, '1');
    return outcome === 'accepted' ? 'accepted' : 'dismissed';
  };

  const dismiss = () => {
    dismissed.value = true;
    localStorage.setItem(DISMISS_KEY, '1');
  };

  return {
    showAndroidBanner,
    showIosBanner,
    canInstall,
    canPromptInstall,
    isIos,
    isAndroid,
    isStandalone: standalone,
    install,
    dismiss,
  };
}
