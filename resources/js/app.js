import './bootstrap';
import IMask from 'imask';

function initDateMasks() {
    document.querySelectorAll('.date-mask:not([data-masked])').forEach(input => {
        input.setAttribute('data-masked', 'true');
        
        // Find corresponding hidden input
        const hiddenInput = input.parentElement.querySelector('input[type="hidden"]');
        
        // If hidden input has an initial YYYY-MM-DD value, populate the masked input
        if (hiddenInput && hiddenInput.value) {
            const parts = hiddenInput.value.split('-');
            if (parts.length === 3) {
                input.value = `${parts[1]}/${parts[2]}/${parts[0]}`;
            }
        }

        const mask = IMask(input, {
            mask: Date,
            pattern: 'm/d/Y',
            lazy: true,
            autofix: true,
            blocks: {
                d: {
                    mask: IMask.MaskedRange,
                    from: 1,
                    to: 31,
                    maxLength: 2,
                },
                m: {
                    mask: IMask.MaskedRange,
                    from: 1,
                    to: 12,
                    maxLength: 2,
                },
                Y: {
                    mask: IMask.MaskedRange,
                    from: 1900,
                    to: 2099,
                }
            },
            format: function (date) {
                let day = date.getDate();
                let month = date.getMonth() + 1;
                let year = date.getFullYear();

                if (day < 10) day = "0" + day;
                if (month < 10) month = "0" + month;

                return [month, day, year].join('/');
            },
            parse: function (str) {
                let parts = str.split('/');
                return new Date(parts[2], parts[0] - 1, parts[1]);
            }
        });

        // Set hidden input value on change/accept
        mask.on('accept', () => {
            if (hiddenInput) {
                if (mask.masked.isComplete) {
                    const date = mask.masked.date;
                    if (date && !isNaN(date.getTime())) {
                        const y = date.getFullYear();
                        const m = String(date.getMonth() + 1).padStart(2, '0');
                        const d = String(date.getDate()).padStart(2, '0');
                        hiddenInput.value = `${y}-${m}-${d}`;
                    }
                } else {
                    hiddenInput.value = '';
                }
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initDateMasks);

// Expose globally for dynamic components
window.initDateMasks = initDateMasks;
