<?php
date_default_timezone_set('America/Sao_Paulo');
include_once('database.php');
include_once('funcao.php');

function data_avatar()
{
	global $mysqli;
	$qry = "SELECT * FROM admin_users WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_avatar = data_avatar();

function qtd_provedor_games($provedor)
{
	global $mysqli;
	$qry = "SELECT * FROM games WHERE provider='" . $provedor . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_num_rows($res);
	return $data;
}

function qtd_provedor_ativos()
{
	global $mysqli;
	$qry = "SELECT * FROM provedores WHERE status=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_num_rows($res);
	return $data;
}

function qtd_games_ativos()
{
	global $mysqli;
	$qry = "SELECT * FROM games WHERE status=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_num_rows($res);
	return $data;
}

function qtd_usuarios()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_rede_1 = [];
    $ids_rede_2 = [];
    $ids_sem_rede = [];

    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }

        if ($found_rede === 1) {
            $ids_rede_1[] = $id;
        } elseif ($found_rede === 2) {
            $ids_rede_2[] = $id;
        } else {
            $ids_sem_rede[] = $id;
        }
    }

    $ids_todos = array_keys($usuarios);

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede, $ids_rede_1);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "WHERE id IN ($ids_implode)";
    } else {
        $where_ids = "WHERE 0";
    }

    $qry = "SELECT COUNT(*) as total FROM usuarios $where_ids";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res)['total'] ?? 0;
    return $data;
}

function qtd_usuarios_depositantes()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $getUsuariosDaRede = function ($invite_code_bspay) use ($mysqli) {
        $ids = [];
        $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
        $res = $mysqli->query($sql);
        $usuarios = [];
        while ($row = $res->fetch_assoc()) {
            $usuarios[$row['id']] = $row;
        }
        foreach ($usuarios as $id => $user) {
            $current_code = $user['invitation_code'];
            $max_depth = 10;
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay) {
                    $ids[] = $id;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        return $ids;
    };

    $ids_rede_1 = $getUsuariosDaRede($invite_code_bspay_1);
    $ids_rede_2 = $getUsuariosDaRede($invite_code_bspay_2);

    $sql = "SELECT id FROM usuarios";
    $res = $mysqli->query($sql);
    $ids_todos = [];
    while ($row = $res->fetch_assoc()) {
        $ids_todos[] = $row['id'];
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_diff($ids_todos, $ids_rede_1, $ids_rede_2);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND usuario IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "
        SELECT COUNT(DISTINCT usuario) as depositantes 
        FROM transacoes 
        WHERE tipo = 'deposito' AND status = 'pago' $where_ids
    ";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res)['depositantes'];

    return $data;
}

function saldo_cassino()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $getUsuariosDaRede = function ($invite_code_bspay) use ($mysqli) {
        $ids = [];
        $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
        $res = $mysqli->query($sql);
        $usuarios = [];
        while ($row = $res->fetch_assoc()) {
            $usuarios[$row['id']] = $row;
        }
        foreach ($usuarios as $id => $user) {
            $current_code = $user['invitation_code'];
            $max_depth = 10;
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay) {
                    $ids[] = $id;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        return $ids;
    };

    $ids_rede_1 = $getUsuariosDaRede($invite_code_bspay_1);
    $ids_rede_2 = $getUsuariosDaRede($invite_code_bspay_2);

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_sem_rede = [];
    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }
        if ($found_rede !== 1 && $found_rede !== 2) {
            $ids_sem_rede[] = $id;
        }
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede, $ids_rede_1);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = " AND usuario IN ($ids_implode)";
        $where_saques = " AND id_user IN ($ids_implode)";
    } else {
        $where_ids = " AND 0";
        $where_saques = " AND 0";
    }

    $qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE tipo='deposito' AND status='pago' $where_ids";
    $result = mysqli_query($mysqli, $qry);
    $deposito = '0.00';
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['total_soma'] > 0) {
            $deposito = $row['total_soma'];
        }
    }

    $qry_saques = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE status=1 $where_saques";
    $result_saques = mysqli_query($mysqli, $qry_saques);
    $saques = '0.00';
    if ($row_saques = mysqli_fetch_assoc($result_saques)) {
        if ($row_saques['total_soma'] > 0) {
            $saques = $row_saques['total_soma'];
        }
    }

    $total = $deposito - $saques;
    return $total;
}

function total_saldos_usuarios()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $getUsuariosDaRede = function ($invite_code_bspay) use ($mysqli) {
        $ids = [];
        $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
        $res = $mysqli->query($sql);
        $usuarios = [];
        while ($row = $res->fetch_assoc()) {
            $usuarios[$row['id']] = $row;
        }
        foreach ($usuarios as $id => $user) {
            $current_code = $user['invitation_code'];
            $max_depth = 10;
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay) {
                    $ids[] = $id;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        return $ids;
    };

    $ids_rede_1 = $getUsuariosDaRede($invite_code_bspay_1);
    $ids_rede_2 = $getUsuariosDaRede($invite_code_bspay_2);

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_sem_rede = [];
    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }
        if ($found_rede !== 1 && $found_rede !== 2) {
            $ids_sem_rede[] = $id;
        }
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede, $ids_rede_1);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = " WHERE id IN ($ids_implode)";
    } else {
        $where_ids = " WHERE 0";
    }

    $qry = "SELECT SUM(saldo) AS total_saldos FROM usuarios" . $where_ids;
    $result = mysqli_query($mysqli, $qry);
    $total_saldos = '0.00';
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['total_saldos'] > 0) {
            $total_saldos = $row['total_saldos'];
        }
    }

    return $total_saldos;
}

function depositos_pendentes()
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE tipo='deposito' AND status='processamento'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$deposito = $row['total_soma'];
		} else {
			$deposito = '0.00';
		}
	}
	return $deposito;
}

function depositos_pendentesemlink()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_rede_1 = [];
    $ids_rede_2 = [];
    $ids_sem_rede = [];

    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;

                foreach ($usuarios as $u) { 
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }

        if ($found_rede === 1) {
            $ids_rede_1[] = $id;
            file_put_contents(
                __DIR__ . '/log_ids_rede_1.json',
                json_encode($ids_rede_1, JSON_PRETTY_PRINT)
            );
        } elseif ($found_rede === 2) {
            $ids_rede_2[] = $id;
        } else {
            $ids_sem_rede[] = $id;
        }
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND usuario IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE status='processamento' $where_ids";
    $result = mysqli_query($mysqli, $qry);
    $deposito = '0.00';
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['total_soma'] > 0) {
            $deposito = $row['total_soma'];
        } else {
            $deposito = '0.00';
        }
    }
    return $deposito;
}

function depositos_diarios()
{
	global $mysqli;
	$data = date('Y-m-d');
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE tipo='deposito' AND status='processamento' AND DATE(data_registro) = ?";
	$stmt = $mysqli->prepare($qry);
	$stmt->bind_param("s", $data);
	$stmt->execute();
	$result = $stmt->get_result();

	$deposito = '0.00';

	if ($row = $result->fetch_assoc()) {
		if ($row['total_soma'] > 0) {
			$deposito = $row['total_soma'];
		}
	}

	return $deposito;
}

function depositos_diarios_pagos()
{
	global $mysqli;
	$data = date('Y-m-d');
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE tipo='deposito' AND status='pago' AND DATE(data_registro) = ?";
	$stmt = $mysqli->prepare($qry);
	$stmt->bind_param("s", $data);
	$stmt->execute();
	$result = $stmt->get_result();

	$deposito = '0.00';

	if ($row = $result->fetch_assoc()) {
		if ($row['total_soma'] > 0) {
			$deposito = $row['total_soma'];
		}
	}

	return $deposito;
}

function depositos_total()
{
	global $mysqli;
	$data = date('Y-m-d');
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE tipo='deposito' AND status='pago'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$deposito = $row['total_soma'];
		} else {
			$deposito = '0.00';
		}
	}
	return $deposito;
}

function depositos_blogueiros()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_rede_1 = [];
    $ids_rede_2 = [];
    $ids_sem_rede = [];

    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;

                foreach ($usuarios as $u) { 
                    if ($u['invite_code'] == $current_code) { 
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }

        if ($found_rede === 1) {
            $ids_rede_1[] = $id;
            file_put_contents(
                __DIR__ . '/log_ids_rede_1.json',
                json_encode($ids_rede_1, JSON_PRETTY_PRINT)
            );
        } elseif ($found_rede === 2) {
            $ids_rede_2[] = $id;
        } else {
            $ids_sem_rede[] = $id;
        }
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_diff($ids_rede_1, $ids_rede_2);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND usuario IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE status='pago' $where_ids";
    $result = mysqli_query($mysqli, $qry);
    $deposito = '0.00';
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['total_soma'] > 0) {
            $deposito = $row['total_soma'];
        } else {
            $deposito = '0.00';
        }
    }
    return $deposito;
}

function depositos_totalsemlink()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_rede_1 = [];
    $ids_rede_2 = [];
    $ids_sem_rede = [];

    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;

                foreach ($usuarios as $u) { 
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }

        if ($found_rede === 1) {
            $ids_rede_1[] = $id;
            file_put_contents(
                __DIR__ . '/log_ids_rede_1.json',
                json_encode($ids_rede_1, JSON_PRETTY_PRINT)
            );
        } elseif ($found_rede === 2) {
            $ids_rede_2[] = $id;
        } else {
            $ids_sem_rede[] = $id;
        }
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND usuario IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE status='pago' $where_ids";
    $result = mysqli_query($mysqli, $qry);
    $deposito = '0.00';
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['total_soma'] > 0) {
            $deposito = $row['total_soma'];
        } else {
            $deposito = '0.00';
        }
    }
    return $deposito;
}

function saques_pendentes()
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE status=0";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$deposito = $row['total_soma'];
		} else {
			$deposito = '0.00';
		}
	}
	return $deposito;
}

function saques_diarios_pagos()
{
	global $mysqli;
	$data = date('Y-m-d');
	$qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE data_registro='" . $data . "' AND status=1";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$deposito = $row['total_soma'];
		} else {
			$deposito = '0.00';
		}
	}
	return $deposito;
}

function saques_total()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios WHERE statusaff = 1";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_rede_1 = [];
    $ids_rede_2 = [];
    $ids_sem_rede = [];

    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }

        if ($found_rede === 1) {
            $ids_rede_1[] = $id;
        } elseif ($found_rede === 2) {
            $ids_rede_2[] = $id;
        } else {
            $ids_sem_rede[] = $id;
        }
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede, $ids_rede_1);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND id_user IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE status='1' $where_ids";
    $result = mysqli_query($mysqli, $qry);
    $deposito = '0.00';
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['total_soma'] > 0) {
            $deposito = $row['total_soma'];
        } else {
            $deposito = '0.00';
        }
    }
    return $deposito;
}

function saques_totalsemlink()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $sql = "SELECT id, invite_code, invitation_code FROM usuarios WHERE statusaff = 0";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    $ids_rede_1 = [];
    $ids_rede_2 = [];
    $ids_sem_rede = [];

    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }

        if ($found_rede === 1) {
            $ids_rede_1[] = $id;
        } elseif ($found_rede === 2) {
            $ids_rede_2[] = $id;
        } else {
            $ids_sem_rede[] = $id;
        }
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede, $ids_rede_1);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND id_user IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE status='1' $where_ids";
    $result = mysqli_query($mysqli, $qry);
    $deposito = '0.00';
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['total_soma'] > 0) {
            $deposito = $row['total_soma'];
        } else {
            $deposito = '0.00';
        }
    }
    return $deposito;
}

function count_saques_pendentes()
{
	global $mysqli;
	$qry = "SELECT * FROM solicitacao_saques WHERE status=0";
	$res = mysqli_query($mysqli, $qry);
	$count = mysqli_num_rows($res);
	return $count;
}

function qtd_usuarios_diarios()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';
    $data = date('Y-m-d');

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $getUsuariosDaRede = function ($invite_code_bspay) use ($mysqli) {
        $ids = [];
        $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
        $res = $mysqli->query($sql);
        $usuarios = [];
        while ($row = $res->fetch_assoc()) {
            $usuarios[$row['id']] = $row;
        }
        foreach ($usuarios as $id => $user) {
            $current_code = $user['invitation_code'];
            $max_depth = 10;
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay) {
                    $ids[] = $id;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        return $ids;
    };

    $ids_rede_1 = $getUsuariosDaRede($invite_code_bspay_1);
    $ids_rede_2 = $getUsuariosDaRede($invite_code_bspay_2);

    $sql = "SELECT id FROM usuarios";
    $res = $mysqli->query($sql);
    $ids_todos = [];
    while ($row = $res->fetch_assoc()) {
        $ids_todos[] = $row['id'];
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_diff($ids_todos, $ids_rede_1, $ids_rede_2);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND id IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "SELECT COUNT(*) as total FROM usuarios WHERE DATE_FORMAT(data_registro, '%Y-%m-%d') = '$data' $where_ids";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res)['total'] ?? 0;

    return $data;
}

function qtd_usuarios_depositantes_diarios()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';
    $data = date('Y-m-d');

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $getUsuariosDaRede = function ($invite_code_bspay) use ($mysqli) {
        $ids = [];
        $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
        $res = $mysqli->query($sql);
        $usuarios = [];
        while ($row = $res->fetch_assoc()) {
            $usuarios[$row['id']] = $row;
        }
        foreach ($usuarios as $id => $user) {
            $current_code = $user['invitation_code'];
            $max_depth = 10;
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay) {
                    $ids[] = $id;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        return $ids;
    };

    $ids_rede_1 = $getUsuariosDaRede($invite_code_bspay_1);
    $ids_rede_2 = $getUsuariosDaRede($invite_code_bspay_2);

    $sql = "SELECT id FROM usuarios";
    $res = $mysqli->query($sql);
    $ids_todos = [];
    while ($row = $res->fetch_assoc()) {
        $ids_todos[] = $row['id'];
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_diff($ids_todos, $ids_rede_1, $ids_rede_2);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND usuario IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "
        SELECT COUNT(DISTINCT usuario) as depositantes 
        FROM transacoes 
        WHERE tipo = 'deposito' AND status = 'pago' AND DATE(data_registro) = '$data' $where_ids
    ";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res)['depositantes'];

    return $data;
}

function qtd_usuarios_90d()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';
    $data_inicio = date('Y-m-d', strtotime('-90 days'));

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $getUsuariosDaRede = function ($invite_code_bspay) use ($mysqli) {
        $ids = [];
        $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
        $res = $mysqli->query($sql);
        $usuarios = [];
        while ($row = $res->fetch_assoc()) {
            $usuarios[$row['id']] = $row;
        }
        foreach ($usuarios as $id => $user) {
            $current_code = $user['invitation_code'];
            $max_depth = 10;
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay) {
                    $ids[] = $id;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        return $ids;
    };

    $ids_rede_1 = $getUsuariosDaRede($invite_code_bspay_1);
    $ids_rede_2 = $getUsuariosDaRede($invite_code_bspay_2);

    $sql = "SELECT id FROM usuarios";
    $res = $mysqli->query($sql);
    $ids_todos = [];
    while ($row = $res->fetch_assoc()) {
        $ids_todos[] = $row['id'];
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_diff($ids_todos, $ids_rede_1, $ids_rede_2);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND id IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "SELECT COUNT(*) as total FROM usuarios WHERE data_registro >= '$data_inicio' $where_ids";
    $res = mysqli_query($mysqli, $qry);
    $total = mysqli_fetch_assoc($res)['total'] ?? 0;
    return $total;
}

function qtd_primeiro_deposito_usuarios_90d()
{
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';
    $data_inicio = date('Y-m-d', strtotime('-90 days'));

    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    $getUsuariosDaRede = function ($invite_code_bspay) use ($mysqli) {
        $ids = [];
        $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
        $res = $mysqli->query($sql);
        $usuarios = [];
        while ($row = $res->fetch_assoc()) {
            $usuarios[$row['id']] = $row;
        }
        foreach ($usuarios as $id => $user) {
            $current_code = $user['invitation_code'];
            $max_depth = 10;
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay) {
                    $ids[] = $id;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) break;
            }
        }
        return $ids;
    };

    $ids_rede_1 = $getUsuariosDaRede($invite_code_bspay_1);
    $ids_rede_2 = $getUsuariosDaRede($invite_code_bspay_2);

    $sql = "SELECT id FROM usuarios";
    $res = $mysqli->query($sql);
    $ids_todos = [];
    while ($row = $res->fetch_assoc()) {
        $ids_todos[] = $row['id'];
    }

    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_diff($ids_todos, $ids_rede_1, $ids_rede_2);
    }

    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND usuario IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    $qry = "
        SELECT COUNT(DISTINCT usuario) as total
        FROM transacoes t1
        WHERE tipo = 'deposito' 
        AND status = 'pago'
        AND DATE(data_registro) >= '$data_inicio'
        AND data_registro = (
            SELECT MIN(data_registro)
            FROM transacoes t2
            WHERE t2.usuario = t1.usuario 
            AND t2.tipo = 'deposito' 
            AND t2.status = 'pago'
        )
        $where_ids
    ";

    $res = mysqli_query($mysqli, $qry);
    $total = mysqli_fetch_assoc($res)['total'] ?? 0;

    return $total;
}

function total_jogadas()
{
	global $mysqli;
	$qry = "SELECT COUNT(*) as total FROM historico_play";
	$result = mysqli_query($mysqli, $qry);
	$row = mysqli_fetch_assoc($result);
	return $row['total'];
}

function formatar_nome_jogo($nome_game)
{
	return ucwords(str_replace('-', ' ', $nome_game));
}

function jogo_mais_jogado()
{
	global $mysqli;
	$qry = "SELECT nome_game, COUNT(*) as total FROM historico_play GROUP BY nome_game ORDER BY total DESC LIMIT 1";
	$result = mysqli_query($mysqli, $qry);
	$row = mysqli_fetch_assoc($result);

	return $row ? formatar_nome_jogo($row['nome_game']) : 'Nenhum jogo encontrado';
}

function percentual_usuarios_diarios()
{
	$total = qtd_usuarios();
	$diarios = qtd_usuarios_diarios();

	if ($total > 0) {
		$percentual = ($diarios / $total) * 100;
	} else {
		$percentual = 0;
	}

	return number_format($percentual, 1);
}

function percentual_usuarios_90d()
{
	$total = qtd_usuarios();
	$usuarios_90d = qtd_usuarios_90d();

	if ($total > 0) {
		$percentual = ($usuarios_90d / $total) * 100;
	} else {
		$percentual = 0;
	}

	return number_format($percentual, 1);
}

function percentual_lucro()
{
	$total_depositos = depositos_total();
	$total_saques = saques_total();

	if ($total_depositos > 0) {
		$percentual_lucro = (($total_depositos - $total_saques) / $total_depositos) * 100;
	} else {
		$percentual_lucro = 0;
	}

	return number_format($percentual_lucro, 1);
}

function count_saques_total()
{
	global $mysqli;
	$qry = "SELECT COUNT(*) as total_count FROM solicitacao_saques WHERE status = '1'";
	$result = mysqli_query($mysqli, $qry);
	$row = mysqli_fetch_assoc($result);
	return $row['total_count'] ?? 0;
}
