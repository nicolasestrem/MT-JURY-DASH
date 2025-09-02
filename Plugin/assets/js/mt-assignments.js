/**
 * Mobility Trailblazers Assignments JavaScript
 * Handles assignment management functionality
 */
(function($) {
    'use strict';
    
    // Helper function for i18n
    function getI18nText(key, defaultValue) {
        // Try mt_assignments_i18n first
        if (typeof mt_assignments_i18n !== 'undefined' && mt_assignments_i18n && mt_assignments_i18n[key]) {
            return mt_assignments_i18n[key];
        }
        // Try mt_admin.i18n
        if (typeof mt_admin !== 'undefined' && mt_admin && mt_admin.i18n && mt_admin.i18n[key]) {
            return mt_admin.i18n[key];
        }
        // Try mt_admin_i18n
        if (typeof mt_admin_i18n !== 'undefined' && mt_admin_i18n && mt_admin_i18n[key]) {
            return mt_admin_i18n[key];
        }
        return defaultValue || '';
    }
    
    // Standardized AJAX wrapper with timeout and error handling
    function mtAjax(options) {
        var defaults = {
            timeout: 15000, // 15 second timeout
            type: 'POST',
            url: (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
                ? mt_admin.ajax_url 
                : ajaxurl,
            error: function(xhr, status, error) {
                var message = getI18nText('error', 'Error') + ': ';
                if (status === 'timeout') {
                    message += getI18nText('request_timeout', 'Request timed out. Please try again.');
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    message += xhr.responseJSON.data;
                } else if (error) {
                    message += error;
                } else {
                    message += getI18nText('unknown_error', 'An unknown error occurred');
                }
                showNotification(message, 'error');
            }
        };
        
        // Ensure nonce is included if available
        if (!options.data) {
            options.data = {};
        }
        if (!options.data.nonce) {
            options.data.nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
                ? mt_admin.nonce 
                : $('#mt_admin_nonce').val();
        }
        
        return $.ajax($.extend({}, defaults, options));
    }
    
    // Wait for DOM ready
    $(document).ready(function() {
        // Initialize assignment functionality
        initAssignments();
    });
    function initAssignments() {
        // Check if we're on the assignments page
        if ($('#mt-auto-assign-btn').length === 0) {
            return;
        }
        // Auto-assign button handler
        $('#mt-auto-assign-btn').off('click').on('click', function(e) {
            e.preventDefault();
            openAutoAssignModal();
        });
        // Manual assignment button handler
        $('#mt-manual-assign-btn').off('click').on('click', function(e) {
            e.preventDefault();
            openManualAssignModal();
        });
        // Modal close button handler
        $('.mt-modal-close').off('click').on('click', function(e) {
            e.preventDefault();
            closeModal($(this).closest('.mt-modal'));
        });
        // Click outside modal to close
        $('.mt-modal').off('click').on('click', function(e) {
            if ($(e.target).hasClass('mt-modal')) {
                closeModal($(this));
            }
        });
        // Auto-assign form submission
        $('#mt-auto-assign-modal form').off('submit').on('submit', function(e) {
            e.preventDefault();
            submitAutoAssignment();
        });
        // Manual assignment form submission
        $('#mt-manual-assignment-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            submitManualAssignment();
        });
        // Remove assignment button handler
        $(document).on('click', '.mt-remove-assignment', function(e) {
            e.preventDefault();
            removeAssignment($(this));
        });
        // Clear all button handler
        $('#mt-clear-all-btn').off('click').on('click', function(e) {
            e.preventDefault();
            clearAllAssignments();
        });
        // Export button handler
        $('#mt-export-btn').off('click').on('click', function(e) {
            e.preventDefault();
            exportAssignments();
        });
        // Bulk actions button handler
        $('#mt-bulk-actions-btn').off('click').on('click', function(e) {
            e.preventDefault();
            toggleBulkActions();
        });
    }
    function openAutoAssignModal() {
        $('#mt-auto-assign-modal').css('display', 'flex').hide().fadeIn(300);
    }
    function openManualAssignModal() {
        $('#mt-manual-assign-modal').css('display', 'flex').hide().fadeIn(300);
    }
    function closeModal($modal) {
        $modal.fadeOut(300);
    }
    function submitAutoAssignment() {
        var method = $('#assignment_method').val();
        var candidatesPerJury = $('#candidates_per_jury').val();
        var clearExisting = $('#clear_existing').is(':checked') ? 'true' : 'false';
        
        mtAjax({
            data: {
                action: 'mt_auto_assign',
                method: method,
                candidates_per_jury: candidatesPerJury,
                clear_existing: clearExisting
            },
            beforeSend: function() {
                $('#mt-auto-assign-modal button[type="submit"]')
                    .prop('disabled', true)
                    .text(getI18nText('processing', 'Processing...'));
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message || getI18nText('auto_assignment_success', 'Auto-assignment completed successfully!'), 'success');
                    closeModal($('#mt-auto-assign-modal'));
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || getI18nText('error_occurred', 'An error occurred'), 'error');
                }
            },
            complete: function() {
                $('#mt-auto-assign-modal button[type="submit"]')
                    .prop('disabled', false)
                    .text(getI18nText('run_auto_assignment', 'Run Auto-Assignment'));
            }
        });
    }
    function submitManualAssignment() {
        var juryMemberId = $('#manual_jury_member').val();
        var candidateIds = [];
        $('input[name="candidate_ids[]"]:checked').each(function() {
            candidateIds.push($(this).val());
        });
        if (!juryMemberId || candidateIds.length === 0) {
            showNotification(getI18nText('select_jury_candidates', 'Please select a jury member and at least one candidate.'), 'warning');
            return;
        }
        
        mtAjax({
            data: {
                action: 'mt_manual_assign',
                jury_member_id: juryMemberId,
                candidate_ids: candidateIds
            },
            beforeSend: function() {
                $('#mt-manual-assignment-form button[type="submit"]')
                    .prop('disabled', true)
                    .text(getI18nText('processing', 'Processing...'));
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message || getI18nText('assignments_created', 'Assignments created successfully!'), 'success');
                    closeModal($('#mt-manual-assign-modal'));
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || getI18nText('error_occurred', 'An error occurred'), 'error');
                }
            },
            complete: function() {
                $('#mt-manual-assignment-form button[type="submit"]')
                    .prop('disabled', false)
                    .text(getI18nText('assign_selected', 'Assign Selected'));
            }
        });
    }
    function removeAssignment($button) {
        var assignmentId = $button.data('assignment-id');
        var juryName = $button.data('jury');
        var candidateName = $button.data('candidate');
        if (!confirm(getI18nText('confirm_remove_assignment', 'Are you sure you want to remove this assignment?'))) {
            return;
        }
        
        mtAjax({
            data: {
                action: 'mt_remove_assignment',
                assignment_id: assignmentId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text(getI18nText('processing', 'Processing...'));
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                        // Check if table is empty
                        if ($('.mt-assignments-table tbody tr').length === 0) {
                            $('.mt-assignments-table tbody').html(
                                '<tr><td colspan="8" class="no-items">' + getI18nText('no_assignments', 'No assignments yet') + '</td></tr>'
                            );
                        }
                    });
                    showNotification(getI18nText('assignment_removed', 'Assignment removed successfully.'), 'success');
                } else {
                    showNotification(response.data || getI18nText('error_occurred', 'An error occurred'), 'error');
                }
            },
            complete: function() {
                $button.prop('disabled', false).text(getI18nText('remove', 'Remove'));
            }
        });
    }
    function clearAllAssignments() {
        if (!confirm(getI18nText('confirm_clear_all', 'Are you sure you want to clear ALL assignments? This cannot be undone.'))) {
            return;
        }
        if (!confirm(getI18nText('confirm_clear_all_final', 'This will remove ALL jury assignments. Are you absolutely sure?'))) {
            return;
        }
        
        mtAjax({
            data: {
                action: 'mt_clear_all_assignments'
            },
            beforeSend: function() {
                $('#mt-clear-all-btn').prop('disabled', true).text(getI18nText('clearing', 'Clearing...'));
            },
            success: function(response) {
                if (response.success) {
                    showNotification(getI18nText('assignments_cleared', 'All assignments have been cleared.'), 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || getI18nText('error_occurred', 'An error occurred'), 'error');
                }
            },
            complete: function() {
                $('#mt-clear-all-btn')
                    .prop('disabled', false)
                    .html('<span class="dashicons dashicons-trash"></span> ' + getI18nText('clear_all', 'Clear All'));
            }
        });
    }
    function exportAssignments() {
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        // Create a form to trigger download
        var form = $('<form/>', {
            action: ajaxUrl,
            method: 'POST'
        });
        form.append($('<input/>', {
            type: 'hidden',
            name: 'action',
            value: 'mt_export_assignments'
        }));
        form.append($('<input/>', {
            type: 'hidden',
            name: 'nonce',
            value: nonce
        }));
        form.appendTo('body').submit().remove();
        
        showNotification(getI18nText('export_started', 'Export started. Download will begin shortly.'), 'info');
    }
    function toggleBulkActions() {
        var $container = $('#mt-bulk-actions-container');
        var $checkboxColumn = $('.check-column');
        if ($container.is(':visible')) {
            $container.slideUp();
            $checkboxColumn.hide();
            $('.mt-assignment-checkbox').prop('checked', false);
            $('#mt-select-all-assignments').prop('checked', false);
        } else {
            $container.slideDown();
            $checkboxColumn.show();
        }
    }
    function showNotification(message, type) {
        type = type || 'info';
        // Remove any existing notifications
        $('.mt-notification').remove();
        // Map types to WordPress notice classes
        var typeMap = {
            'success': 'notice-success',
            'error': 'notice-error',
            'warning': 'notice-warning',
            'info': 'notice-info'
        };
        var noticeClass = typeMap[type] || 'notice-info';
        // Create notification HTML
        var notificationHtml = 
            '<div class="mt-notification notice ' + noticeClass + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                    '<span class="screen-reader-text">' + getI18nText('dismiss_notice', 'Dismiss this notice.') + '</span>' +
                '</button>' +
            '</div>';
        // Add notification after the page title
        var $target = $('.wrap h1').first();
        if ($target.length) {
            $(notificationHtml).insertAfter($target);
        } else {
            // Fallback: add to beginning of .wrap
            $('.wrap').prepend(notificationHtml);
        }
        // Auto-dismiss after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.mt-notification').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        // Handle dismiss button
        $('.mt-notification .notice-dismiss').on('click', function() {
            $(this).closest('.mt-notification').fadeOut(400, function() {
                $(this).remove();
            });
        });
    }
})(jQuery);
