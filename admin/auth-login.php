<?php include 'partials/html.php' ?>


<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once 'services/database.php';
include_once 'services/funcao.php';
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once "services/CSRF_Protect.php";
include_once "l.php";
$csrf = new CSRF_Protect();
?>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <?php $title = "dash"; include 'partials/title-meta.php'; ?>
    <?php include 'partials/head-css.php'; ?>
    <style>
/* Ocultar elementos */
#loadingSpinner {
    display: none !important;
}

#particles-js {
    display: none !important;
}
.favicon-admin {
    display: flex;
    justify-content: center; /* centraliza horizontal */
    align-items: center;     /* centraliza vertical */
    width: 100%;
    height: 100vh;           /* ocupa altura total da tela */
}

.auth-favicon{
    max-width: 300px;
    height: auto;
}

.logo-admin {
    display: flex;
    justify-content: center;
    align-items: center;
}
/* Toast profissional */
.toast.pro {
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.15);
}

.toast.pro .toast-body {
    display: flex;
    align-items: center;
    gap: .5rem;
}

.toast.pro .toast-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(0,0,0,.06);
}

.toast.pro.success {
    border-left: 4px solid var(--bs-success);
}

.toast.pro.danger {
    border-left: 4px solid var(--bs-danger);
}

.toast.pro.info {
    border-left: 4px solid var(--bs-primary);
}

/* Botão flutuante de idioma */
#lang-fab {
    position: fixed;
    right: 16px;
    bottom: 16px;
    z-index: 9999;
}

#lang-fab .btn-lang {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Menu de idiomas */
#lang-menu {
    position: fixed;
    right: 16px;
    bottom: 72px;
    background: #fff;
    color: #111;
    border: 1px solid rgba(0,0,0,.1);
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,.15);
    display: none;
    z-index: 9999;
    min-width: 220px;
    overflow: hidden;
    padding: 6px 0;
}

#lang-menu * {
    color: #111 !important;
}

#lang-menu button {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 10px 16px;
    background: transparent !important;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: #111 !important;
    font-size: 14px;
    line-height: 1.2;
    text-align: left;
}

#lang-menu button + button {
    border-top: 1px solid rgba(0,0,0,.06);
}

#lang-menu button:hover {
    background: #f3f4f6 !important;
}
</style>
<style>
#telegram-fab{
    position: fixed;
    left: 15px;   /* canto esquerdo */
    bottom: 15px; /* distância de baixo */
    z-index: 9999;
}

#telegram-fab .btn-telegram{
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    background:#229ED9;
    border:none;
    box-shadow:0 8px 20px rgba(0,0,0,.25);
    transition:.3s;
}

#telegram-fab .btn-telegram:hover{
    transform: scale(1.1);
    box-shadow:0 10px 30px rgba(0,0,0,.35);
}
</style>
</head>

<body>
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
        <div class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
            
            
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center w-100">
                    <div class="col-md-8 col-lg-6 col-xxl-3">
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="index.php" class="logo logo-admin">
                                   <img src="../uploads/<?= $dataconfig['logo'] ?>" height="75" alt="logo" class="auth-logo">
                                </a>
                                <p class="text-center mb-0"><?= admin_t('login_title') ?></p>
                                <form method="POST" id="form-acessar">
                                    <div class="mb-3">
                                        <label class="form-label" for="email"><?= admin_t('email') ?></label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="<?= admin_t('email_placeholder') ?>" autocomplete="username" inputmode="email">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label" for="senha"><?= admin_t('password') ?></label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" name="senha" id="senha" placeholder="<?= admin_t('password_placeholder') ?>" autocomplete="current-password">
                                            <span class="input-group-text" onclick="togglePassword('senha', this)">
                                                <i class="ti ti-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-grid mt-3">
                                        <?php $csrf->echoInputField(); ?>
                                        <button class="btn btn-primary w-100 py-8 fs-4 mb-4 rounded-2" type="submit">
                                            <?= admin_t('access') ?>
                                            <i class="ti ti-login ms-1"></i>
                                        </button>
                                    </div>
                                    
                                    
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
   
</div>
<?php include 'partials/vendorjs.php'; ?>

<div id="lang-fab"><button type="button" class="btn btn-primary btn-lg btn-lang" id="btnLang">🌐</button></div>
<div id="lang-menu">
    <button type="button" data-lang="pt-BR">🇧🇷 Português (BR)</button>
    <button type="button" data-lang="en-US">🇺🇸 English (US)</button>
    <button type="button" data-lang="es-ES">🇪🇸 Español</button>
    <button type="button" data-lang="zh-CN">🇨🇳 中文 (简体)</button>
</div>

<!-- BOTÃO SUPORTE TELEGRAM -->
<div id="telegram-fab">
    <a href="https://wa.me/+5545999057184" target="_blank" class="btn btn-info btn-lg btn-telegram">
        💬
    </a>
</div>


<script>
    function ensureToastContainer(){
        var c=document.getElementById('toastContainer');
        if(!c){
            c=document.createElement('div');
            c.id='toastContainer';
            c.className='toast-container position-fixed top-0 end-0 p-3';
            c.style.zIndex='9999';
            document.body.appendChild(c);
        }
        return c;
    }
    function showToast(content, type) {
        var container = ensureToastContainer();
        var tone='info';
        if(type==='success') tone='success';
        if(type==='danger') tone='danger';
        var el = document.createElement('div');
        el.className = 'toast pro align-items-center bg-light text-dark ' + tone;
        el.style.background = '#e9ecef';
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');
        var iconClass='bi-info-lg text-primary';
        if(tone==='success') iconClass='bi-check-lg text-success';
        if(tone==='danger') iconClass='bi-x-lg text-danger';
        el.innerHTML = '<div class="d-flex w-100"><div class="toast-body"><span class="toast-icon"><i class="bi '+iconClass+'"></i></span><span>'+ content +'</span></div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
        container.appendChild(el);
        var t = new bootstrap.Toast(el,{delay:5000,autohide:true});
        t.show();
    }
    function getRootIndexUrl(){
        var p=window.location.pathname;
        if(p.indexOf('auth-login.php')!==-1){
            return p.replace('auth-login.php','index.php');
        }
        return 'index.php';
    }

    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        const iconElement = icon.querySelector('i');

        if (input.type === "password") {
            input.type = "text";
            iconElement.classList.remove('ti-eye');
            iconElement.classList.add('ti-eye-off');
        } else {
            input.type = "password";
            iconElement.classList.remove('ti-eye-off');
            iconElement.classList.add('ti-eye');
        }
    }

    $(document).ready(function () {
        function getLang(){var m=document.cookie.match(/(?:^|; )admin_lang=([^;]+)/);return m?decodeURIComponent(m[1]):null}
        function setLang(v){var d=new Date();d.setTime(d.getTime()+365*24*60*60*1000);document.cookie="admin_lang="+encodeURIComponent(v)+"; expires="+d.toUTCString()+"; path=/"}
        var i18n={"pt-BR":{toastAccessando:"Acessando Sua Conta, aguarde...",erroConexao:"Falha ao conectar. Tente novamente."},"en-US":{toastAccessando:"Signing in, please wait...",erroConexao:"Connection failed. Try again."},"es-ES":{toastAccessando:"Accediendo, espere...",erroConexao:"Fallo de conexión. Inténtalo de nuevo."},"zh-CN":{toastAccessando:"正在登录，请稍候…",erroConexao:"连接失败，请重试。"}};
        var cur=getLang()||"<?php echo admin_lang_current(); ?>";window.__AUTH_I18N__=i18n[cur]||i18n["pt-BR"];
        $("#btnLang").on("click",function(e){e.stopPropagation();var m=$("#lang-menu");m.is(":visible")?m.hide():m.show()});
        $(document).on("click",function(e){var m=$("#lang-menu");if(!$(e.target).closest("#lang-menu").length&&!$(e.target).closest("#btnLang").length){m.hide()}});
        $("#lang-menu button").on("click",function(){var l=$(this).data("lang");setLang(l);window.location.reload()})
        $('#form-acessar').submit(function (event) {
            event.preventDefault();
            let formData = $(this).serialize();
            $.ajax({
                url: 'ajax/form-acessar.php',
                type: 'POST',
                data: formData,
                success: function (response) {
                    var txt = $('<div>').html(response).text().trim();
                    var lower = txt.toLowerCase();
                    var type = 'info';
                    if (lower.includes('sucesso') || lower.includes('success') || lower.includes('logado') || lower.includes('ok')) type = 'success';
                    if (lower.includes('erro') || lower.includes('error') || lower.includes('falha')) type = 'danger';
                    if(type==='success'){
                        var t=window.__AUTH_I18N__?window.__AUTH_I18N__.toastAccessando:'Acessando Conta, aguarde...';showToast(t, 'success');
                        setTimeout(function(){ window.location.href=getRootIndexUrl(); },3000);
                    }else{
                        showToast(txt || 'Operação concluída', type);
                    }
                },
                error: function (xhr) {
                    var t=window.__AUTH_I18N__?window.__AUTH_I18N__.erroConexao:'Falha ao conectar. Tente novamente.';showToast(t, 'danger');
                }
            });
        });
    });
</script>

</body>

</html>
