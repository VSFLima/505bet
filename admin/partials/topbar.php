<?php
   ini_set('display_errors', 0);
   error_reporting(E_ALL);
   include_once "./services/database.php";
   include_once './logs/registrar_logs.php';
   include_once "./services/funcao.php";
   include_once "./services/crud.php";
   include_once "./services/crud-adm.php";
   include_once './services/checa_login_adm.php';
   include_once "./services/CSRF_Protect.php";
   include_once "./l.php";
   $csrf = new CSRF_Protect();
   
   checa_login_adm();
   
   if ($_SESSION['data_adm']['status'] != '1') {
       echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
       exit();
   }
   
   $admin_id = $_SESSION['data_adm']['id'];
   
   // Buscar notificações organizadas por categoria
   $notificacoes_por_tipo = [
       'saques' => [],
       'depositos' => [],
       'usuarios' => [],
       'feedbacks' => []
   ];
   
   // Saques (limite de 10 por categoria)
   $query = "SELECT s.id, s.valor, s.data_registro, u.mobile 
             FROM solicitacao_saques s 
             INNER JOIN usuarios u ON s.id_user = u.id 
             WHERE s.status = '0' 
             ORDER BY s.data_registro DESC LIMIT 10";
   $result = mysqli_query($mysqli, $query);
   while ($row = mysqli_fetch_assoc($result)) {
       $notificacoes_por_tipo['saques'][] = [
           'id' => 'saque_' . $row['id'],
           'tipo' => 'saque',
          'icone' => 'ti ti-cash',
           'cor' => 'danger',
           'titulo' => 'Solicitação de Saque',
           'mensagem' => "{$row['mobile']} solicitou R$ " . number_format($row['valor'], 2, ',', '.'),
           'link' => 'saques_pendentes',
           'tempo' => time_elapsed_string($row['data_registro']),
           'timestamp' => strtotime($row['data_registro'])
       ];
   }
   
   // Depósitos
   $query = "SELECT t.id, t.valor, t.data_registro, u.mobile 
             FROM transacoes t 
             INNER JOIN usuarios u ON t.usuario = u.id 
             WHERE t.status = 'processamento' 
             ORDER BY t.data_registro DESC LIMIT 10";
   $result = mysqli_query($mysqli, $query);
   while ($row = mysqli_fetch_assoc($result)) {
       $notificacoes_por_tipo['depositos'][] = [
           'id' => 'deposito_' . $row['id'],
           'tipo' => 'deposito',
          'icone' => 'ti ti-wallet',
           'cor' => 'info',
           'titulo' => 'Depósito Pendente',
           'mensagem' => "{$row['mobile']} gerou um pagamento de R$ " . number_format($row['valor'], 2, ',', '.'),
           'link' => 'depositos_pendentes',
           'tempo' => time_elapsed_string($row['data_registro']),
           'timestamp' => strtotime($row['data_registro'])
       ];
   }
   
   // Usuários
   $query = "SELECT id, mobile, data_registro 
             FROM usuarios 
             WHERE data_registro >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             ORDER BY data_registro DESC LIMIT 10";
   $result = mysqli_query($mysqli, $query);
   while ($row = mysqli_fetch_assoc($result)) {
       $notificacoes_por_tipo['usuarios'][] = [
           'id' => 'usuario_' . $row['id'],
           'tipo' => 'usuario',
          'icone' => 'ti ti-users',
           'cor' => 'success',
           'titulo' => 'Novo Cadastro',
           'mensagem' => "{$row['mobile']} se cadastrou na plataforma",
           'link' => 'usuarios',
           'tempo' => time_elapsed_string($row['data_registro']),
           'timestamp' => strtotime($row['data_registro'])
       ];
   }
   
   $query = "SELECT id, user_id, created_at FROM customer_feedback WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10";
   $result = mysqli_query($mysqli, $query);
   while ($row = mysqli_fetch_assoc($result)) {
       $notificacoes_por_tipo['feedbacks'][] = [
           'id' => 'feedback_' . $row['id'],
           'tipo' => 'feedback',
          'icone' => 'ti ti-message-dots',
           'cor' => 'warning',
           'titulo' => 'Novo Feedback',
           'mensagem' => "Usuário #{$row['user_id']} enviou um feedback",
           'link' => 'notificacoes',
           'tempo' => time_elapsed_string($row['created_at']),
           'timestamp' => strtotime($row['created_at'])
       ];
   }
   
   function time_elapsed_string($datetime, $full = false) {
       $now = new DateTime;
       $ago = new DateTime($datetime);
       $diff = $now->diff($ago);
   
       $w = floor($diff->d / 7);
       $d = $diff->d - ($w * 7);
   
       $string = array(
           'y' => 'ano',
           'm' => 'mês',
           'w' => 'semana',
           'd' => 'dia',
           'h' => 'hora',
           'i' => 'minuto',
           's' => 'segundo',
       );
       
       foreach ($string as $k => &$v) {
           $val = ($k === 'w') ? $w : (($k === 'd') ? $d : $diff->$k);
           if ($val) {
               $v = $val . ' ' . $v . ($val > 1 ? ($k === 'm' ? 'es' : 's') : '');
           } else {
               unset($string[$k]);
           }
       }
   
       if (!$full) $string = array_slice($string, 0, 1);
       return $string ? 'Há ' . implode(', ', $string) : 'Agora';
   }
   
   $notificacoes_json = json_encode($notificacoes_por_tipo);
   
   // Calcular totais
   $total_saques = count($notificacoes_por_tipo['saques']);
   $total_depositos = count($notificacoes_por_tipo['depositos']);
   $total_usuarios = count($notificacoes_por_tipo['usuarios']);
   $total_feedbacks = count($notificacoes_por_tipo['feedbacks']);
   $total_geral = $total_saques + $total_depositos + $total_usuarios + $total_feedbacks;
   
   // Consulta o banco para verificar se o iGameWin está ativo e para recuperar o valor atual de RTP
   $igamewin_active = false;
   $igamewin_url    = "";
   $agent_code      = "";
   $agent_token     = "";
   $rtp_db_value    = 50; // valor padrão
   
   $query  = "SELECT * FROM igamewin WHERE ativo = 1 LIMIT 1";
   $result = mysqli_query($mysqli, $query);
   
   if ($result && mysqli_num_rows($result) > 0) {
       $row            = mysqli_fetch_assoc($result);
       $igamewin_active = true;
       $igamewin_url    = $row['url'];
       $agent_code      = $row['agent_code'];
       $agent_token     = $row['agent_token'];
       if(isset($row['rtp'])){
           $rtp_db_value = (int)$row['rtp'];
       }
   }
?>
<script>
function a(){fetch('/online_heartbeat.php?scope=admin&t='+Date.now(),{credentials:'include'})}
try{a();setInterval(a,60000)}catch(e){}
</script>
 
 
<div class="topbar d-print-none">
   <div class="container-xxl">
      <nav class="topbar-custom d-flex justify-content-between" id="topbar-custom">
         <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0 gap-2">
            <li>
               <button class="btn mobile-menu-btn p-0" id="togglemenu" aria-label="Menu">
               <i class="ti ti-menu-2"></i>
               </button>
            </li>
            <li class="mx-3 welcome-text d-none d-md-block">
               <h3 class="mb-0 fw-bold text-truncate"><span id="welcomeText"><?= admin_t('welcome') ?></span>, <span id="welcomeName"><?=$_SESSION['data_adm']['nome'];?></span>.</h3>
            </li>
         </ul>
         <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0 gap-2">
            <li class="topbar-item me-2">
               <button class="btn nav-icon p-0" id="themeToggleBtn" aria-label="Alternar tema">
                  <i class="ti ti-moon"></i>
               </button>
            </li>
            <li class="dropdown topbar-item me-2" id="adminLangDropdown">
               <a class="nav-link dropdown-toggle arrow-none nav-icon" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                  <i class="ti ti-language"></i>
               </a>
               <div class="dropdown-menu dropdown-menu-end py-0">
                  <button class="dropdown-item" data-lang="pt-BR">🇧🇷 Português (BR)</button>
                  <button class="dropdown-item" data-lang="en-US">🇺🇸 English (US)</button>
                  <button class="dropdown-item" data-lang="es-ES">🇪🇸 Español</button>
                  <button class="dropdown-item" data-lang="zh-CN">🇨🇳 中文 (简体)</button>
               </div>
            </li>
            <?php if ($igamewin_active): ?>
            <li class="rtp-control" title="<?= admin_t('rtp_label') ?>">
               <label for="rtpSlider"><span class="rtp-text"><?= admin_t('rtp_label') ?>:</span> <span id="rtpValueDisplay"><?php echo $rtp_db_value; ?>%</span></label>
               <input type="range" id="rtpSlider" min="10" max="90" step="5" value="<?php echo $rtp_db_value; ?>">
            </li>
            <?php endif; ?>
 
            <li class="dropdown topbar-item me-2">
               <a class="nav-link dropdown-toggle arrow-none nav-icon position-relative" 
                  data-bs-toggle="dropdown" 
                  href="#" 
                  role="button"
                  aria-haspopup="false" 
                  aria-expanded="false"
                  id="notificationDropdownBtn">
               <i class="ti ti-bell bell-animated"></i>
               <span class="notification-badge bg-danger text-white d-none" id="notificationBadge">0</span>
               </a>
               <div class="dropdown-menu dropdown-menu-end notification-dropdown py-0">
                  <div class="notification-header">
                     <div class="d-flex align-items-center justify-content-between">
                        <div>
                           <h6 class="mb-0 fw-bold">
                              <i class="ti ti-bell me-1"></i> <?= admin_t('notifications') ?>
                           </h6>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                           <span class="badge bg-danger rounded-pill d-none" id="headerBadge">0</span>
                           <button class="btn btn-success btn-sm mark-section-read-btn d-none" id="markSectionBtn" onclick="markSectionAsRead(event)">
                           <i class="ti ti-checks me-1"></i><span class="d-none d-sm-inline"><?= admin_t('settings') ?></span><span class="d-sm-none"><?= admin_t('settings') ?></span>
                           </button>
                        </div>
                     </div>
                  </div>
                  <div class="notification-tabs">
                     <button class="notification-tab active" data-tab="saques" onclick="switchTab('saques', event)">
                     <i class="ti ti-cash"></i> <?= admin_t('withdrawals') ?>
                     <span class="tab-badge d-none" id="badge-saques">0</span>
                     </button>
                     <button class="notification-tab" data-tab="depositos" onclick="switchTab('depositos', event)">
                     <i class="ti ti-wallet"></i> <?= admin_t('deposits') ?>
                     <span class="tab-badge d-none" id="badge-depositos">0</span>
                     </button>
                     <button class="notification-tab" data-tab="usuarios" onclick="switchTab('usuarios', event)">
                     <i class="ti ti-users"></i> <?= admin_t('users') ?>
                     <span class="tab-badge d-none" id="badge-usuarios">0</span>
                     </button>
                     <button class="notification-tab" data-tab="feedbacks" onclick="switchTab('feedbacks', event)">
                     <i class="ti ti-message-dots"></i> Feedback
                     <span class="tab-badge d-none" id="badge-feedbacks">0</span>
                     </button>
                  </div>
                  <div id="notificationContent" class="notification-content-scroll">
                     <!-- Seções serão renderizadas aqui -->
                  </div>
                  <div class="pagination-controls d-none" id="paginationControls">
                     <button class="pagination-btn" id="prevBtn" onclick="previousPage(event)">
                     <i class="ti ti-chevron-left"></i> <?= admin_t('previous') ?>
                     </button>
                     <span class="pagination-info" id="pageInfo">1 / 1</span>
                     <button class="pagination-btn" id="nextBtn" onclick="nextPage(event)">
                     <?= admin_t('next') ?> <i class="ti ti-chevron-right"></i>
                     </button>
                  </div>
               </div>
            </li>
            <li class="dropdown topbar-item ms-2">
               <a class="nav-link dropdown-toggle arrow-none nav-icon" data-bs-toggle="dropdown" href="#" role="button"
                  aria-haspopup="false" aria-expanded="false">
               <img src="assets/images/profile/user-1.jpg" alt="" class="avatar-40">
               </a>
               <div class="dropdown-menu dropdown-menu-end py-0">
                  <div class="d-flex align-items-center dropdown-item py-2 bg-secondary-subtle">
                     <div class="flex-shrink-0">
                        <img src="assets/images/profile/user-1.jpg" alt="" class="avatar-40">
                     </div>
                     <div class="flex-grow-1 ms-2 text-truncate align-self-center">
                        <h6 class="my-0 fw-medium text-dark fs-13"><?=$_SESSION['data_adm']['nome'];?></h6>
                        <small class="text-muted mb-0">Plataforma: <?=$dataconfig['nome'];?></small>
                     </div>
                  </div>
                  <div class="dropdown-divider mt-0"></div>
                  <small class="text-muted px-2 pb-1 d-block"><?= admin_t('settings') ?></small>
                  <a class="dropdown-item" href="administradores"><i class="las la-user fs-18 me-1 align-text-bottom"></i> <?= admin_t('operators') ?></a>
                  <a class="dropdown-item text-danger" href="sair"><i class="las la-power-off fs-18 me-1 align-text-bottom"></i> <?= admin_t('logout') ?></a>
               </div>
            </li>
         </ul>
      </nav>
   </div>
</div>
<script>
   function getAdminLang(){var m=document.cookie.match(/(?:^|; )admin_lang=([^;]+)/);return m?decodeURIComponent(m[1]):null}
   function setAdminLang(v){var d=new Date();d.setTime(d.getTime()+365*24*60*60*1000);document.cookie="admin_lang="+encodeURIComponent(v)+"; expires="+d.toUTCString()+"; path=/"; location.reload()}
   document.addEventListener('DOMContentLoaded',function(){var menu=document.querySelectorAll('#adminLangDropdown .dropdown-item');menu.forEach(function(btn){btn.addEventListener('click',function(e){e.preventDefault();var l=this.getAttribute('data-lang');if(l){setAdminLang(l)}})})});
   
   var TB_T_DICT={"pt-BR":{welcome:"<?= admin_t('welcome') ?>",notifications:"<?= admin_t('notifications') ?>",withdrawals:"<?= admin_t('withdrawals') ?>",deposits:"<?= admin_t('deposits') ?>",users:"<?= admin_t('users') ?>",feedback:"<?= admin_t('feedback') ?>",clearSection:"<?= admin_t('settings') ?>",clear:"<?= admin_t('settings') ?>",none:"<?= admin_t('no_notifications') ?>",clean:"<?= admin_t('all_clear') ?>",previous:"<?= admin_t('previous') ?>",next:"<?= admin_t('next') ?>"},"en-US":{welcome:"Welcome",notifications:"Notifications",withdrawals:"Withdrawals",deposits:"Deposits",users:"Users",feedback:"Feedback",clearSection:"Clear section",clear:"Clear",none:"No notifications",clean:"All clear here!",previous:"Previous",next:"Next"},"es-ES":{welcome:"Bienvenido(a)",notifications:"Notificaciones",withdrawals:"Retiros",deposits:"Depósitos",users:"Usuarios",feedback:"Comentarios",clearSection:"Limpiar sección",clear:"Limpiar",none:"Sin notificaciones",clean:"¡Todo limpio aquí!",previous:"Anterior",next:"Siguiente"},"zh-CN":{welcome:"欢迎",notifications:"通知",withdrawals:"提现",deposits:"存款",users:"用户",feedback:"反馈",clearSection:"清空分组",clear:"清空",none:"暂无通知",clean:"这里已清空",previous:"上一页",next:"下一页"}};
   var TB_T=TB_T_DICT[getAdminLang()]||TB_T_DICT["pt-BR"];
   function applyTopbarLang(){var l=getAdminLang()||"pt-BR";TB_T=TB_T_DICT[l]||TB_T_DICT["pt-BR"];document.documentElement.setAttribute("lang",l);var w=document.getElementById("welcomeText");if(w){w.textContent=TB_T.welcome}var h=document.querySelector(".notification-header h6");if(h){h.innerHTML='<i class="ti ti-bell me-1"></i> '+TB_T.notifications}var t1=document.querySelector('[data-tab="saques"]');if(t1){var logistic=t1.querySelector('.tab-badge');t1.innerHTML='<i class="ti ti-cash"></i> '+TB_T.withdrawals+(logistic?logistic.outerHTML:"")}var t2=document.querySelector('[data-tab="depositos"]');if(t2){var b2=t2.querySelector('.tab-badge');t2.innerHTML='<i class="ti ti-wallet"></i> '+TB_T.deposits+(b2?b2.outerHTML:"")}var t3=document.querySelector('[data-tab="usuarios"]');if(t3){var b3=t3.querySelector('.tab-badge');t3.innerHTML='<i class="ti ti-users"></i> '+TB_T.users+(b3?b3.outerHTML:"")}var t4=document.querySelector('[data-tab="feedbacks"]');if(t4){var b4=t4.querySelector('.tab-badge');t4.innerHTML='<i class="ti ti-message-dots"></i> '+TB_T.feedback+(b4?b4.outerHTML:"")}var ms=document.getElementById("markSectionBtn");if(ms){ms.innerHTML='<i class="ti ti-checks me-1"></i><span class="d-none d-sm-inline">'+TB_T.clearSection+'</span><span class="d-sm-none">'+TB_T.clear+"</span>"}var prev=document.getElementById("prevBtn");if(prev){prev.innerHTML='<i class="ti ti-chevron-left"></i> '+TB_T.previous}var next=document.getElementById("nextBtn");if(next){next.innerHTML=TB_T.next+' <i class="ti ti-chevron-right"></i>'}}
   applyTopbarLang();
   
   const allNotificationsByType = <?= $notificacoes_json ?>;
   let currentTab = 'saques';
   let currentPages = {
       saques: 1,
       depositos: 1,
       usuarios: 1,
       feedbacks: 1
   };
   const itemsPerPage = 5;
   function getCurrentTheme() {
       try {
           var saved = localStorage.getItem('adminTheme');
           if (saved === 'dark' || saved === 'light') return saved;
       } catch(e) {}
       return 'light';
   }
   function setTheme(theme) {
       var t = theme === 'dark' ? 'dark' : 'light';
       var root = document.documentElement;
       root.setAttribute('data-bs-theme', t);
       root.setAttribute('data-startbar', t);
       try {
           localStorage.setItem('adminTheme', t);
       } catch(e) {}
       var btn = document.getElementById('themeToggleBtn');
       if (btn) {
           var icon = btn.querySelector('i');
           if (icon) {
               icon.classList.remove('ti-moon','ti-sun');
               if (t === 'dark') {
                   icon.classList.add('ti-sun');
               } else {
                   icon.classList.add('ti-moon');
               }
           }
       }
   }
   
   // Função para obter notificações lidas do localStorage
   function getReadNotifications() {
       const read = localStorage.getItem('readNotifications');
       return read ? JSON.parse(read) : [];
   }
   
   // Função para salvar notificação como lida
   function saveReadNotification(notificationId) {
       const read = getReadNotifications();
       if (!read.includes(notificationId)) {
           read.push(notificationId);
           localStorage.setItem('readNotifications', JSON.stringify(read));
       }
   }
   
   // Função para obter notificações não lidas por tipo
   function getUnreadByType(type) {
       const read = getReadNotifications();
       return allNotificationsByType[type].filter(notif => !read.includes(notif.id));
   }
   
   // Função para calcular total de notificações não lidas
   function getTotalUnread() {
       let total = 0;
       Object.keys(allNotificationsByType).forEach(type => {
           total += getUnreadByType(type).length;
       });
       return total;
   }
   
   // Função para trocar de aba
   function switchTab(tab, event) {
       // Prevenir fechamento do dropdown
       if (event) {
           event.stopPropagation();
           event.preventDefault();
       }
       
       currentTab = tab;
       
       // Atualizar classes das abas
       document.querySelectorAll('.notification-tab').forEach(t => {
           t.classList.remove('active');
       });
       document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
       
       // Renderizar conteúdo
       renderNotifications();
   }
   
   // Função para renderizar notificações
   function renderNotifications() {
       const unread = getUnreadByType(currentTab);
       const content = document.getElementById('notificationContent');
       const markSectionBtn = document.getElementById('markSectionBtn');
       const paginationControls = document.getElementById('paginationControls');
       
       // Atualizar badges
       updateBadges();
       
      if (unread.length === 0) {
          content.innerHTML = '<div class="notification-empty"><p class="mb-0 fw-medium">'+TB_T.none+'</p><small>'+TB_T.clean+'</small></div>';
           markSectionBtn.classList.add('d-none');
           paginationControls.classList.add('d-none');
           return;
       }
       
       markSectionBtn.classList.remove('d-none');
       
       // Calcular paginação
       const totalPages = Math.ceil(unread.length / itemsPerPage);
       const currentPage = currentPages[currentTab];
       const startIndex = (currentPage - 1) * itemsPerPage;
       const endIndex = startIndex + itemsPerPage;
       const pageItems = unread.slice(startIndex, endIndex);
       
       // Renderizar itens da página atual
       let html = '';
       pageItems.forEach(notif => {
           html += `
               <div class="notification-item" data-notification-id="${notif.id}" onclick="handleNotificationClick('${notif.id}', '${notif.link}')">
                   <button class="mark-read-btn btn btn-sm" onclick="event.stopPropagation(); markAsRead('${notif.id}')">
                           <i class="bi bi-check"></i>
                   </button>
                   <div class="d-flex">
                       <div class="notification-icon bg-${notif.cor}-subtle text-${notif.cor} me-3">
                           <i class="${notif.icone}"></i>
                       </div>
                       <div class="flex-grow-1">
                           <h6 class="mb-1 fw-semibold" style="font-size: 12px;">
                               ${notif.titulo}
                           </h6>
                           <p class="mb-1 text-muted" style="font-size: 11px;">
                               ${notif.mensagem}
                           </p>
                           <span class="notification-time">
                               <i class="bi bi-clock me-1"></i>${notif.tempo}
                           </span>
                       </div>
                   </div>
               </div>
           `;
       });
       
       content.innerHTML = html;
       
       // Atualizar paginação
       if (totalPages > 1) {
           paginationControls.classList.remove('d-none');
           document.getElementById('pageInfo').textContent = `${currentPage} / ${totalPages}`;
           document.getElementById('prevBtn').disabled = currentPage === 1;
           document.getElementById('nextBtn').disabled = currentPage === totalPages;
       } else {
           paginationControls.classList.add('d-none');
       }
   }
   
   // Funções de paginação
   function previousPage() {
       if (currentPages[currentTab] > 1) {
           currentPages[currentTab]--;
           renderNotifications();
       }
   }
   
   function nextPage() {
       const unread = getUnreadByType(currentTab);
       const totalPages = Math.ceil(unread.length / itemsPerPage);
       if (currentPages[currentTab] < totalPages) {
           currentPages[currentTab]++;
           renderNotifications();
       }
   }
   
   // Função para atualizar todos os badges
   function updateBadges() {
       const totalUnread = getTotalUnread();
       const mainBadge = document.getElementById('notificationBadge');
       const headerBadge = document.getElementById('headerBadge');
       
       // Badge principal
       if (totalUnread > 0) {
           mainBadge.textContent = totalUnread > 99 ? '99+' : totalUnread;
           mainBadge.classList.remove('d-none');
           headerBadge.textContent = totalUnread;
           headerBadge.classList.remove('d-none');
       } else {
           mainBadge.classList.add('d-none');
           headerBadge.classList.add('d-none');
       }
       
       // Badges das abas
       Object.keys(allNotificationsByType).forEach(type => {
           const count = getUnreadByType(type).length;
           const badge = document.getElementById(`badge-${type}`);
           if (count > 0) {
               badge.textContent = count > 99 ? '99+' : count;
               badge.classList.remove('d-none');
           } else {
               badge.classList.add('d-none');
           }
       });
   }
   
   // Função para marcar como lida
   function markAsRead(notificationId) {
       saveReadNotification(notificationId);
       const element = document.querySelector(`[data-notification-id="${notificationId}"]`);
       if (element) {
           element.style.transition = 'all 0.3s ease';
           element.style.opacity = '0';
           element.style.transform = 'translateX(20px)';
           setTimeout(() => {
               renderNotifications();
           }, 300);
       }
   }
   
   // Função para marcar toda a seção como lida
   function markSectionAsRead(event) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        
        const unread = getUnreadByType(currentTab);
        if (unread.length === 0) return;
        
        // Removi o confirm() - agora marca diretamente como lida
        unread.forEach(notif => {
            saveReadNotification(notif.id);
        });
        currentPages[currentTab] = 1;
        renderNotifications();
   }
   
   // Função para lidar com clique na notificação
   function handleNotificationClick(notificationId, link) {
       saveReadNotification(notificationId);
       window.location.href = link;
   }
   
   // Renderizar ao carregar
   document.addEventListener('DOMContentLoaded', function() {
       setTheme(getCurrentTheme());
       renderNotifications();
       
       // Prevenir fechamento do dropdown ao clicar dentro dele
       const dropdownMenu = document.querySelector('.notification-dropdown');
       if (dropdownMenu) {
           dropdownMenu.addEventListener('click', function(event) {
               event.stopPropagation();
           });
       }
       var themeBtn = document.getElementById('themeToggleBtn');
       if (themeBtn) {
           themeBtn.addEventListener('click', function(event) {
               event.preventDefault();
               var nextTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
               setTheme(nextTheme);
           });
       }
   });
   
   // Fechar dropdown ao clicar fora (mobile)
   document.addEventListener('click', function(event) {
       const dropdown = document.querySelector('.notification-dropdown');
       const btn = document.getElementById('notificationDropdownBtn');
       
       if (dropdown && !dropdown.contains(event.target) && !btn.contains(event.target)) {
           const bsDropdown = bootstrap.Dropdown.getInstance(btn);
           if (bsDropdown) {
               bsDropdown.hide();
           }
       }
   });
   
  
   <?php if ($igamewin_active): ?>
           const apiURL = "<?php echo $igamewin_url; ?>";
           const agentCode = "<?php echo $agent_code; ?>";
           const agentToken = "<?php echo $agent_token; ?>";
           const rtpSlider = document.getElementById('rtpSlider');
   
   if (rtpSlider) {
       rtpSlider.addEventListener('input', function() {
           const rtpValue = parseInt(this.value);
           document.getElementById('rtpValueDisplay').textContent = rtpValue + '%';
       });
   
       rtpSlider.addEventListener('change', function() {
           const rtpValue = parseInt(this.value);
           const data = {
               method: "control_rtp",
               agent_code: agentCode,
               agent_token: agentToken,
               rtp: rtpValue
           };
   
                   
           fetch('partials/updateRtp.php', {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/json'
               },
               body: JSON.stringify({ rtp: rtpValue })
           })
           .then(response => response.json())
           .then(json => {
               if(json.success){
                   //showToast('success', 'RTP alterado com sucesso!');
               } else {
                   //showToast('danger', 'Erro ao alterar RTP: ' + json.message);
               }
           })
           .catch(error => {
               //showToast('danger', 'Erro ao atualizar o banco de dados.');
           });
       });
   }
   <?php endif; ?>
   
</script>