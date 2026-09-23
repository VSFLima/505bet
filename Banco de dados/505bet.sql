-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 23/08/2026 às 20:51
-- Versão do servidor: 11.8.8-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u930079134_novo777`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `adicao_saldo`
--

CREATE TABLE `adicao_saldo` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `valor` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(255) DEFAULT NULL,
  `data_registro` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `adicao_saldo`
--

INSERT INTO `adicao_saldo` (`id`, `id_user`, `valor`, `tipo`, `data_registro`) VALUES
(2, 777209963, 1, 'register_reward', '2026-08-23 04:19:09'),
(3, 777209963, 1000, 'adicao', '2026-08-23 01:32:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `nome` text NOT NULL,
  `email` text NOT NULL,
  `contato` text DEFAULT NULL,
  `senha` text NOT NULL,
  `nivel` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `token_recover` text DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `2fa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `admin_users`
--

INSERT INTO `admin_users` (`id`, `nome`, `email`, `contato`, `senha`, `nivel`, `status`, `token_recover`, `avatar`, `2fa`) VALUES
(1, 'admin', 'admin@eu.com', NULL, '$2y$10$B/idjd5KIrD0xgh/WzMvNeIxtDPF59VS3e44.oqe/NKVWh/8HlmE6', 0, 1, NULL, NULL, '102030');

-- --------------------------------------------------------

--
-- Estrutura para tabela `afiliados_config`
--

CREATE TABLE `afiliados_config` (
  `id` int(11) NOT NULL,
  `cpaLvl1` decimal(10,2) DEFAULT NULL,
  `cpaLvl2` decimal(10,2) DEFAULT NULL,
  `cpaLvl3` decimal(10,2) DEFAULT NULL,
  `chanceCpa` decimal(5,2) DEFAULT NULL,
  `revShareFalso` decimal(5,2) DEFAULT NULL,
  `revShareLvl1` decimal(5,2) DEFAULT NULL,
  `revShareLvl2` decimal(5,2) DEFAULT NULL,
  `revShareLvl3` decimal(5,2) DEFAULT NULL,
  `minDepForCpa` decimal(10,2) DEFAULT NULL,
  `minResgate` decimal(10,2) DEFAULT NULL,
  `pagar_baus` tinyint(1) DEFAULT 1,
  `dep_on` int(11) DEFAULT 1,
  `bet_on` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `afiliados_config`
--

INSERT INTO `afiliados_config` (`id`, `cpaLvl1`, `cpaLvl2`, `cpaLvl3`, `chanceCpa`, `revShareFalso`, `revShareLvl1`, `revShareLvl2`, `revShareLvl3`, `minDepForCpa`, `minResgate`, `pagar_baus`, `dep_on`, `bet_on`) VALUES
(1, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30.00, 400.00, 0, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `assistance_rounds`
--

CREATE TABLE `assistance_rounds` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_amount` int(11) NOT NULL DEFAULT 0,
  `target_amount` int(11) NOT NULL DEFAULT 10000,
  `draw_count` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_flows`
--

CREATE TABLE `audit_flows` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `flow_multiple` decimal(10,2) DEFAULT 1.00,
  `need_flow` decimal(10,2) NOT NULL,
  `current_flow` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'notStarted',
  `flow_type` varchar(50) DEFAULT 'ACTIVITY',
  `activity_name` varchar(100) DEFAULT '',
  `release_setting` varchar(50) DEFAULT 'auto',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `aurenpay`
--

CREATE TABLE `aurenpay` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `client_id` text DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `atualizado` varchar(45) DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `aurenpay`
--

INSERT INTO `aurenpay` (`id`, `url`, `client_id`, `client_secret`, `atualizado`, `ativo`) VALUES
(1, '0', '0', '0', NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `banner`
--

CREATE TABLE `banner` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `img` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `type` varchar(50) DEFAULT 'lobby_carousel',
  `targetValue` text DEFAULT NULL,
  `defaultIconUrl` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `banner`
--

INSERT INTO `banner` (`id`, `titulo`, `criado_em`, `img`, `status`, `type`, `targetValue`, `defaultIconUrl`) VALUES
(1, 'Banner 1', '2024-06-28 21:10:47', '1787508310_1.png', 1, 'lobby_carousel', NULL, NULL),
(2, 'Banner 2', '2024-06-28 21:08:02', '1787508318_2.png', 1, 'lobby_carousel', NULL, NULL),
(3, 'Banner 3', '2024-06-28 21:08:02', '1787508328_3.png', 1, 'lobby_carousel', NULL, NULL),
(6, 'Banner Lobby 1', '2026-01-28 21:44:05', '1787508422_wrlc1767069171017914.jpg', 1, 'lobby_banner', NULL, NULL),
(7, 'Banner Lobby 2', '2026-01-28 21:44:05', '1787508352_354x100.gif', 1, 'lobby_banner', NULL, NULL),
(8, 'Banner Lobby 3', '2026-01-28 21:44:05', '1787508362_354x100-1.gif', 1, 'lobby_banner', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `bau`
--

CREATE TABLE `bau` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `num` text DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `is_get` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bspay`
--

CREATE TABLE `bspay` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `client_id` text DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `atualizado` varchar(45) DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL,
  `invite_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `bspay`
--

INSERT INTO `bspay` (`id`, `url`, `client_id`, `client_secret`, `atualizado`, `ativo`, `invite_code`) VALUES
(1, 'https://api.pixupbr.com', 'acct_live_fbxop97rijevlwwj', 'sk_live_YyAsHm8Oq2Kyfaj91O6tyVHt_Aa1!', NULL, 1, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cadastro_bonus_config`
--

CREATE TABLE `cadastro_bonus_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `valor_min` int(11) NOT NULL DEFAULT 100,
  `valor_max` int(11) NOT NULL DEFAULT 9880,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cadastro_bonus_config`
--

INSERT INTO `cadastro_bonus_config` (`id`, `active`, `valor_min`, `valor_max`, `updated_at`) VALUES
(1, 1, 50, 98, '2026-04-21 02:54:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `sender` enum('user','admin') NOT NULL,
  `agent_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `msg_type` enum('text','image','video','file') DEFAULT 'text',
  `file_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) DEFAULT NULL,
  `nome_site` text DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `grupoplataforma` varchar(255) DEFAULT NULL,
  `logo` text DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `download` varchar(255) DEFAULT NULL,
  `icone_download` varchar(255) DEFAULT NULL,
  `telegram` text DEFAULT NULL,
  `instagram` text DEFAULT NULL,
  `whatsapp` text DEFAULT NULL,
  `suporte` text DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `sublogo` text DEFAULT NULL,
  `facebookads` text DEFAULT NULL,
  `rodapelogo` text DEFAULT NULL,
  `favicon` text DEFAULT NULL,
  `googleAnalytics` text DEFAULT NULL,
  `minplay` int(11) DEFAULT NULL,
  `minsaque` double DEFAULT NULL,
  `maxsaque` int(11) DEFAULT 1000,
  `saque_automatico` int(11) NOT NULL,
  `rollover` int(11) DEFAULT NULL,
  `mindep` text DEFAULT NULL,
  `jackpot` int(11) DEFAULT NULL,
  `navbar` int(11) DEFAULT NULL,
  `numero_jackpot` int(11) DEFAULT NULL,
  `jackpot_custom` text DEFAULT NULL,
  `cor_padrao` varchar(45) NOT NULL,
  `background_padrao` varchar(50) DEFAULT NULL,
  `custom_css` longtext NOT NULL,
  `texto` varchar(45) NOT NULL,
  `img_seo` text DEFAULT NULL,
  `keyword` text DEFAULT NULL,
  `marquee` text DEFAULT NULL,
  `status_topheader` int(11) NOT NULL DEFAULT 0,
  `cor_topheader` varchar(48) DEFAULT '#ed1c24',
  `niveisbau` text DEFAULT NULL,
  `qntsbaus` int(11) DEFAULT NULL,
  `nvlbau` int(11) DEFAULT NULL,
  `pessoasbau` int(11) DEFAULT NULL,
  `tema` int(11) DEFAULT NULL,
  `versao_app_android` text DEFAULT NULL,
  `versao_app_ios` text DEFAULT NULL,
  `mensagem_app` text DEFAULT NULL,
  `link_app_android` text DEFAULT NULL,
  `link_app_ios` text DEFAULT NULL,
  `broadcast` text DEFAULT NULL,
  `limite_saque` int(11) DEFAULT 0,
  `sort_jackpot` int(11) DEFAULT 1,
  `carregamento_img` varchar(255) DEFAULT NULL,
  `imagem_fundo` text DEFAULT NULL,
  `snow_flakes` text DEFAULT NULL,
  `painel_rolante` text DEFAULT NULL,
  `atendimento` text DEFAULT NULL,
  `jackpot_ativado` int(11) NOT NULL DEFAULT 1,
  `limite_de_chaves` int(11) NOT NULL DEFAULT 1,
  `facebook` varchar(255) DEFAULT NULL,
  `baixar_ativado` int(11) DEFAULT NULL,
  `topIconColor` varchar(255) DEFAULT NULL,
  `topBgColor` varchar(255) DEFAULT NULL,
  `tema_popup_inicio` int(11) NOT NULL DEFAULT 1,
  `slogan` text DEFAULT NULL,
  `comissao_gerente` varchar(255) DEFAULT NULL,
  `google_client_id` varchar(255) DEFAULT NULL,
  `google_client_secret` varchar(255) DEFAULT NULL,
  `google_login_status` int(1) DEFAULT 0,
  `menu_navbar_ativo` tinyint(1) NOT NULL DEFAULT 1,
  `natal_theme_active` tinyint(1) DEFAULT 0,
  `oss_url` varchar(255) DEFAULT '',
  `LuckyWheel` int(11) DEFAULT 10000,
  `userRankSwitch` tinyint(1) NOT NULL DEFAULT 0,
  `language` varchar(10) DEFAULT 'pt-BR',
  `phoneCode` varchar(16) DEFAULT '+55',
  `currency` varchar(10) DEFAULT 'BRL',
  `timezone` varchar(64) DEFAULT 'Etc/GMT+3',
  `regionName` varchar(64) DEFAULT 'Brasil',
  `regionId` int(11) DEFAULT 1,
  `support_icon_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `config`
--

INSERT INTO `config` (`id`, `nome`, `nome_site`, `descricao`, `grupoplataforma`, `logo`, `avatar`, `download`, `icone_download`, `telegram`, `instagram`, `whatsapp`, `suporte`, `email`, `sublogo`, `facebookads`, `rodapelogo`, `favicon`, `googleAnalytics`, `minplay`, `minsaque`, `maxsaque`, `saque_automatico`, `rollover`, `mindep`, `jackpot`, `navbar`, `numero_jackpot`, `jackpot_custom`, `cor_padrao`, `background_padrao`, `custom_css`, `texto`, `img_seo`, `keyword`, `marquee`, `status_topheader`, `cor_topheader`, `niveisbau`, `qntsbaus`, `nvlbau`, `pessoasbau`, `tema`, `versao_app_android`, `versao_app_ios`, `mensagem_app`, `link_app_android`, `link_app_ios`, `broadcast`, `limite_saque`, `sort_jackpot`, `carregamento_img`, `imagem_fundo`, `snow_flakes`, `painel_rolante`, `atendimento`, `jackpot_ativado`, `limite_de_chaves`, `facebook`, `baixar_ativado`, `topIconColor`, `topBgColor`, `tema_popup_inicio`, `slogan`, `comissao_gerente`, `google_client_id`, `google_client_secret`, `google_login_status`, `menu_navbar_ativo`, `natal_theme_active`, `oss_url`, `LuckyWheel`, `userRankSwitch`, `language`, `phoneCode`, `currency`, `timezone`, `regionName`, `regionId`, `support_icon_active`) VALUES
(1, '505bet', '', 'Plataforma de entretenimento digital com sistema de sorteios e prêmios regulamentados.', 'Grupo W1', 'img_6a8b3882327f84.16998617.png', 'img_691a00e0a64526.45519973.png', 'img_69331174c51e88.69663002.avif', 'img_691a00e0a63db3.64271709.png', 'https://telegram.me/', 'https://instagram.com/', 'https://telegram.me/', 't', '', '', 'ID AQUI', NULL, 'img_6a8b386181acc0.32836062.png', 'ID AQUI', 1, 20, 2000, 75, 1, '10', 0, 1, 2, 'jackpot_692de48d801b4.avif', '#0096DD', '#010e24', '', '', '154504365733.png', 'tigrinho pagante, jogo estilo tigrinho, jogo tipo tigrinho que paga, chinesa pagante, slot pagante, slots online pagantes, plataforma pagante, plataforma que paga no pix, jogo pagante 2025, caça-níquel pagante, jogo de prêmio online, jogo que paga via pix, jogo que paga de verdade, site que paga no pix, jogos online que pagam, plataforma de prêmios, slots brasileiros pagantes, slots confiáveis, jogo parecido com tigrinho, tigrinho alternativo, jogo de prêmios instantâneos, jogo de celular que paga, app que paga via pix, jogo rápido que paga, ganhar dinheiro jogando, jogo de prêmios reais, plataforma de slots pagantes, slot da chinesa, chinesinha pagante, site de prêmios via pix, plataforma pix pagante, saque rápido pix, depósito via pix instantâneo, jogos virais que pagam, plataforma pixsorte, pixsorte pagante, pixsorte confiável, pixsorte funciona', '✈️✈️ Clique no canal:  Telegram📱📱Baixe o site oficial do APP:  67f.fun ❤️Bônus de primeiro depósito para novos usuários R$3777❤️🎁B6nus de convite: R$ 10 por pessoa🎁🎁Convide amigos, compartilhe e ganhe dinheiro! Comissao até 2%🎁Tempo de chuva do envelope: AS00H 15H 20H E22H🎁👉👉Passo a passo: Visite → Eventos/Promog6es → Indique um amigo → Receba/Recolha tudo.', 0, '#0096dd', '60', 92, 5, 1, 21, '1.0.0.1', '1.0.0.2', 'MENSAGEM POPUP', '#', '#', 'PHILLYPS LINDO', 10, 4, 'img_693312273cfea7.12146031.avif', 'img_67e3328b16f8f2.80383522.png', 'img_68f6ca9661a404.35196359.png', '', 'https://telegram.me/', 1, 5, 'https://instagram.com/', 0, '', '', 1, '<p><span style=\\\"color: #e03e2d;\\\">Cadastre-se e ganhe R$8.888</span><br><span style=\\\"color: #e03e2d;\\\">Convide outras pessoas e ganhe R$ 1 milh&atilde;o!</span></p>', '2', NULL, NULL, 0, 1, 0, '', 10000, 1, 'pt-BR', '+55', 'BRL', 'Etc/GMT+3', 'Brasil', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cupom`
--

CREATE TABLE `cupom` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `valor` int(11) NOT NULL,
  `range_valor` varchar(50) DEFAULT NULL,
  `qtd_insert` int(11) NOT NULL DEFAULT 0 COMMENT 'Quantidade inicial de cupons criados',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: inativo / 1: ativo',
  `qtd_original` int(11) NOT NULL DEFAULT 1,
  `data_expiracao` datetime DEFAULT NULL,
  `descricao` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cupom`
--

INSERT INTO `cupom` (`id`, `nome`, `valor`, `range_valor`, `qtd_insert`, `status`, `qtd_original`, `data_expiracao`, `descricao`) VALUES
(8, 'PROMO77', 0, '10.00,12.00', 10, 1, 1, NULL, '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cupom_usados`
--

CREATE TABLE `cupom_usados` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `codigo` varchar(100) DEFAULT NULL,
  `valor` int(11) NOT NULL COMMENT 'Valor do depósito que gerou o bônus',
  `bonus` int(11) NOT NULL DEFAULT 0 COMMENT 'Valor do bônus recebido',
  `data_registro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `customer_feedback`
--

CREATE TABLE `customer_feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` tinyint(4) NOT NULL,
  `content` text NOT NULL,
  `file_link` varchar(255) DEFAULT NULL,
  `source` varchar(50) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `reply` text DEFAULT NULL,
  `reply_time` datetime DEFAULT NULL,
  `reply_read` tinyint(1) DEFAULT 0,
  `reply_by` int(11) DEFAULT NULL,
  `bonus_amount` decimal(10,2) DEFAULT 0.00,
  `bonus_received` tinyint(1) DEFAULT 0,
  `bonus_received_at` datetime DEFAULT NULL,
  `client_time` bigint(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `drakon`
--

CREATE TABLE `drakon` (
  `id` int(11) NOT NULL,
  `agent_code` varchar(64) NOT NULL,
  `agent_token` varchar(128) NOT NULL,
  `agent_secret_key` varchar(128) NOT NULL,
  `api_base` varchar(255) NOT NULL DEFAULT 'https://gator.drakon.casino/api/v1',
  `ativo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `drakon`
--

INSERT INTO `drakon` (`id`, `agent_code`, `agent_token`, `agent_secret_key`, `api_base`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Agent Code', 'Agent Token', 'Agent Secret', 'https://gator.drakon.casino/api/v1', 0, '2025-11-13 16:19:19', '2026-01-08 22:32:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `expfypay`
--

CREATE TABLE `expfypay` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `client_id` text DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `atualizado` varchar(45) DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `expfypay`
--

INSERT INTO `expfypay` (`id`, `url`, `client_id`, `client_secret`, `atualizado`, `ativo`) VALUES
(1, 'https://pro.expfypay.com', '0', '0', '0', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `festival`
--

CREATE TABLE `festival` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `img` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `festival`
--

INSERT INTO `festival` (`id`, `titulo`, `criado_em`, `img`, `status`) VALUES
(1, 'Festival 1', '2025-03-25 20:58:37', '/holiday/14/apng_top_jr.png', 1),
(2, 'Festival 2', '2025-03-25 20:58:37', '/holiday/14/btn_zc1_jr.avif', 0),
(3, 'Festival 3', '2025-03-25 20:58:54', '/holiday/14/btn_zc1_jr2.avif', 1),
(4, 'Festival 4', '2025-03-25 20:59:06', '/holiday/14/h5_zs_jr.avif', 1),
(5, 'Festival 5', '2025-03-25 20:59:16', '/holiday/14/h5_zs_jr2.avif', 1),
(6, 'Festival 6', '2025-03-25 20:59:25', '/holiday/14/h5_zs_jr3.avif', 1),
(7, 'Festival 7', '2025-03-25 20:59:34', '/holiday/14/icon_btm_jr.avif', 1),
(8, 'Festival 8', '2025-03-25 20:59:47', '/holiday/14/icon_btm_jr2.avif', 1),
(9, 'Festival 9', '2025-03-25 20:59:59', '/holiday/14/icon_btm_jr3.avif', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `financeiro`
--

CREATE TABLE `financeiro` (
  `id` int(11) NOT NULL,
  `usuario` int(11) DEFAULT NULL,
  `saldo` decimal(10,2) DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT NULL,
  `saldo_afiliados` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `financeiro`
--

INSERT INTO `financeiro` (`id`, `usuario`, `saldo`, `bonus`, `saldo_afiliados`) VALUES
(92, 777209963, 0.00, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `floats`
--

CREATE TABLE `floats` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `redirect` text DEFAULT NULL,
  `tipo` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `img` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

--
-- Despejando dados para a tabela `floats`
--

INSERT INTO `floats` (`id`, `titulo`, `redirect`, `tipo`, `criado_em`, `img`, `status`) VALUES
(1, 'telegram', '...', 0, '2024-06-28 21:10:47', '1787509384_1742441129_ef272c3f-472c-46b4-9939-f88376e8c415.gif', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `game_code` text NOT NULL,
  `game_name` text NOT NULL,
  `banner` text DEFAULT NULL,
  `status` int(11) NOT NULL,
  `provider` text DEFAULT NULL,
  `popular` int(11) NOT NULL DEFAULT 0,
  `type` text DEFAULT NULL,
  `game_type` varchar(255) DEFAULT NULL,
  `api` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `games`
--

INSERT INTO `games` (`id`, `game_code`, `game_name`, `banner`, `status`, `provider`, `popular`, `type`, `game_type`, `api`) VALUES
(1, '126', 'Fortune Tiger', 'https://imagensfivers.com/Games/PG-Soft/PGS_126.webp', 1, 'PGSOFT', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(2, '98', 'Fortune Ox', 'https://imagensfivers.com/Games/PG-Soft/PGS_98.webp', 1, 'PGSOFT', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(3, '68', 'Fortune Mouse', 'https://imagensfivers.com/Games/PG-Soft/PGS_68.webp', 1, 'PGSOFT', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(4, '1543462', 'Fortune Rabbit', 'https://imagensfivers.com/Games/PG-Soft/PGS_1543462.webp', 1, 'PGSOFT', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(5, '69', 'Bikini Paradise', 'https://imagensfivers.com/Games/PG-Soft/PGS_69.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(6, '40', 'Jungle Delight', 'https://imagensfivers.com/Games/PG-Soft/PGS_40.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(7, '1695365', 'Fortune Dragon', 'https://imagensfivers.com/Games/PG-Soft/PGS_1695365.webp', 1, 'PGSOFT', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(8, '1738001', 'Chicky Run', 'https://imagensfivers.com/Games/PG-Soft/PGS_1738001.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(9, '42', 'Ganesha Gold', 'https://imagensfivers.com/Games/PG-Soft/PGS_42.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(10, '60', 'Leprechaun Riches', 'https://imagensfivers.com/Games/PG-Soft/PGS_60.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(11, '104', 'Wild Bandito', 'https://imagensfivers.com/Games/PG-Soft/PGS_104.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(12, '63', 'Dragon Tiger Luck', 'https://imagensfivers.com/Games/PG-Soft/PGS_63.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(13, '39', 'Piggy Gold', 'https://imagensfivers.com/Games/PG-Soft/PGS_39.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(14, '48', 'Double Fortune', 'https://imagensfivers.com/Games/PG-Soft/PGS_48.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(15, '53', 'The Great Icescape', 'https://imagensfivers.com/Games/PG-Soft/PGS_53.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(16, '57', 'Dragon Hatch', 'https://imagensfivers.com/Games/PG-Soft/PGS_57.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(17, '74', 'Mahjong Ways 2', 'https://imagensfivers.com/Games/PG-Soft/PGS_74.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(18, '82', 'Phoenix Rises', 'https://imagensfivers.com/Games/PG-Soft/PGS_82.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(19, '87', 'Treasures of Aztec', 'https://imagensfivers.com/Games/PG-Soft/PGS_87.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(20, '88', 'Jewels of Prosperity', 'https://imagensfivers.com/Games/PG-Soft/PGS_88.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(21, '115', 'Supermarket Spree', 'https://imagensfivers.com/Games/PG-Soft/PGS_115.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(22, '121', 'Destiny of Sun & Moon', 'https://imagensfivers.com/Games/PG-Soft/PGS_121.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(23, '123', 'Rooster Rumble', 'https://imagensfivers.com/Games/PG-Soft/PGS_123.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(24, '1402846', 'Midas Fortune', 'https://imagensfivers.com/Games/PG-Soft/PGS_1402846.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(25, '1451122', 'Dragon Hatch 2', 'https://imagensfivers.com/Games/PG-Soft/PGS_1451122.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(26, '1508783', 'Wild Ape #3258', 'https://imagensfivers.com/Games/PG-Soft/PGS_1508783.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(27, '1623475', 'Anubis Wrath', 'https://imagensfivers.com/Games/PG-Soft/PGS_1623475.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(28, '1635221', 'Zombie Outbreak', 'https://imagensfivers.com/Games/PG-Soft/PGS_1635221.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(29, '1682240', 'Cash Mania', 'https://imagensfivers.com/Games/PG-Soft/PGS_1682240.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(30, '1879752', 'Fortune Snake', 'https://imagensfivers.com/Games/PG-Soft/PGS_1879752.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(31, '1786529', 'Rio Fantasia', 'https://imagensfivers.com/Games/PG-Soft/PGS_1786529.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(32, '1727711', 'Three Crazy Piggies', 'https://imagensfivers.com/Games/PG-Soft/PGS_1727711.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(33, '1804577', 'Graffiti Rush', 'https://imagensfivers.com/Games/PG-Soft/PGS_1804577.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(34, '1492288', 'Pinata Wins', 'https://imagensfivers.com/Games/PG-Soft/PGS_1492288.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(35, '1827457', 'Doomsday Rampage', 'https://imagensfivers.com/Games/PG-Soft/PGS_1827457.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(36, '1799745', 'Mr. Treasure’s Fortune', 'https://imagensfivers.com/Games/PG-Soft/PGS_1799745.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(37, '1368367', 'Alchemy Gold', 'https://imagensfivers.com/Games/PG-Soft/PGS_1368367.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(38, '1340277', 'Asgardian Rising', 'https://imagensfivers.com/Games/PG-Soft/PGS_1340277.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(39, '1418544', 'Bakery Bonanza', 'https://imagensfivers.com/Games/PG-Soft/PGS_1418544.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(40, '94', 'Bali Vacation', 'https://imagensfivers.com/Games/PG-Soft/PGS_94.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(41, '124', 'Battleground Royale', 'https://imagensfivers.com/Games/PG-Soft/PGS_124.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(42, '125', 'Butterfly Blossom', 'https://imagensfivers.com/Games/PG-Soft/PGS_125.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(43, '108', 'Buffalo Win', 'https://imagensfivers.com/Games/PG-Soft/PGS_108.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(44, '100', 'Candy Bonanza', 'https://imagensfivers.com/Games/PG-Soft/PGS_100.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(45, '70', 'Candy Burst', 'https://imagensfivers.com/Games/PG-Soft/PGS_70.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(46, '54', 'Captain\'s Bounty', 'https://imagensfivers.com/Games/PG-Soft/PGS_54.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(47, '1666445', 'Chocolate Deluxe', 'https://imagensfivers.com/Games/PG-Soft/PGS_1666445.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(48, '117', 'Cocktail Nights', 'https://imagensfivers.com/Games/PG-Soft/PGS_117.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(49, '1473388', 'Cruise Royale', 'https://imagensfivers.com/Games/PG-Soft/PGS_1473388.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(51, '103', 'Crypto Gold', 'https://imagensfivers.com/Games/PG-Soft/PGS_103.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(53, '29', 'Dragon Legend', 'https://imagensfivers.com/Games/PG-Soft/PGS_29.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(54, '79', 'Dreams of Macau', 'https://imagensfivers.com/Games/PG-Soft/PGS_79.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(56, '44', 'Emperor\'s Favour', 'https://imagensfivers.com/Games/PG-Soft/PGS_44.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(57, '61', 'Flirting Scholar', 'https://imagensfivers.com/Games/PG-Soft/PGS_61.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(59, '3', 'Fortune Gods', 'https://imagensfivers.com/Games/PG-Soft/PGS_3.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(60, '1397455', 'Fruity Candy', 'https://imagensfivers.com/Games/PG-Soft/PGS_1397455.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(61, '86', 'Galactic Gems', 'https://imagensfivers.com/Games/PG-Soft/PGS_86.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(62, '75', 'Ganesha Fortune', 'https://imagensfivers.com/Games/PG-Soft/PGS_75.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(63, '122', 'Garuda Gems', 'https://imagensfivers.com/Games/PG-Soft/PGS_122.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(64, '91', 'Guardians of Ice & Fire', 'https://imagensfivers.com/Games/PG-Soft/PGS_91.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(65, '1702123', 'Geisha\'s Revenge', 'https://imagensfivers.com/Games/PG-Soft/PGS_1702123.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(66, '62', 'Gem Saviour Conquest', 'https://imagensfivers.com/Games/PG-Soft/PGS_62.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(67, '38', 'Gem Saviour Sword', 'https://imagensfivers.com/Games/PG-Soft/PGS_38.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(68, '1671262', 'Gemstones Gold', 'https://imagensfivers.com/Games/PG-Soft/PGS_1671262.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(69, '1572362', 'Gladiator\'s Glory', 'https://imagensfivers.com/Games/PG-Soft/PGS_1572362.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(70, '1381200', 'Hawaiian Tiki', 'https://imagensfivers.com/Games/PG-Soft/PGS_1381200.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(71, '105', 'Heist Stakes', 'https://imagensfivers.com/Games/PG-Soft/PGS_105.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(72, '33', 'Hip Hop Panda', 'https://imagensfivers.com/Games/PG-Soft/PGS_33.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(74, '97', 'Jack Frost\'s Winter', 'https://imagensfivers.com/Games/PG-Soft/PGS_97.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(75, '50', 'Journey to the Wealth', 'https://imagensfivers.com/Games/PG-Soft/PGS_50.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(76, '110', 'Jurassic Kingdom', 'https://imagensfivers.com/Games/PG-Soft/PGS_110.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(77, '34', 'Legend of Hou Yi', 'https://imagensfivers.com/Games/PG-Soft/PGS_34.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(78, '128', 'Legend of Perseus', 'https://imagensfivers.com/Games/PG-Soft/PGS_128.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(79, '107', 'Legendary Monkey King', 'https://imagensfivers.com/Games/PG-Soft/PGS_107.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(80, '1601012', 'Lucky Clover Riches', 'https://imagensfivers.com/Games/PG-Soft/PGS_1601012.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(81, '89', 'Lucky Neko', 'https://imagensfivers.com/Games/PG-Soft/PGS_89.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(82, '130', 'Lucky Piggy', 'https://imagensfivers.com/Games/PG-Soft/PGS_130.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(83, '1580541', 'Mafia Mayhem', 'https://imagensfivers.com/Games/PG-Soft/PGS_1580541.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(84, '65', 'Mahjong Ways', 'https://imagensfivers.com/Games/PG-Soft/PGS_65.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(85, '95', 'Majestic Treasures', 'https://imagensfivers.com/Games/PG-Soft/PGS_95.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(86, '118', 'Mask Carnival', 'https://imagensfivers.com/Games/PG-Soft/PGS_118.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(87, '7', 'Medusa', 'https://imagensfivers.com/Games/PG-Soft/PGS_7.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(88, '102', 'Mermaid Riches', 'https://imagensfivers.com/Games/PG-Soft/PGS_102.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(89, '35', 'Mr. Hallow-Win', 'https://imagensfivers.com/Games/PG-Soft/PGS_35.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(90, '64', 'Muay Thai Champion', 'https://imagensfivers.com/Games/PG-Soft/PGS_64.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(91, '1755623', 'Museum Mystery', 'https://imagensfivers.com/Games/PG-Soft/PGS_1755623.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(92, '1432733', 'Mystical Spirits', 'https://imagensfivers.com/Games/PG-Soft/PGS_1432733.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(93, '1529867', 'Ninja Raccoon Frenzy', 'https://imagensfivers.com/Games/PG-Soft/PGS_1529867.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(94, '59', 'Ninja vs Samurai', 'https://imagensfivers.com/Games/PG-Soft/PGS_59.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(95, '1815268', 'Oishi Delights', 'https://imagensfivers.com/Games/PG-Soft/PGS_1815268.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(96, '93', 'Opera Dynasty', 'https://imagensfivers.com/Games/PG-Soft/PGS_93.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(97, '112', 'Oriental Prosperity', 'https://imagensfivers.com/Games/PG-Soft/PGS_112.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(98, '25', 'Plushie Frenzy', 'https://imagensfivers.com/Games/PG-Soft/PGS_25.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(99, '1312883', 'Prosperity Fortune Tree', 'https://imagensfivers.com/Games/PG-Soft/PGS_1312883.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(100, '36', 'Prosperity Lion', 'https://imagensfivers.com/Games/PG-Soft/PGS_36.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(101, '120', 'The Queen\'s Banquet', 'https://imagensfivers.com/Games/PG-Soft/PGS_120.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(102, '1420892', 'Rave Party Fever', 'https://imagensfivers.com/Games/PG-Soft/PGS_1420892.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(103, '20', 'Reel Love', 'https://imagensfivers.com/Games/PG-Soft/PGS_20.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(104, '1594259', 'Safari Wilds', 'https://imagensfivers.com/Games/PG-Soft/PGS_1594259.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(105, '37', 'Santa\'s Gift Rush', 'https://imagensfivers.com/Games/PG-Soft/PGS_37.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(106, '1648578', 'Shark Bounty', 'https://imagensfivers.com/Games/PG-Soft/PGS_1648578.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(107, '1448762', 'Songkran Splash', 'https://imagensfivers.com/Games/PG-Soft/PGS_1448762.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(108, '127', 'Speed Winner', 'https://imagensfivers.com/Games/PG-Soft/PGS_127.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(109, '119', 'Spirited Wonders', 'https://imagensfivers.com/Games/PG-Soft/PGS_119.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(110, '1513328', 'Super Golf Drive', 'https://imagensfivers.com/Games/PG-Soft/PGS_1513328.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(111, '92', 'Thai River Wonders', 'https://imagensfivers.com/Games/PG-Soft/PGS_92.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(112, '1338274', 'Totem Wonders', 'https://imagensfivers.com/Games/PG-Soft/PGS_1338274.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(113, '1655268', 'Tsar Treasures', 'https://imagensfivers.com/Games/PG-Soft/PGS_1655268.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(114, '1489936', 'Ultimate Striker', 'https://imagensfivers.com/Games/PG-Soft/PGS_1489936.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(115, '58', 'Vampire\'s Charm', 'https://imagensfivers.com/Games/PG-Soft/PGS_58.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(116, '106', 'Ways of the Qilin', 'https://imagensfivers.com/Games/PG-Soft/PGS_106.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(117, '1615454', 'WereWolf\'s Hunt', 'https://imagensfivers.com/Games/PG-Soft/PGS_1615454.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(118, '135', 'Wild Bounty Showdown', 'https://imagensfivers.com/Games/PG-Soft/PGS_135.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(120, '83', 'Wild Fireworks', 'https://imagensfivers.com/Games/PG-Soft/PGS_83.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(121, '1568554', 'Wild Heist Cashout', 'https://imagensfivers.com/Games/PG-Soft/PGS_1568554.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(122, '129', 'Win Win Fish Prawn Crab', 'https://imagensfivers.com/Games/PG-Soft/PGS_129.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(123, '1747549', 'Wings of Iguazu', 'https://imagensfivers.com/Games/PG-Soft/PGS_1747549.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(124, '1760238', 'Yakuza Honor', 'https://imagensfivers.com/Games/PG-Soft/PGS_1760238.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(125, '1940257', 'Alibaba\'s Cave of Fortune', 'https://imagensfivers.com/Games/PG-Soft/PGS_1940257.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(126, '2012025', 'Skylight Wonders', 'https://imagensfivers.com/Games/PG-Soft/PGS_2012025.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(127, '1964781', 'Pharaoh Royals', 'https://imagensfivers.com/Games/PG-Soft/PGS_1964781.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(128, '1929177', 'Kraken Gold Rush', 'https://imagensfivers.com/Games/PG-Soft/PGS_1929177.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(129, '1971587', 'Majestic Empire', 'https://imagensfivers.com/Games/PG-Soft/PGS_1971587.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(130, '1903012', 'Grimms\' Bounty: Hansel & Gretel', 'https://imagensfivers.com/Games/PG-Soft/PGS_1903012.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(131, '1918451', 'Galaxy Miner', 'https://imagensfivers.com/Games/PG-Soft/PGS_1918451.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(132, '1897678', 'Dragon\'s Treasure Quest', 'https://imagensfivers.com/Games/PG-Soft/PGS_1897678.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(133, '1935269', 'Diner Frenzy Spins', 'https://imagensfivers.com/Games/PG-Soft/PGS_1935269.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(134, '1834850', 'Jack the Giant Hunter', 'https://imagensfivers.com/Games/PG-Soft/PGS_1834850.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(135, '1881268', 'Knockout Riches', 'https://imagensfivers.com/Games/PG-Soft/PGS_1881268.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(137, '2075272', 'Zombie Blasters', 'https://imagensfivers.com/Games/PG-Soft/PGS_2075272.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(138, '2040102', 'Super Wildrix', 'https://imagensfivers.com/Games/PG-Soft/PGS_2040102.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(139, '2081892', 'Mighty Mania', 'https://imagensfivers.com/Games/PG-Soft/PGS_2081892.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(140, '2058347', 'Reel Royale Showdown', 'https://imagensfivers.com/Games/PG-Soft/PGS_2058347.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(141, '2035783', 'Funky Fortunez', 'https://imagensfivers.com/Games/PG-Soft/PGS_2035783.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(142, '2024510', 'Perfect Strike', 'https://imagensfivers.com/Games/PG-Soft/PGS_2024510.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(143, '1997301', 'Mayan Destiny', 'https://imagensfivers.com/Games/PG-Soft/PGS_1997301.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(144, '1950910', 'Inferno Mayhem', 'https://imagensfivers.com/Games/PG-Soft/PGS_1950910.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(145, '1981965', 'Forbidden Alchemy', 'https://imagensfivers.com/Games/PG-Soft/PGS_1981965.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(146, '1849515', 'Mythical Guardians', 'https://imagensfivers.com/Games/PG-Soft/PGS_1849515.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(147, '2009635', 'Poker Kingdom Win', 'https://imagensfivers.com/Games/PG-Soft/PGS_2009635.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(149, '1778752', 'Futebol Fever', 'https://imagensfivers.com/Games/PG-Soft/PGS_1778752.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(150, '2100928', 'Fortune Horse', 'https://imagensfivers.com/Games/PG-Soft/PGS_2100928.webp', 1, 'PGSOFT', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(151, 'vs10txbigbass', 'Big Bass Splash', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10txbigbass.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(152, 'vs20sugarrush', 'Sugar Rush', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sugarrush.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(153, 'vs9aztecgemsdx', 'Aztec Gems Deluxe', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs9aztecgemsdx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(154, 'vs5joker', 'Joker s Jewels', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5joker.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(155, 'vs1masterjoker', 'Master Joker', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1masterjoker.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(156, 'vs10bbbonanza', 'Big Bass Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbbonanza.webp', 1, 'PRAGMATIC', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(157, 'vs25mustang', 'Mustang Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25mustang.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(158, 'vs20hburnhs', 'Hot to Burn Hold and Spin', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20hburnhs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(159, 'vs20olympgate', 'Gates of Olympus', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olympgate.webp', 1, 'PRAGMATIC', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(160, 'vs20fruitsw', 'Sweet Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20fruitsw.webp', 1, 'PRAGMATIC', 1, 'slot', 'ELECTRONIC', 'playfiver'),
(161, 'vs10bxmasbnza', 'Christmas Big Bass Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bxmasbnza.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(162, 'vswaysbbb', 'Big Bass Bonanza Megaways(tm)', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbbb.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(163, 'vs5triple8gold', 'X888 Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5triple8gold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(164, 'vs5jokerdice', 'Joker Jewels Dice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5jokerdice.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(165, 'vs100firehot', 'Fire Hot 100', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs100firehot.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(166, 'vs100sh', 'Shining Hot 100', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs100sh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(167, 'vs1024dtiger', 'The Dragon Tiger', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024dtiger.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(168, 'vs1024fortune', 'Fortune Ace', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024fortune.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(169, 'vs1024gmayhem', 'Gorilla Mayhem', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024gmayhem.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(170, 'vs1024lionsd', '5 Lions Dance', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024lionsd.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(171, 'vs1024mahjpanda', 'Mahjong Panda', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024mahjpanda.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(172, 'vs1024mahjwins', 'Mahjong Wins', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024mahjwins.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(173, 'vs1024mjwinbns', 'Mahjong Wins Bonus', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024mjwinbns.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(174, 'vs1024moonsh', 'Moonshot', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024moonsh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(175, 'vs1024temuj', 'Temujin Treasures', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1024temuj.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(176, 'vs10amm', 'Amazing Money Machine', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10amm.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(177, 'vs10bbbbrnd', 'Big Bass Boxing Bonus Round', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbbbrnd.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(178, 'vs10bbbnz', 'Big Bass Day at the Races', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbbnz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(179, 'vs10bbdice', 'Big Bass Dice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbdice.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(180, 'vs10bbdoubled', 'Big Bass Vegas Double Down Deluxe', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbdoubled.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(181, 'vs10bbextreme', 'Big Bass Amazon Xtreme', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbextreme.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(182, 'vs10bbfmission', 'Big Bass Mission Fishin’', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbfmission.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(183, 'vs10bbhas', 'Big Bass Bonanza - Hold & Spinner', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbhas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(184, 'vs10bbkir', 'Big Bass Bonanza Keeping it Reel', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbkir.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(185, 'vs10bblotgl', 'Big Bass Secrets of the Golden Lake', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bblotgl.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(186, 'vs10bblpop', 'Bubble Pop', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bblpop.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(187, 'vs10bbrrp', 'Big Bass Reel Repeat', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbrrp.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(188, 'vs10bbrttr', 'Big Bass Return to the Races', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbrttr.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(189, 'vs10bbsplxmas', 'Big Bass Christmas Bash', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbsplxmas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(190, 'vs10bbxext', 'Big Bass Xmas Xtreme', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bbxext.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(191, 'vs10bhallbnza', 'Big Bass Halloween', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bhallbnza.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(192, 'vs10bhallbnza2', 'Big Bass Halloween 2', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bhallbnza2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(193, 'vs10bookazteck', 'Book of Aztec King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bookazteck.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(194, 'vs10bookfallen', 'Book of Fallen', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bookfallen.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(195, 'vs10bookoftut', 'John Hunter and the Book of Tut', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bookoftut.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(196, 'vs10bookviking', 'Book of Vikings', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10bookviking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(197, 'vs10carpbbbnz', 'Gold Carp Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10carpbbbnz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(198, 'vs10chillihmr', 'Chilli Heat Spicy Spins', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10chillihmr.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(199, 'vs10chkchase', 'Chicken Chase', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10chkchase.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(200, 'vs10coffee', 'Coffee Wild', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10coffee.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(201, 'vs10cowgold', 'Cowboys Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10cowgold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(202, 'vs10crownfire', 'Crown of Fire', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10crownfire.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(203, 'vs10ddcbells', 'Ding Dong Christmas Bells', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10ddcbells.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(204, 'vs10dgold88', 'Dragon Gold 88', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10dgold88.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(205, 'vs10diamondrgh', 'Temple Guardians', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10diamondrgh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(206, 'vs10dyndigd', 'Dynamite Diggin Doug', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10dyndigd.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(207, 'vs10egrich', 'Queen of Gods', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10egrich.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(208, 'vs10egypt', 'Ancient Egypt', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10egypt.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(209, 'vs10egyptcls', 'Ancient Egypt Classic', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10egyptcls.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(210, 'vs10eyestorm', 'Eye of the Storm', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10eyestorm.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(211, 'vs10fdrasbf', 'Floating Dragon - Dragon Boat Festival', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10fdrasbf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(212, 'vs10fdsnake', 'Floating Dragon Year of the Snake', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10fdsnake.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(213, 'vs10fingerlfs', 'Finger Lick’n Free Spins', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10fingerlfs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(214, 'vs10firestrike', 'Fire Strike', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10firestrike.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(215, 'vs10firestrike2', 'Fire Strike 2', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10firestrike2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(216, 'vs10fisheye', 'Fish Eye', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10fisheye.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(217, 'vs10floatdrg', 'Floating Dragon', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10floatdrg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(218, 'vs10fruity2', 'Extra Juicy', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10fruity2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(219, 'vs10gdchalleng', '8 Golden Dragon Challenge', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10gdchalleng.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(220, 'vs10gizagods', 'Gods of Giza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10gizagods.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(221, 'vs10goldfish', 'Fishin Reels', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10goldfish.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(222, 'vs10jokerhot', 'Joker’s Jewels Hot', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10jokerhot.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(223, 'vs10jpblaze', 'Jackpot Blaze', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10jpblaze.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(224, 'vs10kingofdth', 'Kingdom of The Dead', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10kingofdth.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(225, 'vs10luckcharm', 'Lucky Grace And Charm', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10luckcharm.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(226, 'vs10madame', 'Madame Destiny', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10madame.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(227, 'vs10mayangods', 'John Hunter and the Mayan Gods', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10mayangods.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(228, 'vs10mmm', 'Magic Money Maze', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10mmm.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(229, 'vs10nudgeit', 'Rise of Giza PowerNudge', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10nudgeit.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(230, 'vs10piggybank', 'You Can Piggy Bank On It', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10piggybank.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(231, 'vs10powerlines', 'Peak Power', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10powerlines.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(232, 'vs10returndead', 'Return of the Dead', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10returndead.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(233, 'vs10runes', 'Gates of Valhalla', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10runes.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(234, 'vs10sfrenzy', 'Strike Frenzy', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10sfrenzy.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(235, 'vs10snakeeyes', 'Snakes & Ladders - Snake Eyes', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10snakeeyes.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(236, 'vs10spellmastr', 'Spellmaster', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10spellmastr.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(237, 'vs10starpirate', 'Star Pirates Code', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10starpirate.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(238, 'vs10threestar', 'Three Star Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10threestar.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(239, 'vs10tictac', 'Tic Tac Take', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10tictac.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(240, 'vs10trail', 'Mustang Trail', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10trail.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(241, 'vs10vampwolf', 'Vampires VS Wolves', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10vampwolf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(242, 'vs10wildtut', 'Mysterious Egypt', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10wildtut.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(243, 'vs117649starz', 'Starz Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs117649starz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(244, 'vs12bbb', 'Bigger Bass Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs12bbb.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(245, 'vs12bbbxmas', 'Bigger Bass Blizzard - Christmas Catch', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs12bbbxmas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(246, 'vs12bgrbspl', 'Bigger Bass Splash', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs12bgrbspl.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(247, 'vs12scode', 'Samurai Code', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs12scode.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(248, 'vs12tropicana', 'Club Tropicana', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs12tropicana.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(249, 'vs12trpcnhour', 'Club Tropicana – Happy Hour', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs12trpcnhour.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(250, 'vs15diamond', 'Diamond Strike', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs15diamond.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(251, 'vs15fairytale', 'Fairytale Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs15fairytale.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(252, 'vs15godsofwar', 'Zeus vs Hades - Gods of War', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs15godsofwar.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(253, 'vs15pvss', 'Puffers vs Sharks', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs15pvss.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(254, 'vs15samurai4', 'Rise of Samurai 4', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs15samurai4.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(255, 'vs15seoultrain', 'Zombie Train', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs15seoultrain.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(256, 'vs15thundunder', 'Thunder vs Underworld', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs15thundunder.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(257, 'vs1600drago', 'Drago - Jewels of Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1600drago.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(258, 'vs18mashang', 'Treasure Horse', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs18mashang.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(259, 'vs1ball', 'Lucky Dragon Ball', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1ball.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(260, 'vs1dragon8', '888 Dragons', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1dragon8.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(261, 'vs1fortunetree', 'Tree of Riches', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1fortunetree.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(262, 'vs1fufufu', 'Fu Fu Fu', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1fufufu.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(263, 'vs1money', 'Money Money Money', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1money.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(264, 'vs1tigers', 'Triple Tigers', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs1tigers.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(265, 'vs20aladdinsorc', 'Aladdin and the Sorcerer', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20aladdinsorc.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(266, 'vs20alieninv', 'Alien Invaders', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20alieninv.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(267, 'vs20amuleteg', 'Fortune of Giza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20amuleteg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(268, 'vs20asgard', 'Kingdom of Asgard', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20asgard.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(269, 'vs20aztecgates', 'Gates of Aztec', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20aztecgates.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(270, 'vs20bblitz', 'Money Stacks', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20bblitz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(271, 'vs20bchprty', 'Wild Beach Party', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20bchprty.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(272, 'vs20beefed', 'Fat Panda', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20beefed.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(273, 'vs20bermuda', 'John Hunter and the Quest for Bermuda Riches', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20bermuda.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(274, 'vs20bigmass', 'Waves of Poseidon', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20bigmass.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(275, 'vs20bl', 'Busy Bees', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20bl.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(276, 'vs20bnnzdice', 'Sweet Bonanza Dice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20bnnzdice.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(277, 'vs20bonzgold', 'Bonanza Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20bonzgold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(278, 'vs20buddhasgg', 'Laughing Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20buddhasgg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(279, 'vs20candvil', 'Candy Village', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20candvil.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(280, 'vs20candyblitz', 'Candy Blitz', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20candyblitz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(281, 'vs20cappa', 'Cappadocia', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20cappa.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(282, 'vs20cashmachine', 'Cash Box', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20cashmachine.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(283, 'vs20cbrhst', 'Cyber Heist', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20cbrhst.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(284, 'vs20chickdrop', 'Chicken Drop', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20chickdrop.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(285, 'vs20chicken', 'The Great Chicken Escape', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20chicken.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(286, 'vs20cleocatra', 'Cleocatra', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20cleocatra.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(287, 'vs20clspwrndg', 'Sweet Powernudge', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20clspwrndg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(288, 'vs20clustcol', 'Sweet Kingdom', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20clustcol.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(289, 'vs20clustwild', 'Sticky Bees', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20clustwild.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(290, 'vs20colcashzone', 'Colossal Cash Zone', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20colcashzone.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(291, 'vs20crankit', 'Crank It Up', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20crankit.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(292, 'vs20daydead', 'Day of Dead', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20daydead.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(293, 'vs20dhcluster', 'Twilight Princess', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20dhcluster.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(294, 'vs20dhdice', 'The Dog House Dice Show', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20dhdice.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(295, 'vs20dhsuper', 'The Dog House – Royal Hunt', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20dhsuper.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(296, 'vs20dicegatex', 'Gates of Olympus 1000 Dice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20dicegatex.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(297, 'vs20doghouse', 'The Dog House', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20doghouse.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(298, 'vs20doghouse2', 'The Dog House Dog or Alive', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20doghouse2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(299, 'vs20doghousemh', 'The Dog House Multihold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20doghousemh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(300, 'vs20drtgold', 'Drill that Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20drtgold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(301, 'vs20dugems', 'Hot Pepper', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20dugems.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(302, 'vs20earthquake', 'Cyclops Smash', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20earthquake.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(303, 'vs20egypttrs', 'Egyptian Fortunes', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20egypttrs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(304, 'vs20eightdragons', '8 Dragons', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20eightdragons.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(305, 'vs20ekingrr', 'Emerald King Rainbow Road', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20ekingrr.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(306, 'vs20emptybank', 'Empty the Bank', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20emptybank.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(307, 'vs20excalibur', 'Excalibur Unleashed', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20excalibur.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(308, 'vs20farmfest', 'Barn Festival', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20farmfest.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(309, 'vs20fh', 'Fire Hot 20', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20fh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(310, 'vs20forge', 'Forge of Olympus', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20forge.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(311, 'vs20fparty2', 'Fruit Party 2', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20fparty2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(312, 'vs20framazon', 'Fruits of the Amazon', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20framazon.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(313, 'vs20fruitjpas', 'Jajanan Pasar', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20fruitjpas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(314, 'vs20fruitparty', 'Fruit Party', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20fruitparty.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(315, 'vs20fruitswx', 'Sweet Bonanza 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20fruitswx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(316, 'vs20gatotfury', 'Gatot Kaca\'s Fury', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gatotfury.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(317, 'vs20gatotgates', 'Gates of Gatot Kaca', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gatotgates.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(318, 'vs20gatotx', 'Gates of Gatot Kaca 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gatotx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(319, 'vs20gembondx', 'Bow of Artemis', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gembondx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(320, 'vs20gemfirefor', 'Gem Fire Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gemfirefor.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(321, 'vs20gobnudge', 'Goblin Heist Powernudge', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gobnudge.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(322, 'vs20godiva', 'Lady Godiva', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20godiva.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(323, 'vs20goldclust', 'Rabbit Garden', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20goldclust.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(324, 'vs20goldfever', 'Gems Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20goldfever.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(325, 'vs20gorilla', 'Jungle Gorilla', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gorilla.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(326, 'vs20gravity', 'Gravity Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20gravity.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(327, 'vs20hercpeg', 'Hercules and Pegasus', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20hercpeg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(328, 'vs20honey', 'Honey Honey Honey', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20honey.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(329, 'vs20hotzone', 'African Elephant', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20hotzone.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver');
INSERT INTO `games` (`id`, `game_code`, `game_name`, `banner`, `status`, `provider`, `popular`, `type`, `game_type`, `api`) VALUES
(330, 'vs20hstgldngt', 'Heist for the Golden Nuggets', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20hstgldngt.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(331, 'vs20jewelparty', 'Jewel Rush', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20jewelparty.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(332, 'vs20jhunter', 'Jackpot Hunter', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20jhunter.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(333, 'vs20kraken', 'Release the Kraken', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20kraken.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(334, 'vs20lampinf', 'Lamp Of Infinity', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20lampinf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(335, 'vs20lcount', 'Gems of Serengeti', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20lcount.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(336, 'vs20leogatex', 'Gates of LeoVegas 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20leogatex.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(337, 'vs20leprechaun', 'Leprechaun Song', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20leprechaun.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(338, 'vs20leprexmas', 'Leprechaun Carol', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20leprexmas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(339, 'vs20lobcrab', 'Lobster Bob\'s Crazy Crab Shack', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20lobcrab.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(340, 'vs20lvlup', 'Pub Kings', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20lvlup.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(341, 'vs20mafiosa', 'Mafiosa - Dead or a Llama', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mafiosa.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(342, 'vs20magicpot', 'The Magic Cauldron', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20magicpot.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(343, 'vs20mahjxbnz', 'Mahjong X', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mahjxbnz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(344, 'vs20maskgame', 'Cash Chips', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20maskgame.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(345, 'vs20mesmult', 'Yeti Quest', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mesmult.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(346, 'vs20midas', 'The Hand of Midas', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20midas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(347, 'vs20midas2', 'Hand of Midas 2', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20midas2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(348, 'vs20mkrush', 'Monkey King Rush', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mkrush.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(349, 'vs20mochimon', 'Mochimon', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mochimon.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(350, 'vs20mparty', 'Wild Hop & Drop', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mparty.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(351, 'vs20mustanggld2', 'Clover Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mustanggld2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(352, 'vs20mvwild', 'Jasmine Dreams', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20mvwild.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(353, 'vs20ninjapower', 'Power of Ninja', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20ninjapower.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(354, 'vs20octobeer', 'Octobeer Fortunes', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20octobeer.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(355, 'vs20olygames', 'Games in Olympus', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olygames.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(356, 'vs20olympdice', 'Gates of Olympus Dice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olympdice.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(357, 'vs20olympgold', 'Gates of Olympus Super Scatter', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olympgold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(358, 'vs20olympgrace', 'Grace of Ebisu', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olympgrace.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(359, 'vs20olympx', 'Gates of Olympus 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olympx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(360, 'vs20olympxdede', 'Barbar', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olympxdede.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(361, 'vs20olympxmas', 'Gates of Olympus Xmas 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20olympxmas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(362, 'vs20pbonanza', 'Pyramid Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20pbonanza.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(363, 'vs20phoenixf', 'Phoenix Forge', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20phoenixf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(364, 'vs20piggybank', 'Piggy Bankers', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20piggybank.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(365, 'vs20popbottles', 'Ice Mints', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20popbottles.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(366, 'vs20porbs', 'Santa\'s Great Gifts™', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20porbs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(367, 'vs20portals', 'Fire Portals', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20portals.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(368, 'vs20pquestx', 'Pirate Quest 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20pquestx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(369, 'vs20procount', 'Wisdom of Athena', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20procount.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(370, 'vs20procountx', 'Wisdom of Athena 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20procountx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(371, 'vs20rainbowg', 'Rainbow Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20rainbowg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(372, 'vs20rhino', 'Great Rhino', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20rhino.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(373, 'vs20rhinoluxe', 'Great Rhino Deluxe', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20rhinoluxe.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(374, 'vs20riverstx', 'River of Styx', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20riverstx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(375, 'vs20rockvegas', 'Rock Vegas', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20rockvegas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(376, 'vs20rodegate', 'Devil Joker', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20rodegate.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(377, 'vs20rujakbnz', 'Rujak Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20rujakbnz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(378, 'vs20saiman', 'Saiyan Mania', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20saiman.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(379, 'vs20santa', 'Santa', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20santa.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(380, 'vs20santawonder', 'Santa\'s Wonderland', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20santawonder.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(381, 'vs20sbxmas', 'Sweet Bonanza Xmas', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sbxmas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(382, 'vs20schristmas', 'Starlight Christmas', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20schristmas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(383, 'vs20sh', 'Shining Hot 20', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(384, 'vs20sknights', 'The Knight King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sknights.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(385, 'vs20smugcove', 'Smugglers Cove', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20smugcove.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(386, 'vs20sparta', 'Shield of Sparta', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sparta.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(387, 'vs20splmystery', 'Spellbinding Mystery', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20splmystery.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(388, 'vs20starlight', 'Starlight Princess', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20starlight.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(389, 'vs20starlightx', 'Starlight Princess 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20starlightx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(390, 'vs20stickysymbol', 'The Great Stick-Up', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20stickysymbol.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(391, 'vs20stickywild', 'Wild Bison Charge', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20stickywild.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(392, 'vs20sugarcoins', 'Viking Forge', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sugarcoins.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(393, 'vs20sugardice', 'Sugar Rush Dice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sugardice.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(394, 'vs20sugarrushx', 'Sugar Rush 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sugarrushx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(395, 'vs20sugrux', 'Sugar Rush Xmas', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sugrux.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(396, 'vs20sungate', 'The Green Sun', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20sungate.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(397, 'vs20superlanche', 'Monster Superlanche', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20superlanche.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(398, 'vs20supermania', 'Supermania', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20supermania.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(399, 'vs20superx', 'Super X', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20superx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(400, 'vs20swbonsup', 'Sweet Bonanza Super Scatter', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20swbonsup.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(401, 'vs20swdicex', 'Sweet Bonanza 1000 Dice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20swdicex.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(402, 'vs20swordofares', 'Sword of Ares', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20swordofares.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(403, 'vs20trollx', 'Trolling 1000', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20trollx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(404, 'vs20trsbox', 'Treasure Wild', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20trsbox.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(405, 'vs20trswild2', 'Black Bull', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20trswild2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(406, 'vs20tweethouse', 'The Tweety House', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20tweethouse.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(407, 'vs20ultim5', 'The Ultimate 5', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20ultim5.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(408, 'vs20underground', 'Down the Rails', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20underground.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(409, 'vs20vegasmagic', 'Vegas Magic', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20vegasmagic.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(410, 'vs20wildboost', 'Wild Booster', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20wildboost.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(411, 'vs20wildman', 'Wildman Super Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20wildman.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(412, 'vs20wildparty', '3 Buzzing Wilds', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20wildparty.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(413, 'vs20wildpix', 'Wild Pixies', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20wildpix.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(414, 'vs20wolfie', 'Greedy Wolf', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20wolfie.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(415, 'vs20xmascarol', 'Christmas Carol Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20xmascarol.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(416, 'vs20yisunshin', 'Yi Sun Shin', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20yisunshin.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(417, 'vs243caishien', 'Caishen\'s Cash', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243caishien.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(418, 'vs243chargebull', 'Raging Bull', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243chargebull.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(419, 'vs243ckemp', 'Cheeky Emperor', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243ckemp.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(420, 'vs243dancingpar', 'Dance Party', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243dancingpar.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(421, 'vs243discolady', 'Disco Lady', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243discolady.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(422, 'vs243empcaishen', 'Emperor Caishen', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243empcaishen.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(423, 'vs243fdragon', 'Fortune Dragon', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243fdragon.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(424, 'vs243fortseren', 'Greek Gods', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243fortseren.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(425, 'vs243fortune', 'Caishen\'s Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243fortune.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(426, 'vs243goldfor', '888 Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243goldfor.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(427, 'vs243koipond', 'Koi Pond', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243koipond.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(428, 'vs243lions', '5 Lions', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243lions.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(429, 'vs243lionsgold', '5 Lions Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243lionsgold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(430, 'vs243mwarrior', 'Monkey Warrior', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243mwarrior.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(431, 'vs243nudge4gold', 'Hellvis Wild', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243nudge4gold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(432, 'vs243queenie', 'Queenie', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs243queenie.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(433, 'vs25archer', 'Fire Archer', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25archer.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(434, 'vs25asgard', 'Asgard', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25asgard.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(435, 'vs25aztecking', 'Aztec King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25aztecking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(436, 'vs25bkofkngdm', 'Book of Kingdoms', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25bkofkngdm.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(437, 'vs25bomb', 'Bomb Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25bomb.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(438, 'vs25btygold', 'Bounty Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25btygold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(439, 'vs25bullfiesta', 'Bull Fiesta', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25bullfiesta.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(440, 'vs25chilli', 'Chilli Heat', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25chilli.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(441, 'vs25copsrobbers', 'Cash Patrol', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25copsrobbers.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(442, 'vs25davinci', 'Da Vinci\'s Treasure', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25davinci.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(443, 'vs25dragonkingdom', 'Dragon Kingdom', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25dragonkingdom.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(444, 'vs25dwarves_new', 'Dwarven Gold Deluxe', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25dwarves_new.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(445, 'vs25gladiator', 'Wild Gladiator', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25gladiator.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(446, 'vs25gldox', 'Golden Ox', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25gldox.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(447, 'vs25goldparty', 'Gold Party', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25goldparty.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(448, 'vs25goldpartya', 'Gold Party 2-After Hours', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25goldpartya.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(449, 'vs25goldpig', 'Golden Pig', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25goldpig.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(450, 'vs25goldrush', 'Gold Rush', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25goldrush.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(451, 'vs25holiday', 'Holiday Ride', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25holiday.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(452, 'vs25hotfiesta', 'Hot Fiesta', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25hotfiesta.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(453, 'vs25jokerking', 'Joker King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25jokerking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(454, 'vs25jokrace', 'Joker Race', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25jokrace.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(455, 'vs25journey', 'Journey to the West', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25journey.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(456, 'vs25mmouse', 'Money Mouse', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25mmouse.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(457, 'vs25newyear', 'Lucky New Year', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25newyear.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(458, 'vs25pandagold', 'Panda\'s Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25pandagold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(459, 'vs25pandatemple', 'Panda Fortune 2', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25pandatemple.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(460, 'vs25peking', 'Peking Luck', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25peking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(461, 'vs25pyramid', 'Pyramid King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25pyramid.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(462, 'vs25quadwolf', 'Wolf Gold 4 Pack', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25quadwolf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(463, 'vs25rio', 'Heart of Rio', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25rio.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(464, 'vs25safari', 'Hot Safari', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25safari.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(465, 'vs25samurai', 'Rise of Samurai', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25samurai.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(466, 'vs25scarabqueen', 'John Hunter and the Tomb of the Scarab Queen', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25scarabqueen.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(467, 'vs25sea', 'Great Reef', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25sea.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(468, 'vs25spgldways', 'Secret City Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25spgldways.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(469, 'vs25spotz', 'Knight Hot Spotz', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25spotz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(470, 'vs25tigerwar', 'The Tiger Warrior', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25tigerwar.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(471, 'vs25tigeryear', 'Lucky New Year - Tiger Treasures', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25tigeryear.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(472, 'vs25vegas', 'Vegas Nights', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25vegas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(473, 'vs25walker', 'Wild Walker', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25walker.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(474, 'vs25wildspells', 'Wild Spells', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25wildspells.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(475, 'vs25wolfgold', 'Wolf Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25wolfgold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(476, 'vs25xmasparty', 'Penguins Christmas Party Time', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs25xmasparty.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(477, 'vs3train', 'Gold Train', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs3train.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(478, 'vs4096bufking', 'Buffalo King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs4096bufking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(479, 'vs4096jurassic', 'Jurassic Giants', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs4096jurassic.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(480, 'vs4096magician', 'Magician\'s Secrets', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs4096magician.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(481, 'vs4096robber', 'Robber Strike', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs4096robber.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(482, 'vs40beowulf', 'Beowulf', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40beowulf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(483, 'vs40bigjuan', 'Big Juan', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40bigjuan.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(484, 'vs40cleoeye', 'Eye of Cleopatra', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40cleoeye.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(485, 'vs40cosmiccash', 'Cosmic Cash', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40cosmiccash.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(486, 'vs40demonpots', 'Demon Pots', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40demonpots.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(487, 'vs40firehot', 'Fire Hot 40', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40firehot.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(488, 'vs40frrainbow', 'Fruit Rainbow', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40frrainbow.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(489, 'vs40hotburnx', 'Hot to Burn Extreme', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40hotburnx.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(490, 'vs40infwild', 'Infective Wild', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40infwild.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(491, 'vs40pirate', 'Pirate Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40pirate.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(492, 'vs40pirgold', 'Pirate Gold Deluxe', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40pirgold.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(493, 'vs40rainbowr', 'Rainbow Reels', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40rainbowr.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(494, 'vs40samurai3', 'Rise Of Samurai III (Not Available for Japan)', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40samurai3.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(495, 'vs40sh', 'Shining Hot 40', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40sh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(496, 'vs40spartaking', 'Spartan King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40spartaking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(497, 'vs40streetracer', 'Street Racer', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40streetracer.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(498, 'vs40voodoo', 'Voodoo Magic', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40voodoo.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(499, 'vs40wildwest', 'Wild West Gold', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs40wildwest.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(500, 'vs50aladdin', '3 Genie Wishes', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50aladdin.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(501, 'vs50chinesecharms', 'Lucky Dragons', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50chinesecharms.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(502, 'vs50dmdcascade', 'Diamond Cascade', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50dmdcascade.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(503, 'vs50hercules', 'Hercules Son Of Zeus', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50hercules.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(504, 'vs50jucier', 'Kraken\'s Sky Bounty', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50jucier.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(505, 'vs50juicyfr', 'Juicy Fruits', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50juicyfr.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(506, 'vs50kingkong', 'Mighty Kong', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50kingkong.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(507, 'vs50mightra', 'Might of Ra', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50mightra.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(508, 'vs50northgard', 'North Guardians', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50northgard.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(509, 'vs50pixie', 'Pixie Wings', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50pixie.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(510, 'vs50safariking', 'Safari King', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs50safariking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(511, 'vs576hokkwolf', 'Hokkaido Wolf', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs576hokkwolf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(512, 'vs576mrtainwwg', 'Mr Tain\'s Fury: Wild Wild Gladiator', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs576mrtainwwg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(513, 'vs576treasures', 'Wild Wild Riches', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs576treasures.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(514, 'vs5aztecgems', 'Aztec Gems', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5aztecgems.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(515, 'vs5balidragon', 'Bali Dragon', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5balidragon.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(516, 'vs5drhs', 'Dragon Hot Hold and Spin', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5drhs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(517, 'vs5firehot', 'Fire Hot 5', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5firehot.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(518, 'vs5gemstone', 'Gemstone', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5gemstone.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(519, 'vs5hotbmult', 'Hot to Burn Multiplier', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5hotbmult.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(520, 'vs5hotburn', 'Hot to Burn', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5hotburn.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(521, 'vs5jjwild', 'Joker\'s Jewels Wild', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5jjwild.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(522, 'vs5littlegem', 'Little Gem', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5littlegem.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(523, 'vs5sh', 'Shining Hot 5', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5sh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(524, 'vs5spjoker', 'Super Joker', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5spjoker.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(525, 'vs5strh', 'Striking Hot 5', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5strh.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(526, 'vs5super7', 'Super 7s', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5super7.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(527, 'vs5trdragons', 'Triple Dragons', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5trdragons.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(528, 'vs5ultra', 'Ultra Hold and Spin', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5ultra.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(529, 'vs5ultrab', 'Ultra Burn', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5ultrab.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(530, 'vs7776aztec', 'Aztec Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs7776aztec.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(531, 'vs7776secrets', 'John Hunter and the Aztec Treasure', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs7776secrets.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(532, 'vs7fire88', 'Fire 88', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs7fire88.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(533, 'vs7monkeys', '7 Monkeys', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs7monkeys.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(534, 'vs7pigs', '7 Piggies', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs7pigs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(535, 'vs88hockattack', 'Hockey Attack', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs88hockattack.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(536, 'vs8magicjourn', 'Magic Journey', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs8magicjourn.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(537, 'vs9chen', 'Master Chen\'s Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs9chen.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(538, 'vs9gemtrio', 'Gem Trio', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs9gemtrio.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(539, 'vs9hotroll', 'Hot Chilli', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs9hotroll.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(540, 'vs9madmonkey', 'Monkey Madness', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs9madmonkey.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(541, 'vs9outlaw', 'Pirates Pub', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs9outlaw.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(542, 'vs9piggybank', 'Piggy Bank Bills', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs9piggybank.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(543, 'vswaysargonts', 'Argonauts', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysargonts.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(544, 'vswaysaztecking', 'Aztec King Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysaztecking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(545, 'vswaysbankbonz', 'Cash Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbankbonz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(546, 'vswaysbbhas', 'Big Bass Hold & Spinner Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbbhas.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(547, 'vswaysbkingasc', 'Buffalo King Untamed Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbkingasc.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(548, 'vswaysbook', 'Book of Golden Sands', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbook.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(549, 'vswaysbufking', 'Buffalo King Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbufking.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(550, 'vswayscashconv', 'Running Sushi', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayscashconv.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(551, 'vswayscharms', '5 Frozen Charms Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayscharms.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(552, 'vswayscheist', 'Casino Heist Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayscheist.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(553, 'vswayschilheat', 'Chilli Heat Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayschilheat.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(554, 'vswaysconcoll', 'Firebird Spirit', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysconcoll.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(555, 'vswayscryscav', 'Crystal Caverns Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayscryscav.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(556, 'vswaysdogs', 'The Dog House Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysdogs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(557, 'vswayselements', 'Elemental Gems Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayselements.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(558, 'vswaysfirest2', 'Fire Stampede 2', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysfirest2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(559, 'vswaysfltdrg', 'Floating Dragon Megaways Hold & Spin', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysfltdrg.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(560, 'vswaysfltdrgny', 'Floating Dragon New Year Festival Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysfltdrgny.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(561, 'vswaysfrbugs', 'Frogs & Bugs', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysfrbugs.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(562, 'vswaysftropics', 'Frozen Tropics', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysftropics.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(563, 'vswaysfuryodin', 'Fury of Odin Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysfuryodin.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(564, 'vswayshive', 'Star Bounty', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayshive.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(565, 'vswaysincwnd', 'Gold Oasis', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysincwnd.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(566, 'vswaysjapan', 'Starlight Princess Pachi', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysjapan.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(567, 'vswaysjkrdrop', 'Tropical Tiki', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysjkrdrop.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(568, 'vswayslight', 'Lucky Lightning', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayslight.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(569, 'vswayslions', '5 Lions Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayslions.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(570, 'vswayslofhero', 'Legend of Heroes Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayslofhero.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(571, 'vswaysmadame', 'Madame Destiny Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmadame.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(572, 'vswaysmahwblck', 'Mahjong Wins 3 – Black Scatter', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmahwblck.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(573, 'vswaysmahwgong', 'Mahjong Wins - Gong Xi Fa Cai', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmahwgong.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(574, 'vswaysmahwin2', 'Mahjong Wins 2', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmahwin2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(575, 'vswaysmherc', 'Mighty Hercules', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmherc.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(576, 'vswaysmoneyman', 'The Money Men Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmoneyman.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(577, 'vswaysmonkey', '3 Dancing Monkeys', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmonkey.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(578, 'vswaysmorient', 'Mystery Of The Orient', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmorient.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(579, 'vswaysmwss', 'Mahjong Wins Super Scatter', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmwss.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(580, 'vswaysoldminer', 'Old Gold Miner Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysoldminer.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(581, 'vswayspizza', 'Pizza?Pizza?Pizza?', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayspizza.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(582, 'vswayspompmr2', 'Zombie School Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayspompmr2.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(583, 'vswaysrabbits', '5 Rabbits Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysrabbits.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(584, 'vswaysraghex', 'Tundra’s Fortune', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysraghex.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(585, 'vswaysredqueen', 'The Red Queen', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysredqueen.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(586, 'vswaysrhino', 'Great Rhino Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysrhino.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(587, 'vswaysrsm', 'Wild Celebrity Bus Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysrsm.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(588, 'vswayssamurai', 'Rise of Samurai Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayssamurai.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(589, 'vswaysstrlght', 'Fortunes of Aztec', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysstrlght.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(590, 'vswaysstrwild', 'Candy Stars', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysstrwild.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(591, 'vswaystut', 'John Hunter and the Book of Tut Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaystut.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(592, 'vswaysultrcoin', 'Cowboy Coins', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysultrcoin.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(593, 'vswayswerewolf', 'Curse of the Werewolf Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayswerewolf.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(594, 'vswayswest', 'Mystic Chief', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayswest.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(595, 'vswayswildwest', 'Wild West Gold Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayswildwest.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(596, 'vswayswwhex', 'Wild Wild Bananas', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayswwhex.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(597, 'vswaysxjuicy', 'Extra Juicy Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysxjuicy.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(598, 'vswayszombcarn', 'Zombie Carnival', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayszombcarn.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(599, 'vswaysfreezet', 'Eternal Empress - Freeze Time', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysfreezet.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(600, 'vswaysmfreya', 'Might of Freya Megaways', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmfreya.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(601, 'vs20minerush', 'Mining Rush', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20minerush.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(602, 'vs5bb3reeler', 'Big Bass Bonanza 3 Reeler', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs5bb3reeler.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(603, 'vs10dkinghp', 'Dragon King Hot Pots', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10dkinghp.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(604, 'vs20plsmcannon', 'Aztec Smash', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20plsmcannon.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(605, 'vswaysbrickhos', 'Brick House Bonanza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbrickhos.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(606, 'vswayspearls', 'Wild Wild Pearls', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayspearls.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(607, 'vs20irishcrown', 'Irish Crown', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs20irishcrown.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(608, 'vswaysbufstamp', 'Wild Wildebeest Wins', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysbufstamp.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(609, 'vs10fireice', 'Escape the Pyramid - Fire & Ice', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vs10fireice.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(610, 'vswaysmodfr', 'Ancient Island Megaways™', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaysmodfr.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(611, 'vswayssavlgnd', 'Savannah Legend', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswayssavlgnd.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(612, 'vswaystonypzz', 'Peppe\'s Pepperoni Pizza Plaza', 'https://imagensfivers.com/Games/Pragmatic-Play/PP_vswaystonypzz.webp', 1, 'PRAGMATIC', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(722, 'TADA_472', '3 Coin Treasures 2', 'https://imagensfivers.com/Games/Tada/TADA_472.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(723, 'TADA_461', 'Pirate Queen 2', 'https://imagensfivers.com/Games/Tada/TADA_461.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(724, 'TADA_504', 'Crazy777 2', 'https://imagensfivers.com/Games/Tada/TADA_504.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(725, 'TADA_399', '3 Coin Treasures', 'https://imagensfivers.com/Games/Tada/TADA_399.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(726, 'TADA_299', 'Potion Wizard', 'https://imagensfivers.com/Games/Tada/TADA_299.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(727, 'TADA_324', 'Nightfall Hunting', 'https://imagensfivers.com/Games/Tada/TADA_324.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(728, 'TADA_378', 'Golden Bank 2', 'https://imagensfivers.com/Games/Tada/TADA_378.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(729, 'TADA_400', '3 Lucky Piggy', 'https://imagensfivers.com/Games/Tada/TADA_400.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(730, 'TADA_223', 'Fortune Gems 2', 'https://imagensfivers.com/Games/Tada/TADA_223.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(731, 'TADA_109', 'Fortune Gems', 'https://imagensfivers.com/Games/Tada/TADA_109.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(732, 'TADA_103', 'Golden Empire', 'https://imagensfivers.com/Games/Tada/TADA_103.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(733, 'TADA_134', 'Mega Ace', 'https://imagensfivers.com/Games/Tada/TADA_134.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(734, 'TADA_100', 'Super Rich', 'https://imagensfivers.com/Games/Tada/TADA_100.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(735, 'TADA_77', 'BoxingKing', 'https://imagensfivers.com/Games/Tada/TADA_77.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(736, 'TADA_110', 'Ali Baba', 'https://imagensfivers.com/Games/Tada/TADA_110.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(737, 'TADA_91', 'Lucky Coming', 'https://imagensfivers.com/Games/Tada/TADA_91.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(738, 'TADA_6', 'Fortune Tree', 'https://imagensfivers.com/Games/Tada/TADA_6.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(739, 'TADA_87', 'Book of Gold', 'https://imagensfivers.com/Games/Tada/TADA_87.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(740, 'TADA_16', 'Jungle King', 'https://imagensfivers.com/Games/Tada/TADA_16.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(741, 'TADA_108', 'Magic Lamp Bingo', 'https://imagensfivers.com/Games/Tada/TADA_108.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(742, 'TADA_258', 'Devil Fire 2', 'https://imagensfivers.com/Games/Tada/TADA_258.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver');
INSERT INTO `games` (`id`, `game_code`, `game_name`, `banner`, `status`, `provider`, `popular`, `type`, `game_type`, `api`) VALUES
(743, 'TADA_137', 'Gold Rush', 'https://imagensfivers.com/Games/Tada/TADA_137.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(744, 'TADA_244', 'Bounty Frenzy', 'https://imagensfivers.com/Games/Tada/TADA_244.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(745, 'TADA_146', 'World Cup', 'https://imagensfivers.com/Games/Tada/TADA_146.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(746, 'TADA_85', 'Pharaoh Treasure', 'https://imagensfivers.com/Games/Tada/TADA_85.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(747, 'TADA_303', 'Fortune Monkey', 'https://imagensfivers.com/Games/Tada/TADA_303.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(748, 'TADA_58', 'Golden Queen', 'https://imagensfivers.com/Games/Tada/TADA_58.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(749, 'TADA_136', 'Samba', 'https://imagensfivers.com/Games/Tada/TADA_136.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(750, 'TADA_102', 'Roma X', 'https://imagensfivers.com/Games/Tada/TADA_102.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(751, 'TADA_126', 'Bone Fortune', 'https://imagensfivers.com/Games/Tada/TADA_126.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(752, 'TADA_193', 'Devil Fire', 'https://imagensfivers.com/Games/Tada/TADA_193.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(753, 'TADA_225', 'Cricket King 18', 'https://imagensfivers.com/Games/Tada/TADA_225.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(754, 'TADA_33', 'Fortune Pig', 'https://imagensfivers.com/Games/Tada/TADA_33.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(755, 'TADA_130', 'Thor X', 'https://imagensfivers.com/Games/Tada/TADA_130.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(756, 'TADA_263', 'The Pig House', 'https://imagensfivers.com/Games/Tada/TADA_263.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(757, 'TADA_115', 'Agent Ace', 'https://imagensfivers.com/Games/Tada/TADA_115.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(758, 'TADA_37', 'Night City', 'https://imagensfivers.com/Games/Tada/TADA_37.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(759, 'TADA_38', 'Fengshen', 'https://imagensfivers.com/Games/Tada/TADA_38.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(760, 'TADA_171', 'Sin City', 'https://imagensfivers.com/Games/Tada/TADA_171.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(761, 'TADA_2', 'Chin Shi Huang', 'https://imagensfivers.com/Games/Tada/TADA_2.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(762, 'TADA_226', 'Witches Night', 'https://imagensfivers.com/Games/Tada/TADA_226.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(763, 'TADA_239', 'Dabanggg', 'https://imagensfivers.com/Games/Tada/TADA_239.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(764, 'TADA_23', 'Candy Baby', 'https://imagensfivers.com/Games/Tada/TADA_23.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(765, 'TADA_230', 'Cricket Sah 75', 'https://imagensfivers.com/Games/Tada/TADA_230.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(766, 'TADA_222', 'NekoNeko', 'https://imagensfivers.com/Games/Tada/TADA_222.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(767, 'TADA_245', 'Kung Fu Tiger', 'https://imagensfivers.com/Games/Tada/TADA_245.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(768, 'TADA_214', 'King Arthur', 'https://imagensfivers.com/Games/Tada/TADA_214.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(769, 'TADA_76', 'Party Night', 'https://imagensfivers.com/Games/Tada/TADA_76.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(770, 'TADA_49', 'Super Ace', 'https://imagensfivers.com/Games/Tada/TADA_49.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(771, 'TADA_238', 'Bangla Beauty', 'https://imagensfivers.com/Games/Tada/TADA_238.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(772, 'TADA_228', 'Arena Fighter', 'https://imagensfivers.com/Games/Tada/TADA_228.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(773, 'TADA_44', 'Diamond Party', 'https://imagensfivers.com/Games/Tada/TADA_44.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(774, 'TADA_374', 'Lucky Doggy', 'https://imagensfivers.com/Games/Tada/TADA_374.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(775, 'TADA_392', 'Coin Tree', 'https://imagensfivers.com/Games/Tada/TADA_392.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(776, 'TADA_181', 'WILD ACE', 'https://imagensfivers.com/Games/Tada/TADA_181.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(777, 'TADA_191', 'Golden Temple', 'https://imagensfivers.com/Games/Tada/TADA_191.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(778, 'TADA_375', 'Poseidon', 'https://imagensfivers.com/Games/Tada/TADA_375.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(779, 'TADA_208', 'Trial of Phoenix', 'https://imagensfivers.com/Games/Tada/TADA_208.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(780, 'TADA_166', 'WILD RACER', 'https://imagensfivers.com/Games/Tada/TADA_166.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(781, 'TADA_422', 'Fruity Wheel', 'https://imagensfivers.com/Games/Tada/TADA_422.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(782, 'TADA_307', 'Treasure Quest', 'https://imagensfivers.com/Games/Tada/TADA_307.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(783, 'TADA_264', 'Egypt\'s Glow', 'https://imagensfivers.com/Games/Tada/TADA_264.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(784, 'TADA_523', 'Fortune Coins', 'https://imagensfivers.com/Games/Tada/TADA_523.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(785, 'TADA_379', 'Money Pot', 'https://imagensfivers.com/Games/Tada/TADA_379.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(786, 'TADA_394', 'Sweet Magic', 'https://imagensfivers.com/Games/Tada/TADA_394.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(787, 'TADA_377', 'Safari Mystery', 'https://imagensfivers.com/Games/Tada/TADA_377.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(788, 'TADA_680', 'X7 HOT', 'https://imagensfivers.com/Games/Tada/TADA_680.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(789, 'TADA_664', 'Lucky Jaguar 500', 'https://imagensfivers.com/Games/Tada/TADA_664.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(790, 'TADA_597', 'Supernova', 'https://imagensfivers.com/Games/Tada/TADA_597.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(791, 'TADA_643', 'Clover Ladybug', 'https://imagensfivers.com/Games/Tada/TADA_643.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(792, 'TADA_635', 'Joker\'s Fortune', 'https://imagensfivers.com/Games/Tada/TADA_635.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(793, 'TADA_623', 'Coin of Lightning', 'https://imagensfivers.com/Games/Tada/TADA_623.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(794, 'TADA_583', 'Cash Coin', 'https://imagensfivers.com/Games/Tada/TADA_583.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(795, 'TADA_595', 'Dice & Drop', 'https://imagensfivers.com/Games/Tada/TADA_595.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(796, 'TADA_621', 'Crown of Fortune', 'https://imagensfivers.com/Games/Tada/TADA_621.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(797, 'TADA_711', 'Rise Of Egypt', 'https://imagensfivers.com/Games/Tada/TADA_711.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(798, 'TADA_594', 'Fortune Hook Boom', 'https://imagensfivers.com/Games/Tada/TADA_594.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(799, 'TADA_593', 'Devil Fire Twins', 'https://imagensfivers.com/Games/Tada/TADA_593.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(800, 'TADA_591', '100 Blazing Clover', 'https://imagensfivers.com/Games/Tada/TADA_591.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(801, 'TADA_590', 'Clover Coins 4x4', 'https://imagensfivers.com/Games/Tada/TADA_590.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(802, 'TADA_541', 'Money Pot DELUXE', 'https://imagensfivers.com/Games/Tada/TADA_541.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(803, 'TADA_589', '40 Sparkling Crown', 'https://imagensfivers.com/Games/Tada/TADA_589.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(804, 'TADA_558', 'Lucky Tiger', 'https://imagensfivers.com/Games/Tada/TADA_558.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(805, 'TADA_540', 'Fortune Gems 500', 'https://imagensfivers.com/Games/Tada/TADA_540.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(806, 'TADA_581', '20 Blazing Clover', 'https://imagensfivers.com/Games/Tada/TADA_581.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(807, 'TADA_393', 'Fortune Hook', 'https://imagensfivers.com/Games/Tada/TADA_393.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(808, 'TADA_545', 'Clover Coins 3x3', 'https://imagensfivers.com/Games/Tada/TADA_545.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(809, 'TADA_448', 'Rapid Gems 777', 'https://imagensfivers.com/Games/Tada/TADA_448.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(810, 'TADA_526', '10 Sparkling Crown', 'https://imagensfivers.com/Games/Tada/TADA_526.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(811, 'TADA_423', 'Bikini Lady', 'https://imagensfivers.com/Games/Tada/TADA_423.webp', 1, 'TADA', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(812, 'POPOK_31', 'Yummy: Trick or Treat', 'https://imagensfivers.com/Games/PopOk/POPOK_31.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(813, 'POPOK_94', 'FastMaster', 'https://imagensfivers.com/Games/PopOk/POPOK_94.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(814, 'POPOK_28', 'Yummy', 'https://imagensfivers.com/Games/PopOk/POPOK_28.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(815, 'POPOK_27', 'Lost Treasure', 'https://imagensfivers.com/Games/PopOk/POPOK_27.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(816, 'POPOK_29', 'Octopus Life', 'https://imagensfivers.com/Games/PopOk/POPOK_29.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(817, 'POPOK_45', 'Richy Panda', 'https://imagensfivers.com/Games/PopOk/POPOK_45.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(818, 'POPOK_42', 'Crazy Poki', 'https://imagensfivers.com/Games/PopOk/POPOK_42.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(819, 'POPOK_22', 'Novel of Alexander', 'https://imagensfivers.com/Games/PopOk/POPOK_22.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(820, 'POPOK_30', 'Huga Chaga', 'https://imagensfivers.com/Games/PopOk/POPOK_30.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(821, 'POPOK_32', 'Happy Easter', 'https://imagensfivers.com/Games/PopOk/POPOK_32.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(822, 'POPOK_23', 'Sultan\'s tale', 'https://imagensfivers.com/Games/PopOk/POPOK_23.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(823, 'POPOK_195', 'Rico Rabbit', 'https://imagensfivers.com/Games/PopOk/POPOK_195.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(824, 'POPOK_106', 'Paradise Beach', 'https://imagensfivers.com/Games/PopOk/POPOK_106.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(825, 'POPOK_203', 'Rico Tiger', 'https://imagensfivers.com/Games/PopOk/POPOK_203.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(826, 'POPOK_131', 'Fiery Fruits Six Fold', 'https://imagensfivers.com/Games/PopOk/POPOK_131.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(827, 'POPOK_157', 'Alpaca Pays', 'https://imagensfivers.com/Games/PopOk/POPOK_157.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(828, 'POPOK_175', 'RicoDJ', 'https://imagensfivers.com/Games/PopOk/POPOK_175.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(829, 'POPOK_184', 'Lions Legacy', 'https://imagensfivers.com/Games/PopOk/POPOK_184.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(830, 'POPOK_174', '777 Triple Seven', 'https://imagensfivers.com/Games/PopOk/POPOK_174.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(831, 'POPOK_181', 'Eye On The Money', 'https://imagensfivers.com/Games/PopOk/POPOK_181.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(832, 'POPOK_112', 'Book Of Sherlock', 'https://imagensfivers.com/Games/PopOk/POPOK_112.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(833, 'POPOK_111', 'Fiesta', 'https://imagensfivers.com/Games/PopOk/POPOK_111.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(834, 'POPOK_169', 'Papai Noel da Fortuna', 'https://imagensfivers.com/Games/PopOk/POPOK_169.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(835, 'POPOK_167', 'Yo Dragon', 'https://imagensfivers.com/Games/PopOk/POPOK_167.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(836, 'POPOK_163', 'Sumo Baby', 'https://imagensfivers.com/Games/PopOk/POPOK_163.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(837, 'POPOK_117', 'Crazy Poki Boo', 'https://imagensfivers.com/Games/PopOk/POPOK_117.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(838, 'POPOK_164', 'Rico Gorila', 'https://imagensfivers.com/Games/PopOk/POPOK_164.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(839, 'POPOK_162', 'Fortune Panda', 'https://imagensfivers.com/Games/PopOk/POPOK_162.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(840, 'POPOK_134', 'Spinosaurus', 'https://imagensfivers.com/Games/PopOk/POPOK_134.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(841, 'POPOK_150', 'Hacivat and Karagoz', 'https://imagensfivers.com/Games/PopOk/POPOK_150.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(842, 'POPOK_144', 'Lucky Clover', 'https://imagensfivers.com/Games/PopOk/POPOK_144.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(843, 'POPOK_149', 'Mega Diamond', 'https://imagensfivers.com/Games/PopOk/POPOK_149.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(844, 'POPOK_138', 'Pirate Fortune', 'https://imagensfivers.com/Games/PopOk/POPOK_138.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(845, 'POPOK_135', 'Wild Shamrock', 'https://imagensfivers.com/Games/PopOk/POPOK_135.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(846, 'POPOK_124', 'White Mustang', 'https://imagensfivers.com/Games/PopOk/POPOK_124.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(847, 'POPOK_20', 'Lucky Jungle', 'https://imagensfivers.com/Games/PopOk/POPOK_20.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(848, 'POPOK_33', 'Hot Muchacho', 'https://imagensfivers.com/Games/PopOk/POPOK_33.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(849, 'POPOK_103', 'Fast Fruits', 'https://imagensfivers.com/Games/PopOk/POPOK_103.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(850, 'POPOK_52', 'Los Toros', 'https://imagensfivers.com/Games/PopOk/POPOK_52.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(851, 'POPOK_123', 'Mr. First Lucky Wheel', 'https://imagensfivers.com/Games/PopOk/POPOK_123.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(852, 'POPOK_40', 'Hot Angels', 'https://imagensfivers.com/Games/PopOk/POPOK_40.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(853, 'POPOK_46', 'Gold of Egypt', 'https://imagensfivers.com/Games/PopOk/POPOK_46.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(854, 'POPOK_21', 'Lucky Jungle 1024', 'https://imagensfivers.com/Games/PopOk/POPOK_21.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(855, 'POPOK_51', 'Incredible Genie', 'https://imagensfivers.com/Games/PopOk/POPOK_51.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(856, 'POPOK_125', 'Royal Flash', 'https://imagensfivers.com/Games/PopOk/POPOK_125.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(857, 'POPOK_38', 'Vikings Mystery', 'https://imagensfivers.com/Games/PopOk/POPOK_38.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(858, 'POPOK_96', 'Milky Farm', 'https://imagensfivers.com/Games/PopOk/POPOK_96.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(859, 'POPOK_93', 'Age of Vikings', 'https://imagensfivers.com/Games/PopOk/POPOK_93.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(860, 'POPOK_34', 'Lucky Smile', 'https://imagensfivers.com/Games/PopOk/POPOK_34.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(861, 'POPOK_15', 'Poki Wild', 'https://imagensfivers.com/Games/PopOk/POPOK_15.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(862, 'POPOK_86', 'Wild Queen', 'https://imagensfivers.com/Games/PopOk/POPOK_86.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(863, 'POPOK_35', 'Happy Valentines Day', 'https://imagensfivers.com/Games/PopOk/POPOK_35.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(864, 'POPOK_76', 'Ho Ho Ho', 'https://imagensfivers.com/Games/PopOk/POPOK_76.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(865, 'POPOK_54', 'Black Booze', 'https://imagensfivers.com/Games/PopOk/POPOK_54.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(866, 'POPOK_87', 'Magic Time', 'https://imagensfivers.com/Games/PopOk/POPOK_87.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(867, 'POPOK_55', 'Ronin', 'https://imagensfivers.com/Games/PopOk/POPOK_55.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(868, 'POPOK_200', 'King Craft: Menomin', 'https://imagensfivers.com/Games/PopOk/POPOK_200.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(869, 'POPOK_62', 'Flaming Phoenix', 'https://imagensfivers.com/Games/PopOk/POPOK_62.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(870, 'POPOK_36', 'Era of Dragons', 'https://imagensfivers.com/Games/PopOk/POPOK_36.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(871, 'POPOK_220', 'BonBon', 'https://imagensfivers.com/Games/PopOk/POPOK_220.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(872, 'POPOK_294', 'Cavalo de Fogo Fortune', 'https://imagensfivers.com/Games/PopOk/POPOK_294.webp', 1, 'POPOK', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(873, 'JILI_472', '3 Coin Treasures 2', 'https://imagensfivers.com/Games/Jili/JILI_472.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(874, 'JILI_461', 'Pirate Queen 2', 'https://imagensfivers.com/Games/Jili/JILI_461.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(875, 'JILI_408', 'Safari King', 'https://imagensfivers.com/Games/Jili/JILI_408.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(876, 'JILI_518', 'Circus Joker 4096', 'https://imagensfivers.com/Games/Jili/JILI_518.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(877, 'JILI_394', 'Sweet Magic', 'https://imagensfivers.com/Games/Jili/JILI_394.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(878, 'JILI_517', 'Roma X Deluxe', 'https://imagensfivers.com/Games/Jili/JILI_517.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(879, 'JILI_504', 'Crazy777 2', 'https://imagensfivers.com/Games/Jili/JILI_504.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(880, 'JILI_542', 'Super Ace 2', 'https://imagensfivers.com/Games/Jili/JILI_542.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(881, 'JILI_424', 'Golden Empire 2', 'https://imagensfivers.com/Games/Jili/JILI_424.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(882, 'JILI_409', 'Super Ace Joker', 'https://imagensfivers.com/Games/Jili/JILI_409.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(883, 'JILI_399', '3 Coin Treasures', 'https://imagensfivers.com/Games/Jili/JILI_399.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(884, 'JILI_299', 'Potion Wizard', 'https://imagensfivers.com/Games/Jili/JILI_299.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(885, 'JILI_324', 'Nightfall Hunting', 'https://imagensfivers.com/Games/Jili/JILI_324.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(886, 'JILI_378', 'Golden Bank 2', 'https://imagensfivers.com/Games/Jili/JILI_378.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(887, 'JILI_463', '3 LUCKY LION', 'https://imagensfivers.com/Games/Jili/JILI_463.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(888, 'JILI_400', '3 Lucky Piggy', 'https://imagensfivers.com/Games/Jili/JILI_400.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(889, 'JILI_223', 'Fortune Gems 2', 'https://imagensfivers.com/Games/Jili/JILI_223.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(890, 'JILI_300', 'Fortune Gems 3', 'https://imagensfivers.com/Games/Jili/JILI_300.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(891, 'JILI_109', 'Fortune Gems', 'https://imagensfivers.com/Games/Jili/JILI_109.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(892, 'JILI_35', 'Crazy Seven', 'https://imagensfivers.com/Games/Jili/JILI_35.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(893, 'JILI_103', 'Golden Empire', 'https://imagensfivers.com/Games/Jili/JILI_103.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(894, 'JILI_47', 'Charge Buffalo', 'https://imagensfivers.com/Games/Jili/JILI_47.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(895, 'JILI_27', 'SevenSevenSeven', 'https://imagensfivers.com/Games/Jili/JILI_27.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(896, 'JILI_134', 'Mega Ace', 'https://imagensfivers.com/Games/Jili/JILI_134.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(897, 'JILI_100', 'Super Rich', 'https://imagensfivers.com/Games/Jili/JILI_100.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(898, 'JILI_77', 'BoxingKing', 'https://imagensfivers.com/Games/Jili/JILI_77.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(899, 'JILI_45', 'Golden Bank', 'https://imagensfivers.com/Games/Jili/JILI_45.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(900, 'JILI_209', 'Aztec Priestess', 'https://imagensfivers.com/Games/Jili/JILI_209.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(901, 'JILI_110', 'Ali Baba', 'https://imagensfivers.com/Games/Jili/JILI_110.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(902, 'JILI_91', 'Lucky Coming', 'https://imagensfivers.com/Games/Jili/JILI_91.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(903, 'JILI_40', 'Crazy FaFaFa', 'https://imagensfivers.com/Games/Jili/JILI_40.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(904, 'JILI_6', 'Fortune Tree', 'https://imagensfivers.com/Games/Jili/JILI_6.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(905, 'JILI_87', 'Book of Gold', 'https://imagensfivers.com/Games/Jili/JILI_87.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(906, 'JILI_16', 'Jungle King', 'https://imagensfivers.com/Games/Jili/JILI_16.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(907, 'JILI_108', 'Magic Lamp Bingo', 'https://imagensfivers.com/Games/Jili/JILI_108.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(908, 'JILI_258', 'Devil Fire 2', 'https://imagensfivers.com/Games/Jili/JILI_258.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(909, 'JILI_137', 'Gold Rush', 'https://imagensfivers.com/Games/Jili/JILI_137.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(910, 'JILI_106', 'TWIN WINS', 'https://imagensfivers.com/Games/Jili/JILI_106.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(911, 'JILI_142', 'Bonus Hunter', 'https://imagensfivers.com/Games/Jili/JILI_142.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(912, 'JILI_146', 'World Cup', 'https://imagensfivers.com/Games/Jili/JILI_146.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(913, 'JILI_85', 'Pharaoh Treasure', 'https://imagensfivers.com/Games/Jili/JILI_85.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(914, 'JILI_303', 'Fortune Monkey', 'https://imagensfivers.com/Games/Jili/JILI_303.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(915, 'JILI_58', 'Golden Queen', 'https://imagensfivers.com/Games/Jili/JILI_58.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(916, 'JILI_180', 'Legacy of Egypt', 'https://imagensfivers.com/Games/Jili/JILI_180.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(917, 'JILI_21', 'Fa Fa Fa', 'https://imagensfivers.com/Games/Jili/JILI_21.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(918, 'JILI_164', 'Pirate Queen', 'https://imagensfivers.com/Games/Jili/JILI_164.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(919, 'JILI_102', 'Roma X', 'https://imagensfivers.com/Games/Jili/JILI_102.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(920, 'JILI_126', 'Bone Fortune', 'https://imagensfivers.com/Games/Jili/JILI_126.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(921, 'JILI_135', 'Mayan Empire', 'https://imagensfivers.com/Games/Jili/JILI_135.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(922, 'JILI_193', 'Devil Fire', 'https://imagensfivers.com/Games/Jili/JILI_193.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(923, 'JILI_4', 'God Of Martial', 'https://imagensfivers.com/Games/Jili/JILI_4.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(924, 'JILI_225', 'Cricket King 18', 'https://imagensfivers.com/Games/Jili/JILI_225.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(925, 'JILI_43', 'XiYangYang', 'https://imagensfivers.com/Games/Jili/JILI_43.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(926, 'JILI_36', 'Bao boon chin', 'https://imagensfivers.com/Games/Jili/JILI_36.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(927, 'JILI_130', 'Thor X', 'https://imagensfivers.com/Games/Jili/JILI_130.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(928, 'JILI_263', 'The Pig House', 'https://imagensfivers.com/Games/Jili/JILI_263.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(929, 'JILI_115', 'Agent Ace', 'https://imagensfivers.com/Games/Jili/JILI_115.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(930, 'JILI_37', 'Night City', 'https://imagensfivers.com/Games/Jili/JILI_37.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(931, 'JILI_38', 'Fengshen', 'https://imagensfivers.com/Games/Jili/JILI_38.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(932, 'JILI_171', 'Sin City', 'https://imagensfivers.com/Games/Jili/JILI_171.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(933, 'JILI_2', 'Chin Shi Huang', 'https://imagensfivers.com/Games/Jili/JILI_2.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(934, 'JILI_226', 'Witches Night', 'https://imagensfivers.com/Games/Jili/JILI_226.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(935, 'JILI_239', 'Dabanggg', 'https://imagensfivers.com/Games/Jili/JILI_239.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(936, 'JILI_23', 'Candy Baby', 'https://imagensfivers.com/Games/Jili/JILI_23.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(937, 'JILI_198', 'Sweet Land', 'https://imagensfivers.com/Games/Jili/JILI_198.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(938, 'JILI_230', 'Cricket Sah 75', 'https://imagensfivers.com/Games/Jili/JILI_230.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(939, 'JILI_252', 'Zeus', 'https://imagensfivers.com/Games/Jili/JILI_252.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(940, 'JILI_145', 'Neko Fortune', 'https://imagensfivers.com/Games/Jili/JILI_145.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(941, 'JILI_9', 'War Of Dragons', 'https://imagensfivers.com/Games/Jili/JILI_9.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(942, 'JILI_214', 'King Arthur', 'https://imagensfivers.com/Games/Jili/JILI_214.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(943, 'JILI_116', 'Happy Taxi', 'https://imagensfivers.com/Games/Jili/JILI_116.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(944, 'JILI_17', 'Shanghai Beauty', 'https://imagensfivers.com/Games/Jili/JILI_17.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(945, 'JILI_76', 'Party Night', 'https://imagensfivers.com/Games/Jili/JILI_76.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(946, 'JILI_403', 'Super Ace Deluxe', 'https://imagensfivers.com/Games/Jili/JILI_403.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(947, 'JILI_49', 'Super Ace', 'https://imagensfivers.com/Games/Jili/JILI_49.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(948, 'JILI_238', 'Bangla Beauty', 'https://imagensfivers.com/Games/Jili/JILI_238.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(949, 'JILI_228', 'Arena Fighter', 'https://imagensfivers.com/Games/Jili/JILI_228.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(950, 'JILI_44', 'Diamond Party', 'https://imagensfivers.com/Games/Jili/JILI_44.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(951, 'JILI_13', 'Lucky Ball', 'https://imagensfivers.com/Games/Jili/JILI_13.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(952, 'JILI_26', 'Hawaii Beauty', 'https://imagensfivers.com/Games/Jili/JILI_26.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(953, 'JILI_374', 'Lucky Doggy', 'https://imagensfivers.com/Games/Jili/JILI_374.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(954, 'JILI_392', 'Coin Tree', 'https://imagensfivers.com/Games/Jili/JILI_392.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(955, 'JILI_181', 'WILD ACE', 'https://imagensfivers.com/Games/Jili/JILI_181.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(956, 'JILI_191', 'Golden Temple', 'https://imagensfivers.com/Games/Jili/JILI_191.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(957, 'JILI_375', 'Poseidon', 'https://imagensfivers.com/Games/Jili/JILI_375.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(958, 'JILI_240', 'Party Star', 'https://imagensfivers.com/Games/Jili/JILI_240.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(959, 'JILI_208', 'Trial of Phoenix', 'https://imagensfivers.com/Games/Jili/JILI_208.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(960, 'JILI_166', 'WILD RACER', 'https://imagensfivers.com/Games/Jili/JILI_166.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(961, 'JILI_422', 'Fruity Wheel', 'https://imagensfivers.com/Games/Jili/JILI_422.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(962, 'JILI_307', 'Treasure Quest', 'https://imagensfivers.com/Games/Jili/JILI_307.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(963, 'JILI_264', 'Egypt\'s Glow', 'https://imagensfivers.com/Games/Jili/JILI_264.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(964, 'JILI_523', 'Fortune Coins', 'https://imagensfivers.com/Games/Jili/JILI_523.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(965, 'JILI_460', 'Three Charge Buffalo', 'https://imagensfivers.com/Games/Jili/JILI_460.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(966, 'JILI_421', 'Lucky Jaguar', 'https://imagensfivers.com/Games/Jili/JILI_421.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(967, 'JILI_379', 'Money Pot', 'https://imagensfivers.com/Games/Jili/JILI_379.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(968, 'JILI_372', 'Three Pot Dragons', 'https://imagensfivers.com/Games/Jili/JILI_372.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(969, 'JILI_543', 'Money Coming 2', 'https://imagensfivers.com/Games/Jili/JILI_543.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(970, 'JILI_637', 'ROMA X 10000', 'https://imagensfivers.com/Games/Jili/JILI_637.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(971, 'JILI_447', 'Crystal 777 DELUXE', 'https://imagensfivers.com/Games/Jili/JILI_447.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(972, 'JILI_586', 'Party Ape', 'https://imagensfivers.com/Games/Jili/JILI_586.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(973, 'JILI_622', 'Three Coin Wild Tiger', 'https://imagensfivers.com/Games/Jili/JILI_622.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(974, 'JILI_563', 'Three Lucky Baozhu', 'https://imagensfivers.com/Games/Jili/JILI_563.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(975, 'JILI_581', 'Vinte Blazing Clover', 'https://imagensfivers.com/Games/Jili/JILI_581.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(976, 'JILI_541', 'Money Pot DELUXE', 'https://imagensfivers.com/Games/Jili/JILI_541.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(977, 'JILI_545', 'Clover Coins 3x3', 'https://imagensfivers.com/Games/Jili/JILI_545.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(978, 'JILI_423', 'Bikini Lady', 'https://imagensfivers.com/Games/Jili/JILI_423.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(979, 'JILI_448', 'Rapid Gems 777', 'https://imagensfivers.com/Games/Jili/JILI_448.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(980, 'JILI_526', 'Ten Sparkling Crown', 'https://imagensfivers.com/Games/Jili/JILI_526.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(981, 'JILI_558', 'Lucky Tiger', 'https://imagensfivers.com/Games/Jili/JILI_558.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(982, 'JILI_589', 'Forty Sparkling Crown', 'https://imagensfivers.com/Games/Jili/JILI_589.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(983, 'JILI_590', 'Clover Coins 4x4', 'https://imagensfivers.com/Games/Jili/JILI_590.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(984, 'JILI_591', 'Hundred Blazing Clover', 'https://imagensfivers.com/Games/Jili/JILI_591.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(985, 'JILI_593', 'Devil Fire Twins', 'https://imagensfivers.com/Games/Jili/JILI_593.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(986, 'JILI_594', 'Fortune Hook Boom', 'https://imagensfivers.com/Games/Jili/JILI_594.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(987, 'JILI_595', 'Dice & Drop', 'https://imagensfivers.com/Games/Jili/JILI_595.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(988, 'JILI_597', 'Supernova', 'https://imagensfivers.com/Games/Jili/JILI_597.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(989, 'JILI_623', 'Coin of Lightning', 'https://imagensfivers.com/Games/Jili/JILI_623.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(990, 'JILI_635', 'Joker\'s Fortune', 'https://imagensfivers.com/Games/Jili/JILI_635.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(991, 'JILI_643', 'Clover Ladybug', 'https://imagensfivers.com/Games/Jili/JILI_643.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(992, 'JILI_664', 'Lucky Jaguar 500', 'https://imagensfivers.com/Games/Jili/JILI_664.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(993, 'JILI_680', 'X7 HOT', 'https://imagensfivers.com/Games/Jili/JILI_680.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(994, 'JILI_711', 'Rise Of Egypt', 'https://imagensfivers.com/Games/Jili/JILI_711.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(995, 'JILI_393', 'Fortune Hook', 'https://imagensfivers.com/Games/Jili/JILI_393.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(996, 'JILI_547', 'Three Coin Golden OX', 'https://imagensfivers.com/Games/Jili/JILI_547.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(997, 'JILI_401', 'Big Boss', 'https://imagensfivers.com/Games/Jili/JILI_401.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(998, 'JILI_598', 'Lucky Macaw', 'https://imagensfivers.com/Games/Jili/JILI_598.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(999, 'JILI_621', 'Crown of Fortune', 'https://imagensfivers.com/Games/Jili/JILI_621.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1000, 'JILI_136', 'Samba', 'https://imagensfivers.com/Games/Jili/JILI_136.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1001, 'JILI_583', 'Cash Coin', 'https://imagensfivers.com/Games/Jili/JILI_583.webp', 1, 'JILI', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1002, 'rp.242', 'Jozi Gold: Soft Life Deluxe', 'https://imagensfivers.com/Games/rubyplay/rp.242.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1003, 'rp.216', 'Bufón Fortuna Deluxe', 'https://imagensfivers.com/Games/rubyplay/rp.216.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1004, 'rp.211', 'Mad Hit® Diamonds Deluxe', 'https://imagensfivers.com/Games/rubyplay/rp.211.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1005, 'rp.200', 'Diamond Explosion® 777', 'https://imagensfivers.com/Games/rubyplay/rp.200.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1006, 'rp.228', 'J Mania® Goal Nations', 'https://imagensfivers.com/Games/rubyplay/rp.228.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1007, 'rp.191', 'Rush Fever® Catch’em All', 'https://imagensfivers.com/Games/rubyplay/rp.191.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1008, 'rp.218', 'Olympus Explosion', 'https://imagensfivers.com/Games/rubyplay/rp.218.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1009, 'rp.215', 'Mad Hit® Gold', 'https://imagensfivers.com/Games/rubyplay/rp.215.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1010, 'rp.210', 'Mad Hit® Easter Eggsplosion', 'https://imagensfivers.com/Games/rubyplay/rp.210.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1011, 'rp.170', 'Fu Double Luck Diamond Explosion®', 'https://imagensfivers.com/Games/rubyplay/rp.170.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1012, 'rp.194', 'Bufón Fortuna', 'https://imagensfivers.com/Games/rubyplay/rp.194.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1013, 'rp.203', 'J Mania® Triple Jam', 'https://imagensfivers.com/Games/rubyplay/rp.203.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1014, 'rp.198', 'Book of Riches Deluxe: Chapter 2 SE', 'https://imagensfivers.com/Games/rubyplay/rp.198.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1015, 'rp.182', 'Ten Ton Ways® Odin’s Gifts', 'https://imagensfivers.com/Games/rubyplay/rp.182.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1016, 'rp.190', 'Mad Hit® Ho Ho Hot Pudding', 'https://imagensfivers.com/Games/rubyplay/rp.190.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1017, 'rp.204', 'Giga Match® Mahjong', 'https://imagensfivers.com/Games/rubyplay/rp.204.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1018, 'rp.199', 'Egyptian Sun SE', 'https://imagensfivers.com/Games/rubyplay/rp.199.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1019, 'rp.188', 'Mad Hit® Slippery Soap', 'https://imagensfivers.com/Games/rubyplay/rp.188.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1020, 'rp.180', 'Mad Hit® Mr Coin', 'https://imagensfivers.com/Games/rubyplay/rp.180.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1021, 'rp.187', 'Giga Match® Phantasmal', 'https://imagensfivers.com/Games/rubyplay/rp.187.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1022, 'rp.181', 'J Mania® Maniac', 'https://imagensfivers.com/Games/rubyplay/rp.181.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1023, 'rp.177', 'J Mania® Lucky Pyggs', 'https://imagensfivers.com/Games/rubyplay/rp.177.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1024, 'rp.185', 'J Mania® Hot Asses', 'https://imagensfivers.com/Games/rubyplay/rp.185.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1025, 'rp.176', 'Giga Match® Moolah', 'https://imagensfivers.com/Games/rubyplay/rp.176.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1026, 'rp.171', 'Go High® Safari', 'https://imagensfivers.com/Games/rubyplay/rp.171.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1027, 'rp.167', 'Mad Hit® Jokester', 'https://imagensfivers.com/Games/rubyplay/rp.167.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1028, 'rp.169', 'Mad Hit® Gorillatron', 'https://imagensfivers.com/Games/rubyplay/rp.169.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1029, 'rp.168', 'Giga Match® Gems', 'https://imagensfivers.com/Games/rubyplay/rp.168.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1030, 'rp.151', 'Rush Fever® 7s Deluxe SE', 'https://imagensfivers.com/Games/rubyplay/rp.151.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1031, 'rp.152', 'J Mania® 3 Buffalo', 'https://imagensfivers.com/Games/rubyplay/rp.152.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1032, 'rp.134', 'J Mania® Mayan Cache Dice', 'https://imagensfivers.com/Games/rubyplay/rp.134.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1033, 'rp.147', 'Vegas No Limit Wins SE', 'https://imagensfivers.com/Games/rubyplay/rp.147.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1034, 'rp.148', 'Zeus Rush Fever® Deluxe SE', 'https://imagensfivers.com/Games/rubyplay/rp.148.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1035, 'rp.140', 'Mad Hit® Diamonds', 'https://imagensfivers.com/Games/rubyplay/rp.140.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1036, 'rp.160', 'Giga Match® Bunny', 'https://imagensfivers.com/Games/rubyplay/rp.160.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1037, 'rp.139', 'Mad Hit® Full Steam', 'https://imagensfivers.com/Games/rubyplay/rp.139.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1038, 'rp.149', 'Elephant Stampede SE', 'https://imagensfivers.com/Games/rubyplay/rp.149.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1039, 'rp.165', 'Go High® Joker', 'https://imagensfivers.com/Games/rubyplay/rp.165.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1040, 'rp.163', 'Ten Ton Ways® Luck', 'https://imagensfivers.com/Games/rubyplay/rp.163.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1041, 'rp.120', 'Rush Fever® Soda Pop', 'https://imagensfivers.com/Games/rubyplay/rp.120.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1042, 'rp.155', 'Mad Hit® Savannah', 'https://imagensfivers.com/Games/rubyplay/rp.155.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1043, 'rp.150', 'Volcano Rising SE', 'https://imagensfivers.com/Games/rubyplay/rp.150.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1044, 'rp.159', 'Go High® Puppy Love', 'https://imagensfivers.com/Games/rubyplay/rp.159.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1045, 'rp.146', 'Diamond Explosion® 7s SE', 'https://imagensfivers.com/Games/rubyplay/rp.146.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1046, 'rp.130', 'Giga Match® Fruits', 'https://imagensfivers.com/Games/rubyplay/rp.130.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1047, 'rp.138', 'Giga Match® North Pole', 'https://imagensfivers.com/Games/rubyplay/rp.138.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1048, 'rp.144', 'Diamond Explosion® Christmas Tango', 'https://imagensfivers.com/Games/rubyplay/rp.144.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1049, 'rp.129', 'Ten Ton Ways® Goldfish', 'https://imagensfivers.com/Games/rubyplay/rp.129.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1050, 'rp.142', 'Go High® Gallina', 'https://imagensfivers.com/Games/rubyplay/rp.142.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1051, 'rp.136', 'Giga Match® Sugar Skull', 'https://imagensfivers.com/Games/rubyplay/rp.136.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1052, 'rp.125', 'Gummy Giga Match®', 'https://imagensfivers.com/Games/rubyplay/rp.125.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1053, 'rp.133', 'Mad Hit® Bearhaus', 'https://imagensfivers.com/Games/rubyplay/rp.133.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1054, 'rp.132', 'J Mania® Turkey Chef', 'https://imagensfivers.com/Games/rubyplay/rp.132.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1055, 'rp.119', 'Sugar Rush Fever®', 'https://imagensfivers.com/Games/rubyplay/rp.119.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1056, 'rp.109', 'Go High® Panda', 'https://imagensfivers.com/Games/rubyplay/rp.109.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1057, 'rp.124', 'Diamond Explosion® Patriots', 'https://imagensfivers.com/Games/rubyplay/rp.124.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1058, 'rp.114', 'Go High® Olympus', 'https://imagensfivers.com/Games/rubyplay/rp.114.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1059, 'rp.118', 'J Mania® Diamonds', 'https://imagensfivers.com/Games/rubyplay/rp.118.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1060, 'rp.103', 'Mad Hit® Hidden Cities', 'https://imagensfivers.com/Games/rubyplay/rp.103.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1061, 'rp.112', 'Go High® Fruit', 'https://imagensfivers.com/Games/rubyplay/rp.112.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1062, 'rp.108', 'Mad Hit® Supernova', 'https://imagensfivers.com/Games/rubyplay/rp.108.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1063, 'rp.111', 'Go High® Gone Fishing', 'https://imagensfivers.com/Games/rubyplay/rp.111.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1064, 'rp.104', 'J Mania® Golden Eggs', 'https://imagensfivers.com/Games/rubyplay/rp.104.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1065, 'rp.107', 'Mad Hit® Wild Alice', 'https://imagensfivers.com/Games/rubyplay/rp.107.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1066, 'rp.101', 'Diamond Explosion® Sweet Love', 'https://imagensfivers.com/Games/rubyplay/rp.101.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1067, 'rp.98', 'Go High® Harvest', 'https://imagensfivers.com/Games/rubyplay/rp.98.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1068, 'rp.102', 'Mad Hit® Marlin Bonanza', 'https://imagensfivers.com/Games/rubyplay/rp.102.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1069, 'rp.99', 'Stampede Rush® Speedy Santa', 'https://imagensfivers.com/Games/rubyplay/rp.99.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1070, 'rp.96', 'Mad Hit® Oasis', 'https://imagensfivers.com/Games/rubyplay/rp.96.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1071, 'rp.97', 'Stampede Rush® Wicked', 'https://imagensfivers.com/Games/rubyplay/rp.97.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1072, 'rp.91', 'ZapOtec Gold', 'https://imagensfivers.com/Games/rubyplay/rp.91.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1073, 'rp.85', 'Prince of Riches', 'https://imagensfivers.com/Games/rubyplay/rp.85.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1074, 'rp.84', 'Vegas Repeat Wins', 'https://imagensfivers.com/Games/rubyplay/rp.84.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1075, 'rp.81', 'Cache Rush', 'https://imagensfivers.com/Games/rubyplay/rp.81.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1076, 'rp.83', 'Rockin’ Gorilla', 'https://imagensfivers.com/Games/rubyplay/rp.83.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1077, 'rp.80', 'Prosperity Blessing', 'https://imagensfivers.com/Games/rubyplay/rp.80.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1078, 'rp.16', 'Immortal Empress', 'https://imagensfivers.com/Games/rubyplay/rp.16.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1079, 'rp.79', 'Royal Griffin', 'https://imagensfivers.com/Games/rubyplay/rp.79.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1080, 'rp.78', 'Tiki Blaze', 'https://imagensfivers.com/Games/rubyplay/rp.78.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1081, 'rp.73', 'Rush Fever 7s Deluxe', 'https://imagensfivers.com/Games/rubyplay/rp.73.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1082, 'rp.71', 'Arabian Secret', 'https://imagensfivers.com/Games/rubyplay/rp.71.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1083, 'rp.72', 'Diamond Explosion ® 7s', 'https://imagensfivers.com/Games/rubyplay/rp.72.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1084, 'rp.68', 'Zeus Rush Fever Deluxe', 'https://imagensfivers.com/Games/rubyplay/rp.68.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1085, 'rp.65', 'Mayan Blaze', 'https://imagensfivers.com/Games/rubyplay/rp.65.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1086, 'rp.67', 'Roman Legend', 'https://imagensfivers.com/Games/rubyplay/rp.67.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1087, 'rp.63', 'Book of Riches Deluxe: Chapter 2', 'https://imagensfivers.com/Games/rubyplay/rp.63.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1088, 'rp.62', 'New Year Rising', 'https://imagensfivers.com/Games/rubyplay/rp.62.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1089, 'rp.58', 'Volcano Rising', 'https://imagensfivers.com/Games/rubyplay/rp.58.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1090, 'rp.56', 'Wizard’s Spell', 'https://imagensfivers.com/Games/rubyplay/rp.56.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1091, 'rp.52', 'Vegas No Limit Wins', 'https://imagensfivers.com/Games/rubyplay/rp.52.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1092, 'rp.51', 'Adventures of Li’l Red', 'https://imagensfivers.com/Games/rubyplay/rp.51.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1093, 'rp.50', 'Enter the Vault', 'https://imagensfivers.com/Games/rubyplay/rp.50.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1094, 'rp.49', 'Bull Fever', 'https://imagensfivers.com/Games/rubyplay/rp.49.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1095, 'rp.45', 'Silver & Gold Mine', 'https://imagensfivers.com/Games/rubyplay/rp.45.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1096, 'rp.44', 'Rush Fever 7s', 'https://imagensfivers.com/Games/rubyplay/rp.44.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1097, 'rp.43', 'Dawn of the Incas', 'https://imagensfivers.com/Games/rubyplay/rp.43.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1098, 'rp.42', 'Elephant Stampede', 'https://imagensfivers.com/Games/rubyplay/rp.42.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver');
INSERT INTO `games` (`id`, `game_code`, `game_name`, `banner`, `status`, `provider`, `popular`, `type`, `game_type`, `api`) VALUES
(1099, 'rp.41', 'Prosperity Journey', 'https://imagensfivers.com/Games/rubyplay/rp.41.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1100, 'rp.38', 'Zeus Rush Fever', 'https://imagensfivers.com/Games/rubyplay/rp.38.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1101, 'rp.36', 'He He Yang', 'https://imagensfivers.com/Games/rubyplay/rp.36.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1102, 'rp.21', 'Dr. Frankenstein', 'https://imagensfivers.com/Games/rubyplay/rp.21.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1103, 'rp.34', 'Sun of Ra', 'https://imagensfivers.com/Games/rubyplay/rp.34.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1104, 'rp.33', 'Dragon 8s 25x', 'https://imagensfivers.com/Games/rubyplay/rp.33.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1105, 'rp.28', 'Egyptian Sun', 'https://imagensfivers.com/Games/rubyplay/rp.28.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1106, 'rp.24', 'Arcane Beauties', 'https://imagensfivers.com/Games/rubyplay/rp.24.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1107, 'rp.30', 'Reel All-Stars', 'https://imagensfivers.com/Games/rubyplay/rp.30.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1108, 'rp.31', 'Pirate’s Booty', 'https://imagensfivers.com/Games/rubyplay/rp.31.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1109, 'rp.15', 'Brilliant Gems', 'https://imagensfivers.com/Games/rubyplay/rp.15.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1110, 'rp.23', 'Quest of Gods', 'https://imagensfivers.com/Games/rubyplay/rp.23.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1111, 'rp.18', 'Gallantry', 'https://imagensfivers.com/Games/rubyplay/rp.18.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1112, 'rp.32', 'Mayan Cache', 'https://imagensfivers.com/Games/rubyplay/rp.32.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1113, 'rp.22', 'Book of Riches Deluxe', 'https://imagensfivers.com/Games/rubyplay/rp.22.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1114, 'rp.20', 'Loco Habanero', 'https://imagensfivers.com/Games/rubyplay/rp.20.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1115, 'rp.11', 'Bamboo Fortune', 'https://imagensfivers.com/Games/rubyplay/rp.11.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1116, 'rp.8', 'Lady Phoenix', 'https://imagensfivers.com/Games/rubyplay/rp.8.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1117, 'rp.13', 'Rio Pleasures', 'https://imagensfivers.com/Games/rubyplay/rp.13.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1118, 'rp.10', 'Book of Riches', 'https://imagensfivers.com/Games/rubyplay/rp.10.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1119, 'rp.5', 'Hot and Heavy', 'https://imagensfivers.com/Games/rubyplay/rp.5.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1120, 'rp.3', 'Dragon Prophecy', 'https://imagensfivers.com/Games/rubyplay/rp.3.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1121, 'rp.1', 'Madame Luck', 'https://imagensfivers.com/Games/rubyplay/rp.1.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1122, 'rp.2', 'Medusa Money', 'https://imagensfivers.com/Games/rubyplay/rp.2.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1123, 'rp.12', 'Sheerluck', 'https://imagensfivers.com/Games/rubyplay/rp.12.webp', 1, 'RUBYPLAY', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1397, 'dt.alchemist', 'Crazy Alchemist', 'https://imagensfivers.com/Games/Dreamtech/alchemist.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1398, 'dt.bacteria', 'Germ Lab', 'https://imagensfivers.com/Games/Dreamtech/bacteria.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1399, 'dt.baseball', 'Baseball Frenzy', 'https://imagensfivers.com/Games/Dreamtech/baseball.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1400, 'dt.bigred', 'Big Red', 'https://imagensfivers.com/Games/Dreamtech/bigred.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1401, 'dt.bikini', 'Bikini Party', 'https://imagensfivers.com/Games/Dreamtech/bikini.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1402, 'dt.bird', 'Bird Island', 'https://imagensfivers.com/Games/Dreamtech/bird.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1403, 'dt.bloodmoon', 'Blood Wolf legend', 'https://imagensfivers.com/Games/Dreamtech/bloodmoon.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1404, 'dt.bluesea', 'Blue Sea', 'https://imagensfivers.com/Games/Dreamtech/bluesea.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1405, 'dt.boxingarena', 'Boxing Arena', 'https://imagensfivers.com/Games/Dreamtech/boxingarena.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1406, 'dt.candycrush', 'Candy Kingdom', 'https://imagensfivers.com/Games/Dreamtech/candycrush.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1407, 'dt.casino', '3D Slot', 'https://imagensfivers.com/Games/Dreamtech/casino.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1408, 'dt.cheekyemojis', 'Cheeky Emojis', 'https://imagensfivers.com/Games/Dreamtech/cheekyemojis.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1409, 'dt.chess', 'Dragon Auto Chess', 'https://imagensfivers.com/Games/Dreamtech/chess.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1410, 'dt.circus', 'Crazy Circus', 'https://imagensfivers.com/Games/Dreamtech/circus.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1411, 'dt.clash', 'Clash of Three kingdoms', 'https://imagensfivers.com/Games/Dreamtech/clash.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1412, 'dt.crystal', 'Glory of Heroes', 'https://imagensfivers.com/Games/Dreamtech/crystal.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1413, 'dt.dash', 'Four Wheels & Bro', 'https://imagensfivers.com/Games/Dreamtech/dash.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1414, 'dt.dnp', 'DragonPhoenix Prosper', 'https://imagensfivers.com/Games/Dreamtech/dnp.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1415, 'dt.dragonball2', 'Heroes of the East', 'https://imagensfivers.com/Games/Dreamtech/dragonball2.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1416, 'dt.easyrider', 'Easy Rider', 'https://imagensfivers.com/Games/Dreamtech/easyrider.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1417, 'dt.egyptian', 'Egyptian Empire', 'https://imagensfivers.com/Games/Dreamtech/egyptian.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1418, 'dt.fantasyforest', 'Elf Kingdom', 'https://imagensfivers.com/Games/Dreamtech/fantasyforest.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1419, 'dt.fls', 'FULUSHOU', 'https://imagensfivers.com/Games/Dreamtech/fls.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1420, 'dt.fourbeauty', 'Four Beauties', 'https://imagensfivers.com/Games/Dreamtech/fourbeauty.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1421, 'dt.fourss', 'Four Holy Beasts', 'https://imagensfivers.com/Games/Dreamtech/fourss.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1422, 'dt.galaxywars', 'Galaxy Wars', 'https://imagensfivers.com/Games/Dreamtech/galaxywars.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1423, 'dt.garden', 'Little Big Garden', 'https://imagensfivers.com/Games/Dreamtech/garden.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1424, 'dt.genie', 'Aladdin\'s Wish', 'https://imagensfivers.com/Games/Dreamtech/genie.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1425, 'dt.greatgatsby', 'Great Gatsby', 'https://imagensfivers.com/Games/Dreamtech/greatgatsby.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1426, 'dt.guhuozai', 'Blood Street', 'https://imagensfivers.com/Games/Dreamtech/guhuozai.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1427, 'dt.halloween', 'Witch Winnings', 'https://imagensfivers.com/Games/Dreamtech/halloween.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1428, 'dt.honor', 'Field Of Honor', 'https://imagensfivers.com/Games/Dreamtech/honor.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1429, 'dt.hotpotfeast', 'Hotpot Feast', 'https://imagensfivers.com/Games/Dreamtech/hotpotfeast.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1430, 'dt.icefire', 'Ice And Fire', 'https://imagensfivers.com/Games/Dreamtech/icefire.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1431, 'dt.jtw', 'Demon Hunt to West', 'https://imagensfivers.com/Games/Dreamtech/jtw.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1432, 'dt.legendary', 'Legendary Tales', 'https://imagensfivers.com/Games/Dreamtech/legendary.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1433, 'dt.lovefighters', 'Love Fighters', 'https://imagensfivers.com/Games/Dreamtech/lovefighters.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1434, 'dt.magicbean', 'Magic Bean Legend', 'https://imagensfivers.com/Games/Dreamtech/magicbean.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1435, 'dt.magician', 'Magician', 'https://imagensfivers.com/Games/Dreamtech/magician.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1436, 'dt.merryxmas', 'Merry Xmas', 'https://imagensfivers.com/Games/Dreamtech/merryxmas.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1437, 'dt.muaythai', 'Muaythai', 'https://imagensfivers.com/Games/Dreamtech/muaythai.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1438, 'dt.museum', 'Wondrous Museum', 'https://imagensfivers.com/Games/Dreamtech/museum.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1439, 'dt.mysticalstones', 'Mystical Stones', 'https://imagensfivers.com/Games/Dreamtech/mysticalstones.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1440, 'dt.newonepiece', 'KING OF PIRATE', 'https://imagensfivers.com/Games/Dreamtech/newonepiece.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1441, 'dt.newsd', 'Basketball Master', 'https://imagensfivers.com/Games/Dreamtech/newsd.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1442, 'dt.newsd5', 'Basketball Master 2', 'https://imagensfivers.com/Games/Dreamtech/newsd5.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1443, 'dt.newyear', 'Happy New Year', 'https://imagensfivers.com/Games/Dreamtech/newyear.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1444, 'dt.nezha', 'Nezha Legend', 'https://imagensfivers.com/Games/Dreamtech/nezha.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1445, 'dt.nightclub', 'Infinity Club', 'https://imagensfivers.com/Games/Dreamtech/nightclub.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1446, 'dt.opera', 'Beijing opera', 'https://imagensfivers.com/Games/Dreamtech/opera.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1447, 'dt.peeping', 'Peeping', 'https://imagensfivers.com/Games/Dreamtech/peeping.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1448, 'dt.piratetreasure', 'Pirate_Treasure', 'https://imagensfivers.com/Games/Dreamtech/piratetreasure.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1449, 'dt.rocknight', 'RocknRoll Night', 'https://imagensfivers.com/Games/Dreamtech/rocknight.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1450, 'dt.rocknight2', 'Rock Night2', 'https://imagensfivers.com/Games/Dreamtech/rocknight2.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1451, 'dt.sailor', 'Sailor Princess', 'https://imagensfivers.com/Games/Dreamtech/sailor.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1452, 'dt.san', 'Battle of Red Cliffs', 'https://imagensfivers.com/Games/Dreamtech/san.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1453, 'dt.secretdate', 'Secret Date', 'https://imagensfivers.com/Games/Dreamtech/secretdate.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1454, 'dt.shiningstars', 'ShiningStars', 'https://imagensfivers.com/Games/Dreamtech/shiningstars.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1455, 'dt.shokudo', 'shokudo', 'https://imagensfivers.com/Games/Dreamtech/shokudo.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1456, 'dt.sincity', 'Sin City', 'https://imagensfivers.com/Games/Dreamtech/sincity.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1457, 'dt.sweethouse', 'Candy House', 'https://imagensfivers.com/Games/Dreamtech/sweethouse.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1458, 'dt.tgow', 'Wealthy God Arriving', 'https://imagensfivers.com/Games/Dreamtech/tgow.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1459, 'dt.tgow2', 'Cai Shen Dao', 'https://imagensfivers.com/Games/Dreamtech/tgow2.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1460, 'dt.tgow2plus', 'Cai Shen Dao Plus', 'https://imagensfivers.com/Games/Dreamtech/tgow2plus.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1461, 'dt.tlod', 'Investiture of Gods', 'https://imagensfivers.com/Games/Dreamtech/tlod.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1462, 'dt.tombshadow', 'Tomb Shadow', 'https://imagensfivers.com/Games/Dreamtech/tombshadow.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1463, 'dt.train', 'Treasure Hunt Trip', 'https://imagensfivers.com/Games/Dreamtech/train.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1464, 'dt.train2', 'Treasure Hunt Trip 2', 'https://imagensfivers.com/Games/Dreamtech/train2.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1465, 'dt.treasures', 'Secret Treasures', 'https://imagensfivers.com/Games/Dreamtech/treasures.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1466, 'dt.vikings', 'Nordic Saga', 'https://imagensfivers.com/Games/Dreamtech/vikings.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1467, 'dt.watermargin', 'Tales Of Wusong', 'https://imagensfivers.com/Games/Dreamtech/watermargin.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1468, 'dt.westwild', 'West Wild', 'https://imagensfivers.com/Games/Dreamtech/westwild.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1469, 'dt.whackamole', 'Whack-A-Mole', 'https://imagensfivers.com/Games/Dreamtech/whackamole.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1470, 'dt.wheel', 'Secrets of The Pentagram', 'https://imagensfivers.com/Games/Dreamtech/wheel.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1471, 'dt.whitesnake', 'Tales of White Snake', 'https://imagensfivers.com/Games/Dreamtech/whitesnake.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1472, 'dt.wrathofthor', 'Wrath of Thor', 'https://imagensfivers.com/Games/Dreamtech/wrathofthor.webp', 1, 'DREAMTECH', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1473, 'tt.battleheroes', 'Battle Heroes', 'https://imagensfivers.com/Games/Toptrend/BattleHeroes.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1474, 'tt.chilligoldh5', 'Chilli Gold', 'https://imagensfivers.com/Games/Toptrend/ChilliGoldH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1475, 'tt.diamondfortune', 'DiamondFortune', 'https://imagensfivers.com/Games/Toptrend/DiamondFortune.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1476, 'tt.dolphingoldh5', 'DolphinGold', 'https://imagensfivers.com/Games/Toptrend/DolphinGoldH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1477, 'tt.dragonkingh5', 'DragonKing', 'https://imagensfivers.com/Games/Toptrend/DragonKingH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1478, 'tt.dragonpalaceh5', 'Dragon Palace', 'https://imagensfivers.com/Games/Toptrend/DragonPalaceH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1479, 'tt.dynastyempire', 'Dynasty Empire', 'https://imagensfivers.com/Games/Toptrend/DynastyEmpire.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1480, 'tt.everlastingspins', 'EverlastingSpins', 'https://imagensfivers.com/Games/Toptrend/EverlastingSpins.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1481, 'tt.goldenamazon', 'Golden Amazon', 'https://imagensfivers.com/Games/Toptrend/GoldenAmazon.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1482, 'tt.goldenclaw', 'GoldenClaw', 'https://imagensfivers.com/Games/Toptrend/GoldenClaw.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1483, 'tt.goldengenie', 'GoldenGenie', 'https://imagensfivers.com/Games/Toptrend/GoldenGenie.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1484, 'tt.heroesneverdie', 'Heroes Never Die', 'https://imagensfivers.com/Games/Toptrend/HeroesNeverDie.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1485, 'tt.hotvolcanoh5', 'HotVolcano', 'https://imagensfivers.com/Games/Toptrend/HotVolcanoH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1486, 'tt.huluwa', 'Huluwa', 'https://imagensfivers.com/Games/Toptrend/Huluwa.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1487, 'tt.kingdinosaur', 'KingDinosaur', 'https://imagensfivers.com/Games/Toptrend/KingDinosaur.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1488, 'tt.leagueofchampions', 'League Of Champions', 'https://imagensfivers.com/Games/Toptrend/LeagueOfChampions.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1489, 'tt.legendoflinkh5', 'LegendOfLink', 'https://imagensfivers.com/Games/Toptrend/LegendOfLinkH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1490, 'tt.luckypandah5', 'LuckyPanda', 'https://imagensfivers.com/Games/Toptrend/LuckyPandaH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1491, 'tt.madmonkey2', 'MadMonkey', 'https://imagensfivers.com/Games/Toptrend/MadMonkey2.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1492, 'tt.medusacurse', 'Medusa Curse', 'https://imagensfivers.com/Games/Toptrend/MedusaCurse.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1493, 'tt.monkeyluck', 'MonkeyLuck', 'https://imagensfivers.com/Games/Toptrend/MonkeyLuck.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1494, 'tt.neptunesgoldh5', 'Neptunes Gold', 'https://imagensfivers.com/Games/Toptrend/NeptunesGoldH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1495, 'tt.pandawarrior', 'PandaWarrior', 'https://imagensfivers.com/Games/Toptrend/PandaWarrior.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1496, 'tt.reelsoffortune', 'Reels Of Fortune', 'https://imagensfivers.com/Games/Toptrend/ReelsOfFortune.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1497, 'tt.royalgoldendragon', 'RoyalGoldenDragon', 'https://imagensfivers.com/Games/Toptrend/RoyalGoldenDragon.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1498, 'tt.silverlionh5', 'Silver Lion', 'https://imagensfivers.com/Games/Toptrend/SilverLionH5.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1499, 'tt.superkids', 'SuperKids', 'https://imagensfivers.com/Games/Toptrend/SuperKids.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1500, 'tt.thunderingzeus', 'ThunderingZeus', 'https://imagensfivers.com/Games/Toptrend/ThunderingZeus.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1501, 'tt.ultimatefighter', 'UltimateFighter', 'https://imagensfivers.com/Games/Toptrend/UltimateFighter.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1502, 'tt.wildkartracers', 'Wild Kart Racers', 'https://imagensfivers.com/Games/Toptrend/WildKartRacers.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1503, 'tt.wildwildwitch', 'Wild Wild Witch', 'https://imagensfivers.com/Games/Toptrend/WildWildWitch.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver'),
(1504, 'tt.zoomania', 'Zoomania', 'https://imagensfivers.com/Games/Toptrend/Zoomania.webp', 1, 'TOPTREND', 0, 'slot', 'ELECTRONIC', 'playfiver');

-- --------------------------------------------------------

--
-- Estrutura para tabela `game_categories`
--

CREATE TABLE `game_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `game_categories`
--

INSERT INTO `game_categories` (`id`, `name`, `slug`, `sort`, `status`) VALUES
(1, 'All', 'all', 1, 1),
(2, 'Electronic', 'ELECTRONIC', 2, 1),
(3, 'Chess', 'CHESS', 3, 1),
(4, 'Fishing', 'FISHING', 4, 0),
(5, 'Video', 'VIDEO', 5, 0),
(6, 'Sports', 'SPORTS', 6, 0),
(7, 'Lottery', 'LOTTERY', 7, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `greepay`
--

CREATE TABLE `greepay` (
  `id` int(11) NOT NULL,
  `ativo` int(11) DEFAULT 0,
  `client_id` varchar(255) DEFAULT NULL,
  `client_secret` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `greepay`
--

INSERT INTO `greepay` (`id`, `ativo`, `client_id`, `client_secret`, `url`, `created_at`, `updated_at`) VALUES
(1, 1, 'patrickwillianhundemann_RNFPTQ8H', 'Wwx9DsAloTQCImDkuWD5TCkqUUYLltvp5ir2w4M9iyqlc5Jkx4LRWm9vvEkPbUVaHAtanLoAT7Z0StTj5dOeXWmBgl594EMMaHZs', 'https://api.greepay.com.br', '2025-12-28 20:11:15', '2026-03-07 12:08:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_play`
--

CREATE TABLE `historico_play` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `nome_game` text NOT NULL,
  `bet_money` decimal(10,2) NOT NULL DEFAULT 0.00,
  `win_money` decimal(10,2) NOT NULL DEFAULT 0.00,
  `txn_id` text NOT NULL,
  `created_at` datetime NOT NULL,
  `status_play` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `historico_play`
--

INSERT INTO `historico_play` (`id`, `id_user`, `nome_game`, `bet_money`, `win_money`, `txn_id`, `created_at`, `status_play`) VALUES
(26, 777209963, '2100928', 0.50, 0.00, '1f19eabd-32e5-6462-839b-d6af185a8f44', '2026-08-23 04:33:44', 1),
(27, 777209963, '2100928', 0.50, 0.00, '1f19eabd-754e-672c-93e3-6e4757122f15', '2026-08-23 04:33:51', 1),
(28, 777209963, '126', 1.20, 0.00, '1f19eac3-dd1c-6de4-83e4-ee41c2bfbb94', '2026-08-23 04:36:45', 1),
(29, 777209963, '126', 1.20, 0.00, '1f19eac4-3e41-6d0e-b0aa-f6b022d55034', '2026-08-23 04:36:53', 1),
(30, 777209963, '126', 1.20, 0.00, '1f19ead7-5c86-639c-afd9-4640ad2f6c52', '2026-08-23 04:45:27', 1),
(31, 777209963, '126', 1.20, 0.00, '1f19ead7-a8b8-6d14-9ad9-e210081893b2', '2026-08-23 04:45:34', 1),
(32, 777209963, '126', 1.20, 0.00, '1f19ead7-cdf5-61d6-8cd2-82d859554e05', '2026-08-23 04:45:38', 1),
(33, 777209963, '2100928', 0.50, 0.00, '1f19eadf-31af-6468-8b73-120a9d342346', '2026-08-23 04:48:57', 1),
(34, 777209963, '2100928', 0.50, 0.00, '1f19eadf-a0ce-6ede-b011-862d8b062007', '2026-08-23 04:49:08', 1),
(35, 777209963, '2100928', 0.50, 0.00, '1f19eadf-bcd0-69c0-be89-3a915f929c90', '2026-08-23 04:49:11', 1),
(36, 777209963, '2100928', 0.50, 0.00, '1f19eae0-413f-6fb2-b20a-e210081893b2', '2026-08-23 04:49:27', 1),
(37, 777209963, '2100928', 0.50, 0.00, '1f19eae0-d024-6408-b4b3-862d8b062007', '2026-08-23 04:49:40', 1),
(38, 777209963, '2100928', 0.50, 0.00, '1f19eae0-eff3-620c-944d-6e4757122f15', '2026-08-23 04:49:43', 1),
(39, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf0-b979-6a4a-aad9-6211287f85d9', '2026-08-23 04:56:47', 1),
(40, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf0-cca7-6f9a-9da5-120a9d342346', '2026-08-23 04:56:49', 1),
(41, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf1-0f8d-68e6-8805-c6e4923ba50d', '2026-08-23 04:56:56', 1),
(42, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf1-2276-65ac-8c6e-92bbb659c674', '2026-08-23 04:56:58', 1),
(43, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf1-31c0-6364-bc48-a2b716130736', '2026-08-23 04:57:00', 1),
(44, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf1-3f4c-6ec4-9adf-4640ad2f6c52', '2026-08-23 04:57:01', 1),
(45, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf1-4f9a-69fc-b2ff-6e4757122f15', '2026-08-23 04:57:03', 1),
(46, 777209963, 'vswaystonypzz', 0.40, 0.00, '1f19eaf1-8ee0-6986-a090-92bbb659c674', '2026-08-23 04:57:10', 1),
(47, 777209963, 'rp.12', 2.00, 0.00, '1f19eafd-5df6-6d14-97bc-862d8b062007', '2026-08-23 05:02:27', 1),
(48, 777209963, 'rp.12', 2.00, 0.00, '1f19eafd-7527-65f6-9d36-e210081893b2', '2026-08-23 05:02:29', 1),
(49, 777209963, 'rp.12', 2.00, 0.00, '1f19eafd-9653-6162-b2fc-120a9d342346', '2026-08-23 05:02:32', 1),
(50, 777209963, 'rp.12', 2.00, 4.40, '1f19eafd-c147-60da-962f-4640ad2f6c52', '2026-08-23 05:02:37', 1),
(51, 777209963, 'rp.12', 2.00, 1.20, '1f19eafd-f663-6688-9d0e-c6e4923ba50d', '2026-08-23 05:02:43', 1),
(52, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-16cd-65c2-bd3e-faaf93a9be81', '2026-08-23 05:02:46', 1),
(53, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-3cd4-64a0-a3de-c6e4923ba50d', '2026-08-23 05:02:50', 1),
(54, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-680f-62c8-be21-d6af185a8f44', '2026-08-23 05:02:54', 1),
(55, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-79f5-63ca-af48-d6af185a8f44', '2026-08-23 05:02:56', 1),
(56, 777209963, 'rp.12', 2.00, 9.00, '1f19eafe-8d67-6c96-b823-0a5e8161e793', '2026-08-23 05:02:58', 1),
(57, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-ad91-681e-99ec-4640ad2f6c52', '2026-08-23 05:03:02', 1),
(58, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-c39a-6782-9799-c6e4923ba50d', '2026-08-23 05:03:04', 1),
(59, 777209963, 'rp.12', 2.00, 0.60, '1f19eafe-d230-6a6c-8ade-d6af185a8f44', '2026-08-23 05:03:06', 1),
(60, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-e1ca-6554-8949-6211287f85d9', '2026-08-23 05:03:07', 1),
(61, 777209963, 'rp.12', 2.00, 0.00, '1f19eafe-ef99-6194-8736-6e4757122f15', '2026-08-23 05:03:09', 1),
(62, 777209963, 'rp.12', 2.00, 10.00, '1f19eafe-fc41-65ae-9d51-0eabfe8e0c94', '2026-08-23 05:03:10', 1),
(63, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-13a6-6bea-b380-92bbb659c674', '2026-08-23 05:03:12', 1),
(64, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-2897-60fe-942e-e210081893b2', '2026-08-23 05:03:15', 1),
(65, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-3a7a-6708-b01a-d6af185a8f44', '2026-08-23 05:03:17', 1),
(66, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-5159-6abe-9afe-82d859554e05', '2026-08-23 05:03:19', 1),
(67, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-604b-6b76-a432-faaf93a9be81', '2026-08-23 05:03:20', 1),
(68, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-7052-639e-a29f-d6af185a8f44', '2026-08-23 05:03:22', 1),
(69, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-7ea2-62d2-b58a-56d6bc8abba1', '2026-08-23 05:03:24', 1),
(70, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-8f07-6f96-a153-c6e4923ba50d', '2026-08-23 05:03:25', 1),
(71, 777209963, 'rp.12', 2.00, 1.20, '1f19eaff-a44b-629a-87f5-120a9d342346', '2026-08-23 05:03:28', 1),
(72, 777209963, 'rp.12', 2.00, 1.60, '1f19eaff-bbe3-6330-af20-c6e4923ba50d', '2026-08-23 05:03:30', 1),
(73, 777209963, 'rp.12', 2.00, 0.00, '1f19eaff-ceb0-67a6-a561-6e4757122f15', '2026-08-23 05:03:32', 1),
(74, 777209963, 'rp.12', 2.00, 16.80, '1f19eaff-e1fe-6a60-afb7-ee41c2bfbb94', '2026-08-23 05:03:34', 1),
(75, 777209963, 'rp.12', 2.00, 1.80, '1f19eaff-ff75-68d2-93cb-862d8b062007', '2026-08-23 05:03:37', 1),
(76, 777209963, 'rp.12', 2.00, 0.00, '1f19eb00-16ae-6350-99a8-f6b022d55034', '2026-08-23 05:03:40', 1),
(77, 777209963, 'rp.12', 2.00, 0.00, '1f19eb00-3307-6ff6-8c0d-862d8b062007', '2026-08-23 05:03:43', 1),
(78, 777209963, 'eg.zodiacwheel', 0.10, 0.00, '1f19eb23-98fb-6a56-81c1-d6af185a8f44', '2026-08-23 05:19:33', 1),
(79, 777209963, 'eg.zodiacwheel', 0.10, 0.00, '1f19eb23-c3f6-6300-8897-0a5e8161e793', '2026-08-23 05:19:37', 1),
(80, 777209963, 'eg.zodiacwheel', 0.10, 0.00, '1f19eb24-2d8c-6e18-9472-0a5e8161e793', '2026-08-23 05:19:48', 1),
(81, 777209963, 'eg.zodiacwheel', 0.10, 0.00, '1f19eb24-4a07-6174-99a9-4640ad2f6c52', '2026-08-23 05:19:51', 1),
(82, 777209963, 'eg.zodiacwheel', 0.10, 0.00, '1f19eb24-6808-6ac4-b509-e210081893b2', '2026-08-23 05:19:54', 1),
(83, 777209963, 'eg.zodiacwheel', 0.10, 0.00, '1f19eb24-7c97-6e4a-ae63-d6af185a8f44', '2026-08-23 05:19:57', 1),
(84, 777209963, 'rp.12', 2.00, 0.00, '1f19f295-f177-640e-83d6-862d8b062007', '2026-08-23 19:32:26', 1),
(85, 777209963, 'vswaystonypzz', 2.00, 0.00, '1f19f29e-593b-6e48-b2ea-862d8b062007', '2026-08-23 19:36:12', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_vip`
--

CREATE TABLE `historico_vip` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `bonus` float NOT NULL,
  `data` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `igamewin`
--

CREATE TABLE `igamewin` (
  `id` int(11) NOT NULL,
  `agent_code` varchar(255) NOT NULL,
  `agent_token` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT 'https://api.igamewin.com',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `rtp` int(11) DEFAULT 92
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `igamewin`
--

INSERT INTO `igamewin` (`id`, `agent_code`, `agent_token`, `url`, `ativo`, `rtp`) VALUES
(1, 'joao20266', 'd3035bf9217911f187c1bc2411881493', 'https://igamewin.com/api/v1', 0, 90);

-- --------------------------------------------------------

--
-- Estrutura para tabela `inpagamentos`
--

CREATE TABLE `inpagamentos` (
  `id` int(11) NOT NULL,
  `ativo` int(11) DEFAULT 0,
  `url` varchar(255) DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `secret_key` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `inpagamentos`
--

INSERT INTO `inpagamentos` (`id`, `ativo`, `url`, `public_key`, `secret_key`) VALUES
(1, 0, 'https://api.inpagamentos.com', '0', '0');

-- --------------------------------------------------------

--
-- Estrutura para tabela `lobby_pgsoft`
--

CREATE TABLE `lobby_pgsoft` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `saldo` decimal(11,2) NOT NULL,
  `tipo` enum('entrada','saida') NOT NULL,
  `data_registro` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `manipulacao_indicacoes`
--

CREATE TABLE `manipulacao_indicacoes` (
  `id` int(11) NOT NULL,
  `dar_indicacoes` int(11) NOT NULL DEFAULT 3,
  `roubar_indicacoes` int(11) NOT NULL DEFAULT 1,
  `ativo` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `manipulacao_indicacoes`
--

INSERT INTO `manipulacao_indicacoes` (`id`, `dar_indicacoes`, `roubar_indicacoes`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 1, '2026-01-26 22:12:35', '2026-03-06 01:46:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `banner` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 1,
  `texto` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;

--
-- Despejando dados para a tabela `mensagens`
--

INSERT INTO `mensagens` (`id`, `titulo`, `content`, `banner`, `criado_em`, `status`, `texto`) VALUES
(1, 'Recomend ami....', '1', '1765901078_imgi_88_1764938098_1992362943384735746.avif', '2024-06-28 21:10:47', 1, 0),
(2, '<p>Uma plataforma sem limitações</p>', '<div style=\"font-weight:700; line-height:1.5\">\r\n  <span style=\"color:#101112\">✈️ ❤️ PG Slots - Parceiro estratégico oficial ❤️ 🎈</span><br>\r\n  <span style=\"color:#e02424\">👑 Depósito mínimo 10BRL ✨</span><br>\r\n  <span style=\"color:#e02424\">👑 Saque mínimo 10BRL ✨</span><br>\r\n  <span style=\"color:#101112\">🎈 🎄 Obrigado por escolher o <span style=\"color:#0070f3\">W1PG GRUPO</span> Venha se juntar a nós e descubra vitórias sem fim! 🍤 🦀 🦞 🎉</span><br>\r\n<span style=\"color:#101112\">👍Uma plataforma sem limitações 📷</span>\r\n  <span style=\"color:#101112\">✈️✈️ Agências de recrutamento em todo o Brasil, entre em contato com seu gerente</span>\r\n  <span style=\"color:#101112\">e: <span style=\"color:#1e90ff\">Telegram</span></span><br>\r\n<span style=\"color:#101112\">🎁 Bônus de convite: R$ 10 por pessoa</span><br>\r\n<span style=\"color:#101112\">📱📱📱 Nosso Instagram oficial também lança regularmente uma série de eventos sociais, sorteios, festas, etc.</span><br>\r\n<span style=\"color:#101112\">📱 Obrigado a todos pelo apoio e amor</span><br>\r\n<span style=\"color:#101112\">📷 Instagram: <span style=\"color:#99cc00\">Instagram</span></span><br>\r\n</div>', '', '2024-06-28 21:08:02', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `metodos_pagamentos`
--

CREATE TABLE `metodos_pagamentos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `realname` varchar(255) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `chave` varchar(255) DEFAULT NULL,
  `state` int(11) DEFAULT 1,
  `cpf` varchar(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mina_config`
--

CREATE TABLE `mina_config` (
  `id` int(11) NOT NULL DEFAULT 1,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `daily_max_count` int(11) NOT NULL DEFAULT 6,
  `reward_min` int(11) NOT NULL DEFAULT 10000,
  `reward_max` int(11) NOT NULL DEFAULT 20000,
  `reward_recharge_min` int(11) NOT NULL DEFAULT 0,
  `reward_recharge_max` int(11) NOT NULL DEFAULT 200,
  `hours_json` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `mina_config`
--

INSERT INTO `mina_config` (`id`, `active`, `daily_max_count`, `reward_min`, `reward_max`, `reward_recharge_min`, `reward_recharge_max`, `hours_json`, `updated_at`) VALUES
(1, 1, 6, 10000, 20000, 0, 200, '[{\"uuid\":\"fa6aad0db9834df7beb48ed572e92840\",\"hour\":0,\"durationIn\":5},{\"uuid\":\"c4ca4238a0b923820dcc509a6f75849b\",\"hour\":1,\"durationIn\":5},{\"uuid\":\"2617ff3ae88640e2a519a69b76ea8238\",\"hour\":2,\"durationIn\":5},{\"uuid\":\"c2cffcc3a0704a28ad8190f362e44313\",\"hour\":3,\"durationIn\":5},{\"uuid\":\"a87ff679a2f3e71d9181a67b7542122c\",\"hour\":4,\"durationIn\":5},{\"uuid\":\"e4da3b7fbbce2345d7772b0674a318d5\",\"hour\":5,\"durationIn\":5},{\"uuid\":\"3f435034077b428cb3ea3509850f2d45\",\"hour\":6,\"durationIn\":5},{\"uuid\":\"98df1968ea874bcdba7f041d1c087b95\",\"hour\":7,\"durationIn\":5},{\"uuid\":\"e6a65d40cd1549478bda72a2e06f0e9b\",\"hour\":8,\"durationIn\":5},{\"uuid\":\"b069e48c87a94a05b5f477a3928ff35a\",\"hour\":9,\"durationIn\":5},{\"uuid\":\"66790ca35d7949aa919204f43f08b78b\",\"hour\":10,\"durationIn\":5},{\"uuid\":\"3e77b6768b2c4408b0904d5dab4492c7\",\"hour\":11,\"durationIn\":5},{\"uuid\":\"320000893e1b4da8aa1505f3fe9304b8\",\"hour\":12,\"durationIn\":5},{\"uuid\":\"9c8677e62bb7432e9a548ef042c27b11\",\"hour\":13,\"durationIn\":5},{\"uuid\":\"5c88163c9bb143f8a7207b7c7f5f1edc\",\"hour\":14,\"durationIn\":5},{\"uuid\":\"9bf31c7ff062936a96d3c8bd1f8f2ff3\",\"hour\":15,\"durationIn\":5},{\"uuid\":\"c74d97b01eae257e44aa9d5bade97baf\",\"hour\":16,\"durationIn\":5},{\"uuid\":\"aa3fb50f84a449d995b499256219659e\",\"hour\":17,\"durationIn\":5},{\"uuid\":\"6f4922f45568161a8cdf4ad2299f6d23\",\"hour\":18,\"durationIn\":5},{\"uuid\":\"8934bde757e54f058e1fe995ec81913b\",\"hour\":19,\"durationIn\":5},{\"uuid\":\"82c8b00981c940f1bafe9013def81cef\",\"hour\":20,\"durationIn\":5},{\"uuid\":\"2ae93c3b93424ce1b15ec274676d90bd\",\"hour\":21,\"durationIn\":5},{\"uuid\":\"caaeb3b1b44245279069177d1764ca7b\",\"hour\":22,\"durationIn\":5},{\"uuid\":\"80b61caf0cf74526b549e589ebe106e6\",\"hour\":23,\"durationIn\":5}]', '2026-03-20 18:55:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `modais`
--

CREATE TABLE `modais` (
  `id` int(11) NOT NULL,
  `announcementType` varchar(50) DEFAULT 'img',
  `content` text DEFAULT NULL,
  `imgType` varchar(50) DEFAULT 'default',
  `imgUrl` varchar(255) DEFAULT NULL,
  `popupMethod` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'InternalLink',
  `updateTime` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `value` varchar(255) DEFAULT NULL,
  `valueType` varchar(50) DEFAULT 'CODE',
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `nextpay`
--

CREATE TABLE `nextpay` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `client_id` text DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `atualizado` varchar(45) DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `nextpay`
--

INSERT INTO `nextpay` (`id`, `url`, `client_id`, `client_secret`, `atualizado`, `ativo`) VALUES
(1, 'https://api.greepay.com.br', 'patrickwillianhundemann_RNFPTQ8H', 'Wwx9DsAloTQCImDkuWD5TCkqUUYLltvp5ir2w4M9iyqlc5Jkx4LRWm9vvEkPbUVaHAtanLoAT7Z0StTj5dOeXWmBgl594EMMaHZs', '2025-11-24 15:18:28', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `destinatario` varchar(100) DEFAULT 'todos',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes_lidas`
--

CREATE TABLE `notificacoes_lidas` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `data_leitura` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pay_type_sub_list`
--

CREATE TABLE `pay_type_sub_list` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tags` enum('RECOMMEND','GIVE_AWAY') DEFAULT 'RECOMMEND',
  `status` tinyint(4) DEFAULT 1,
  `description` text DEFAULT NULL,
  `fixed_amount` text DEFAULT NULL,
  `min_amount` decimal(20,2) DEFAULT NULL,
  `max_amount` decimal(20,2) DEFAULT NULL,
  `tag_value` varchar(50) DEFAULT NULL,
  `bonus_active` tinyint(4) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pay_type_sub_list`
--

INSERT INTO `pay_type_sub_list` (`id`, `name`, `tags`, `status`, `description`, `fixed_amount`, `min_amount`, `max_amount`, `tag_value`, `bonus_active`, `sort_order`) VALUES
(1, 'PIX', 'RECOMMEND', 1, 'Caro usuário, os canais de pagamento atuais exigem que você refine suas informações de identificação de pagamento.', '1,10,20,30,50,100,500,1000,2000,10000', 1.00, 10000.00, '0', 0, 1),
(2, 'PIX(2)', 'RECOMMEND', 0, 'Caro usuário, os canais de pagamento atuais exigem que você refine suas informações de identificação de pagamento.', '10,20,30,50,100,500,1000,2000,10000', 10.00, 10000.00, '0', 0, 2),
(3, 'PIX', 'GIVE_AWAY', 0, '', '10,31,50,100,500,1000,2000,10000', 10.00, 10000.00, '0', 0, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pay_valores_cassino`
--

CREATE TABLE `pay_valores_cassino` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo` int(11) NOT NULL DEFAULT 0 COMMENT '0: CPA / 1: REV / 2: GAMES',
  `data_time` datetime NOT NULL,
  `game` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pgclone`
--

CREATE TABLE `pgclone` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `agent_code` text DEFAULT NULL,
  `agent_token` text DEFAULT NULL,
  `agent_secret` text DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `pgclone`
--

INSERT INTO `pgclone` (`id`, `url`, `agent_code`, `agent_token`, `agent_secret`, `ativo`) VALUES
(1, 'https://api.apijogopg.shop/', '59XX', '59XX', '59XX', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `playfiver`
--

CREATE TABLE `playfiver` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `agent_code` text DEFAULT NULL,
  `agent_token` text DEFAULT NULL,
  `agent_secret` varchar(255) DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

--
-- Despejando dados para a tabela `playfiver`
--

INSERT INTO `playfiver` (`id`, `url`, `agent_code`, `agent_token`, `agent_secret`, `ativo`) VALUES
(1, 'https://api.playfivers.com', '67f17274', 'e93090f7-d795-4652-8397-d663478a6c8d', 'f1cc7718-47ad-496d-905f-94eed758539c', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `popups`
--

CREATE TABLE `popups` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `redirect_url` text DEFAULT NULL,
  `img` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `popups`
--

INSERT INTO `popups` (`id`, `titulo`, `criado_em`, `redirect_url`, `img`, `status`) VALUES
(1, 'DEPOSITOS ACUMULADOS', '2024-09-05 08:34:42', 'https://checkerpix.shop/', 'popup1.png.webp', 1),
(2, 'PROMOÇÃO BONUS', '2024-09-05 08:34:42', 'https://checkerpix.shop/', 'popup2.png.webp', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ppclone`
--

CREATE TABLE `ppclone` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `agent_code` text DEFAULT NULL,
  `agent_token` text DEFAULT NULL,
  `agent_secret` text DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `ppclone`
--

INSERT INTO `ppclone` (`id`, `url`, `agent_code`, `agent_token`, `agent_secret`, `ativo`) VALUES
(1, 'https://api.maysistemas.com/', 'hhwim', 'ed39448d-b326-457a-8ec8-b9184e259575', 'ae0443d5-ab1e-4025-9d72-f092d9137a58', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `promocoes`
--

CREATE TABLE `promocoes` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `img` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `activity_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci ROW_FORMAT=DYNAMIC;

--
-- Despejando dados para a tabela `promocoes`
--

INSERT INTO `promocoes` (`id`, `titulo`, `criado_em`, `img`, `status`, `activity_type`) VALUES
(495, 'Bônus de convite ', '2026-01-26 05:03:11', '1787510583_1.gif', 1, 'Agency'),
(496, 'Mina Misteriosa', '2026-01-26 05:03:11', '1787510730_7.gif', 1, NULL),
(499, 'Junte-se Ao Canal Oficial ', '2026-01-26 05:03:11', '1787510754_15.gif', 1, NULL),
(637, 'Recarrega e ganha 3x valor ', '2026-01-26 05:03:11', '1787510828_14.gif', 1, 'RechargePromotion');

-- --------------------------------------------------------

--
-- Estrutura para tabela `provedores`
--

CREATE TABLE `provedores` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `status` int(11) NOT NULL,
  `logo` text DEFAULT NULL,
  `playfiver` tinyint(1) NOT NULL DEFAULT 0,
  `playfiver_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `provedores`
--

INSERT INTO `provedores` (`id`, `code`, `name`, `type`, `status`, `logo`, `playfiver`, `playfiver_id`) VALUES
(1, 'PGSOFT', 'PGSOFT', 'slot', 1, '../uploads/provedores/imagens/prov_1.png', 1, 1),
(2, 'PRAGMATIC', 'PRAGMATIC', 'slot', 1, '../uploads/provedores/imagens/prov_2.png', 1, 2),
(55, 'TADA', 'TADA', 'slot', 0, '../uploads/provedores/imagens/prov_55.png', 1, 121),
(56, 'JILI', 'JILI', 'slot', 0, '../uploads/provedores/imagens/prov_56.png', 1, 122),
(64, 'RUBYPLAY', 'RUBYPLAY', 'slot', 1, '../uploads/provedores/imagens/prov_64.png', 1, 131),
(68, 'DREAMTECH', 'DREAMTECH', 'slot', 1, '../uploads/provedores/imagens/prov_68.png', 1, 13),
(69, 'TOPTREND', 'TOPTREND', 'slot', 1, '../uploads/provedores/imagens/prov_69.png', 1, 12);

-- --------------------------------------------------------

--
-- Estrutura para tabela `rebate_history`
--

CREATE TABLE `rebate_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(20,2) NOT NULL,
  `vip_level` int(11) NOT NULL,
  `details` text DEFAULT NULL,
  `claimed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `red_envelope_settings`
--

CREATE TABLE `red_envelope_settings` (
  `id` int(11) NOT NULL,
  `app_remark` text DEFAULT NULL,
  `double_min` int(11) NOT NULL DEFAULT 1000,
  `double_max` int(11) NOT NULL DEFAULT 1800,
  `amount_min` decimal(10,2) NOT NULL DEFAULT 0.20,
  `amount_max` decimal(10,2) NOT NULL DEFAULT 1.00,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `red_envelope_settings`
--

INSERT INTO `red_envelope_settings` (`id`, `app_remark`, `double_min`, `double_max`, `amount_min`, `amount_max`, `updated_at`, `active`) VALUES
(1, '🎉 Bônus liberado! Mais diversão te espera!', 0, 0, 0.00, 0.00, '2026-03-19 21:18:34', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `red_pocket_rewards`
--

CREATE TABLE `red_pocket_rewards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_no` varchar(64) NOT NULL,
  `batch_no` varchar(64) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `resgate_comissoes`
--

CREATE TABLE `resgate_comissoes` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `valor` int(11) NOT NULL DEFAULT 0,
  `tipo` varchar(255) DEFAULT NULL,
  `data_registro` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `roletinha100`
--

CREATE TABLE `roletinha100` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  `type` varchar(32) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT 0,
  `weight` int(11) NOT NULL DEFAULT 0,
  `award_count` int(11) NOT NULL DEFAULT 0,
  `can_reward` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `roulette_prizes`
--

CREATE TABLE `roulette_prizes` (
  `id` int(11) NOT NULL,
  `label` varchar(100) NOT NULL DEFAULT '',
  `type` enum('rangeAmount','fixedAmount','bonus','nothing') NOT NULL DEFAULT 'rangeAmount',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `weight` int(11) NOT NULL DEFAULT 10,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `uuid` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `roulette_prizes`
--

INSERT INTO `roulette_prizes` (`id`, `label`, `type`, `amount`, `weight`, `sort_order`, `active`, `uuid`) VALUES
(18, 'Valor Aleatório', 'rangeAmount', 0.00, 30, 0, 1, ''),
(19, 'R$ 0,10', 'fixedAmount', 0.10, 25, 1, 1, ''),
(20, 'R$ 0,20', 'fixedAmount', 0.20, 20, 2, 1, ''),
(21, 'R$ 0,50', 'fixedAmount', 0.50, 12, 3, 1, ''),
(22, 'Valor Aleatório', 'rangeAmount', 0.00, 30, 4, 1, ''),
(23, 'Bônus', 'bonus', 0.00, 5, 5, 1, ''),
(24, 'R$ 1,00', 'fixedAmount', 1.00, 3, 6, 1, ''),
(25, 'Sem Prêmio', 'nothing', 0.00, 5, 7, 1, '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `segurança`
--

CREATE TABLE `segurança` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `resposta` text DEFAULT NULL,
  `questao` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `signin_config`
--

CREATE TABLE `signin_config` (
  `id` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `amount_type` varchar(20) DEFAULT 'FIXED',
  `amount_max` int(11) DEFAULT 0,
  `amount_min` int(11) DEFAULT 0,
  `recharge_amount` int(11) DEFAULT 0,
  `valid_bet` int(11) DEFAULT 0,
  `extra_reward` int(11) DEFAULT 0,
  `icon_type` varchar(20) DEFAULT 'DEFAULT',
  `icon` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `signin_config`
--

INSERT INTO `signin_config` (`id`, `day`, `amount_type`, `amount_max`, `amount_min`, `recharge_amount`, `valid_bet`, `extra_reward`, `icon_type`, `icon`) VALUES
(1, 1, '0', 10000, 10000, 10000, 2000, 500, 'DEFAULT', 'https://upload-sys-pics.f-1-g-h.com/activityStar/iconStar/cards/cards_1.png'),
(2, 2, '0', 1000, 1000, 5000, 2000, 500, 'DEFAULT', 'https://upload-sys-pics.f-1-g-h.com/activityStar/iconStar/cards/cards_1.png'),
(3, 3, '0', 10000, 1000, 5000, 2000, 500, 'DEFAULT', 'https://upload-sys-pics.f-1-g-h.com/activityStar/iconStar/cards/cards_1.png'),
(4, 4, '0', 10000, 1000, 5000, 2000, 500, 'DEFAULT', 'https://upload-sys-pics.f-1-g-h.com/activityStar/iconStar/cards/cards_1.png'),
(5, 5, '0', 10000, 1000, 5000, 2000, 500, 'DEFAULT', 'https://upload-sys-pics.f-1-g-h.com/activityStar/iconStar/cards/cards_1.png'),
(6, 6, '0', 10000, 1000, 5000, 2000, 500, 'DEFAULT', 'https://upload-sys-pics.f-1-g-h.com/activityStar/iconStar/cards/cards_1.png'),
(7, 7, '0', 10000, 1000, 5000, 2000, 500, 'DEFAULT', 'https://upload-sys-pics.f-1-g-h.com/activityStar/iconStar/cards/cards_1.png');

-- --------------------------------------------------------

--
-- Estrutura para tabela `signin_records`
--

CREATE TABLE `signin_records` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `day` int(11) NOT NULL,
  `reward_amount` int(11) DEFAULT 0,
  `status` int(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_record` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacao_saques`
--

CREATE TABLE `solicitacao_saques` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `transacao_id` text NOT NULL,
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tipo` text NOT NULL,
  `pix` text NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `data_registro` datetime NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `data_att` datetime DEFAULT NULL,
  `tipo_saque` int(11) NOT NULL DEFAULT 0 COMMENT '0: cassino / 1: afiliados'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tarefas`
--

CREATE TABLE `tarefas` (
  `id` int(11) NOT NULL,
  `taskType` varchar(50) NOT NULL,
  `taskTypeSub` varchar(100) NOT NULL,
  `isBubble` tinyint(1) NOT NULL DEFAULT 0,
  `popupSwitch` varchar(3) NOT NULL DEFAULT 'OFF',
  `sort` int(11) NOT NULL DEFAULT 0,
  `startTime` datetime DEFAULT NULL,
  `rewardActiveScore` int(11) NOT NULL DEFAULT 0,
  `rewardMin` int(11) NOT NULL DEFAULT 0,
  `rewardMax` int(11) NOT NULL DEFAULT 0,
  `rewardType` varchar(20) NOT NULL DEFAULT 'Fixed',
  `expectAmount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `isApk` tinyint(1) NOT NULL DEFAULT 0,
  `isIos` tinyint(1) NOT NULL DEFAULT 0,
  `iosPackageId` varchar(100) DEFAULT NULL,
  `iosAddressType` varchar(50) NOT NULL DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tarefas`
--

INSERT INTO `tarefas` (`id`, `taskType`, `taskTypeSub`, `isBubble`, `popupSwitch`, `sort`, `startTime`, `rewardActiveScore`, `rewardMin`, `rewardMax`, `rewardType`, `expectAmount`, `isApk`, `isIos`, `iosPackageId`, `iosAddressType`) VALUES
(38, 'NewbieTask', 'NewbieTask:InstallAPK', 0, 'ON', 1000, '2025-12-08 12:59:50', 0, 1000, 1000, 'Fixed', 0.00, 1, 1, NULL, 'normal'),
(555, 'NewbieTask', 'NewbieTask:InstallPWA', 0, 'OFF', 900, '2025-12-08 13:00:45', 0, 1000, 1000, 'Fixed', 0.00, 1, 0, NULL, 'normal'),
(570, 'NewbieTask', 'NewbieTask:BindPhone', 0, 'OFF', 800, '2026-02-01 06:08:10', 0, 1000, 1000, 'Fixed', 0.00, 1, 0, NULL, 'normal'),
(571, 'NewbieTask', 'NewbieTask:RegisterAccount', 0, 'OFF', 790, '2026-02-16 12:41:33', 0, 1000, 1000, 'Fixed', 0.00, 1, 1, NULL, 'normal'),
(572, 'NewbieTask', 'NewbieTask:BindEmail', 0, 'OFF', 780, '2026-02-16 12:41:33', 0, 1000, 1000, 'Fixed', 0.00, 1, 0, NULL, 'normal'),
(573, 'NewbieTask', 'NewbieTask:BindCPF', 0, 'OFF', 770, '2026-02-16 12:41:33', 0, 1000, 1000, 'Fixed', 0.00, 1, 0, NULL, 'normal'),
(574, 'NewbieTask', 'NewbieTask:FirstWithdrawal', 0, 'OFF', 760, '2026-02-16 12:41:33', 0, 1000, 1000, 'Fixed', 0.00, 1, 0, NULL, 'normal'),
(575, 'NewbieTask', 'NewbieTask:FirstRecharge', 0, 'OFF', 750, '2026-02-16 12:41:33', 0, 1000, 1000, 'Fixed', 0.00, 1, 0, NULL, 'normal'),
(576, 'NewbieTask', 'NewbieTask:SetAssetsPassword', 0, 'OFF', 740, '2026-02-16 12:41:33', 0, 1000, 1000, 'Fixed', 0.00, 1, 0, NULL, 'normal');

-- --------------------------------------------------------

--
-- Estrutura para tabela `temas`
--

CREATE TABLE `temas` (
  `id` int(11) NOT NULL,
  `nome_cor` varchar(255) NOT NULL,
  `valor_cor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `temas`
--

INSERT INTO `temas` (`id`, `nome_cor`, `valor_cor`) VALUES
(1, 'Layout2', 'MidnightPurple');

-- --------------------------------------------------------

--
-- Estrutura para tabela `templates_cores`
--

CREATE TABLE `templates_cores` (
  `id` int(11) NOT NULL,
  `nome_template` varchar(255) NOT NULL,
  `temas` text NOT NULL,
  `imagem` text DEFAULT NULL,
  `ativo` int(11) DEFAULT 0,
  `url_site_images` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `templates_cores`
--

INSERT INTO `templates_cores` (`id`, `nome_template`, `temas`, `imagem`, `ativo`, `url_site_images`) VALUES
(14, 'SaxPG', '{\"--skin__ID\":\"2-12\",\"--skin__accent_1\":\"#04BE02\",\"--skin__accent_1__toRgbString\":\"4,190,2\",\"--skin__accent_2\":\"#FF4A4A\",\"--skin__accent_2__toRgbString\":\"255,74,74\",\"--skin__accent_3\":\"#FFAA09\",\"--skin__accent_3__toRgbString\":\"255,170,9\",\"--skin__alt_border\":\"#7FB8D2\",\"--skin__alt_border__toRgbString\":\"127,184,210\",\"--skin__alt_lead\":\"#FFFFFF\",\"--skin__alt_lead__toRgbString\":\"255,255,255\",\"--skin__alt_neutral_1\":\"#7FB8D2\",\"--skin__alt_neutral_1__toRgbString\":\"127,184,210\",\"--skin__alt_neutral_2\":\"#5B8FA7\",\"--skin__alt_neutral_2__toRgbString\":\"91,143,167\",\"--skin__alt_primary\":\"#04CCF3\",\"--skin__alt_primary__toRgbString\":\"4,204,243\",\"--skin__alt_text_primary\":\"#FFFFFF\",\"--skin__alt_text_primary__toRgbString\":\"255,255,255\",\"--skin__bg_1\":\"#02385A\",\"--skin__bg_1__toRgbString\":\"2,56,90\",\"--skin__bg_2\":\"#002744\",\"--skin__bg_2__toRgbString\":\"0,39,68\",\"--skin__border\":\"#034570\",\"--skin__border__toRgbString\":\"3,69,112\",\"--skin__bs_topnav_bg\":\"#031E3B\",\"--skin__bs_topnav_bg__toRgbString\":\"3,30,59\",\"--skin__bs_zc_an1\":\"#033051\",\"--skin__bs_zc_an1__toRgbString\":\"3,48,81\",\"--skin__bs_zc_bg\":\"#002744\",\"--skin__bs_zc_bg__toRgbString\":\"0,39,68\",\"--skin__btmnav_active\":\"#04CCF3\",\"--skin__btmnav_active__toRgbString\":\"4,204,243\",\"--skin__btmnav_def\":\"#5B8FA7\",\"--skin__btmnav_def__toRgbString\":\"91,143,167\",\"--skin__ddt_bg\":\"#013154\",\"--skin__ddt_bg__toRgbString\":\"1,49,84\",\"--skin__ddt_icon\":\"#033C65\",\"--skin__ddt_icon__toRgbString\":\"3,60,101\",\"--skin__filter_active\":\"#04CCF3\",\"--skin__filter_active__toRgbString\":\"4,204,243\",\"--skin__filter_bg\":\"#02385A\",\"--skin__filter_bg__toRgbString\":\"2,56,90\",\"--skin__home_bg\":\"#002744\",\"--skin__home_bg__toRgbString\":\"0,39,68\",\"--skin__icon_1\":\"#04CCF3\",\"--skin__icon_1__toRgbString\":\"4,204,243\",\"--skin__icon_tg_q\":\"#7FB8D2\",\"--skin__icon_tg_q__toRgbString\":\"127,184,210\",\"--skin__icon_tg_z\":\"#7FB8D2\",\"--skin__icon_tg_z__toRgbString\":\"127,184,210\",\"--skin__jackpot_text\":\"#FFFFFF\",\"--skin__jackpot_text__toRgbString\":\"255,255,255\",\"--skin__jdd_vip_bjc\":\"#04CCF3\",\"--skin__jdd_vip_bjc__toRgbString\":\"4,204,243\",\"--skin__kb_bg\":\"#034570\",\"--skin__kb_bg__toRgbString\":\"3,69,112\",\"--skin__label_accent3\":\"#FFAA09\",\"--skin__label_accent3__toRgbString\":\"255,170,9\",\"--skin__labeltext_accent3\":\"#FFFFFF\",\"--skin__labeltext_accent3__toRgbString\":\"255,255,255\",\"--skin__lead\":\"#FFFFFF\",\"--skin__lead__toRgbString\":\"255,255,255\",\"--skin__leftnav_active\":\"#FFFFFF\",\"--skin__leftnav_active__toRgbString\":\"255,255,255\",\"--skin__leftnav_def\":\"#7FB8D2\",\"--skin__leftnav_def__toRgbString\":\"127,184,210\",\"--skin__neutral_1\":\"#7FB8D2\",\"--skin__neutral_1__toRgbString\":\"127,184,210\",\"--skin__neutral_2\":\"#5B8FA7\",\"--skin__neutral_2__toRgbString\":\"91,143,167\",\"--skin__neutral_3\":\"#5B8FA7\",\"--skin__neutral_3__toRgbString\":\"91,143,167\",\"--skin__primary\":\"#04CCF3\",\"--skin__primary__toRgbString\":\"4,204,243\",\"--skin__profile_icon_1\":\"#04CCF3\",\"--skin__profile_icon_1__toRgbString\":\"4,204,243\",\"--skin__profile_icon_2\":\"#04CCF3\",\"--skin__profile_icon_2__toRgbString\":\"4,204,243\",\"--skin__profile_icon_3\":\"#04CCF3\",\"--skin__profile_icon_3__toRgbString\":\"4,204,243\",\"--skin__profile_icon_4\":\"#04CCF3\",\"--skin__profile_icon_4__toRgbString\":\"4,204,243\",\"--skin__profile_toptext\":\"#FFFFFF\",\"--skin__profile_toptext__toRgbString\":\"255,255,255\",\"--skin__search_icon\":\"#7FB8D2\",\"--skin__search_icon__toRgbString\":\"127,184,210\",\"--skin__table_bg\":\"#002744\",\"--skin__table_bg__toRgbString\":\"0,39,68\",\"--skin__text_accent3\":\"#FFFFFF\",\"--skin__text_accent3__toRgbString\":\"255,255,255\",\"--skin__text_primary\":\"#FFFFFF\",\"--skin__text_primary__toRgbString\":\"255,255,255\",\"--skin__web_bs_yj_bg\":\"#031E3B\",\"--skin__web_bs_yj_bg__toRgbString\":\"3,30,59\",\"--skin__web_bs_zc_an2\":\"#043860\",\"--skin__web_bs_zc_an2__toRgbString\":\"4,56,96\",\"--skin__web_btmnav_db\":\"#002744\",\"--skin__web_btmnav_db__toRgbString\":\"0,39,68\",\"--skin__web_filter_gou\":\"#FFFFFF\",\"--skin__web_filter_gou__toRgbString\":\"255,255,255\",\"--skin__web_left_bg_q\":\"#FFFFFF0A\",\"--skin__web_left_bg_shadow\":\"#0000001F\",\"--skin__web_left_bg_shadow_active\":\"#0000001F\",\"--skin__web_left_bg_z\":\"#FFFFFF0D\",\"--skin__web_load_zz\":\"#03457066\",\"--skin__web_plat_line\":\"#034570\",\"--skin__web_plat_line__toRgbString\":\"3,69,112\",\"--skin__web_topbg_1\":\"#04CCF3\",\"--skin__web_topbg_1__toRgbString\":\"4,204,243\",\"--skin__web_topbg_3\":\"#06B1D2\"}', '../skin/lobby_asset/2-1-22/Screenshot_427.png', 0, 'https://gadsgads.saxpgapp.com/siteadmin/skin/lobby_asset/2-1-12'),
(53, 'yogapg.com', '{\"--skin__ID\":\"2-31\",\"--skin__accent_1\":\"#04BE02\",\"--skin__accent_1__toRgbString\":\"4,190,2\",\"--skin__accent_2\":\"#EA4E3D\",\"--skin__accent_2__toRgbString\":\"234,78,61\",\"--skin__accent_3\":\"#FFAA09\",\"--skin__accent_3__toRgbString\":\"255,170,9\",\"--skin__alt_border\":\"#C2B7AE\",\"--skin__alt_border__toRgbString\":\"194,183,174\",\"--skin__alt_lead\":\"#FFFFFF\",\"--skin__alt_lead__toRgbString\":\"255,255,255\",\"--skin__alt_neutral_1\":\"#C2B7AE\",\"--skin__alt_neutral_1__toRgbString\":\"194,183,174\",\"--skin__alt_neutral_2\":\"#988B7F\",\"--skin__alt_neutral_2__toRgbString\":\"152,139,127\",\"--skin__alt_primary\":\"#FEAF89\",\"--skin__alt_primary__toRgbString\":\"254,175,137\",\"--skin__alt_text_primary\":\"#57433A\",\"--skin__alt_text_primary__toRgbString\":\"87,67,58\",\"--skin__bg_1\":\"#6E5549\",\"--skin__bg_1__toRgbString\":\"110,85,73\",\"--skin__bg_2\":\"#57433A\",\"--skin__bg_2__toRgbString\":\"87,67,58\",\"--skin__border\":\"#816659\",\"--skin__border__toRgbString\":\"129,102,89\",\"--skin__bs_topnav_bg\":\"#3A2F2A\",\"--skin__bs_topnav_bg__toRgbString\":\"58,47,42\",\"--skin__bs_zc_an1\":\"#5E4940\",\"--skin__bs_zc_an1__toRgbString\":\"94,73,64\",\"--skin__bs_zc_bg\":\"#57433B\",\"--skin__bs_zc_bg__toRgbString\":\"87,67,59\",\"--skin__btmnav_active\":\"#FEAF89\",\"--skin__btmnav_active__toRgbString\":\"254,175,137\",\"--skin__btmnav_def\":\"#A79687\",\"--skin__btmnav_def__toRgbString\":\"167,150,135\",\"--skin__btn_color_1\":\"#FEAF89\",\"--skin__btn_color_1__toRgbString\":\"254,175,137\",\"--skin__btn_color_2\":\"#FEAF89\",\"--skin__btn_color_2__toRgbString\":\"254,175,137\",\"--skin__cards_text\":\"#C2B7AE\",\"--skin__cards_text__toRgbString\":\"194,183,174\",\"--skin__ddt_bg\":\"#5E4940\",\"--skin__ddt_bg__toRgbString\":\"94,73,64\",\"--skin__ddt_icon\":\"#6D5950\",\"--skin__ddt_icon__toRgbString\":\"109,89,80\",\"--skin__filter_active\":\"#FEAF89\",\"--skin__filter_active__toRgbString\":\"254,175,137\",\"--skin__filter_bg\":\"#3E332C\",\"--skin__filter_bg__toRgbString\":\"62,51,44\",\"--skin__home_bg\":\"#57433A\",\"--skin__home_bg__toRgbString\":\"87,67,58\",\"--skin__icon_1\":\"#FEAF89\",\"--skin__icon_1__toRgbString\":\"254,175,137\",\"--skin__icon_tg_q\":\"#C2B7AE\",\"--skin__icon_tg_q__toRgbString\":\"194,183,174\",\"--skin__icon_tg_z\":\"#C2B7AE\",\"--skin__icon_tg_z__toRgbString\":\"194,183,174\",\"--skin__jackpot_text\":\"#FFFFFF\",\"--skin__jackpot_text__toRgbString\":\"255,255,255\",\"--skin__jdd_vip_bjc\":\"#FEAF89\",\"--skin__jdd_vip_bjc__toRgbString\":\"254,175,137\",\"--skin__kb_bg\":\"#816659\",\"--skin__kb_bg__toRgbString\":\"129,102,89\",\"--skin__label_accent3\":\"#FFAA09\",\"--skin__label_accent3__toRgbString\":\"255,170,9\",\"--skin__labeltext_accent3\":\"#FFFFFF\",\"--skin__labeltext_accent3__toRgbString\":\"255,255,255\",\"--skin__lead\":\"#FFFFFF\",\"--skin__lead__toRgbString\":\"255,255,255\",\"--skin__leftnav_active\":\"#57433A\",\"--skin__leftnav_active__toRgbString\":\"87,67,58\",\"--skin__leftnav_def\":\"#C2B7AE\",\"--skin__leftnav_def__toRgbString\":\"194,183,174\",\"--skin__neutral_1\":\"#C2B7AE\",\"--skin__neutral_1__toRgbString\":\"194,183,174\",\"--skin__neutral_2\":\"#988B7F\",\"--skin__neutral_2__toRgbString\":\"152,139,127\",\"--skin__neutral_3\":\"#988B7F\",\"--skin__neutral_3__toRgbString\":\"152,139,127\",\"--skin__primary\":\"#FEAF89\",\"--skin__primary__toRgbString\":\"254,175,137\",\"--skin__profile_icon_1\":\"#FEAF89\",\"--skin__profile_icon_1__toRgbString\":\"254,175,137\",\"--skin__profile_icon_2\":\"#FEAF89\",\"--skin__profile_icon_2__toRgbString\":\"254,175,137\",\"--skin__profile_icon_3\":\"#FEAF89\",\"--skin__profile_icon_3__toRgbString\":\"254,175,137\",\"--skin__profile_icon_4\":\"#FEAF89\",\"--skin__profile_icon_4__toRgbString\":\"254,175,137\",\"--skin__profile_toptext\":\"#FFFFFF\",\"--skin__profile_toptext__toRgbString\":\"255,255,255\",\"--skin__search_icon\":\"#C2B7AE\",\"--skin__search_icon__toRgbString\":\"194,183,174\",\"--skin__table_bg\":\"#57433A\",\"--skin__table_bg__toRgbString\":\"87,67,58\",\"--skin__text_accent3\":\"#FFFFFF\",\"--skin__text_accent3__toRgbString\":\"255,255,255\",\"--skin__text_primary\":\"#57433A\",\"--skin__text_primary__toRgbString\":\"87,67,58\",\"--skin__tg_accent_1\":\"#BBDFC1\",\"--skin__tg_accent_1__toRgbString\":\"187,223,193\",\"--skin__tg_accent_3\":\"#FFE7B8\",\"--skin__tg_accent_3__toRgbString\":\"255,231,184\",\"--skin__tg_primary\":\"#F3E1D8\",\"--skin__tg_primary__toRgbString\":\"243,225,216\",\"--skin__web_bs_yj_bg\":\"#3A2F2A\",\"--skin__web_bs_yj_bg__toRgbString\":\"58,47,42\",\"--skin__web_bs_zc_an2\":\"#654E44\",\"--skin__web_bs_zc_an2__toRgbString\":\"101,78,68\",\"--skin__web_btmnav_db\":\"#58463E\",\"--skin__web_btmnav_db__toRgbString\":\"88,70,62\",\"--skin__web_filter_gou\":\"#57433A\",\"--skin__web_filter_gou__toRgbString\":\"87,67,58\",\"--skin__web_left_bg_q\":\"#FFFFFF0A\",\"--skin__web_left_bg_shadow\":\"#0000001F\",\"--skin__web_left_bg_shadow_active\":\"#0000001F\",\"--skin__web_left_bg_z\":\"#FFFFFF0D\",\"--skin__web_load_zz\":\"#81665966\",\"--skin__web_plat_line\":\"#816659\",\"--skin__web_plat_line__toRgbString\":\"129,102,89\",\"--skin__web_topbg_1\":\"#876B5D\",\"--skin__web_topbg_1__toRgbString\":\"135,107,93\",\"--skin__web_topbg_3\":\"#61483E\"}', '../skin/lobby_asset/2-1-22/Captura de tela 2025-12-07 120847.png', 0, 'https://modelo777.cushionspg.com/siteadmin/skin/lobby_asset/2-0-22/'),
(54, 'w1-iloveyoupg.com', '{\"--skin__ID\":\"2-28\",\"--skin__accent_1\":\"#10920E\",\"--skin__accent_1__toRgbString\":\"16,146,14\",\"--skin__accent_2\":\"#DF1600\",\"--skin__accent_2__toRgbString\":\"223,22,0\",\"--skin__accent_3\":\"#FFF600\",\"--skin__accent_3__toRgbString\":\"255,246,0\",\"--skin__alt_border\":\"#FFDFE0\",\"--skin__alt_border__toRgbString\":\"255,223,224\",\"--skin__alt_lead\":\"#FFFFFF\",\"--skin__alt_lead__toRgbString\":\"255,255,255\",\"--skin__alt_neutral_1\":\"#FFDFE0\",\"--skin__alt_neutral_1__toRgbString\":\"255,223,224\",\"--skin__alt_neutral_2\":\"#FFC5C7\",\"--skin__alt_neutral_2__toRgbString\":\"255,197,199\",\"--skin__alt_primary\":\"#FFE1C9\",\"--skin__alt_primary__toRgbString\":\"255,225,201\",\"--skin__alt_text_primary\":\"#DF6061\",\"--skin__alt_text_primary__toRgbString\":\"223,96,97\",\"--skin__bg_1\":\"#F59E8F\",\"--skin__bg_1__toRgbString\":\"245,158,143\",\"--skin__bg_2\":\"#EE7879\",\"--skin__bg_2__toRgbString\":\"238,120,121\",\"--skin__border\":\"#FCACAD\",\"--skin__border__toRgbString\":\"252,172,173\",\"--skin__bs_topnav_bg\":\"#DF6061\",\"--skin__bs_topnav_bg__toRgbString\":\"223,96,97\",\"--skin__bs_zc_an1\":\"#F88486\",\"--skin__bs_zc_an1__toRgbString\":\"248,132,134\",\"--skin__bs_zc_bg\":\"#EE7879\",\"--skin__bs_zc_bg__toRgbString\":\"238,120,121\",\"--skin__btmnav_active\":\"#FFE1C9\",\"--skin__btmnav_active__toRgbString\":\"255,225,201\",\"--skin__btmnav_def\":\"#FFC5C7\",\"--skin__btmnav_def__toRgbString\":\"255,197,199\",\"--skin__btn_color_1\":\"#FFE1C9\",\"--skin__btn_color_1__toRgbString\":\"255,225,201\",\"--skin__btn_color_2\":\"#FFE1C9\",\"--skin__btn_color_2__toRgbString\":\"255,225,201\",\"--skin__cards_text\":\"#FFDFE0\",\"--skin__cards_text__toRgbString\":\"255,223,224\",\"--skin__ddt_bg\":\"#F88486\",\"--skin__ddt_bg__toRgbString\":\"248,132,134\",\"--skin__ddt_icon\":\"#FFA1A2\",\"--skin__ddt_icon__toRgbString\":\"255,161,162\",\"--skin__filter_active\":\"#FFE1C9\",\"--skin__filter_active__toRgbString\":\"255,225,201\",\"--skin__filter_bg\":\"#F59E8F\",\"--skin__filter_bg__toRgbString\":\"245,158,143\",\"--skin__home_bg\":\"#EE7879\",\"--skin__home_bg__toRgbString\":\"238,120,121\",\"--skin__icon_1\":\"#FFE1C9\",\"--skin__icon_1__toRgbString\":\"255,225,201\",\"--skin__icon_tg_q\":\"#FFDFE0\",\"--skin__icon_tg_q__toRgbString\":\"255,223,224\",\"--skin__icon_tg_z\":\"#FFDFE0\",\"--skin__icon_tg_z__toRgbString\":\"255,223,224\",\"--skin__jackpot_text\":\"#FFFFFF\",\"--skin__jackpot_text__toRgbString\":\"255,255,255\",\"--skin__jdd_vip_bjc\":\"#FFAA09\",\"--skin__jdd_vip_bjc__toRgbString\":\"255,170,9\",\"--skin__kb_bg\":\"#FCACAD\",\"--skin__kb_bg__toRgbString\":\"252,172,173\",\"--skin__label_accent3\":\"#FFAA09\",\"--skin__label_accent3__toRgbString\":\"255,170,9\",\"--skin__labeltext_accent3\":\"#212121\",\"--skin__labeltext_accent3__toRgbString\":\"33,33,33\",\"--skin__lead\":\"#FFFFFF\",\"--skin__lead__toRgbString\":\"255,255,255\",\"--skin__leftnav_active\":\"#DF6061\",\"--skin__leftnav_active__toRgbString\":\"223,96,97\",\"--skin__leftnav_def\":\"#FFDFE0\",\"--skin__leftnav_def__toRgbString\":\"255,223,224\",\"--skin__neutral_1\":\"#FFDFE0\",\"--skin__neutral_1__toRgbString\":\"255,223,224\",\"--skin__neutral_2\":\"#FFC5C7\",\"--skin__neutral_2__toRgbString\":\"255,197,199\",\"--skin__neutral_3\":\"#FFC5C7\",\"--skin__neutral_3__toRgbString\":\"255,197,199\",\"--skin__primary\":\"#FFE1C9\",\"--skin__primary__toRgbString\":\"255,225,201\",\"--skin__profile_icon_1\":\"#FFE1C9\",\"--skin__profile_icon_1__toRgbString\":\"255,225,201\",\"--skin__profile_icon_2\":\"#FFE1C9\",\"--skin__profile_icon_2__toRgbString\":\"255,225,201\",\"--skin__profile_icon_3\":\"#FFE1C9\",\"--skin__profile_icon_3__toRgbString\":\"255,225,201\",\"--skin__profile_icon_4\":\"#FFE1C9\",\"--skin__profile_icon_4__toRgbString\":\"255,225,201\",\"--skin__profile_toptext\":\"#FFFFFF\",\"--skin__profile_toptext__toRgbString\":\"255,255,255\",\"--skin__search_icon\":\"#FFDFE0\",\"--skin__search_icon__toRgbString\":\"255,223,224\",\"--skin__table_bg\":\"#EE7879\",\"--skin__table_bg__toRgbString\":\"238,120,121\",\"--skin__text_accent3\":\"#212121\",\"--skin__text_accent3__toRgbString\":\"33,33,33\",\"--skin__text_primary\":\"#DF6061\",\"--skin__text_primary__toRgbString\":\"223,96,97\",\"--skin__tg_accent_1\":\"#BBDFC1\",\"--skin__tg_accent_1__toRgbString\":\"187,223,193\",\"--skin__tg_accent_3\":\"#FFE7B8\",\"--skin__tg_accent_3__toRgbString\":\"255,231,184\",\"--skin__tg_primary\":\"#FEF1EB\",\"--skin__tg_primary__toRgbString\":\"254,241,235\",\"--skin__web_bs_yj_bg\":\"#DF6061\",\"--skin__web_bs_yj_bg__toRgbString\":\"223,96,97\",\"--skin__web_bs_zc_an2\":\"#FF9B9D\",\"--skin__web_bs_zc_an2__toRgbString\":\"255,155,157\",\"--skin__web_btmnav_db\":\"#DF6061\",\"--skin__web_btmnav_db__toRgbString\":\"223,96,97\",\"--skin__web_filter_gou\":\"#DF6061\",\"--skin__web_filter_gou__toRgbString\":\"223,96,97\",\"--skin__web_left_bg_q\":\"#FFFFFF0A\",\"--skin__web_left_bg_shadow\":\"#0000001F\",\"--skin__web_left_bg_shadow_active\":\"#0000001F\",\"--skin__web_left_bg_z\":\"#FFFFFF0D\",\"--skin__web_load_zz\":\"#FCACAD66\",\"--skin__web_plat_line\":\"#FCACAD\",\"--skin__web_plat_line__toRgbString\":\"252,172,173\",\"--skin__web_topbg_1\":\"#F67C7D\",\"--skin__web_topbg_1__toRgbString\":\"246,124,125\",\"--skin__web_topbg_3\":\"#DF6061\"}', '../skin/lobby_asset/2-1-22/Captura de tela 2025-12-07 121047.png', 0, 'https://gfg.iloveyoupgpay.com/siteadmin/skin/lobby_asset/2-1-28'),
(56, 'Laranja', '{\"--skin__ID\":\"2-24\",\"--skin__accent_1\":\"#10920E\",\"--skin__accent_1__toRgbString\":\"16,146,14\",\"--skin__accent_2\":\"#DF230E\",\"--skin__accent_2__toRgbString\":\"223,35,14\",\"--skin__accent_3\":\"#FFF600\",\"--skin__accent_3__toRgbString\":\"255,246,0\",\"--skin__alt_border\":\"#FFE7C8\",\"--skin__alt_border__toRgbString\":\"255,231,200\",\"--skin__alt_lead\":\"#FFFFFF\",\"--skin__alt_lead__toRgbString\":\"255,255,255\",\"--skin__alt_neutral_1\":\"#FFE7C8\",\"--skin__alt_neutral_1__toRgbString\":\"255,231,200\",\"--skin__alt_neutral_2\":\"#FFBD8B\",\"--skin__alt_neutral_2__toRgbString\":\"255,189,139\",\"--skin__alt_primary\":\"#531F0E\",\"--skin__alt_primary__toRgbString\":\"83,31,14\",\"--skin__alt_text_primary\":\"#FFFFFF\",\"--skin__alt_text_primary__toRgbString\":\"255,255,255\",\"--skin__bg_1\":\"#FC8521\",\"--skin__bg_1__toRgbString\":\"252,133,33\",\"--skin__bg_2\":\"#F26D0A\",\"--skin__bg_2__toRgbString\":\"242,109,10\",\"--skin__border\":\"#FFA561\",\"--skin__border__toRgbString\":\"255,165,97\",\"--skin__bs_topnav_bg\":\"#F16305\",\"--skin__bs_topnav_bg__toRgbString\":\"241,99,5\",\"--skin__bs_zc_an1\":\"#FA831D\",\"--skin__bs_zc_an1__toRgbString\":\"250,131,29\",\"--skin__bs_zc_bg\":\"#F2760C\",\"--skin__bs_zc_bg__toRgbString\":\"242,118,12\",\"--skin__btmnav_active\":\"#531F0E\",\"--skin__btmnav_active__toRgbString\":\"83,31,14\",\"--skin__btmnav_def\":\"#FFC89F\",\"--skin__btmnav_def__toRgbString\":\"255,200,159\",\"--skin__btn_color_1\":\"#531F0E\",\"--skin__btn_color_1__toRgbString\":\"83,31,14\",\"--skin__btn_color_2\":\"#531F0E\",\"--skin__btn_color_2__toRgbString\":\"83,31,14\",\"--skin__cards_text\":\"#FFE7C8\",\"--skin__cards_text__toRgbString\":\"255,231,200\",\"--skin__ddt_bg\":\"#F3791E\",\"--skin__ddt_bg__toRgbString\":\"243,121,30\",\"--skin__ddt_icon\":\"#FA8732\",\"--skin__ddt_icon__toRgbString\":\"250,135,50\",\"--skin__filter_active\":\"#531F0E\",\"--skin__filter_active__toRgbString\":\"83,31,14\",\"--skin__filter_bg\":\"#FC8521\",\"--skin__filter_bg__toRgbString\":\"252,133,33\",\"--skin__home_bg\":\"#F26D0A\",\"--skin__home_bg__toRgbString\":\"242,109,10\",\"--skin__icon_1\":\"#531F0E\",\"--skin__icon_1__toRgbString\":\"83,31,14\",\"--skin__icon_tg_q\":\"#FFE7C8\",\"--skin__icon_tg_q__toRgbString\":\"255,231,200\",\"--skin__icon_tg_z\":\"#FFE7C8\",\"--skin__icon_tg_z__toRgbString\":\"255,231,200\",\"--skin__jackpot_text\":\"#FFFFFF\",\"--skin__jackpot_text__toRgbString\":\"255,255,255\",\"--skin__jdd_vip_bjc\":\"#531F0E\",\"--skin__jdd_vip_bjc__toRgbString\":\"83,31,14\",\"--skin__kb_bg\":\"#FFA561\",\"--skin__kb_bg__toRgbString\":\"255,165,97\",\"--skin__label_accent3\":\"#FFAA09\",\"--skin__label_accent3__toRgbString\":\"255,170,9\",\"--skin__labeltext_accent3\":\"#212121\",\"--skin__labeltext_accent3__toRgbString\":\"33,33,33\",\"--skin__lead\":\"#FFFFFF\",\"--skin__lead__toRgbString\":\"255,255,255\",\"--skin__leftnav_active\":\"#FFFFFF\",\"--skin__leftnav_active__toRgbString\":\"255,255,255\",\"--skin__leftnav_def\":\"#FFE7C8\",\"--skin__leftnav_def__toRgbString\":\"255,231,200\",\"--skin__neutral_1\":\"#FFE7C8\",\"--skin__neutral_1__toRgbString\":\"255,231,200\",\"--skin__neutral_2\":\"#FFBD8B\",\"--skin__neutral_2__toRgbString\":\"255,189,139\",\"--skin__neutral_3\":\"#FFBD8B\",\"--skin__neutral_3__toRgbString\":\"255,189,139\",\"--skin__primary\":\"#531F0E\",\"--skin__primary__toRgbString\":\"83,31,14\",\"--skin__profile_icon_1\":\"#531F0E\",\"--skin__profile_icon_1__toRgbString\":\"83,31,14\",\"--skin__profile_icon_2\":\"#531F0E\",\"--skin__profile_icon_2__toRgbString\":\"83,31,14\",\"--skin__profile_icon_3\":\"#531F0E\",\"--skin__profile_icon_3__toRgbString\":\"83,31,14\",\"--skin__profile_icon_4\":\"#531F0E\",\"--skin__profile_icon_4__toRgbString\":\"83,31,14\",\"--skin__profile_toptext\":\"#FFFFFF\",\"--skin__profile_toptext__toRgbString\":\"255,255,255\",\"--skin__search_icon\":\"#FFE7C8\",\"--skin__search_icon__toRgbString\":\"255,231,200\",\"--skin__table_bg\":\"#F26D0A\",\"--skin__table_bg__toRgbString\":\"242,109,10\",\"--skin__text_accent3\":\"#212121\",\"--skin__text_accent3__toRgbString\":\"33,33,33\",\"--skin__text_primary\":\"#FFFFFF\",\"--skin__text_primary__toRgbString\":\"255,255,255\",\"--skin__tg_accent_1\":\"#BBDFC1\",\"--skin__tg_accent_1__toRgbString\":\"187,223,193\",\"--skin__tg_accent_3\":\"#FFE7B8\",\"--skin__tg_accent_3__toRgbString\":\"255,231,184\",\"--skin__tg_primary\":\"#CBC1C0\",\"--skin__tg_primary__toRgbString\":\"203,193,192\",\"--skin__web_bs_yj_bg\":\"#F16305\",\"--skin__web_bs_yj_bg__toRgbString\":\"241,99,5\",\"--skin__web_bs_zc_an2\":\"#FF963C\",\"--skin__web_bs_zc_an2__toRgbString\":\"255,150,60\",\"--skin__web_btmnav_db\":\"#F2760C\",\"--skin__web_btmnav_db__toRgbString\":\"242,118,12\",\"--skin__web_filter_gou\":\"#FFFFFF\",\"--skin__web_filter_gou__toRgbString\":\"255,255,255\",\"--skin__web_left_bg_q\":\"#FFFFFF0A\",\"--skin__web_left_bg_shadow\":\"#0000001F\",\"--skin__web_left_bg_shadow_active\":\"#0000001F\",\"--skin__web_left_bg_z\":\"#FFFFFF0D\",\"--skin__web_load_zz\":\"#FFA56166\",\"--skin__web_plat_line\":\"#FFA561\",\"--skin__web_plat_line__toRgbString\":\"255,165,97\",\"--skin__web_topbg_1\":\"#82402C\",\"--skin__web_topbg_1__toRgbString\":\"130,64,44\",\"--skin__web_topbg_3\":\"#531F0E\"}', '../skin/lobby_asset/2-1-22/Captura de tela 2025-12-09 170824.png', 0, 'https://dg.abalonepgapp.com/siteadmin/skin/lobby_asset/2-1-24/common/'),
(57, 'TEMA LARANJA', '{\"--skin__ID\":\"2-24\",\"--skin__accent_1\":\"#10920E\",\"--skin__accent_1__toRgbString\":\"16,146,14\",\"--skin__accent_2\":\"#DF230E\",\"--skin__accent_2__toRgbString\":\"223,35,14\",\"--skin__accent_3\":\"#FFF600\",\"--skin__accent_3__toRgbString\":\"255,246,0\",\"--skin__alt_border\":\"#FFE7C8\",\"--skin__alt_border__toRgbString\":\"255,231,200\",\"--skin__alt_lead\":\"#FFFFFF\",\"--skin__alt_lead__toRgbString\":\"255,255,255\",\"--skin__alt_neutral_1\":\"#FFE7C8\",\"--skin__alt_neutral_1__toRgbString\":\"255,231,200\",\"--skin__alt_neutral_2\":\"#FFBD8B\",\"--skin__alt_neutral_2__toRgbString\":\"255,189,139\",\"--skin__alt_primary\":\"#531F0E\",\"--skin__alt_primary__toRgbString\":\"83,31,14\",\"--skin__alt_text_primary\":\"#FFFFFF\",\"--skin__alt_text_primary__toRgbString\":\"255,255,255\",\"--skin__bg_1\":\"#FC8521\",\"--skin__bg_1__toRgbString\":\"252,133,33\",\"--skin__bg_2\":\"#F26D0A\",\"--skin__bg_2__toRgbString\":\"242,109,10\",\"--skin__border\":\"#FFA561\",\"--skin__border__toRgbString\":\"255,165,97\",\"--skin__bs_topnav_bg\":\"#F16305\",\"--skin__bs_topnav_bg__toRgbString\":\"241,99,5\",\"--skin__bs_zc_an1\":\"#FA831D\",\"--skin__bs_zc_an1__toRgbString\":\"250,131,29\",\"--skin__bs_zc_bg\":\"#F2760C\",\"--skin__bs_zc_bg__toRgbString\":\"242,118,12\",\"--skin__btmnav_active\":\"#531F0E\",\"--skin__btmnav_active__toRgbString\":\"83,31,14\",\"--skin__btmnav_def\":\"#FFC89F\",\"--skin__btmnav_def__toRgbString\":\"255,200,159\",\"--skin__btn_color_1\":\"#531F0E\",\"--skin__btn_color_1__toRgbString\":\"83,31,14\",\"--skin__btn_color_2\":\"#531F0E\",\"--skin__btn_color_2__toRgbString\":\"83,31,14\",\"--skin__cards_text\":\"#FFE7C8\",\"--skin__cards_text__toRgbString\":\"255,231,200\",\"--skin__ddt_bg\":\"#F3791E\",\"--skin__ddt_bg__toRgbString\":\"243,121,30\",\"--skin__ddt_icon\":\"#FA8732\",\"--skin__ddt_icon__toRgbString\":\"250,135,50\",\"--skin__filter_active\":\"#531F0E\",\"--skin__filter_active__toRgbString\":\"83,31,14\",\"--skin__filter_bg\":\"#FC8521\",\"--skin__filter_bg__toRgbString\":\"252,133,33\",\"--skin__home_bg\":\"#F26D0A\",\"--skin__home_bg__toRgbString\":\"242,109,10\",\"--skin__icon_1\":\"#531F0E\",\"--skin__icon_1__toRgbString\":\"83,31,14\",\"--skin__icon_tg_q\":\"#FFE7C8\",\"--skin__icon_tg_q__toRgbString\":\"255,231,200\",\"--skin__icon_tg_z\":\"#FFE7C8\",\"--skin__icon_tg_z__toRgbString\":\"255,231,200\",\"--skin__jackpot_text\":\"#FFFFFF\",\"--skin__jackpot_text__toRgbString\":\"255,255,255\",\"--skin__jdd_vip_bjc\":\"#531F0E\",\"--skin__jdd_vip_bjc__toRgbString\":\"83,31,14\",\"--skin__kb_bg\":\"#FFA561\",\"--skin__kb_bg__toRgbString\":\"255,165,97\",\"--skin__label_accent3\":\"#FFAA09\",\"--skin__label_accent3__toRgbString\":\"255,170,9\",\"--skin__labeltext_accent3\":\"#212121\",\"--skin__labeltext_accent3__toRgbString\":\"33,33,33\",\"--skin__lead\":\"#FFFFFF\",\"--skin__lead__toRgbString\":\"255,255,255\",\"--skin__leftnav_active\":\"#FFFFFF\",\"--skin__leftnav_active__toRgbString\":\"255,255,255\",\"--skin__leftnav_def\":\"#FFE7C8\",\"--skin__leftnav_def__toRgbString\":\"255,231,200\",\"--skin__neutral_1\":\"#FFE7C8\",\"--skin__neutral_1__toRgbString\":\"255,231,200\",\"--skin__neutral_2\":\"#FFBD8B\",\"--skin__neutral_2__toRgbString\":\"255,189,139\",\"--skin__neutral_3\":\"#FFBD8B\",\"--skin__neutral_3__toRgbString\":\"255,189,139\",\"--skin__primary\":\"#531F0E\",\"--skin__primary__toRgbString\":\"83,31,14\",\"--skin__profile_icon_1\":\"#531F0E\",\"--skin__profile_icon_1__toRgbString\":\"83,31,14\",\"--skin__profile_icon_2\":\"#531F0E\",\"--skin__profile_icon_2__toRgbString\":\"83,31,14\",\"--skin__profile_icon_3\":\"#531F0E\",\"--skin__profile_icon_3__toRgbString\":\"83,31,14\",\"--skin__profile_icon_4\":\"#531F0E\",\"--skin__profile_icon_4__toRgbString\":\"83,31,14\",\"--skin__profile_toptext\":\"#FFFFFF\",\"--skin__profile_toptext__toRgbString\":\"255,255,255\",\"--skin__search_icon\":\"#FFE7C8\",\"--skin__search_icon__toRgbString\":\"255,231,200\",\"--skin__table_bg\":\"#F26D0A\",\"--skin__table_bg__toRgbString\":\"242,109,10\",\"--skin__text_accent3\":\"#212121\",\"--skin__text_accent3__toRgbString\":\"33,33,33\",\"--skin__text_primary\":\"#FFFFFF\",\"--skin__text_primary__toRgbString\":\"255,255,255\",\"--skin__tg_accent_1\":\"#BBDFC1\",\"--skin__tg_accent_1__toRgbString\":\"187,223,193\",\"--skin__tg_accent_3\":\"#FFE7B8\",\"--skin__tg_accent_3__toRgbString\":\"255,231,184\",\"--skin__tg_primary\":\"#CBC1C0\",\"--skin__tg_primary__toRgbString\":\"203,193,192\",\"--skin__web_bs_yj_bg\":\"#F16305\",\"--skin__web_bs_yj_bg__toRgbString\":\"241,99,5\",\"--skin__web_bs_zc_an2\":\"#FF963C\",\"--skin__web_bs_zc_an2__toRgbString\":\"255,150,60\",\"--skin__web_btmnav_db\":\"#F2760C\",\"--skin__web_btmnav_db__toRgbString\":\"242,118,12\",\"--skin__web_filter_gou\":\"#FFFFFF\",\"--skin__web_filter_gou__toRgbString\":\"255,255,255\",\"--skin__web_left_bg_q\":\"#FFFFFF0A\",\"--skin__web_left_bg_shadow\":\"#0000001F\",\"--skin__web_left_bg_shadow_active\":\"#0000001F\",\"--skin__web_left_bg_z\":\"#FFFFFF0D\",\"--skin__web_load_zz\":\"#FFA56166\",\"--skin__web_plat_line\":\"#FFA561\",\"--skin__web_plat_line__toRgbString\":\"255,165,97\",\"--skin__web_topbg_1\":\"#82402C\",\"--skin__web_topbg_1__toRgbString\":\"130,64,44\",\"--skin__web_topbg_3\":\"#531F0E\"}', '../skin/lobby_asset/2-1-22/Captura de tela 2025-12-24 121258.png', 1, 'https://dg.skirtinipgpay1.com/siteadmin/skin/lobby_asset/2-1-24/common/');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tokens_recuperacoes`
--

CREATE TABLE `tokens_recuperacoes` (
  `id_usuario` int(11) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes`
--

CREATE TABLE `transacoes` (
  `id` int(11) NOT NULL,
  `transacao_id` varchar(255) DEFAULT NULL,
  `usuario` int(11) DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `tipo` enum('deposito','saque') DEFAULT NULL,
  `data_registro` datetime DEFAULT NULL,
  `qrcode` longtext DEFAULT NULL,
  `code` text DEFAULT NULL,
  `status` enum('pago','processamento','expirado') DEFAULT NULL,
  `pay_type_sub_list_id` int(11) DEFAULT NULL,
  `join_bonus` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `celular` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `saldo` decimal(10,2) DEFAULT 0.00,
  `saldo_afiliados` decimal(10,2) DEFAULT 0.00,
  `rev` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_rev` decimal(10,2) NOT NULL DEFAULT 0.00,
  `real_name` varchar(255) DEFAULT NULL,
  `spassword` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `data_registro` datetime DEFAULT current_timestamp(),
  `invite_code` varchar(255) NOT NULL,
  `invitation_code` varchar(255) DEFAULT NULL,
  `cpf` varchar(11) DEFAULT NULL,
  `tipo_pagamento` int(11) NOT NULL DEFAULT 0,
  `senha_saque` int(11) NOT NULL DEFAULT 0,
  `senhaparasacar` varchar(255) DEFAULT NULL,
  `pessoas_convidadas` int(11) NOT NULL DEFAULT 0,
  `indicacoes_roubadas` int(11) DEFAULT 0,
  `statusaff` int(11) NOT NULL DEFAULT 0,
  `banido` int(11) DEFAULT 0,
  `historico` text DEFAULT NULL,
  `favoritos` text DEFAULT NULL,
  `vip` int(11) NOT NULL DEFAULT 0,
  `recompensa_vip` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_recompensa_vip` decimal(10,2) NOT NULL DEFAULT 0.00,
  `data_nascimento` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(244) NOT NULL DEFAULT 'first_male_7.png',
  `facebook` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(255) DEFAULT NULL,
  `telegram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `birth` varchar(255) DEFAULT NULL,
  `freeze` int(11) DEFAULT 0,
  `relogar` int(11) NOT NULL DEFAULT 0,
  `lobby` int(11) DEFAULT 1,
  `rtp` int(11) DEFAULT NULL,
  `modo_demo` tinyint(1) DEFAULT 0,
  `cpaLvl1` float DEFAULT NULL,
  `cpaLvl2` float DEFAULT NULL,
  `cpaLvl3` float DEFAULT NULL,
  `token_refer` varchar(255) DEFAULT NULL,
  `comissao_recebida` decimal(10,2) NOT NULL DEFAULT 0.00,
  `LuckyWheel` int(11) DEFAULT 1,
  `canApplyRegisterReward` tinyint(1) DEFAULT 1,
  `last_rebate_claim` datetime DEFAULT NULL,
  `task_install_apk_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_register_account_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_install_pwa_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_bind_cpf_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_bind_phone_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_bind_email_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_first_withdrawal_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_first_recharge_done` tinyint(1) NOT NULL DEFAULT 0,
  `task_set_assets_password_done` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `versell`
--

CREATE TABLE `versell` (
  `id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `client_id` text DEFAULT NULL,
  `client_secret` text DEFAULT NULL,
  `atualizado` varchar(45) DEFAULT NULL,
  `ativo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `versell`
--

INSERT INTO `versell` (`id`, `url`, `client_id`, `client_secret`, `atualizado`, `ativo`) VALUES
(1, '0', '0', '0', '0', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `vip_levels`
--

CREATE TABLE `vip_levels` (
  `id` int(11) NOT NULL,
  `id_vip` int(11) NOT NULL,
  `meta` float NOT NULL,
  `bonus` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `vip_levels`
--

INSERT INTO `vip_levels` (`id`, `id_vip`, `meta`, `bonus`) VALUES
(1, 1, 1, 5),
(2, 2, 18000, 180),
(3, 3, 100000, 1000),
(4, 4, 200000, 2000),
(5, 5, 1000000, 5000),
(6, 6, 1000000, 390),
(7, 7, 1000000, 500),
(8, 8, 1000000, 600),
(9, 9, 1000000, 700),
(10, 10, 1000000, 800),
(11, 11, 2000000, 900),
(12, 12, 3000000, 1000),
(13, 13, 4000000, 1100),
(14, 14, 5000000, 1200),
(15, 15, 6000000, 1300),
(16, 16, 7000000, 1400);

-- --------------------------------------------------------

--
-- Estrutura para tabela `vip_rebate_config`
--

CREATE TABLE `vip_rebate_config` (
  `id` int(11) NOT NULL,
  `vip_level` int(11) NOT NULL,
  `game_type` varchar(50) NOT NULL,
  `rebate_rate` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `vip_rebate_config`
--

INSERT INTO `vip_rebate_config` (`id`, `vip_level`, `game_type`, `rebate_rate`) VALUES
(1, 1, 'slot', 0.45),
(2, 1, 'live', 0.36),
(3, 1, 'fishing', 0.45),
(4, 1, 'sports', 0.36),
(5, 1, 'cards', 0.45),
(6, 1, 'lottery', 0.45),
(7, 1, 'blockchain', 0.23),
(8, 2, 'slot', 0.50),
(9, 2, 'live', 0.40),
(10, 2, 'fishing', 0.50),
(11, 2, 'sports', 0.40),
(12, 2, 'cards', 0.50),
(13, 2, 'lottery', 0.50),
(14, 2, 'blockchain', 0.25),
(15, 3, 'slot', 0.55),
(16, 3, 'live', 0.44),
(17, 3, 'fishing', 0.55),
(18, 3, 'sports', 0.44),
(19, 3, 'cards', 0.55),
(20, 3, 'lottery', 0.55),
(21, 3, 'blockchain', 0.28),
(22, 4, 'slot', 0.60),
(23, 4, 'live', 0.48),
(24, 4, 'fishing', 0.60),
(25, 4, 'sports', 0.48),
(26, 4, 'cards', 0.60),
(27, 4, 'lottery', 0.60),
(28, 4, 'blockchain', 0.30),
(29, 5, 'slot', 0.65),
(30, 5, 'live', 0.52),
(31, 5, 'fishing', 0.65),
(32, 5, 'sports', 0.52),
(33, 5, 'cards', 0.65),
(34, 5, 'lottery', 0.65),
(35, 5, 'blockchain', 0.33),
(36, 6, 'slot', 0.70),
(37, 6, 'live', 0.56),
(38, 6, 'fishing', 0.70),
(39, 6, 'sports', 0.56),
(40, 6, 'cards', 0.70),
(41, 6, 'lottery', 0.70),
(42, 6, 'blockchain', 0.35),
(43, 7, 'slot', 0.75),
(44, 7, 'live', 0.60),
(45, 7, 'fishing', 0.75),
(46, 7, 'sports', 0.60),
(47, 7, 'cards', 0.75),
(48, 7, 'lottery', 0.75),
(49, 7, 'blockchain', 0.38),
(50, 8, 'slot', 0.80),
(51, 8, 'live', 0.64),
(52, 8, 'fishing', 0.80),
(53, 8, 'sports', 0.64),
(54, 8, 'cards', 0.80),
(55, 8, 'lottery', 0.80),
(56, 8, 'blockchain', 0.40),
(57, 9, 'slot', 0.85),
(58, 9, 'live', 0.68),
(59, 9, 'fishing', 0.85),
(60, 9, 'sports', 0.68),
(61, 9, 'cards', 0.85),
(62, 9, 'lottery', 0.85),
(63, 9, 'blockchain', 0.43),
(64, 10, 'slot', 0.90),
(65, 10, 'live', 0.72),
(66, 10, 'fishing', 0.90),
(67, 10, 'sports', 0.72),
(68, 10, 'cards', 0.90),
(69, 10, 'lottery', 0.90),
(70, 10, 'blockchain', 0.45),
(71, 11, 'slot', 0.95),
(72, 11, 'live', 0.76),
(73, 11, 'fishing', 0.95),
(74, 11, 'sports', 0.76),
(75, 11, 'cards', 0.95),
(76, 11, 'lottery', 0.95),
(77, 11, 'blockchain', 0.48),
(78, 12, 'slot', 1.00),
(79, 12, 'live', 0.80),
(80, 12, 'fishing', 1.00),
(81, 12, 'sports', 0.80),
(82, 12, 'cards', 1.00),
(83, 12, 'lottery', 1.00),
(84, 12, 'blockchain', 0.50),
(85, 13, 'slot', 1.05),
(86, 13, 'live', 0.84),
(87, 13, 'fishing', 1.05),
(88, 13, 'sports', 0.84),
(89, 13, 'cards', 1.05),
(90, 13, 'lottery', 1.05),
(91, 13, 'blockchain', 0.53),
(92, 14, 'slot', 1.10),
(93, 14, 'live', 0.88),
(94, 14, 'fishing', 1.10),
(95, 14, 'sports', 0.88),
(96, 14, 'cards', 1.10),
(97, 14, 'lottery', 1.10),
(98, 14, 'blockchain', 0.55),
(99, 15, 'slot', 1.15),
(100, 15, 'live', 0.92),
(101, 15, 'fishing', 1.15),
(102, 15, 'sports', 0.92),
(103, 15, 'cards', 1.15),
(104, 15, 'lottery', 1.15),
(105, 15, 'blockchain', 0.58),
(106, 16, 'slot', 1.20),
(107, 16, 'live', 0.96),
(108, 16, 'fishing', 1.20),
(109, 16, 'sports', 0.96),
(110, 16, 'cards', 1.20),
(111, 16, 'lottery', 1.20),
(112, 16, 'blockchain', 0.60);

-- --------------------------------------------------------

--
-- Estrutura para tabela `vip_rewards_log`
--

CREATE TABLE `vip_rewards_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vip_level` int(11) NOT NULL,
  `reward_amount` decimal(20,2) NOT NULL,
  `reward_type` varchar(50) NOT NULL DEFAULT 'promotion',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `visita_site`
--

CREATE TABLE `visita_site` (
  `id` int(11) NOT NULL,
  `nav_os` text DEFAULT NULL,
  `mac_os` text DEFAULT NULL,
  `ip_visita` text DEFAULT NULL,
  `refer_visita` text DEFAULT NULL,
  `data_cad` date DEFAULT NULL,
  `hora_cad` time DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `pais` text DEFAULT NULL,
  `cidade` text DEFAULT NULL,
  `estado` text DEFAULT NULL,
  `ads_tipo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `visita_site`
--

INSERT INTO `visita_site` (`id`, `nav_os`, `mac_os`, `ip_visita`, `refer_visita`, `data_cad`, `hora_cad`, `id_user`, `pais`, `cidade`, `estado`, `ads_tipo`) VALUES
(1, 'Handheld Browser', 'Android', '190.124.190.199', 'https://nobrepix.online/admin/api', '2026-08-23', '17:50:31', 1, 'Brazil', 'Dois Vizinhos', 'Parana', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `webhook`
--

CREATE TABLE `webhook` (
  `id` int(11) NOT NULL,
  `nome` text NOT NULL,
  `bot_id` varchar(255) NOT NULL,
  `chat_id` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `webhook`
--

INSERT INTO `webhook` (`id`, `nome`, `bot_id`, `chat_id`, `status`) VALUES
(1, 'Cadastros e Pixs', 'Aqui', 'aqui', 0),
(2, 'Saques', '0', '0', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `welcome_roulette_settings`
--

CREATE TABLE `welcome_roulette_settings` (
  `id` int(11) NOT NULL,
  `min_cents` int(11) NOT NULL DEFAULT 30,
  `max_cents` int(11) NOT NULL DEFAULT 300,
  `audit_multiple` decimal(10,2) NOT NULL DEFAULT 1.00,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Despejando dados para a tabela `welcome_roulette_settings`
--

INSERT INTO `welcome_roulette_settings` (`id`, `min_cents`, `max_cents`, `audit_multiple`, `updated_at`, `active`) VALUES
(1, 0, 0, 0.00, '2026-03-22 21:12:29', 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `adicao_saldo`
--
ALTER TABLE `adicao_saldo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `afiliados_config`
--
ALTER TABLE `afiliados_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `assistance_rounds`
--
ALTER TABLE `assistance_rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`);

--
-- Índices de tabela `audit_flows`
--
ALTER TABLE `audit_flows`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `aurenpay`
--
ALTER TABLE `aurenpay`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `bau`
--
ALTER TABLE `bau`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `bspay`
--
ALTER TABLE `bspay`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cadastro_bonus_config`
--
ALTER TABLE `cadastro_bonus_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Índices de tabela `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Índices de tabela `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cupom`
--
ALTER TABLE `cupom`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `cupom_usados`
--
ALTER TABLE `cupom_usados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `valor` (`valor`);

--
-- Índices de tabela `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `drakon`
--
ALTER TABLE `drakon`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `expfypay`
--
ALTER TABLE `expfypay`
  ADD UNIQUE KEY `id` (`id`);

--
-- Índices de tabela `festival`
--
ALTER TABLE `festival`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `financeiro`
--
ALTER TABLE `financeiro`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `floats`
--
ALTER TABLE `floats`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Índices de tabela `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `game_categories`
--
ALTER TABLE `game_categories`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `greepay`
--
ALTER TABLE `greepay`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_play`
--
ALTER TABLE `historico_play`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_vip`
--
ALTER TABLE `historico_vip`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `igamewin`
--
ALTER TABLE `igamewin`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `inpagamentos`
--
ALTER TABLE `inpagamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `lobby_pgsoft`
--
ALTER TABLE `lobby_pgsoft`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `manipulacao_indicacoes`
--
ALTER TABLE `manipulacao_indicacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Índices de tabela `metodos_pagamentos`
--
ALTER TABLE `metodos_pagamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mina_config`
--
ALTER TABLE `mina_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `modais`
--
ALTER TABLE `modais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `nextpay`
--
ALTER TABLE `nextpay`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `notificacoes_lidas`
--
ALTER TABLE `notificacoes_lidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`admin_id`,`notification_type`,`notification_id`);

--
-- Índices de tabela `pay_type_sub_list`
--
ALTER TABLE `pay_type_sub_list`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pay_valores_cassino`
--
ALTER TABLE `pay_valores_cassino`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pgclone`
--
ALTER TABLE `pgclone`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `playfiver`
--
ALTER TABLE `playfiver`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Índices de tabela `popups`
--
ALTER TABLE `popups`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `ppclone`
--
ALTER TABLE `ppclone`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `promocoes`
--
ALTER TABLE `promocoes`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Índices de tabela `provedores`
--
ALTER TABLE `provedores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prov_code` (`code`);

--
-- Índices de tabela `rebate_history`
--
ALTER TABLE `rebate_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `red_envelope_settings`
--
ALTER TABLE `red_envelope_settings`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `red_pocket_rewards`
--
ALTER TABLE `red_pocket_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`);

--
-- Índices de tabela `resgate_comissoes`
--
ALTER TABLE `resgate_comissoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `roletinha100`
--
ALTER TABLE `roletinha100`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Índices de tabela `roulette_prizes`
--
ALTER TABLE `roulette_prizes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `segurança`
--
ALTER TABLE `segurança`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `signin_config`
--
ALTER TABLE `signin_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `signin_records`
--
ALTER TABLE `signin_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`date_record`);

--
-- Índices de tabela `solicitacao_saques`
--
ALTER TABLE `solicitacao_saques`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tarefas`
--
ALTER TABLE `tarefas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_taskTypeSub` (`taskTypeSub`);

--
-- Índices de tabela `temas`
--
ALTER TABLE `temas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `templates_cores`
--
ALTER TABLE `templates_cores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `transacoes`
--
ALTER TABLE `transacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `versell`
--
ALTER TABLE `versell`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `vip_levels`
--
ALTER TABLE `vip_levels`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `vip_rebate_config`
--
ALTER TABLE `vip_rebate_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vip_game` (`vip_level`,`game_type`);

--
-- Índices de tabela `vip_rewards_log`
--
ALTER TABLE `vip_rewards_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_vip` (`user_id`,`vip_level`,`reward_type`);

--
-- Índices de tabela `visita_site`
--
ALTER TABLE `visita_site`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `webhook`
--
ALTER TABLE `webhook`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `welcome_roulette_settings`
--
ALTER TABLE `welcome_roulette_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `adicao_saldo`
--
ALTER TABLE `adicao_saldo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `afiliados_config`
--
ALTER TABLE `afiliados_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `assistance_rounds`
--
ALTER TABLE `assistance_rounds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `audit_flows`
--
ALTER TABLE `audit_flows`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `banner`
--
ALTER TABLE `banner`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `bau`
--
ALTER TABLE `bau`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `cupom`
--
ALTER TABLE `cupom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `cupom_usados`
--
ALTER TABLE `cupom_usados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `customer_feedback`
--
ALTER TABLE `customer_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `festival`
--
ALTER TABLE `festival`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `financeiro`
--
ALTER TABLE `financeiro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de tabela `floats`
--
ALTER TABLE `floats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1505;

--
-- AUTO_INCREMENT de tabela `game_categories`
--
ALTER TABLE `game_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `greepay`
--
ALTER TABLE `greepay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `historico_play`
--
ALTER TABLE `historico_play`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT de tabela `historico_vip`
--
ALTER TABLE `historico_vip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `igamewin`
--
ALTER TABLE `igamewin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `inpagamentos`
--
ALTER TABLE `inpagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `lobby_pgsoft`
--
ALTER TABLE `lobby_pgsoft`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `manipulacao_indicacoes`
--
ALTER TABLE `manipulacao_indicacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `metodos_pagamentos`
--
ALTER TABLE `metodos_pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `modais`
--
ALTER TABLE `modais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `notificacoes_lidas`
--
ALTER TABLE `notificacoes_lidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `pay_type_sub_list`
--
ALTER TABLE `pay_type_sub_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pay_valores_cassino`
--
ALTER TABLE `pay_valores_cassino`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `promocoes`
--
ALTER TABLE `promocoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4077;

--
-- AUTO_INCREMENT de tabela `provedores`
--
ALTER TABLE `provedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de tabela `rebate_history`
--
ALTER TABLE `rebate_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `red_pocket_rewards`
--
ALTER TABLE `red_pocket_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de tabela `resgate_comissoes`
--
ALTER TABLE `resgate_comissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `roletinha100`
--
ALTER TABLE `roletinha100`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `roulette_prizes`
--
ALTER TABLE `roulette_prizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `segurança`
--
ALTER TABLE `segurança`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `signin_config`
--
ALTER TABLE `signin_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `signin_records`
--
ALTER TABLE `signin_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `solicitacao_saques`
--
ALTER TABLE `solicitacao_saques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `temas`
--
ALTER TABLE `temas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `templates_cores`
--
ALTER TABLE `templates_cores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de tabela `transacoes`
--
ALTER TABLE `transacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=777209964;

--
-- AUTO_INCREMENT de tabela `vip_levels`
--
ALTER TABLE `vip_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `vip_rebate_config`
--
ALTER TABLE `vip_rebate_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT de tabela `vip_rewards_log`
--
ALTER TABLE `vip_rewards_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `visita_site`
--
ALTER TABLE `visita_site`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `customer_feedback`
--
ALTER TABLE `customer_feedback`
  ADD CONSTRAINT `customer_feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
