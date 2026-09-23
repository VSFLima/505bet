<?php
include '../services/database.php';
$zip_filename = 'usuarios_exportados.zip';

$csv_file = tempnam(sys_get_temp_dir(), 'contatos_exportados_');
$txt_file = tempnam(sys_get_temp_dir(), 'telefones_exportados_');

$csv_output = fopen($csv_file, 'w');
fwrite($csv_output, "CONTATOS EXPORTADOS\n\n");

$query = "SELECT mobile, telefone, real_name, saldo FROM usuarios";
$result = mysqli_query($mysqli, $query);

$telefones_formatados = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $mobile = $row['mobile'];
        $telefone = $row['telefone'];
        $real_name = $row['real_name'];
        $saldo = number_format($row['saldo'], 2, ',', '.');
        $whatsapp_link = "https://wa.me/+55" . preg_replace('/\D/', '', $telefone);

        fwrite($csv_output, "Usuário: $mobile\n");
        fwrite($csv_output, "Telefone: $telefone\n");
        fwrite($csv_output, "Nome verdadeiro: $real_name\n");
        fwrite($csv_output, "Saldo: R$ $saldo\n");
        fwrite($csv_output, "Link para chamar no WhatsApp: $whatsapp_link\n\n");

        $telefones_formatados[] = "+55" . preg_replace('/\D/', '', $telefone);
    }

    $txt_output = fopen($txt_file, 'w');
    foreach ($telefones_formatados as $telefone) {
        fwrite($txt_output, $telefone . "\n");
    }

    fclose($txt_output);
} else {
    fwrite($csv_output, "Nenhum usuário encontrado.");
}

fclose($csv_output);

$zip = new ZipArchive();
if ($zip->open($zip_filename, ZipArchive::CREATE) === TRUE) {
    $zip->addFile($csv_file, 'contatos_exportados.csv');
    $zip->addFile($txt_file, 'telefones_exportados.txt');
    $zip->close();

    unlink($csv_file);
    unlink($txt_file);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($zip_filename));

    readfile($zip_filename);
    unlink($zip_filename);
    exit;
} else {
    echo "Erro ao criar o arquivo ZIP.";
    exit;
}
?>