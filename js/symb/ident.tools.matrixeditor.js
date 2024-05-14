document.getElementById("selection").addEventListener("change", function() {
    document.getElementById("filterform").submit();
});

function preventOnArrowKey(event) {
    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
        event.preventDefault();
        const radio = document.querySelectorAll('input[type="radio"]');
        const focus = document.querySelector('input[type="radio"]:focus');
        const i = Array.from(radio).indexOf(focus);

        if (i !== -1) {
            if (event.key === 'ArrowUp' && i > 0) {
                radio[i - 1].focus();
            }
            else if (event.key === 'ArrowDown' && i < radio.length - 1) {
                radio[i + 1].focus();
            }
        }
    }
    else if (event.key === 'Enter') {
        const focus = document.querySelector('input[type="radio"]:focus');
        if (focus) {
            focus.checked = true;
        }
    }
}

document.addEventListener('keydown', preventOnArrowKey);