/**
 * Debug script to force modal visibility
 * Fixed: XSS vulnerability with safe DOM creation
 */
jQuery(document).ready(function($) {
    
    // Create a simple test modal function using safe DOM creation
    window.showTestModal = function() {
        // Remove any existing test modal
        $('#test-modal').remove();
        
        // Create modal elements safely using jQuery
        var $modal = $('<div>', {
            id: 'test-modal',
            css: {
                position: 'fixed',
                top: '0',
                left: '0',
                width: '100%',
                height: '100%',
                background: 'rgba(0,0,0,0.8)',
                zIndex: '9999999',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center'
            }
        });
        
        var $content = $('<div>', {
            css: {
                background: 'white',
                padding: '30px',
                borderRadius: '8px',
                maxWidth: '500px'
            }
        });
        
        // Add content safely
        $content.append(
            $('<h2>').text('Test Modal'),
            $('<p>').text('If you can see this, modals can work!'),
            $('<button>').text('Close').on('click', function() {
                $('#test-modal').remove();
            })
        );
        
        $modal.append($content);
        $('body').append($modal);
    };
    
    // Override the button clicks to use our existing modals differently
    $('#mt-auto-assign-btn').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $modal = $('#mt-auto-assign-modal');
        var $content = $modal.find('.mt-modal-content');
        
        // Remove the modal from its current position and add to body
        $modal.detach().appendTo('body');
        
        // Apply styles using jQuery css method (safer than attr)
        $modal.css({
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            background: 'rgba(0,0,0,0.8)',
            zIndex: '9999999',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
        });
        
        $content.css({
            background: 'white',
            padding: '30px',
            borderRadius: '8px',
            maxWidth: '600px',
            width: '90%',
            maxHeight: '90vh',
            overflowY: 'auto',
            position: 'relative',
            zIndex: '10000000'
        });
        
        return false;
    });
    
    $('#mt-manual-assign-btn').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $modal = $('#mt-manual-assign-modal');
        var $content = $modal.find('.mt-modal-content');
        
        // Remove the modal from its current position and add to body
        $modal.detach().appendTo('body');
        
        // Apply styles using jQuery css method (safer than attr)
        $modal.css({
            position: 'fixed',
            top: '0',
            left: '0',
            width: '100%',
            height: '100%',
            background: 'rgba(0,0,0,0.8)',
            zIndex: '9999999',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
        });
        
        $content.css({
            background: 'white',
            padding: '30px',
            borderRadius: '8px',
            maxWidth: '600px',
            width: '90%',
            maxHeight: '90vh',
            overflowY: 'auto',
            position: 'relative',
            zIndex: '10000000'
        });
        
        return false;
    });
    
    // Close button handlers
    $(document).on('click', '.mt-modal-close', function(e) {
        e.preventDefault();
        $(this).closest('.mt-modal').hide();
    });
    
    // Click outside to close
    $(document).on('click', '.mt-modal', function(e) {
        if ($(e.target).hasClass('mt-modal')) {
            $(this).hide();
        }
    });
});