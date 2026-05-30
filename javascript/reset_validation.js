document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("resetForm");
    if (!form) return;

    const email = document.querySelector("input[name='email']");
    const password = document.querySelector("input[name='password']");
    const confirmPassword = document.querySelector("input[name='confirm_password']");
    const messageBox = document.getElementById("js-message");

    form.addEventListener("submit", function (e) {

        let errors = [];

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value.trim())) {
            errors.push("Inserisci un'email valida.");
        }

        if (password.value.length < 6) {
            errors.push("La password deve contenere almeno 6 caratteri.");
        }

        if (password.value !== confirmPassword.value) {
            errors.push("Le password non coincidono.");
        }

        if (errors.length > 0) {
            e.preventDefault();
            messageBox.style.color = "red";
            messageBox.textContent = errors.join(" ");
        } else {
            messageBox.textContent = "";
        }
    });
});
