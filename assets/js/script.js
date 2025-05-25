// Placeholder for custom scripts
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registrationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                alert('Password must be at least 6 characters long.');
                e.preventDefault();
            }
        });
    }

    // Interactive booking form slot checking
    const facilitySelect = document.getElementById('facility_id');
    const dateInput = document.getElementById('date');
    const timeSelect = document.getElementById('time');
    function fetchSlots() {
        const facilityId = facilitySelect.value;
        const date = dateInput.value;
        if (!facilityId || !date) {
            timeSelect.innerHTML = '<option value="">Select a facility and date</option>';
            return;
        }
        fetch(`index.php?facility_id=${facilityId}&date=${date}`)
            .then(res => res.json())
            .then(slots => {
                if (slots.length === 0) {
                    timeSelect.innerHTML = '<option value="">No available slots</option>';
                } else {
                    timeSelect.innerHTML = slots.map(s => `<option value="${s}">${s} - ${('0'+(parseInt(s)+1)).slice(-2)}:00</option>`).join('');
                }
            });
    }
    if (facilitySelect && dateInput && timeSelect) {
        facilitySelect.addEventListener('change', fetchSlots);
        dateInput.addEventListener('change', fetchSlots);
    }
});

// Responsive navbar toggle
function setupNavbarToggle() {
    const toggle = document.querySelector('.nav-toggle');
    const links = document.querySelector('.nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            links.classList.toggle('active');
        });
    }
}
document.addEventListener('DOMContentLoaded', setupNavbarToggle);
