<?php
// 1. Inicia a sessão para o PHP saber qual é a sessão que tem de destruir
session_start();

// 2. Limpa todas as variáveis da sessão
session_unset();

// 3. Destrói a sessão por completo
session_destroy();

// 4. Redireciona o utilizador de volta para a página de Login
header("Location: login.php");
exit();
?>