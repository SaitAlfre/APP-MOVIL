<script>
    (function () {
        try {
            var stored = localStorage.getItem('eh-theme');
            var dark = stored === 'dark';
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
</script>
