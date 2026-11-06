import {
    ArrowDownRight,
    ArrowUpRight,
    Banknote,
    Bot,
    BookOpen,
    Briefcase,
    Camera,
    Car,
    ChartPie,
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
import Chart from 'chart.js/auto';

Chart.defaults.font.family = "'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif";

const icons = {
    ArrowDownRight,
    ArrowUpRight,
    Banknote,
    Bot,
    BookOpen,
    Briefcase,
    Camera,
    Car,
    ChartPie,
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

const chartInstances = new Map();

function isDarkMode() {
    const explicit = document.documentElement.getAttribute('data-theme');

    if (explicit === 'kasku-dark') {
        return true;
    }

    if (explicit === 'kasku') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

/**
 * Palet grafik gaya Buku Kas: gradasi dari warna aksen emerald ke abu-abu.
 * Warna per kategori dari database sengaja tidak dipakai supaya hanya ada satu warna aksen.
 */
const chartPalettes = {
    light: ['#0f7b5f', '#3d957b', '#6fb19b', '#a3cdbd', '#5f6368', '#85898e', '#adb0b4', '#d3d5d7'],
    dark: ['#3dd6a3', '#2fae86', '#25876a', '#1c624f', '#9aa0a6', '#7b8187', '#5e6368', '#43474c'],
};

function chartThemeColors() {
    const dark = isDarkMode();

    return {
        legend: dark ? '#eceae4' : '#16181d',
        border: dark ? '#1a1d20' : '#ffffff',
        palette: dark ? chartPalettes.dark : chartPalettes.light,
    };
}

function paletteColor(palette, index) {
    return palette[index % palette.length];
}

function applyDatasetColors(datasets, { border, palette }) {
    datasets.forEach((dataset, datasetIndex) => {
        dataset.backgroundColor =
            datasets.length === 1
                ? (dataset.data ?? []).map((_, index) => paletteColor(palette, index))
                : paletteColor(palette, datasetIndex);
        dataset.borderColor = border;
        dataset.borderWidth = 2;
    });
}

function applyChartTheme(config) {
    const theme = chartThemeColors();

    config.options = config.options ?? {};
    config.options.plugins = config.options.plugins ?? {};
    config.options.plugins.legend = config.options.plugins.legend ?? {};
    config.options.plugins.legend.labels = config.options.plugins.legend.labels ?? {};
    config.options.plugins.legend.labels.color = theme.legend;

    applyDatasetColors(config.data.datasets ?? [], theme);

    return config;
}

function initCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const configJson = canvas.dataset.chart;

        if (canvas.dataset.chartConfig === configJson) {
            return;
        }

        const config = applyChartTheme(JSON.parse(configJson));
        chartInstances.get(canvas)?.destroy();
        chartInstances.set(canvas, new Chart(canvas, config));
        canvas.dataset.chartConfig = configJson;
    });
}

function refreshChartsTheme() {
    const theme = chartThemeColors();

    chartInstances.forEach((chart) => {
        chart.options.plugins.legend.labels.color = theme.legend;
        applyDatasetColors(chart.data.datasets, theme);
        chart.update();
    });
}

function refreshPage() {
    createIcons({ icons });
    initCharts();
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

window.addEventListener('kasku-theme-changed', refreshChartsTheme);
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (!document.documentElement.getAttribute('data-theme')) {
        refreshChartsTheme();
    }
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
