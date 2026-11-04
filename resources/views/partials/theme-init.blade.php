<script>
    (function () {
        try {
            var stored = localStorage.getItem('kasku-theme');
            var dark = stored === 'dark' || (stored !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            if (stored === 'light') {
                document.documentElement.setAttribute('data-theme', 'kasku');
            } else if (stored === 'dark') {
                document.documentElement.setAttribute('data-theme', 'kasku-dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }

            var meta = document.getElementById('theme-color-meta');
            if (meta) {
                meta.setAttribute('content', dark ? '#0b0f14' : '#ffffff');
            }
        } catch (e) {}
    })();
</script>
