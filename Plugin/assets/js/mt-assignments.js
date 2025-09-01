/**
 * Mobility Trailblazers Assignments JavaScript
 * Handles assignment management functionality
 */
(function($) {
    'use strict';
    // Signal ownership so admin.js can avoid double-binding on Assignments page
    try { window.MT_ASSIGNMENTS_OWNED = true; } catch(e) {}
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
        $('#mt-auto-assign-btn').off('click.mtAssign').on('click.mtAssign', function(e) {
            e.preventDefault();
            openAutoAssignModal();
        });
        // Manual assignment button handler
        $('#mt-manual-assign-btn').off('click.mtAssign').on('click.mtAssign', function(e) {
            e.preventDefault();
            openManualAssignModal();
        });
        // Modal close button handler
        $('.mt-modal-close').off('click.mtAssign').on('click.mtAssign', function(e) {
            e.preventDefault();
            closeModal($(this).closest('.mt-modal'));
        });
        // Click outside modal to close
        $('.mt-modal').off('click.mtAssign').on('click.mtAssign', function(e) {
            if ($(e.target).hasClass('mt-modal')) {
                closeModal($(this));
            }
        });
        // Auto-assign form submission
        $('#mt-auto-assign-modal form').off('submit.mtAssign').on('submit.mtAssign', function(e) {
            e.preventDefault();
            submitAutoAssignment();
        });
        // Manual assignment form submission
        $('#mt-manual-assignment-form').off('submit.mtAssign').on('submit.mtAssign', function(e) {
            e.preventDefault();
            submitManualAssignment();
        });
        // Remove assignment button handler
        $(document).off('click.mtAssign', '.mt-remove-assignment').on('click.mtAssign', '.mt-remove-assignment', function(e) {
            e.preventDefault();
            removeAssignment($(this));
        });
        // Clear all button handler
        $('#mt-clear-all-btn').off('click.mtAssign').on('click.mtAssign', function(e) {
            e.preventDefault();
            clearAllAssignments();
        });
        // Export button handler
        $('#mt-export-btn').off('click.mtAssign').on('click.mtAssign', function(e) {
            e.preventDefault();
            exportAssignments();
        });
        // Bulk actions button handler
        $('#mt-bulk-actions-btn').off('click.mtAssign').on('click.mtAssign', function(e) {
            e.preventDefault();
            toggleBulkActions();
        });
    }
    // Track in-flight requests to prevent duplicates and allow cancellation
    var autoAssignXhr = null;
    var manualAssignXhr = null;
    var clearAllXhr = null;
    var removeXhr = null;

    function openAutoAssignModal() {
        var $modal = $('#mt-auto-assign-modal');
        $modal.css('display', 'flex').hide().fadeIn(300);
        try { trapFocus($modal); } catch(e) {}
    }
    function openManualAssignModal() {
        var $modal = $('#mt-manual-assign-modal');
        $modal.css('display', 'flex').hide().fadeIn(300);
        try { trapFocus($modal); } catch(e) {}
    }
    function closeModal($modal) {
        $modal.fadeOut(300);
        // Cancel any in-flight request related to the modal being closed
        var id = $modal && $modal.attr('id');
        if (id === 'mt-auto-assign-modal' && autoAssignXhr && autoAssignXhr.readyState !== 4) {
            try { autoAssignXhr.abort(); } catch(e) {}
        }
        if (id === 'mt-manual-assign-modal' && manualAssignXhr && manualAssignXhr.readyState !== 4) {
            try { manualAssignXhr.abort(); } catch(e) {}
        }
        try { releaseFocus($modal); } catch(e) {}
    }
    // Basic focus trap for accessibility inside modals
    var previousFocus = null;
    function trapFocus($modal) {
        var $content = $modal.find('.mt-modal-content').attr('tabindex', '-1');
        previousFocus = document.activeElement;
        var focusable = $content.find('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])')
            .filter(':visible');
        var first = focusable.get(0);
        var last = focusable.get(focusable.length - 1);
        if (first) { first.focus(); }
        $modal.on('keydown.mtAssignFocus', function(e) {
            if (e.key === 'Escape') { e.preventDefault(); closeModal($modal); }
            if (e.key !== 'Tab') return;
            if (focusable.length === 0) return;
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault(); last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault(); first.focus();
            }
        });
    }
    function releaseFocus($modal) {
        $modal.off('keydown.mtAssignFocus');
        if (previousFocus && typeof previousFocus.focus === 'function') {
            try { previousFocus.focus(); } catch(e) {}
        }
        previousFocus = null;
    }
    function submitAutoAssignment() {
        var method = $('#assignment_method').val();
        var candidatesPerJury = $('#candidates_per_jury').val();
        var clearExisting = $('#clear_existing').is(':checked') ? 'true' : 'false';
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        if (autoAssignXhr && autoAssignXhr.readyState !== 4) { try { autoAssignXhr.abort(); } catch(e) {} }
        autoAssignXhr = $.ajax({
            url: ajaxUrl,
            type: 'POST',
            timeout: 15000,
            data: {
                action: 'mt_auto_assign',
                nonce: nonce,
                method: method,
                candidates_per_jury: candidatesPerJury,
                clear_existing: clearExisting
            },
            beforeSend: function() {
                $('#mt-auto-assign-modal button[type="submit"]')
                    .prop('disabled', true)
                    .text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message || 'Auto-assignment completed successfully!', 'success');
                    closeModal($('#mt-auto-assign-modal'));
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification((mt_admin && mt_admin.i18n && mt_admin.i18n.error ? mt_admin.i18n.error : 'Fehler') + ': ' + error, 'error');
            },
            complete: function() {
                $('#mt-auto-assign-modal button[type="submit"]')
                    .prop('disabled', false)
                    .text('Run Auto-Assignment');
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
            showNotification(mt_admin && mt_admin.i18n && mt_admin.i18n.select_jury_candidates ? mt_admin.i18n.select_jury_candidates : 'Bitte wählen Sie ein Jurymitglied und mindestens einen Kandidaten aus.', 'warning');
            return;
        }
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        if (manualAssignXhr && manualAssignXhr.readyState !== 4) { try { manualAssignXhr.abort(); } catch(e) {} }
        manualAssignXhr = $.ajax({
            url: ajaxUrl,
            type: 'POST',
            timeout: 15000,
            data: {
                action: 'mt_manual_assign',
                nonce: nonce,
                jury_member_id: juryMemberId,
                candidate_ids: candidateIds
            },
            beforeSend: function() {
                $('#mt-manual-assignment-form button[type="submit"]')
                    .prop('disabled', true)
                    .text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message || 'Assignments created successfully!', 'success');
                    closeModal($('#mt-manual-assign-modal'));
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification((mt_admin && mt_admin.i18n && mt_admin.i18n.error ? mt_admin.i18n.error : 'Fehler') + ': ' + error, 'error');
            },
            complete: function() {
                $('#mt-manual-assignment-form button[type="submit"]')
                    .prop('disabled', false)
                    .text('Assign Selected');
            }
        });
    }
    function removeAssignment($button) {
        var assignmentId = $button.data('assignment-id');
        var juryName = $button.data('jury');
        var candidateName = $button.data('candidate');
        if (!confirm('Are you sure you want to remove this assignment?')) {
            return;
        }
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        if (removeXhr && removeXhr.readyState !== 4) { try { removeXhr.abort(); } catch(e) {} }
        removeXhr = $.ajax({
            url: ajaxUrl,
            type: 'POST',
            timeout: 15000,
            data: {
                action: 'mt_remove_assignment',
                nonce: nonce,
                assignment_id: assignmentId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                        // Check if table is empty
                        if ($('.mt-assignments-table tbody tr').length === 0) {
                            $('.mt-assignments-table tbody').html(
                                '<tr><td colspan="8" class="no-items">No assignments yet</td></tr>'
                            );
                        }
                    });
                    showNotification(mt_admin && mt_admin.i18n && mt_admin.i18n.assignment_removed ? mt_admin.i18n.assignment_removed : 'Zuweisung erfolgreich entfernt.', 'success');
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function() {
                showNotification(mt_admin && mt_admin.i18n && mt_admin.i18n.error_occurred ? mt_admin.i18n.error_occurred : 'Ein Fehler ist aufgetreten', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(mt_admin && mt_admin.i18n && mt_admin.i18n.remove ? mt_admin.i18n.remove : 'Entfernen');
            }
        });
    }
    function clearAllAssignments() {
        if (!confirm('Are you sure you want to clear ALL assignments? This cannot be undone.')) {
            return;
        }
        if (!confirm('This will remove ALL jury assignments. Are you absolutely sure?')) {
            return;
        }
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        if (clearAllXhr && clearAllXhr.readyState !== 4) { try { clearAllXhr.abort(); } catch(e) {} }
        clearAllXhr = $.ajax({
            url: ajaxUrl,
            type: 'POST',
            timeout: 15000,
            data: {
                action: 'mt_clear_all_assignments',
                nonce: nonce
            },
            beforeSend: function() {
                $('#mt-clear-all-btn').prop('disabled', true).text('Clearing...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification(mt_admin && mt_admin.i18n && mt_admin.i18n.assignments_cleared ? mt_admin.i18n.assignments_cleared : 'Alle Zuweisungen wurden gelöscht.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function() {
                showNotification(mt_admin && mt_admin.i18n && mt_admin.i18n.error_occurred ? mt_admin.i18n.error_occurred : 'Ein Fehler ist aufgetreten', 'error');
            },
            complete: function() {
                $('#mt-clear-all-btn')
                    .prop('disabled', false)
                    .html('<span class="dashicons dashicons-trash"></span> Clear All');
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
        
        showNotification(mt_admin && mt_admin.i18n && mt_admin.i18n.export_started ? mt_admin.i18n.export_started : 'Export gestartet. Der Download beginnt in Kürze.', 'info');
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
                    '<span class="screen-reader-text">Dismiss this notice.</span>' +
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
