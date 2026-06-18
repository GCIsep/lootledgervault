<?php
$dir = './'; // Substitua pelo caminho da sua diretoria

// Verifica se o diretório existe
if (is_dir($dir)) {
    // Lê o conteúdo removendo o '.' e o '..'
	$conteudo = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($conteudo as $item) {
		if (str_starts_with($item, 'tester')) {
        continue;
    }
        // Exibe o nome do ficheiro ou pasta
        echo $item . "<br>";
    }
} else {
    echo "Diretório não encontrado.";
}
?>
