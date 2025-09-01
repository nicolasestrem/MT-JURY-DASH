/**
 * MT Assignments Fallback Handler
 * Extracted from inline JavaScript in assignments.php
 * 
 * @package MobilityTrailblazers
 * @since 2.5.42
 */
(function($) {
    'use strict';

    // Only initialize if main assignment handlers aren't available
    if (window.MT_ASSIGNMENTS_FALLBACK_INITIALIZED) {
        return;
    }
    window.MT_ASSIGNMENTS_FALLBACK_INITIALIZED = true;

    $(document).ready(function() {
        // Check if primary handlers are loaded
        if (window.MT_ASSIGNMENTS_OWNED || 
            (typeof MTAssignmentManager !== 'undefined' && MTAssignmentManager.initialized)) {
            if (window.MT_DEBUG) {
                console.log('MT Assignments Fallback: Primary handlers detected, skipping fallback');
            }
            return;
        }

        // Initialize fallback mt_admin object if needed
        if (typeof mt_admin === 'undefined') {
            if (window.MT_DEBUG) {
                console.log('MT Assignments Fallback: Creating mt_admin object');
            }
            window.mt_admin = {
                ajax_url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                nonce: $('#mt_admin_nonce').val() || '',
                i18n: {
                    processing: 'Processing...',
                    error_occurred: 'An error occurred. Please try again.',
                    assignments_created: 'Assignments created successfully.',
                    select_jury_and_candidates: 'Please select a jury member and at least one candidate.',
                    confirm_remove_assignment: 'Are you sure you want to remove this assignment?',
                    assignment_removed: 'Assignment removed successfully.',
                    confirm_clear_all: 'Are you sure you want to clear ALL assignments? This cannot be undone!',
                    confirm_clear_all_second: 'This will remove ALL jury assignments. Are you absolutely sure?',
                    all_assignments_cleared: 'All assignments have been cleared.',
                    clearing: 'Clearing...',
                    clear_all: 'Clear All',
                    remove: 'Remove',
                    assign_selected: 'Assign Selected'
                }
            };
        }

        // Modal helper functions
        function showModal(modalId) {
            const $modal = $('#' + modalId);
            if ($modal.length) {
                $modal.css('display', 'flex').hide().fadeIn(300);
                // Trap focus for accessibility
                const $focusable = $modal.find('button, input, select, textarea, a').filter(':visible');
                if ($focusable.length) {
                    $focusable.first().focus();
                }
            }
        }

        function hideModal(modalId) {
            const $modal = $('#' + modalId);
            if ($modal.length) {
                $modal.fadeOut(300);
            }
        }

        // Notification helper
        function showNotification(message, type) {
            if (typeof window.mtShowNotification === 'function') {
                window.mtShowNotification(message, type);
            } else {
                // Fallback to alert
                alert(message);
            }
        }

        // Auto-assign button handler
        $('#mt-auto-assign-btn').off('click.fallback').on('click.fallback', function(e) {
            e.preventDefault();
            showModal('mt-auto-assign-modal');
        });

        // Manual assign button handler
        $('#mt-manual-assign-btn').off('click.fallback').on('click.fallback', function(e) {
            e.preventDefault();
            showModal('mt-manual-assign-modal');
        });

        // Modal close handlers
        $('.mt-modal-close').off('click.fallback').on('click.fallback', function(e) {
            e.preventDefault();
            const modalId = $(this).closest('.mt-modal').attr('id');
            hideModal(modalId);
        });

        // Click outside modal to close
        $('.mt-modal').off('click.fallback').on('click.fallback', function(e) {
            if ($(e.target).hasClass('mt-modal')) {
                hideModal(this.id);
            }
        });

        // ESC key to close modal
        $(document).off('keydown.fallback').on('keydown.fallback', function(e) {
            if (e.key === 'Escape') {
                $('.mt-modal:visible').each(function() {
                    hideModal(this.id);
                });
            }
        });

        // Auto-assign form submission
        $('#mt-auto-assign-modal form').off('submit.fallback').on('submit.fallback', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Use action-specific nonce
            const nonce = $('#mt_auto_assign_nonce').val() || mt_admin.nonce;
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_auto_assign',
                    nonce: nonce,
                    method: $('#assignment_method').val(),
                    candidates_per_jury: $('#candidates_per_jury').val(),
                    clear_existing: $('#clear_existing').is(':checked') ? 'true' : 'false'
                },
                timeout: 15000, // 15 second timeout
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).text(mt_admin.i18n.processing);
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message || mt_admin.i18n.assignments_created, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(response.data || mt_admin.i18n.error_occurred, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = mt_admin.i18n.error_occurred;
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. Please try again.';
                    }
                    showNotification(errorMsg + ' (' + error + ')', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Manual assignment form submission
        $('#mt-manual-assignment-form').off('submit.fallback').on('submit.fallback', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            const candidateIds = [];
            $('input[name="candidate_ids[]"]:checked').each(function() {
                candidateIds.push($(this).val());
            });
            
            const juryMemberId = $('#manual_jury_member').val();
            
            if (!juryMemberId || candidateIds.length === 0) {
                showNotification(mt_admin.i18n.select_jury_and_candidates, 'warning');
                return;
            }
            
            // Use action-specific nonce
            const nonce = $('#mt_manual_assign_nonce').val() || mt_admin.nonce;
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_manual_assign',
                    nonce: nonce,
                    jury_member_id: juryMemberId,
                    candidate_ids: candidateIds
                },
                timeout: 15000, // 15 second timeout
                beforeSend: function() {
                    $submitBtn.prop('disabled', true).text(mt_admin.i18n.processing);
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message || mt_admin.i18n.assignments_created, 'success');
                        hideModal('mt-manual-assign-modal');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(response.data || mt_admin.i18n.error_occurred, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = mt_admin.i18n.error_occurred;
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. Please try again.';
                    }
                    showNotification(errorMsg + ' (' + error + ')', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Clear all assignments handler
        $('#mt-clear-all-btn').off('click.fallback').on('click.fallback', function(e) {
            e.preventDefault();
            
            if (!confirm(mt_admin.i18n.confirm_clear_all)) {
                return;
            }
            
            if (!confirm(mt_admin.i18n.confirm_clear_all_second)) {
                return;
            }
            
            const $button = $(this);
            const originalHtml = $button.html();
            
            // Use action-specific nonce
            const nonce = $('#mt_clear_assignments_nonce').val() || mt_admin.nonce;
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_clear_all_assignments',
                    nonce: nonce
                },
                timeout: 15000,
                beforeSend: function() {
                    $button.prop('disabled', true).text(mt_admin.i18n.clearing);
                },
                success: function(response) {
                    if (response.success) {
                        showNotification(mt_admin.i18n.all_assignments_cleared, 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(response.data || mt_admin.i18n.error_occurred, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification(mt_admin.i18n.error_occurred + ' (' + error + ')', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Remove assignment handler
        $(document).on('click.fallback', '.mt-remove-assignment', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const assignmentId = $button.data('assignment-id');
            
            if (!confirm(mt_admin.i18n.confirm_remove_assignment)) {
                return;
            }
            
            const originalText = $button.text();
            
            // Use action-specific nonce
            const nonce = $('#mt_remove_assignment_nonce').val() || mt_admin.nonce;
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_remove_assignment',
                    nonce: nonce,
                    assignment_id: assignmentId
                },
                timeout: 15000,
                beforeSend: function() {
                    $button.prop('disabled', true).text(mt_admin.i18n.processing);
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
                        showNotification(mt_admin.i18n.assignment_removed, 'success');
                    } else {
                        showNotification(response.data || mt_admin.i18n.error_occurred, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification(mt_admin.i18n.error_occurred + ' (' + error + ')', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Export assignments handler
        $('#mt-export-btn').off('click.fallback').on('click.fallback', function(e) {
            e.preventDefault();
            
            // Create a form to trigger download
            const $form = $('<form>', {
                method: 'POST',
                action: mt_admin.ajax_url
            });
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'mt_export_assignments'
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: mt_admin.nonce
            }));
            
            $form.appendTo('body').submit().remove();
        });

        if (window.MT_DEBUG) {
            console.log('MT Assignments Fallback: Initialized successfully');
        }
    });

})(jQuery);