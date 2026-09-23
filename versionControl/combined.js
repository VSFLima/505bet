// Combined version of index.js, adAnalytics.js, and reload.css
// This reduces HTTP requests from 3 to 1 for better performance
// Note: progress.js is kept separate as it needs to be loaded dynamically

// First, inject the CSS styles
(function() {
  const css = `:root{--item-translateX:-100px}.boxButton+.circular,.boxButton+.circular+.fork,.boxButton,.box-item{position:absolute;top:200px;right:-20px}.boxButton{display:block;width:30px;height:30px;z-index:2;opacity:0;cursor:pointer}.boxButton+.circular{width:30px;height:30px;display:block;z-index:1;border-radius:50%;background-color:rgba(255,255,255,0.9);box-shadow:0 0 0 10px rgba(255,255,255,0.2);margin:10px;transition:all .5s;transform-origin:50% 50%}.boxButton:checked+.circular+.fork{width:30px;height:30px;display:block;z-index:1;border-radius:50%;background-color:rgba(255,255,255,0.9);box-shadow:0 0 0 10px rgba(255,255,255,0.2);margin:10px;transition:all .5s}.boxButton:checked+.circular+.fork::after,.boxButton:checked+.circular+.fork::before{content:"";width:30px;height:3px;display:block;z-index:1;border-radius:2.5px;transition:all .5s;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)}.boxButton:checked+.circular+.fork{transform:rotate(360deg)}.boxButton:checked+.circular+.fork::after{transform:translate(-50%,-50%) rotate(45deg)}.boxButton:checked+.circular+.fork::before{transform:translate(-50%,-50%) rotate(-45deg)}.boxButton:checked+.circular{width:0;height:0;border:0;box-shadow:none}.boxButton:checked~ul .box-item{opacity:1}.box-item:nth-child(1){transform:rotate(90deg)}.box-item:nth-child(2){transform:rotate(45deg)}.box-item:nth-child(3){transform:rotate(0deg)}.box-item:nth-child(4){transform:rotate(315deg)}.box-item:nth-child(5){transform:rotate(270deg)}.box-item:nth-child(1) a{transform:rotate(0deg)}.box-item:nth-child(2) a{transform:rotate(-45deg)}.box-item:nth-child(3) a{transform:rotate(0deg)}.box-item:nth-child(4) a{transform:rotate(45deg)}.box-item:nth-child(5) a{transform:rotate(90deg)}.boxButton1:checked~ul .box-item:nth-child(1){transform:rotate(45deg) translateX(var(--item-translateX))}.boxButton1:checked~ul .box-item:nth-child(2){transform:rotate(0deg) translateX(var(--item-translateX))}.boxButton1:checked~ul .box-item:nth-child(3){transform:rotate(-45deg) translateX(var(--item-translateX))}.boxButton:checked~ul .box-item a{pointer-events:auto}.boxButton+.circular::before{top:10px}.boxButton+.circular::after{top:-10px}.box-item{display:block;width:60px;height:60px;opacity:0;transition:.5s}.box-item a{display:block;width:inherit;height:inherit;line-height:60px;color:rgba(255,255,255,0.65);background-color:rgba(0,0,0,.6);border-radius:50%;text-align:center;text-decoration:none;font-size:30px;pointer-events:none;transition:.2s}.touming{opacity:.2}.xuanfu{position:absolute}.boxButton2:checked~ul .box-item:nth-child(1){transform:rotate(135deg) translateX(var(--item-translateX))}.boxButton2:checked~ul .box-item:nth-child(2){transform:rotate(180deg) translateX(var(--item-translateX))}.boxButton2:checked~ul .box-item:nth-child(3){transform:rotate(225deg) translateX(var(--item-translateX))}.box-button1:checked~ul .box-item:nth-child(4){transform:rotate(315deg) translateX(var(--item-translateX))}.box-button1:checked~ul .box-item:nth-child(5){transform:rotate(270deg) translateX(var(--item-translateX))}.rotate_svg{transform:rotate(360deg)}`;
  
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);
})();

// Floating refresh button functionality (from index.js)
(function () {
  window._refresh_btn = {};
  const uRLSearchParams = new URLSearchParams(window.location.search)
  const isFromSwCallDomain = uRLSearchParams.get('fromEntry') === 'sw';
  if (isFromSwCallDomain) {
    return;
  }
  const _html = `<div class="box xuanfu touming">
<input type="checkbox" class="boxButton " id="box-buttons" />
<label class="circular" for="box-buttons"></label>
<label class="fork" for="box-buttons"></label>
<ul>
  <li class="box-item">
    <a onclick="location.reload()" style="display: flex; align-items: center; justify-content: center;">
    <?xml version="1.0" encoding="utf-8"?><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools -->
<svg fill="#ccc" width="50px" height="50px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M27.1 14.313V5.396L24.158 8.34c-2.33-2.325-5.033-3.503-8.11-3.503C9.902 4.837 4.901 9.847 4.899 16c.001 6.152 5.003 11.158 11.15 11.16 4.276 0 9.369-2.227 10.836-8.478l.028-.122h-3.23l-.022.068c-1.078 3.242-4.138 5.421-7.613 5.421a8 8 0 0 1-5.691-2.359A7.993 7.993 0 0 1 8 16.001c0-4.438 3.611-8.049 8.05-8.049 2.069 0 3.638.58 5.924 2.573l-3.792 3.789H27.1z"/></svg>
    </a>
  </li>
</ul>
</div>`
  document.body.insertAdjacentHTML('beforeend', _html)
  const xuanfu = document.querySelector('.xuanfu')
  const boxButton = document.querySelector('.boxButton')
  // 判断父元素，是否有指定的类名
  function hasParentWithClassName(element, className) {
    while (element && element !== document.body) {
      if (element.classList.contains(className)) {
        return true;
      }
      element = element.parentElement;
    }
    return false;
  }
  //绑定事件触摸
  xuanfu.addEventListener('touchstart', function (e) {
    // 获取元素当前距离左侧的偏移量
    this.l = this.offsetLeft
    // 获取触摸开始时 触摸点距离左侧的偏移量
    this.x = e.targetTouches[0].clientX

    // 获取元素当前距离顶侧的偏移量
    this.t = this.offsetTop
    // 获取触摸开始时 触摸点距离顶侧的偏移量
    this.y = e.targetTouches[0].clientY

    // 使其不透明
    this.classList.remove('touming')
  }, { passive: true })
  xuanfu.addEventListener('touchmove', function (e) {
    // 获取触摸移动后,触摸点距离左侧的偏移
    this._x = e.targetTouches[0].clientX
    // 计算最终的left
    let newLeft = this._x - (this.x - this.l);
    // 判断
    if (newLeft <= 0) newLeft = 0
    if (newLeft >= document.documentElement.clientWidth - this.offsetWidth) {
      newLeft = document.documentElement.clientWidth - this.offsetWidth
    }

    // 获取触摸移动后,触摸点距离左侧的偏移
    this._y = e.targetTouches[0].clientY
    // 计算最终的left
    let newTop = this._y - (this.y - this.t);
    // 判断
    if (newTop <= 0) newTop = 0
    if (newTop >= document.documentElement.clientWidth - this.offsetHeight) {
      newTop = document.documentElement.clientWidth - this.offsetHeight
    }
    // 设置样式
    this.style.left = newLeft + 'px'
    this.style.top = newTop + 'px'
  }, { passive: true })
  function hideRefreshButton(e) {
    if (!hasParentWithClassName(e.target, 'xuanfu')) {
      xuanfu.querySelector('.boxButton').checked = false
    }
  }
  function listenerFloatBtn(e) {
    // 清空定时器
    clearTimeout(window._refresh_btn.timer1)
    clearTimeout(window._refresh_btn.timer2)
    // 增加过渡效果
    this.style.transition = 'none'

    // 吸附效果
    // 计算居中状态下left值
    this.middle = (document.documentElement.clientWidth - this.offsetWidth) / 2
    //计算
    this.left = this.offsetLeft

    // 声明变量 存放最终的left值
    let newLeft
    //判断
    if (this.left <= this.middle) {
      newLeft = 0
    } else {
      newLeft = document.documentElement.clientWidth - this.offsetWidth
    }
    // 设置
    this.style.left = newLeft + 'px'
    // 启动定时器,使其变成透明的
    window._refresh_btn.timer1 = setTimeout(() => {
      this.classList.add('touming')
      clearTimeout(window._refresh_btn.timer1)
    }, 6000)
    window._refresh_btn.timer2 = setTimeout(() => {
      this.querySelector('.boxButton').checked = false
      document.removeEventListener('touchstart', hideRefreshButton)
      clearTimeout(window._refresh_btn.timer2)
    }, 6000)
    document.addEventListener('touchstart', hideRefreshButton)
  }
  xuanfu.addEventListener('touchend', listenerFloatBtn)
  xuanfu.addEventListener('click', listenerFloatBtn)
  window.addEventListener('beforeunload', function (e) {
    document.removeEventListener('touchstart', hideRefreshButton)
    xuanfu.removeEventListener('click', listenerFloatBtn)
    xuanfu.removeEventListener('touchend', listenerFloatBtn)
  });
  boxButton.addEventListener('click', function (e) {
    let res = (document.documentElement.clientWidth) / 2
    if (e.clientX <= res) {
      this.classList.add('boxButton2')
    } else {
      this.classList.remove('boxButton2')
      this.classList.add('boxButton1')
    }
  })
}());

// Ad Analytics functionality (from adAnalytics.js)
(function() {
  const keys = ['kwaiId', 'fbPixelId', 'ttPixelId', 'ch', 'sdmode', 'bgPixel', 'gtagId', 'test', 'tt_test_id', 'ttclid', 'afId', 'mgSkyPixelId', 'device_id', 'adid'];
    // 优化解析URL参数
  const urlParams = new URLSearchParams(window.location.search);
  keys.forEach(key => {
    if (urlParams.has(key)) {
      window[key] = localStorage[key] || urlParams.get(key);  // 优先去拿用户首次绑定过的广告平台id
    }
  });


  const analyticsLoaders = {
    kwaiId: loadKwaiAnalytics,
    fbPixelId: loadFacebookAnalytics,
    ttPixelId: loadTiktokAnalytics,
    bgPixel: loadBigoAnalytics,
    gtagId: loadGoogleAnalytics,
    mgSkyPixelId: loadMGSkyAnalytics
  };

  Object.keys(analyticsLoaders).forEach(key => {
    const value = urlParams.get(key);
    if (value) {
      analyticsLoaders[key](value);
    }
  });

  function loadKwaiAnalytics(key) {
    !function(e,t){"object"==typeof exports&&"object"==typeof module?module.exports=t():"function"==typeof define&&define.amd?define([],t):"object"==typeof exports?exports.install=t():e.install=t()}("undefined"!=typeof window?window:self,(function(){return function(e){var t={};function n(o){if(t[o])return t[o].exports;var r=t[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,n),r.l=!0,r.exports}return n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)n.d(o,r,function(t){return e[t]}.bind(null,r));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=72)}({72:function(e,t,n){"use strict";var o=this&&this.__spreadArray||function(e,t,n){if(n||2===arguments.length)for(var o,r=0,i=t.length;r<i;r++)!o&&r in t||(o||(o=Array.prototype.slice.call(t,0,r)),o[r]=t[r]);return e.concat(o||Array.prototype.slice.call(t))};Object.defineProperty(t,"__esModule",{value:!0});var r=function(e,t,n){var o,i=e.createElement("script");i.type="text/javascript",i.async=!0,i.src=t,n&&(i.onerror=function(){r(e,n)});var a=e.getElementsByTagName("script")[0];null===(o=a.parentNode)||void 0===o||o.insertBefore(i,a)};!function(e,t,n){e.KwaiAnalyticsObject=n;var i=e[n]=e[n]||[];i.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];var a=function(e,t){e[t]=function(){for(var n=[],r=0;r<arguments.length;r++)n[r]=arguments[r];var i=o([t],n,!0);e.push(i)}};i.methods.forEach((function(e){a(i,e)})),i.instance=function(e){var t,n=(null===(t=i._i)||void 0===t?void 0:t[e])||[];return i.methods.forEach((function(e){a(n,e)})),n},i.load=function(e,o){var a="https://s21-def.ap4r.com/kos/s101/nlav112572/pixel/events.js";i._i=i._i||{},i._i[e]=[],i._i[e]._u=a,i._t=i._t||{},i._t[e]=+new Date,i._o=i._o||{},i._o[e]=o||{};var c="?sdkid=".concat(e,"&lib=").concat(n);r(t,a+c,"https://s21-def.ks-la.net/kos/s101/nlav112572/pixel/events.js"+c)}}(window,document,"kwaiq")}})}));
    try {
      kwaiq.load(key);
      kwaiq.page();
    } catch (error) {
      console.log(error);
    }
  }

  function loadBigoAnalytics(key) {
    window.bgdataLayer = window.bgdataLayer || [];
    function bge() { window.bgdataLayer.push(arguments); }
    bge('init', key);
    (function (b, e, v, n, t, s) {
      t = b.createElement(e);
      t.async = !0;
      t.src = v;
      s = b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t, s)
    }(document, 'script', 'https://api.imotech.video/ad/events.js?pixel_id=' + key));
  }

  function loadTiktokAnalytics(key) {
    (function (w, d, t) {
      w.TiktokAnalyticsObject = t; var ttq = w[t] = w[t] || []; ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias", "group", "enableCookie", "disableCookie"], ttq.setAndDefer = function (t, e) { t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))) } }; for (var i = 0; i < ttq.methods.length; i++)ttq.setAndDefer(ttq, ttq.methods[i]); ttq.instance = function (t) { for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++)ttq.setAndDefer(e, ttq.methods[n]); return e }, ttq.load = function (e, n) { var i = "https://analytics.tiktok.com/i18n/pixel/events.js"; ttq._i = ttq._i || {}, ttq._i[e] = [], ttq._i[e]._u = i, ttq._t = ttq._t || {}, ttq._t[e] = +new Date, ttq._o = ttq._o || {}, ttq._o[e] = n || {}; var o = document.createElement("script"); o.type = "text/javascript", o.async = !0, o.src = i + "?sdkid=" + e + "&lib=" + t; var a = document.getElementsByTagName("script")[0]; a.parentNode.insertBefore(o, a) };
      ttq.load(key);
      ttq.page();
    }(window, document, 'ttq'));
  }

  function loadFacebookAnalytics(key) {
    (function (f, b, e, v, n, t, s) {
      if (f.fbq) return; n = f.fbq = function () {
        n.callMethod ?
          n.callMethod.apply(n, arguments) : n.queue.push(arguments)
      };
      if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
      n.queue = []; t = b.createElement(e); t.async = !0;
      t.src = v; s = b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t, s)
    }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js'));
    fbq('init', key + '');
    fbq('track', 'PageView');
  }

  function loadGoogleAnalytics(key) {
    (function (w, d, s, l, i) {
      w[l] = w[l] || []; w[l].push({
        'gtm.start':
          new Date().getTime(), event: 'gtm.js'
      }); var f = d.getElementsByTagName(s)[0],
        j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
          'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', key);
  }

  function loadMGSkyAnalytics(key) {
    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = `https://s.mgskyads.com/js/tag.js?aa=${key}`;
    document.head.appendChild(script);
  }
})();