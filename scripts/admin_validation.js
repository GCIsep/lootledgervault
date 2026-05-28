// Aguarda que o HTML da página carregue totalmente
document.addEventListener('DOMContentLoaded', function() {
    
    // Capturar os elementos do formulário
    const formCriarConta = document.getElementById('formCriarConta');
    const passwordInput = document.getElementById('password');
    const erroMensagem = document.getElementById('erroPassword');

    // Se o formulário existir na página, adiciona a validação
    if (formCriarConta) {
        formCriarConta.addEventListener('submit', function(evento) {
            
            // 1. Limpar estilos e mensagens de tentativas anteriores
            erroMensagem.style.display = 'none';
            erroMensagem.innerText = '';
            passwordInput.style.borderColor = '#444';
            passwordInput.style.boxShadow = 'none';

            // 2. Verificar o tamanho da password
            const passwordValue = passwordInput.value;

            if (passwordValue.length < 8) {
                // Impede o formulário de ser enviado para o servidor (PHP)
                evento.preventDefault();

                // Apresentar mensagem de ajuda ao utilizador
                erroMensagem.innerText = '⚠️ Segurança insuficiente: A password tem de ter no mínimo 8 caracteres.';
                erroMensagem.style.display = 'block';

                // Alterar a forma do objeto (bordo e sombra vermelha para destacar o erro)
                passwordInput.style.borderColor = '#ff4d4d';
                passwordInput.style.boxShadow = '0 0 8px rgba(255, 77, 77, 0.5)';
                
                // Colocar o cursor de volta na caixa da password
                passwordInput.focus();
            }
        });
    }
});