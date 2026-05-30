document.querySelectorAll("form").forEach(form => {
    form.addEventListener("submit", function(e) {
        const password = form.querySelector('input[type="password"]');
        
        if(password && password.value.length < 6) {
            alert("Password must be at least 6 characters");
            e.preventDefault();
        }
    });
});