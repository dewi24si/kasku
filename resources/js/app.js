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
    CalendarClock,
    Check,
    ChevronLeft,
    ChevronRight,
    Clock,
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
    ShoppingBag,
    Sparkles,
    Square,
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
    CalendarClock,
    Check,
    ChevronLeft,
    ChevronRight,
    Clock,
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
    ShoppingBag,
    Sparkles,
    Square,
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

document.addEventListener('alpine:init', () => {
    window.Alpine.data('voiceInput', voiceInput);
});


if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
