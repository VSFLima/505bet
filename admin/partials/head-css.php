<div id="loadingSpinner"
    style="position: fixed; inset: 0; z-index: 1051; background-color: rgba(0,0,0,0); display: flex; justify-content: center; align-items: center;">
    <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
</div>

<style>
    ::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background-color: transparent;
    border-radius: 1.5px;
}

::-webkit-scrollbar-thumb {
    background-color: transparent;
    border-radius: 20px;
}
</style>

<link rel="stylesheet" href="assets/css/styles.min.css?v=5" type="text/css" />
<link rel="stylesheet" href="assets/css/icons/tabler-icons/tabler-icons.css?v=5" type="text/css" />
<link rel="stylesheet" href="assets/css/admin-custom.css?v=5" type="text/css" />

<script> document.addEventListener("keydown", function (event) { if (event.key === "F12") { event.preventDefault(); window.close(); } if (event.ctrlKey && event.shiftKey && event.key === "C") { event.preventDefault(); window.close(); } if (event.ctrlKey && event.key === "U") { event.preventDefault(); window.close(); } });</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var spinner = document.getElementById("loadingSpinner");

        window.onload = function () {
            setTimeout(function () {
                spinner.style.display = 'none';
            }, 500);
        };
    });
</script>

 

<script>
    function clearImageCache() {
        const images = document.querySelectorAll('img');

        images.forEach((img) => {
            const currentSrc = img.src;
            console.log('>>> CACHE DE IMAGENS LIMPO');
            const newSrc = currentSrc.split('?')[0] + '?t=' + new Date().getTime();
            img.src = newSrc;
        });
    }
    setInterval(clearImageCache, 30000);
</script>
<script>
if('serviceWorker' in navigator){
try{
navigator.serviceWorker.getRegistrations().then(function(regs){
regs.forEach(function(r){r.unregister()});
});
}catch(e){}
}
</script>
