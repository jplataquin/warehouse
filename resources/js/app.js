import './bootstrap';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.css';

window.TomSelect = TomSelect;

export function initSearchableDropdowns(root = document) {
    const TS = window.TomSelect || TomSelect;
    if (!TS) return;

    const selects = root.querySelectorAll('select.form-select, select.form-control, select[data-searchable]');
    selects.forEach(select => {
        // Skip template elements
        if (select.closest('template')) return;
        // Skip explicitly non-searchable selects
        if (select.dataset.searchable === 'false' || select.dataset.noSearchable !== undefined) return;
        // Skip already initialized or custom-handled selects
        if (select.tomselect || select.classList.contains('tomselected') || select.dataset.customTomselect !== undefined) return;

        try {
            const hasEmpty = Array.from(select.options).some(o => o.value === '');
            const placeholder = select.getAttribute('placeholder') || 
                                (hasEmpty && select.options[0]?.value === '' ? select.options[0].text : 'Search or select...');

            new TS(select, {
                create: false,
                allowEmptyOption: true,
                placeholder: placeholder,
                maxOptions: 1000,
                sortField: select.dataset.sort === 'asc' ? { field: 'text', direction: 'asc' } : [{ field: '$order' }]
            });
        } catch (e) {
            console.warn('Could not initialize TomSelect on', select, e);
        }
    });
}

window.initSearchableDropdowns = initSearchableDropdowns;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initSearchableDropdowns());
} else {
    initSearchableDropdowns();
}

