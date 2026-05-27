// Funcionalidad para la página de login

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar funcionalidades
    initPasswordToggle();
    initCaptcha();
    
    // Mostrar el formulario de login por defecto
    showForm('login');
});

// Alternar visibilidad de contraseña
function initPasswordToggle() {
    const passwordField = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    
    if (togglePassword && passwordField) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Cambiar icono
            const icon = togglePassword.querySelector('i');
            if (type === 'password') {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    }
}

// Generar CAPTCHA matemático
function generateCaptcha() {
    const operators = ['+', '-'];
    const operator = operators[Math.floor(Math.random() * operators.length)];
    let num1, num2, answer;
    
    // Asegurar que el resultado sea positivo para una mejor experiencia de usuario
    if (operator === '+') {
        num1 = Math.floor(Math.random() * 10) + 1;
        num2 = Math.floor(Math.random() * 10) + 1;
        answer = num1 + num2;
    } else {
        num1 = Math.floor(Math.random() * 10) + 6;
        num2 = Math.floor(Math.random() * 5) + 1;
        answer = num1 - num2;
    }
    
    return {
        question: `${num1} ${operator} ${num2}`,
        answer: answer
    };
}

// Inicializar CAPTCHA para los formularios
function initCaptcha() {
    const recoverCaptcha = document.getElementById('captcha-question-recover');
    const changeCaptcha = document.getElementById('captcha-question-change');
    
    if (recoverCaptcha) {
        const captcha = generateCaptcha();
        recoverCaptcha.textContent = captcha.question;
        recoverCaptcha.dataset.answer = captcha.answer;
    }
    
    if (changeCaptcha) {
        const captcha = generateCaptcha();
        changeCaptcha.textContent = captcha.question;
        changeCaptcha.dataset.answer = captcha.answer;
    }
}

// Validar CAPTCHA antes de enviar formularios
function validateCaptcha(formId) {
    let captchaQuestion, captchaAnswer, userAnswer;
    
    if (formId === 'recover-form') {
        captchaQuestion = document.getElementById('captcha-question-recover');
        userAnswer = document.getElementById('captcha-answer-recover').value;
    } else if (formId === 'change-form') {
        captchaQuestion = document.getElementById('captcha-question-change');
        userAnswer = document.getElementById('captcha-answer-change').value;
    } else {
        return true; // No hay CAPTCHA en el formulario de login
    }
    
    if (!captchaQuestion) return true;
    
    const correctAnswer = captchaQuestion.dataset.answer;
    
    if (parseInt(userAnswer) !== parseInt(correctAnswer)) {
        alert('La respuesta de verificación es incorrecta. Por favor, inténtelo de nuevo.');
        initCaptcha(); // Regenerar CAPTCHA
        return false;
    }
    
    return true;
}

// Mostrar formulario específico
function showForm(formType) {
    // Ocultar todos los formularios
    const forms = document.querySelectorAll('.form-container');
    forms.forEach(form => {
        form.classList.remove('active');
    });
    
    // Mostrar el formulario seleccionado
    const targetForm = document.getElementById(`${formType}-form`);
    if (targetForm) {
        targetForm.classList.add('active');
        
        // Regenerar CAPTCHA si es necesario
        if (formType === 'recover' || formType === 'change') {
            initCaptcha();
        }
    }
}

// Manejar envío de formularios
document.addEventListener('DOMContentLoaded', function() {
    // Formulario de recuperación
    const recoverForm = document.getElementById('formrecuperar');
    if (recoverForm) {
        recoverForm.addEventListener('submit', function(e) {
            if (!validateCaptcha('recover-form')) {
                e.preventDefault();
            }
        });
    }
    
    // Formulario de cambio de contraseña
    const changeForm = document.getElementById('formcambiarpw');
    if (changeForm) {
        changeForm.addEventListener('submit', function(e) {
            if (!validateCaptcha('change-form')) {
                e.preventDefault();
            }
        });
    }
});

// Validación adicional para campos de email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Mejora de UX: Limpiar campos al cambiar entre formularios
function clearFormFields(formId) {
    const form = document.getElementById(formId);
    if (form) {
        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => {
            if (input.type !== 'submit' && input.type !== 'button') {
                input.value = '';
            }
        });
    }
}