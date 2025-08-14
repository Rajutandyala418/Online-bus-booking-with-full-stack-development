document.addEventListener('DOMContentLoaded', () => {
    console.log("Bus Booking System JS loaded");

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = "Processing...";
            }
        });
    });

    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => input.setAttribute('min', today));
});
