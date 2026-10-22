document.addEventListener('DOMContentLoaded', () => {
    function toggleFields() {
        const selected = document.querySelector('input[name="listnest_list_option"]:checked');
        if (!selected) return;

        const val = selected.value;

        const redirectFields = document.getElementById('redirect-fields');
        const changeTextFields = document.getElementById('change-text-fields');
        const popupFields = document.getElementById('popup-fields');

        redirectFields.style.display = (val === 'redirect') ? 'block' : 'none';
        changeTextFields.style.display = (val === 'change_text') ? 'block' : 'none';
        popupFields.style.display = (val === 'popup') ? 'block' : 'none';
    }

    function toggleFormFields() {
        const selected = document.querySelector('input[name="listnest_list_form"]:checked');
        const val = selected ? selected.value : '';

        const builtinFields = document.getElementById('form-builtin-fields');
        const cf7Fields = document.getElementById('form-cf7-fields');

        if (builtinFields) builtinFields.style.display = (val === 'builtin') ? 'block' : 'none';
        if (cf7Fields) cf7Fields.style.display = (val === 'cf7') ? 'block' : 'none';
    }

    const radios = document.querySelectorAll('input[name="listnest_list_option"]');
    radios.forEach(radio => {
        radio.addEventListener('change', toggleFields);
    });

    const formRadios = document.querySelectorAll('input[name="listnest_list_form"]');
    formRadios.forEach(radio => {
        radio.addEventListener('change', toggleFormFields);
    });

    toggleFields();
    toggleFormFields();
});
