document.addEventListener('DOMContentLoaded', function() {
    // Capturar o formulário e os campos de input
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    if (loginForm) {
        loginForm.addEventListener('submit', function(evento) {
            let isValid = true;

            // Função auxiliar para marcar um campo com erro
            function marcarErro(elemento) {
                elemento.style.borderColor = 'var(--error-red)';
                elemento.style.boxShadow = '0 0 25px var(--error-red)';
                // Adiciona um pequeno fundo avermelhado para chamar mais a atenção
                elemento.style.backgroundColor = 'rgba(255, 77, 77, 0.1)';
            }

            // Validar Username (o .trim() remove espaços em branco acidentais)
            if (usernameInput.value.trim() === '') {
                isValid = false;
                marcarErro(usernameInput);
            }

            // Validar Password
            if (passwordInput.value.trim() === '') {
                isValid = false;
                marcarErro(passwordInput);
            }

            // Se algum dos campos estiver inválido, cancela o envio para o PHP
            if (!isValid) {
                evento.preventDefault();
                
                // Focar no primeiro campo que estiver vazio
                if (usernameInput.value.trim() === '') {
                    usernameInput.focus();
                } else {
                    passwordInput.focus();
                }
            }
        });

        // Evento extra: Limpar o aspeto de erro assim que o utilizador começar a escrever
        [usernameInput, passwordInput].forEach(input => {
            if (input) {
                input.addEventListener('input', function() {
                    this.style.borderColor = 'var(--arcane-gold)';
                    this.style.boxShadow = '0 0 15px rgba(245, 210, 29, 0.3)';
                    this.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
                });
            }
        });
    }
});