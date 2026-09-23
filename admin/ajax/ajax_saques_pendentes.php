<style>
	/* Please ❤ this if you like it! */
/* Follow Me https://codepen.io/designfenix */
/**/
/**/
/**/
/**/
/**/
/**/
/**/
@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap");
:root {
	--vs-primary: 29 92 255;
}

/*Dialog Styles*/
dialog {
	padding: 1rem 3rem;
	background: #ffffffa3;
	max-width: 400px;
	padding-top: 2rem;
	border-radius: 20px;
	border: 0;
	box-shadow: 0 5px 30px 0 rgb(0 0 0 / 10%);
	animation: fadeIn 1s ease both;
	top: 0;
  left: 0;
  right: 0;
  bottom: 0;
	&::backdrop {
		animation: fadeIn 1s ease both;
		background: rgb(255 255 255 / 40%);
		z-index: 2;
		backdrop-filter: blur(20px);
	}
	.x {
		filter: grayscale(1);
		border: none;
		background: none;
		position: absolute;
		top: 15px;
		right: 10px;
		transition: ease filter, transform 0.3s;
		cursor: pointer;
		transform-origin: center;
		&:hover {
			filter: grayscale(0);
			transform: scale(1.1);
		}
	}
	h2 {
		font-weight: 600;
		font-size: 2rem;
		padding-bottom: 1rem;
	}
	p {
		font-size: 1rem;
		line-height: 1.3rem;
		padding: 0.5rem 0;
		a {
			&:visited {
				color: rgb(var(--vs-primary));
			}
		}
	}
}

@keyframes fadeIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}
.modal-backdrop {
        animation: fadeIn 1s ease both;
        background: rgba(255, 255, 255, 0.4);
        z-index: 2;
        backdrop-filter: blur(20px);
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
    }

    .modal-backdrop.show {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    .modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: #1a1c23;
        color: white;
        padding: 20px;
        border-radius: 10px;
        z-index: 1000;
    }

    .modal.show {
        display: block;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-body {
        margin-top: 10px;
    }

    .modal-footer {
        margin-top: 20px;
    }
	.dash-form {
  -webkit-backdrop-filter: blur(7px);
  backdrop-filter: blur(7px);
  mix-blend-mode: normal;
  border: 1px #000;
  border-radius: 5px;
  transform: translate(0);
}
	</style>
	 <?php
#======================================#
ini_set('display_errors', 0);
error_reporting(E_ALL);
#======================================#
session_start();
include_once("../services/database.php");
include_once("../services/funcao.php");
include_once("../services/crud.php");
include_once("../services/crud-adm.php");
include_once('../services/checa_login_adm.php');
include_once("../services/CSRF_Protect.php");
$csrf = new CSRF_Protect();
#======================================#
#expulsa user
checa_login_adm();
#======================================#

global $mysqli;

// Captura os dados do formulário
$pagina = filter_input(INPUT_POST, 'pagina', FILTER_SANITIZE_NUMBER_INT);
$qnt_result_pg = filter_input(INPUT_POST, 'qnt_result_pg', FILTER_SANITIZE_NUMBER_INT);

// Calcula o início da visualização
$inicio = ($pagina * $qnt_result_pg) - $qnt_result_pg;

// Consulta no banco de dados
$result_usuario = "SELECT * FROM solicitacao_saques WHERE status=0 ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
$resultado_usuario = mysqli_query($mysqli, $result_usuario);

// Verifica se encontrou resultados na tabela "transacoes"
if (($resultado_usuario) AND ($resultado_usuario->num_rows != 0)) {
    while($data = mysqli_fetch_assoc($resultado_usuario)){
        $data_return = data_user_id($data['id_user']);
        $status_view = $data['status'] == 'pago' ? '<span class="status-badge green"><div class="small-dot _4px bg-green-300"></div> PAGO</span>' : '<span class="status-badge yellow"><div class="small-dot _4px bg-secondary-5"></div> PENDENTE</span>';
?>
<div class="recent-orders-table-row" style="display: flex; justify-content: space-between;">
    <div class="flex align-center">
        <div class="paragraph-small color-neutral-100"><?=$data_return['id'];?></div>
    </div>
    <div class="flex align-center">
        <div class="paragraph-small color-neutral-100"><?=$data_return['mobile'];?></div>
    </div>
    <div class="flex align-center">
        <div class="paragraph-small color-neutral-100"><?=ver_data($data['data_registro']);?></div>
    </div>
    <div class="flex align-center">
        <div class="paragraph-small color-neutral-100"><?=$status_view;?></div>
    </div>
    <div class="flex align-center">
        <div class="paragraph-small color-neutral-100">R$ <?=Reais2($data['valor']);?></div>
    </div>
    <div class="flex align-center gap-column-6px">
        <button type="button" onclick="event.preventDefault();toggleModal('modal-default<?= $data['id']; ?>', true)" class="dashdark-custom-icon edit-icon" style="background-color: transparent;"></button>
        <button type="button" onclick="event.preventDefault();toggleModal('modal-default2<?= $data['id']; ?>', true)" class="dashdark-custom-icon edit-icon" style="background-color: transparent;"></button>
    </div>
</div>

<dialog id="modal-default2<?= $data['id']; ?>" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content" style="color: grey;">
            <div class="modal-header" style="border-bottom: 0px solid #e5e5e5; text-align: end;">
                <h4 style="text-align: center;color:red;">RECUSAR SAQUE</h4>
            </div>
            <div class="modal-body" style="background: #a6a8b0;">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <form role="form" method="post">
                            <div class="box-body">
                                <div class="dash-form">
                                    <div class="alert alert-warning alert-dismissible">
                                        <h4 style="color:red"><i class="icon fa fa-warning"></i> Aviso!</h4>
                                        <h5 style="color:#0b0a0a"> Atualize a Solicitação de Saque somente se você tenha certeza que deseja recusar o saque.</h5>
                                    </div>
                                </div>
                                <div class="dash-form">
                                    <label for="exampleInputEmail1">Valor a ser Recusado</label>
                                    <h1><strong>R$ <?= Reais2($data['valor']); ?></strong></h1>
                                </div>
                                <div class="dash-form">
                                    <label for="exampleInputEmail1">Nome User</label>
                                    <input type="text" class="dash-form" id="exampleInputEmail1" value="<?= $data_return['nome']; ?>" readonly>
                                </div>
                                <div class="dash-form">
                                    <label for="exampleInputEmail1">Tipo Pix</label>
                                    <input type="text" class="dash-form" id="exampleInputEmail1" value="<?= $data['tipo']; ?>" readonly>
                                </div>
                                <div class="dash-form">
                                    <label for="exampleInputPassword1">Key Pix</label>
                                    <input type="text" class="dash-form" value="<?= $data['pix']; ?>">
                                </div>
                            </div>
                            <div class="box-footer">
                                <?php $csrf->echoInputField(); ?>
                               <input type="hidden" name="id_pay"
                                                            value="<?=intval($data['id']);?>" required />

                                                            <input type="hidden" name="valor_reprovado"
                                                            value="<?=$data['valor'];?>" required />

                                                            <input type="hidden" name="email_reprovado"
                                                            value="<?=$data_return['mobile'];?>" required />

                                                            <br>
                                <button type="submit" id="confirmar-pagamento" name="att-pay" class="btn-primary w-inline-block">Atualizar Solicitação de Saque</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button onclick="event.preventDefault();toggleModal('modal-default2<?= $data['id']; ?>', false)" aria-label="close" class="x">❌</button>
</dialog>

<dialog id="modal-default<?= $data['id']; ?>" style="display: none;">
<div class="modal-dialog">
        <div class="modal-content" style="color: grey;">
            <div class="modal-header" style="border-bottom: 0px solid #e5e5e5; text-align: end;">
                <h4 style="text-align: center;color:green;">APROVAR SAQUE</h4>
            </div>
            <div class="modal-body" style="background: #a6a8b0;">
                <div class="col-md-12">
                    <div class="box box-primary">
                        <div class="box-body">
                            <div class="dash-form">
                                <div class="alert alert-warning alert-dismissible">
                                <h4 style="color:red"><i class="icon fa fa-warning"></i> Aviso!</h4>
                                        <h5 style="color:#0b0a0a"> Atualize a Solicitação de Saque somente se você tenha certeza que deseja aprovar o saque.</h5>
                                </div>
                            </div>
                            <div class="dash-form">
                                <label for="valorPago">Valor a ser Pago</label>
                                <h1><strong>R$ <?= Reais2($data['valor']); ?></strong></h1>
                            </div>
                            <div class="dash-form">
                                <label for="nomeUser">Nome User</label>
                                <input type="text" class="dash-form" id="nomeUser" value="<?= $data_return['nome']; ?>" readonly>
                            </div>
                            <div class="dash-form">
                                <label for="tipoPix">Tipo Pix</label>
                                <input type="text" class="dash-form" id="tipoPix" value="<?= $data['tipo']; ?>" readonly>
                            </div>
                            <div class="dash-form">
                                <label for="keyPix">Key Pix</label>
                                <input type="text" class="dash-form" id="keyPix" value="<?= $data['pix']; ?>">
                            </div>
                            <input type="hidden" id="modalExternalReference" value="<?= $data['transacao_id']; ?>" required />
                        </div>
                        <div class="box-footer">
                            <br>
                            <form action='payment_manual.php?id=<?= $data['transacao_id']; ?>' method="POST">
                                <button id="confirmPayment" class="btn-primary w-inline-block">
                                    Atualizar Solicitação de Saque
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
           
        </div>
    </div>
    <button onclick="event.preventDefault();toggleModal('modal-default<?= $data['id']; ?>', false)" aria-label="close" class="x">❌</button>
</dialog>

<script>
	//abrir modal
function toggleModal(id, show) {
  var modal = document.getElementById(id);
  if (modal) {
    modal.style.display = show ? "block" : "none";
  }
}

	</script>

<?php
    }
} else {
?>
<tr>
<div class="text-300 medium color-neutral-100" style="display: flex;align-items: center;/*! margin-top: auto; */margin: 20px;">
                          <div class="tag"></div>
                            <h4 style="margin-top: 10px;">Não existem Saques pendentes!</span></h4>
                          </div>
</tr>
<?php } ?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet" />
     <script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
     <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>


