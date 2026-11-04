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

function chartThemeColors() {
    const dark = isDarkMode();

    return {
        legend: dark ? '#e6e8eb' : '#111111',
        border: dark ? '#151b23' : '#ffffff',
    };
}

function applyChartTheme(config) {
    const { legend, border } = chartThemeColors();

    config.options = config.options ?? {};
    config.options.plugins = config.options.plugins ?? {};
    config.options.plugins.legend = config.options.plugins.legend ?? {};
    config.options.plugins.legend.labels = config.options.plugins.legend.labels ?? {};
    config.options.plugins.legend.labels.color = legend;

    (config.data.datasets ?? []).forEach((dataset) => {
        dataset.borderColor = border;
        dataset.borderWidth = 2;
    });

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
    const { legend, border } = chartThemeColors();

    chartInstances.forEach((chart) => {
        chart.options.plugins.legend.labels.color = legend;
        chart.data.datasets.forEach((dataset) => {
            dataset.borderColor = border;
            dataset.borderWidth = 2;
        });
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
