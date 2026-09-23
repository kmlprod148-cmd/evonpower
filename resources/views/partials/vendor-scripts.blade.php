<script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    // Select2 Initialization Function
    function initSelect2(selector, options = {}) {
        if (typeof $.fn.select2 !== 'undefined') {
            const defaultOptions = {
                theme: 'default',
                width: '100%',
                placeholder: $(selector).data('placeholder') || 'Sélectionner...',
                allowClear: true,
                language: {
                    noResults: function() {
                        return "Aucun résultat trouvé";
                    }
                }
            };
            
            $(selector).select2({...defaultOptions, ...options});
            
            // Dark Mode Styles for Select2
            function adjustSelect2Styles() {
                const isDarkMode = document.documentElement.classList.contains('dark');
                
                // Selectors for styling
                const selectors = {
                    selection: '.select2-container--default .select2-selection--single, .select2-container--default .select2-selection--multiple',
                    rendered: '.select2-container--default .select2-selection__rendered',
                    dropdown: '.select2-dropdown',
                    searchField: '.select2-search__field',
                    results: '.select2-results__option'
                };
                
                // Dark mode styles
                const darkStyles = {
                    selection: {
                        'background-color': '#1f2937',
                        'border-color': '#374151',
                        'color': '#f3f4f6'
                    },
                    rendered: { 
                        'color': '#f3f4f6' 
                    },
                    dropdown: {
                        'background-color': '#1f2937',
                        'border-color': '#374151'
                    },
                    searchField: {
                        'background-color': '#374151',
                        'color': '#f3f4f6',
                        'border-color': '#4b5563'
                    },
                    results: { 
                        'color': '#f3f4f6' 
                    }
                };
                
                // Apply or reset styles based on dark mode
                Object.entries(selectors).forEach(([key, selector]) => {
                    $(selector).css(isDarkMode ? darkStyles[key] : {});
                });
            }
            
            // Apply styles and observe changes
            adjustSelect2Styles();
            const observer = new MutationObserver(mutations => {
                mutations.forEach(mutation => {
                    if (mutation.attributeName === 'class') {
                        adjustSelect2Styles();
                    }
                });
            });
            
            observer.observe(document.documentElement, { attributes: true });
        }
    }
</script>