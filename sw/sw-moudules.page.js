const pageHtml = `<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    :root {
      --bg-color: #0c1a2a;
      --loader-btn-color: #4dabf7;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      margin: 0;
      padding: 0;
      background-color: var(--bg-color);
      min-height: 100vh;
      overflow: hidden;
    }

    #app {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      box-sizing: border-box;
      background: var(--bg-color);
      overflow: hidden;
    }

    #main {
      display: none;
      width: 100%;
      height: 100%;
      box-sizing: border-box;
    }

    #loading {
      display: block;
      width: 100%;
      height: 100%;
      background: var(--bg-color);
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .loaders {
      width: 30px;
      aspect-ratio: 1;
      border-radius: 50%;
      border: 3px solid var(--loader-btn-color);
      animation:
        l20-1 0.8s infinite linear alternate,
        l20-2 1.6s infinite linear;
    }

    @keyframes l20-1 {
      0% {
        clip-path: polygon(50% 50%, 0 0, 50% 0%, 50% 0%, 50% 0%, 50% 0%, 50% 0%)
      }

      12.5% {
        clip-path: polygon(50% 50%, 0 0, 50% 0%, 100% 0%, 100% 0%, 100% 0%, 100% 0%)
      }

      25% {
        clip-path: polygon(50% 50%, 0 0, 50% 0%, 100% 0%, 100% 100%, 100% 100%, 100% 100%)
      }

      50% {
        clip-path: polygon(50% 50%, 0 0, 50% 0%, 100% 0%, 100% 100%, 50% 100%, 0% 100%)
      }

      62.5% {
        clip-path: polygon(50% 50%, 100% 0, 100% 0%, 100% 0%, 100% 100%, 50% 100%, 0% 100%)
      }

      75% {
        clip-path: polygon(50% 50%, 100% 100%, 100% 100%, 100% 100%, 100% 100%, 50% 100%, 0% 100%)
      }

      100% {
        clip-path: polygon(50% 50%, 50% 100%, 50% 100%, 50% 100%, 50% 100%, 50% 100%, 0% 100%)
      }
    }

    @keyframes l20-2 {
      0% {
        transform: scaleY(1) rotate(0deg)
      }

      49.99% {
        transform: scaleY(1) rotate(0deg)
      }

      50% {
        transform: scaleY(-1) rotate(0deg)
      }

      100% {
        transform: scaleY(-1) rotate(-90deg)
      }
    }
  </style>
</head>

<body>
  <div id="app">
    <div id="loading">
      <div class="loaders"></div>
    </div>
    <div id="main">
      <iframe id="iframe" src="" frameborder="0" style="width: 100%; height: 100%;"></iframe>
    </div>
  </div>
  <script>
    const iframe = document.getElementById('iframe');
    const loading = document.getElementById('loading');
    const main = document.getElementById('main');

    const urlParams = new URLSearchParams(window.location.search);
    const targetUrl = urlParams.get('url');

    if (targetUrl) {
      iframe.src = targetUrl;
      iframe.onload = () => {
        loading.style.display = 'none';
        main.style.display = 'block';
      };
    } else {
      loading.innerHTML = 'Error: No URL provided';
    }
  <\/script>
</body>

</html>`;
