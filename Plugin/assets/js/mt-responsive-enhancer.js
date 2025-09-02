/**
 * Mobility Trailblazers Responsive Enhancer
 * JavaScript-only responsive improvements without CSS changes
 * 
 * @package MobilityTrailblazers
 * @since 2.5.42
 */
(function($) {
    'use strict';
    
    window.MTResponsiveEnhancer = {
        
        // Viewport breakpoints (matching existing CSS framework)
        breakpoints: {
            mobile: 576,
            tablet: 768,
            desktop: 992,
            wide: 1200
        },
        
        // Current viewport state
        currentViewport: null,
        
        // Initialize responsive enhancements
        init: function() {
            this.updateViewport();
            this.bindEvents();
            this.applyInitialEnhancements();
        },
        
        // Bind responsive events
        bindEvents: function() {
            var self = this;
            
            // Use MTEventManager if available, otherwise fallback
            if (typeof MTEventManager !== 'undefined') {
                MTEventManager.debounce('resize', window, function() {
                    self.handleResize();
                }, 250);
            } else {
                // Fallback with manual debounce
                var resizeTimer;
                $(window).on('resize.mtresponsive', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function() {
                        self.handleResize();
                    }, 250);
                });
            }
            
            // Listen for orientation changes on mobile
            if ('matchMedia' in window) {
                var orientationQuery = window.matchMedia('(orientation: portrait)');
                orientationQuery.addListener(function(mq) {
                    self.handleOrientationChange(mq.matches);
                });
            }
        },
        
        // Handle window resize
        handleResize: function() {
            var previousViewport = this.currentViewport;
            this.updateViewport();
            
            if (previousViewport !== this.currentViewport) {
                this.applyViewportChanges();
            }
        },
        
        // Update current viewport designation
        updateViewport: function() {
            var width = window.innerWidth;
            var root = document.documentElement;
            
            // Remove previous viewport attributes
            root.removeAttribute('data-mt-viewport');
            
            // Set new viewport attribute
            if (width < this.breakpoints.mobile) {
                this.currentViewport = 'mobile';
            } else if (width < this.breakpoints.tablet) {
                this.currentViewport = 'tablet';
            } else if (width < this.breakpoints.desktop) {
                this.currentViewport = 'desktop';
            } else {
                this.currentViewport = 'wide';
            }
            
            root.setAttribute('data-mt-viewport', this.currentViewport);
        },
        
        // Apply viewport-specific changes
        applyViewportChanges: function() {
            var viewport = this.currentViewport;
            
            // Mobile optimizations
            if (viewport === 'mobile') {
                this.applyMobileOptimizations();
            } else {
                this.removeMobileOptimizations();
            }
            
            // Tablet adjustments
            if (viewport === 'tablet') {
                this.applyTabletAdjustments();
            }
            
            // Desktop enhancements
            if (viewport === 'desktop' || viewport === 'wide') {
                this.applyDesktopEnhancements();
            }
        },
        
        // Apply initial enhancements on page load
        applyInitialEnhancements: function() {
            // Add lazy loading to images if not present
            $('img:not([loading])').each(function() {
                var $img = $(this);
                // Don't lazy load above-the-fold images
                if ($img.offset().top > window.innerHeight * 1.5) {
                    $img.attr('loading', 'lazy');
                }
            });
            
            // Add appropriate sizes hints based on container
            $('.mt-candidate-card img').each(function() {
                var $img = $(this);
                if (!$img.attr('sizes')) {
                    $img.attr('sizes', '(max-width: 576px) 100vw, (max-width: 768px) 50vw, 33vw');
                }
            });
            
            // Apply viewport-specific changes
            this.applyViewportChanges();
        },
        
        // Mobile-specific optimizations
        applyMobileOptimizations: function() {
            // Reduce animation durations for performance
            if (typeof jQuery !== 'undefined') {
                jQuery.fx.speeds._default = 200;
            }
            
            // Disable hover effects on mobile (convert to click)
            $('.mt-candidate-card').off('mouseenter.mobile mouseleave.mobile');
            
            // Simplify complex animations
            $('.mt-ranking-item').css('transition-duration', '0.2s');
            
            // Convert tooltips to tap-to-show on mobile
            $('[data-tooltip]').off('mouseenter.tooltip mouseleave.tooltip')
                .on('click.tooltip', function(e) {
                    e.stopPropagation();
                    var $this = $(this);
                    var tooltip = $this.data('tooltip');
                    if (tooltip) {
                        // Simple mobile tooltip
                        var $tooltip = $('<div class="mt-mobile-tooltip">' + tooltip + '</div>');
                        $this.append($tooltip);
                        setTimeout(function() {
                            $tooltip.fadeOut(300, function() {
                                $tooltip.remove();
                            });
                        }, 3000);
                    }
                });
            
            // Optimize tables for mobile
            this.optimizeTablesForMobile();
        },
        
        // Remove mobile optimizations when viewport changes
        removeMobileOptimizations: function() {
            // Restore normal animation speeds
            if (typeof jQuery !== 'undefined') {
                jQuery.fx.speeds._default = 400;
            }
            
            // Restore hover effects
            $('.mt-candidate-card').off('mouseenter.mobile mouseleave.mobile')
                .on('mouseenter.desktop', function() {
                    $(this).addClass('mt-hover');
                })
                .on('mouseleave.desktop', function() {
                    $(this).removeClass('mt-hover');
                });
            
            // Restore normal tooltips
            $('[data-tooltip]').off('click.tooltip');
            
            // Remove mobile table optimizations
            $('.mt-table-scroll-wrapper').contents().unwrap();
            $('.mt-mobile-card-view').removeClass('mt-mobile-card-view');
        },
        
        // Tablet-specific adjustments
        applyTabletAdjustments: function() {
            // Adjust grid layouts for tablet
            $('.mt-candidates-grid').attr('data-columns', '2');
            
            // Optimize touch targets
            $('button, .mt-button, a.mt-link').each(function() {
                var $el = $(this);
                var height = $el.outerHeight();
                if (height < 44) {
                    $el.css('min-height', '44px');
                }
            });
        },
        
        // Desktop enhancements
        applyDesktopEnhancements: function() {
            // Enable advanced animations on desktop
            $('.mt-candidates-grid').attr('data-columns', '3');
            
            // Enable parallax effects if performance allows
            if (window.requestAnimationFrame && !this.reducedMotion()) {
                this.enableParallaxEffects();
            }
        },
        
        // Handle orientation changes
        handleOrientationChange: function(isPortrait) {
            if (this.currentViewport === 'mobile' || this.currentViewport === 'tablet') {
                if (isPortrait) {
                    $('body').attr('data-orientation', 'portrait');
                } else {
                    $('body').attr('data-orientation', 'landscape');
                    // Adjust layout for landscape mobile
                    this.optimizeForLandscape();
                }
            }
        },
        
        // Optimize tables for mobile viewing
        optimizeTablesForMobile: function() {
            $('.mt-assignments-table, .mt-evaluations-table').each(function() {
                var $table = $(this);
                if (!$table.parent('.mt-table-scroll-wrapper').length) {
                    $table.wrap('<div class="mt-table-scroll-wrapper" style="overflow-x: auto; -webkit-overflow-scrolling: touch;"></div>');
                }
            });
            
            // Add card view for complex tables on very small screens
            if (window.innerWidth < 480) {
                $('.mt-assignments-table tbody tr').addClass('mt-mobile-card-view');
            }
        },
        
        // Optimize for landscape orientation
        optimizeForLandscape: function() {
            // Reduce vertical spacing in landscape mode
            $('.mt-section-header').css('padding-top', '10px');
            $('.mt-section-header').css('padding-bottom', '10px');
        },
        
        // Check for reduced motion preference
        reducedMotion: function() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },
        
        // Enable parallax effects on desktop
        enableParallaxEffects: function() {
            if (this.reducedMotion()) return;
            
            var self = this;
            var $parallaxElements = $('[data-parallax]');
            
            if ($parallaxElements.length) {
                $(window).on('scroll.parallax', function() {
                    self.updateParallax($parallaxElements);
                });
            }
        },
        
        // Update parallax positions
        updateParallax: function($elements) {
            var scrollTop = $(window).scrollTop();
            var windowHeight = $(window).height();
            
            $elements.each(function() {
                var $el = $(this);
                var speed = $el.data('parallax') || 0.5;
                var offset = $el.offset().top;
                var height = $el.outerHeight();
                
                // Check if element is in viewport
                if (offset + height >= scrollTop && offset <= scrollTop + windowHeight) {
                    var yPos = -(scrollTop - offset) * speed;
                    $el.css('transform', 'translateY(' + yPos + 'px)');
                }
            });
        },
        
        // Public API: Check current viewport
        isViewport: function(viewport) {
            return this.currentViewport === viewport;
        },
        
        // Public API: Get current viewport
        getViewport: function() {
            return this.currentViewport;
        },
        
        // Public API: Check if mobile
        isMobile: function() {
            return this.currentViewport === 'mobile';
        },
        
        // Public API: Check if touch device
        isTouchDevice: function() {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MTResponsiveEnhancer.init();
    });
    
    // Expose to global scope for other modules
    window.MTResponsive = MTResponsiveEnhancer;
    
})(jQuery);