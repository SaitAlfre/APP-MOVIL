<script>
    (function () {
        try {
            var stored = localStorage.getItem('eh-theme') || 'light';
            var dark = stored === 'dark' || (stored === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            ['accent', 'density', 'container'].forEach(function (key) {
                var value = localStorage.getItem('eh-' + key);
                if (value) document.documentElement.dataset[key] = value;
            });
        } catch (e) {}
    })();
</script>
