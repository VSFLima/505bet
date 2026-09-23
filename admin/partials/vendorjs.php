<script src="assets/libs/jquery/dist/jquery.min.js"></script>
<script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/sidebarmenu.js"></script>
<script src="assets/libs/apexcharts/dist/apexcharts.min.js"></script>
<script src="assets/libs/simplebar/dist/simplebar.js"></script>
<style>
.toast.pro{border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.15)}
.toast.pro .toast-body{display:flex;align-items:center;gap:.5rem}
.toast.pro .toast-icon{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:rgba(0,0,0,.06)}
.toast.pro.success{border-left:4px solid var(--bs-success)}
.toast.pro.danger{border-left:4px solid var(--bs-danger)}
.toast.pro.info{border-left:4px solid var(--bs-primary)}
.toast.pro.warning{border-left:4px solid var(--bs-warning)}
</style>
<script>
(function(){
if(!window.ensureToastContainer){
window.ensureToastContainer=function(){
var c=document.getElementById('toastContainer');
if(!c){
c=document.createElement('div');
c.id='toastContainer';
c.className='toast-container position-fixed top-0 end-0 p-3';
c.style.zIndex='9999';
document.body.appendChild(c);
}
return c;
};
}
window.showToast=function(a,b){
var content='';var type='info';function nt(t){t=(t||'').toString().toLowerCase();if(t==='error'||t==='erro')return'danger';if(t==='sucesso')return'success';if(t==='aviso')return'warning';if(t==='success'||t==='danger'||t==='warning'||t==='info')return t;return'info';}
if(typeof a==='string'&&a){if(/^(success|danger|warning|info|error|erro|sucesso|aviso)$/i.test(a)){type=nt(a);content=b||'';}else{content=a||'';type=nt(b);}}else{content=a||'';type=nt(b);}
var container=window.ensureToastContainer();
var el=document.createElement('div');
el.className='toast pro align-items-center text-bg-light '+type;
el.setAttribute('role','alert');
el.setAttribute('aria-live','assertive');
el.setAttribute('aria-atomic','true');
var icon='ti ti-info-circle text-primary';
if(type==='success')icon='ti ti-check text-success';
if(type==='danger')icon='ti ti-x text-danger';
if(type==='warning')icon='ti ti-alert-triangle text-warning';
el.innerHTML='<div class="d-flex w-100"><div class="toast-body"><span class="toast-icon"><i class="'+icon+'"></i></span><span>'+content+'</span></div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
container.appendChild(el);
var t=new bootstrap.Toast(el,{delay:5000,autohide:true});t.show();
};
})();
</script>
