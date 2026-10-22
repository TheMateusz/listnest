document.addEventListener('DOMContentLoaded', () => {
    function toggleFields() {
        const selected = document.querySelector('input[name="lnst_list_option"]:checked');
        if (!selected) return;

        const val = selected.value;

        const redirectFields = document.getElementById('redirect-fields');
        const changeTextFields = document.getElementById('change-text-fields');
        const popupFields = document.getElementById('popup-fields');

        redirectFields.style.display = (val === 'redirect') ? 'block' : 'none';
        changeTextFields.style.display = (val === 'change_text') ? 'block' : 'none';
        popupFields.style.display = (val === 'popup') ? 'block' : 'none';
    }

    const radios = document.querySelectorAll('input[name="lnst_list_option"]');
    radios.forEach(radio => {
        radio.addEventListener('change', toggleFields);
    });

    toggleFields();
});
