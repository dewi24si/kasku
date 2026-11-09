import {
    ArrowDownRight,
    ArrowUpRight,
    Banknote,
    Bot,
    BookOpen,
    Briefcase,
    Camera,
    Car,
    ChartBarDecreasing,
    ChartPie,
    CalendarClock,
    Check,
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    Clock,
    Compass,
    createIcons,
    Download,
    FileText,
    Gamepad2,
    Gift,
    HeartPulse,
    History,
    Inbox,
    LayoutDashboard,
    Lightbulb,
    Loader2,
    LockKeyhole,
    LogOut,
    Mail,
    Mic,
    MessageCircle,
    Package,
    Pencil,
    PiggyBank,
    Plus,
    Receipt,
    Repeat,
    RotateCcw,
    Search,
    Send,
    Share,
    ShoppingBag,
    Smartphone,
    Sparkles,
    Square,
    SquarePlus,
    SunMoon,
    Tags,
    Trash2,
    TrendingUp,
    TrendingDown,
    TriangleAlert,
    User,
    UserRound,
    Utensils,
    Wallet,
    X,
} from 'lucide';

const icons = {
    ArrowDownRight,
    ArrowUpRight,
    Banknote,
    Bot,
    BookOpen,
    Briefcase,
    Camera,
    Car,
    ChartBarDecreasing,
    ChartPie,
    CalendarClock,
    Check,
    ChevronLeft,
    ChevronRight,
    CircleCheck,
    Clock,
    Compass,
    Download,
    FileText,
    Gamepad2,
    Gift,
    HeartPulse,
    History,
    Inbox,
    LayoutDashboard,
    Lightbulb,
    Loader2,
    LockKeyhole,
    LogOut,
    Mail,
    Mic,
    MessageCircle,
    Package,
    Pencil,
    PiggyBank,
    Plus,
    Receipt,
    Repeat,
    RotateCcw,
    Search,
    Send,
    Share,
    ShoppingBag,
    Smartphone,
    Sparkles,
    Square,
    SquarePlus,
    SunMoon,
    Tags,
    Trash2,
    TrendingUp,
    TrendingDown,
    TriangleAlert,
    User,
    UserRound,
    Utensils,
    Wallet,
    X,
};

function refreshPage() {
    createIcons({ icons });
}

refreshPage();
document.addEventListener('DOMContentLoaded', refreshPage);
document.addEventListener('livewire:init', () => {
    Livewire.on('chat-message-added', refreshPage);
    Livewire.on('charts-updated', refreshPage);

    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => queueMicrotask(refreshPage));
    });
});

function showToast(message, type = 'error') {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }));
}

/**
 * Input suara di chat memakai Web Speech API bawaan browser (bahasa id-ID).
 * Hasil suara hanya dimasukkan ke kolom input, tidak langsung dikirim.
 */
function voiceInput() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    return {
        supported: Boolean(SpeechRecognition),
        recording: false,
        recognition: null,

        toggle() {
            if (this.recording) {
                this.recognition?.stop();

                return;
            }

            this.start();
        },

        start() {
            if (!this.supported) {
                return;
            }

            const recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.onresult = (event) => {
                const transcript = Array.from(event.results)
                    .map((result) => result[0].transcript)
                    .join(' ')
                    .trim();

                if (transcript === '') {
                    return;
                }

                const currentMessage = (this.$wire.message || '').trim();
                this.$wire.message = currentMessage === '' ? transcript : `${currentMessage} ${transcript}`;

                this.$nextTick(() => {
                    const input = this.$refs.messageInput;
                    input?.focus();
                    input?.setSelectionRange(input.value.length, input.value.length);
                });
            };

            recognition.onerror = (event) => {
                if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                    showToast('Izin mikrofon ditolak. Aktifkan di pengaturan browser.');
                } else if (event.error === 'no-speech') {
                    showToast('Suara tidak terdengar. Coba tekan mic lagi lalu bicara.', 'warning');
                } else if (event.error === 'audio-capture') {
                    showToast('Mikrofon tidak ditemukan. Pastikan mikrofon terpasang.');
                } else if (event.error !== 'aborted') {
                    showToast('Input suara gagal. Coba lagi ya.');
                }
            };

            recognition.onend = () => {
                this.recording = false;
                this.recognition = null;
            };

            try {
                recognition.start();
                this.recognition = recognition;
                this.recording = true;
            } catch {
                showToast('Input suara gagal. Coba lagi ya.');
            }
        },

        destroy() {
            this.recognition?.abort();
        },
    };
}

/*
 * Panduan memasang Kasku sebagai PWA.
 * beforeinstallprompt bisa terpicu sebelum Alpine siap, jadi event-nya ditangkap dan disimpan di sini dulu.
 */
const INSTALL_DISMISSED_KEY = 'kasku-install-dismissed';
const INSTALL_LATER_KEY = 'kasku-install-later-at';
const INSTALL_SHOWN_SESSION_KEY = 'kasku-install-popup-shown';
const INSTALL_POPUP_DELAY_MS = 3000;
const INSTALL_LATER_DURATION_MS = 7 * 24 * 60 * 60 * 1000;
const INSTALL_POPUP_OFFSET_PROPERTY = '--install-popup-offset';
const OPEN_INSTALL_GUIDE_EVENT = 'kasku-open-install-guide';

let deferredInstallPrompt = null;

/*
 * Cadangan kalau sessionStorage tidak bisa dipakai. Variabel modul tetap hidup selama pindah halaman lewat
 * wire:navigate, jadi pop-up tetap tidak muncul dobel.
 */
let installPopupShownThisSession = false;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredInstallPrompt = event;
    window.dispatchEvent(new CustomEvent('kasku-install-changed'));
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    window.dispatchEvent(new CustomEvent('kasku-installed'));
});

function isRunningAsInstalledApp() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function isAppleMobileDevice() {
    const userAgent = window.navigator.userAgent;

    // iPadOS 13+ mengaku sebagai Mac, jadi dikenali dari layar sentuhnya.
    return /iPad|iPhone|iPod/.test(userAgent) || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
}

function isAppleSafari() {
    const userAgent = window.navigator.userAgent;

    return /Safari/.test(userAgent) && !/CriOS|FxiOS|EdgiOS|OPiOS|GSA|YaBrowser|DuckDuckGo|Instagram|FBAN|FBAV|Line\//i.test(userAgent);
}

function readStorage(storageName, key) {
    try {
        return window[storageName].getItem(key);
    } catch (error) {
        return null;
    }
}

function writeStorage(storageName, key, value) {
    try {
        window[storageName].setItem(key, value);
    } catch (error) {
        // Mode privat atau storage penuh: status hanya berlaku sampai halaman dimuat ulang.
    }
}

function isInstallDismissedForever() {
    return readStorage('localStorage', INSTALL_DISMISSED_KEY) === '1';
}

function isInstallPostponed() {
    const laterAt = Number(readStorage('localStorage', INSTALL_LATER_KEY));

    return Number.isFinite(laterAt) && laterAt > 0 && Date.now() - laterAt < INSTALL_LATER_DURATION_MS;
}

function wasInstallPopupShownThisSession() {
    return installPopupShownThisSession || readStorage('sessionStorage', INSTALL_SHOWN_SESSION_KEY) === '1';
}

function markInstallPopupShownThisSession() {
    installPopupShownThisSession = true;
    writeStorage('sessionStorage', INSTALL_SHOWN_SESSION_KEY, '1');
}

function isChatInputFocused() {
    return document.activeElement?.matches('[data-chat-input]') === true;
}

/**
 * @param {boolean} isPopup true untuk pop-up di layout app, false untuk kartu di Profil.
 */
function installGuide(isPopup = false) {
    return {
        mode: 'hidden',
        isPopup,
        visible: false,
        popupDelayPassed: false,
        hiddenForChatInput: false,
        openedManually: false,
        cleanupCallbacks: [],

        init() {
            if (isRunningAsInstalledApp()) {
                this.mode = 'installed';

                return;
            }

            this.mode = this.detectMode();

            this.listen(window, 'kasku-install-changed', () => {
                if (this.mode !== 'installed') {
                    this.mode = this.detectMode();
                    this.refresh();
                }
            });
            this.listen(window, 'kasku-installed', () => {
                this.mode = 'installed';
                this.hide();
            });

            if (!isPopup) {
                this.visible = true;

                return;
            }

            // Dibuka manual (misalnya dari checklist "Mulai di sini"), jadi tidak terikat aturan tampil otomatis.
            this.listen(window, OPEN_INSTALL_GUIDE_EVENT, () => this.openManually());

            if (this.isPopupBlocked()) {
                return;
            }

            const delayTimer = window.setTimeout(() => {
                this.popupDelayPassed = true;
                this.refresh();
            }, INSTALL_POPUP_DELAY_MS);
            this.cleanupCallbacks.push(() => window.clearTimeout(delayTimer));

            this.listen(document, 'focusin', () => {
                if (this.visible && isChatInputFocused()) {
                    this.hiddenForChatInput = true;
                    this.hide();
                }
            });
            this.listen(document, 'focusout', () => {
                // Tunggu fokus pindah dulu supaya document.activeElement sudah terbarui.
                window.setTimeout(() => this.refresh(), 0);
            });

            const resizeObserver = new ResizeObserver(() => this.updatePopupOffset());
            resizeObserver.observe(this.$el);
            this.cleanupCallbacks.push(() => resizeObserver.disconnect());
        },

        /*
         * Dipanggil Alpine saat elemen dibuang, misalnya ketika pindah halaman lewat wire:navigate,
         * supaya timer dan listener lama tidak memunculkan pop-up dobel.
         */
        destroy() {
            this.cleanupCallbacks.forEach((cleanup) => cleanup());
            this.cleanupCallbacks = [];

            if (isPopup) {
                document.documentElement.style.removeProperty(INSTALL_POPUP_OFFSET_PROPERTY);
            }
        },

        listen(target, eventName, handler) {
            target.addEventListener(eventName, handler);
            this.cleanupCallbacks.push(() => target.removeEventListener(eventName, handler));
        },

        detectMode() {
            if (deferredInstallPrompt) {
                return 'prompt';
            }

            if (isAppleMobileDevice()) {
                return isAppleSafari() ? 'ios-safari' : 'ios-other';
            }

            return 'unsupported';
        },

        isPopupBlocked() {
            return isInstallDismissedForever() || isInstallPostponed() || (wasInstallPopupShownThisSession() && !this.hiddenForChatInput);
        },

        refresh() {
            if (this.mode === 'installed' || this.mode === 'hidden') {
                this.hide();

                return;
            }

            if (!isPopup) {
                this.visible = true;

                return;
            }

            if (this.mode === 'unsupported' && !this.openedManually) {
                this.hide();

                return;
            }

            if (this.visible || !this.popupDelayPassed || isChatInputFocused() || this.isPopupBlocked()) {
                return;
            }

            this.hiddenForChatInput = false;
            this.visible = true;
            markInstallPopupShownThisSession();
            this.$nextTick(() => this.updatePopupOffset());
        },

        hide() {
            this.visible = false;
            this.openedManually = false;
            this.updatePopupOffset();
        },

        openManually() {
            if (this.mode === 'installed') {
                return;
            }

            this.openedManually = true;
            this.hiddenForChatInput = false;
            this.visible = true;
            markInstallPopupShownThisSession();
            this.$nextTick(() => this.updatePopupOffset());
        },

        /*
         * Tinggi pop-up dibagikan lewat CSS variable supaya tombol + di Dashboard bisa naik dan tidak tertumpuk.
         */
        updatePopupOffset() {
            if (!isPopup) {
                return;
            }

            const offset = this.visible ? this.$el.offsetHeight + 12 : 0;
            document.documentElement.style.setProperty(INSTALL_POPUP_OFFSET_PROPERTY, `${offset}px`);
        },

        async install() {
            const promptEvent = deferredInstallPrompt;

            if (!promptEvent) {
                return;
            }

            // Prompt hanya bisa dipakai sekali. Kabari komponen lain supaya tombolnya ikut menyesuaikan.
            deferredInstallPrompt = null;
            promptEvent.prompt();
            const { outcome } = await promptEvent.userChoice;

            if (outcome === 'accepted') {
                this.mode = 'installed';
                this.hide();
            } else {
                this.mode = this.detectMode();
                this.refresh();
            }

            window.dispatchEvent(new CustomEvent('kasku-install-changed'));
        },

        later() {
            writeStorage('localStorage', INSTALL_LATER_KEY, String(Date.now()));
            this.hide();
        },

        dismiss() {
            writeStorage('localStorage', INSTALL_DISMISSED_KEY, '1');
            this.hide();
        },
    };
}

/**
 * Checklist "Mulai di sini" di Dashboard. Langkah transaksi dan budget dihitung server, langkah pasang di HP
 * hanya bisa dideteksi di browser.
 *
 * @param {{ hasTransaction: boolean, hasBudget: boolean }} serverSteps
 */
function onboardingChecklist({ hasTransaction, hasBudget }) {
    return {
        hasTransaction,
        hasBudget,
        isInstalled: isRunningAsInstalledApp(),

        init() {
            const markInstalled = () => {
                this.isInstalled = true;
            };

            window.addEventListener('kasku-installed', markInstalled);
            this.removeInstalledListener = () => window.removeEventListener('kasku-installed', markInstalled);
        },

        destroy() {
            this.removeInstalledListener?.();
        },

        get completedSteps() {
            return [this.hasTransaction, this.hasBudget, this.isInstalled].filter(Boolean).length;
        },

        get isComplete() {
            return this.completedSteps === 3;
        },

        openInstallGuide() {
            window.dispatchEvent(new CustomEvent(OPEN_INSTALL_GUIDE_EVENT));
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('voiceInput', voiceInput);
    window.Alpine.data('installGuide', installGuide);
    window.Alpine.data('onboardingChecklist', onboardingChecklist);
});


if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
