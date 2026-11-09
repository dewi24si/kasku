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
let deferredInstallPrompt = null;

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

function readInstallDismissed() {
    try {
        return localStorage.getItem(INSTALL_DISMISSED_KEY) === '1';
    } catch (error) {
        return false;
    }
}

/**
 * @param {boolean} dismissible true untuk banner dashboard (bisa ditutup), false untuk kartu di Profil.
 */
function installGuide(dismissible = false) {
    return {
        mode: 'hidden',
        dismissible,
        dismissed: false,
        visible: false,

        init() {
            if (isRunningAsInstalledApp()) {
                this.setMode('installed');

                return;
            }

            this.dismissed = dismissible && readInstallDismissed();
            this.setMode(this.detectMode());

            window.addEventListener('kasku-install-changed', () => {
                if (this.mode !== 'installed') {
                    this.setMode(this.detectMode());
                }
            });
            window.addEventListener('kasku-installed', () => {
                this.setMode('installed');
            });
        },

        setMode(mode) {
            this.mode = mode;
            this.updateVisibility();
        },

        /*
         * Banner (dismissible) tidak tampil di browser yang belum mendukung pemasangan; kartu Profil selalu tampil
         * kecuali Kasku sudah terpasang.
         */
        updateVisibility() {
            if (this.mode === 'installed' || this.mode === 'hidden') {
                this.visible = false;
            } else if (this.dismissible) {
                this.visible = !this.dismissed && this.mode !== 'unsupported';
            } else {
                this.visible = true;
            }
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

        async install() {
            const promptEvent = deferredInstallPrompt;

            if (!promptEvent) {
                return;
            }

            // Prompt hanya bisa dipakai sekali. Kabari komponen lain supaya tombolnya ikut menyesuaikan.
            deferredInstallPrompt = null;
            promptEvent.prompt();
            const { outcome } = await promptEvent.userChoice;

            this.setMode(outcome === 'accepted' ? 'installed' : this.detectMode());
            window.dispatchEvent(new CustomEvent('kasku-install-changed'));
        },

        dismiss() {
            this.dismissed = true;
            this.updateVisibility();

            try {
                localStorage.setItem(INSTALL_DISMISSED_KEY, '1');
            } catch (error) {
                // Mode privat atau storage penuh: banner tetap tertutup sampai halaman dimuat ulang.
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('voiceInput', voiceInput);
    window.Alpine.data('installGuide', installGuide);
});


if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
