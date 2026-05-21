<div
    id="cmp-page-loader"
    class="pointer-events-auto fixed inset-0 z-[99999] flex items-center justify-center transition-opacity duration-300"
    style="background: #0a0a0a"
    aria-hidden="true"
>
    <img
        src="{{ asset('assets/pre-loader/cmp.gif') }}"
        alt=""
        class="max-h-32 max-w-[min(90vw,280px)] object-contain"
        width="200"
        height="200"
    />
</div>
<style>
    #cmp-page-loader.cmp-page-loader--hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
</style>
<script>
    (function () {
        const el = document.getElementById('cmp-page-loader');
        if (!el) return;
        const hide = () => {
            el.classList.add('cmp-page-loader--hidden');
            window.setTimeout(() => el.remove(), 400);
        };
        if (document.readyState === 'complete') {
            hide();
        } else {
            window.addEventListener('load', hide, { once: true });
        }
        document.addEventListener('livewire:navigated', hide);
        window.setTimeout(hide, 12000);
    })();
</script>
