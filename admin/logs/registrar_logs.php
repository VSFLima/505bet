<?php
include(__DIR__ . '/../services/database.php');

function registrarLog($conn, $email, $action, $user_id = null) {
    $sql = "INSERT INTO logs (user_id, email, action) VALUES (?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $user_id, $email, $action);
        if ($stmt->execute()) {
            return true;
        } else {
            error_log("Erro ao registrar o log: " . $stmt->error);
            return false;
        }
        $stmt->close();
    } else {
        error_log("Erro ao preparar a declaração: " . $conn->error);
        return false;
    }
}
?>