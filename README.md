# 🎰 505bet — Plataforma de Apostas

> **Plataforma de apostas/cassino** com painel admin, API v1, QR Code PIX, roleta, luckyBet, afiliados e atendimento.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Status](https://img.shields.io/badge/status-privado-6e5494?style=for-the-badge)

---

## 🚀 Visão Geral

Plataforma **505bet** de apostas e cassino: front PHP com múltiplos temas
(`style_18`) e assets, API v1 (`api/v1/api.php` com `phpqrcode.php` para geração
de QR Code), callbacks de pagamento, roleta configurável
(`roulette_prizes_config.php`) e jogo **luckyBet**.

## ✨ Funcionalidades

| Módulo | Descrição |
|---|---|
| 🎰 **Cassino/Apostas** | `index.php`, `luckyBet/`, roleta configurável |
| 💳 **PIX** | `api/v1/phpqrcode.php` + `api/v1/api.php` |
| 🔔 **Callbacks** | `callback/`, `callbackpayment/` |
| 🛠️ **Admin** | `admin/` (administradores, afiliados, atendimento, alterarpainel) |
| 📊 **Dashboard** | `dash/` |
| 🎨 **Temas** | `style_18/`, `svg/`, `icons/`, assets |
| 🖼️ **Uploads** | `uploads/`, `images/`, `img/` |

## 🛠️ Stack

- **Backend:** PHP (API v1, callbacks)
- **Frontend:** HTML/CSS/JS + temas
- **Banco:** MySQL (`Banco de dados/`)

## ⚙️ Configuração

1. Importar o banco (`Banco de dados/`).
2. Configurar `config.php` e os arquivos de configuração (roleta, red envelope).

---

> 🔒 **Repositório privado.**