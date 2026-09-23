<?php include_once __DIR__ . '/../l.php'; ?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="light" data-bs-theme="light">
<script>
(function(){
var storedTheme=null;
try{storedTheme=localStorage.getItem('adminTheme');}catch(e){}
var theme='light';
if(storedTheme==='dark') theme='dark';
var root=document.documentElement;
root.setAttribute('data-bs-theme',theme);
root.setAttribute('data-startbar',theme);
if(storedTheme!==theme){
try{localStorage.setItem('adminTheme',theme);}catch(e){}
}
})();
</script>