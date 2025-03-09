/**
 * HeadlessWP Admin JavaScript
 */
(function($) {
    'use strict';

    /**
     * CORS Origins Manager
     */
    const CORSManager = {
        init: function() {
            this.cacheDom();
            this.bindEvents();
            this.checkInitialState();
        },

        cacheDom: function() {
            this.$originsTypeRadios = $('.js-toggle-origins');
            this.$originsManager = $('.origins-manager');
            this.$originsTable = $('#origins-table');
            this.$addOriginButton = $('.js-add-origin');
            this.$originTemplate = $('#origin-template');
        },

        bindEvents: function() {
            // Toggle between all origins and specific origins
            this.$originsTypeRadios.on('change', this.toggleOriginsManager.bind(this));

            // Add new origin
            this.$addOriginButton.on('click', this.addOrigin.bind(this));

            // Remove origin (delegated event)
            $(document).on('click', '.js-remove-origin', this.removeOrigin);
        },

        checkInitialState: function() {
            // If "specific origins" is selected but there are no origins yet, add an empty row
            if (this.$originsTypeRadios.filter('[value="specific"]:checked').length &&
                this.$originsTable.find('tbody tr:visible').length === 0) {
                this.addOrigin();
            }
        },

        toggleOriginsManager: function() {
            const useAllOrigins = this.$originsTypeRadios.filter(':checked').val() === 'all';

            if (useAllOrigins) {
                this.$originsManager.addClass('hidden');
            } else {
                this.$originsManager.removeClass('hidden');

                // If no origins exist yet, add an empty row
                if (this.$originsTable.find('tbody tr:visible').length === 0) {
                    this.addOrigin();
                }
            }
        },

        addOrigin: function() {
            const $newRow = this.$originTemplate.clone();
            $newRow.removeAttr('id').css('display', '');
            this.$originsTable.find('tbody').append($newRow);
        },

        removeOrigin: function() {
            $(this).closest('tr').remove();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on the security page
        if ($('.origins-manager').length) {
            CORSManager.init();
        }
    });

})(jQuery);