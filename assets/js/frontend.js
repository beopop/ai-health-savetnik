/**
 * AI Health Savetnik - Frontend JavaScript
 *
 * Main JavaScript functionality for the frontend components
 */

(function($) {
    'use strict';

    // Global AIHS object
    window.AIHS = window.AIHS || {};

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        AIHS.init();
    });

    /**
     * Main initialization
     */
    AIHS.init = function() {
        AIHS.Quiz.init();
        AIHS.Forms.init();
        AIHS.Products.init();
        AIHS.Packages.init();
        AIHS.Dashboard.init();
        AIHS.Utils.init();
    };

    /**
     * Quiz functionality
     */
    AIHS.Quiz = {
        currentStep: 1,
        totalSteps: 0,
        responses: {},
        intensityData: {},
        autosaveTimer: null,

        init: function() {
            this.bindEvents();
            this.loadSavedData();
            this.updateProgress();
        },

        bindEvents: function() {
            $(document).on('change', '.aihs-answer-option input[type="radio"]', this.handleAnswerChange.bind(this));
            $(document).on('change', '.aihs-intensity-option input[type="radio"]', this.handleIntensityChange.bind(this));
            $(document).on('click', '.aihs-next-question', this.nextQuestion.bind(this));
            $(document).on('click', '.aihs-prev-question', this.prevQuestion.bind(this));
            $(document).on('click', '.aihs-submit-quiz', this.submitQuiz.bind(this));
            $(document).on('submit', '.aihs-quiz-form', this.handleFormSubmit.bind(this));
        },

        handleAnswerChange: function(e) {
            const $option = $(e.target).closest('.aihs-answer-option');
            const $question = $option.closest('.aihs-question-card');
            const questionId = $question.data('question-id');
            const answer = e.target.value;

            // Update visual state
            $question.find('.aihs-answer-option').removeClass('selected');
            $option.addClass('selected');

            // Store response
            this.responses[questionId] = answer;

            // Show/hide intensity section
            const $intensitySection = $question.find('.aihs-intensity-section');
            if (answer === 'yes' && $intensitySection.length) {
                $intensitySection.addClass('visible');
            } else {
                $intensitySection.removeClass('visible');
                delete this.intensityData[questionId];
            }

            // Auto-advance if enabled
            if ($question.data('auto-advance') === 'yes') {
                setTimeout(() => {
                    this.nextQuestion();
                }, 1000);
            }

            // Schedule autosave
            this.scheduleAutosave();
        },

        handleIntensityChange: function(e) {
            const $question = $(e.target).closest('.aihs-question-card');
            const questionId = $question.data('question-id');
            const intensity = e.target.value;

            this.intensityData[questionId] = intensity;
            this.scheduleAutosave();
        },

        nextQuestion: function(e) {
            if (e) e.preventDefault();

            const $currentQuestion = $('.aihs-question-card').eq(this.currentStep - 1);
            const $nextQuestion = $('.aihs-question-card').eq(this.currentStep);

            if ($nextQuestion.length) {
                $currentQuestion.fadeOut(300, function() {
                    $nextQuestion.fadeIn(300);
                });
                this.currentStep++;
                this.updateProgress();
                this.scrollToTop();
            }
        },

        prevQuestion: function(e) {
            if (e) e.preventDefault();

            if (this.currentStep > 1) {
                const $currentQuestion = $('.aihs-question-card').eq(this.currentStep - 1);
                const $prevQuestion = $('.aihs-question-card').eq(this.currentStep - 2);

                $currentQuestion.fadeOut(300, function() {
                    $prevQuestion.fadeIn(300);
                });
                this.currentStep--;
                this.updateProgress();
                this.scrollToTop();
            }
        },

        updateProgress: function() {
            this.totalSteps = $('.aihs-question-card').length;
            const progress = (this.currentStep / this.totalSteps) * 100;

            $('.aihs-progress-bar').css('width', progress + '%');
            $('.aihs-progress-text').text(`Korak ${this.currentStep} od ${this.totalSteps}`);

            // Update navigation buttons
            $('.aihs-prev-question').prop('disabled', this.currentStep === 1);
            $('.aihs-next-question').toggle(this.currentStep < this.totalSteps);
            $('.aihs-submit-quiz').toggle(this.currentStep === this.totalSteps);
        },

        scheduleAutosave: function() {
            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = setTimeout(() => {
                this.autosave();
            }, 2000);
        },

        autosave: function() {
            if (typeof aihs_ajax === 'undefined') return;

            const data = {
                action: 'aihs_autosave_response',
                answers: JSON.stringify(this.responses),
                intensity_data: JSON.stringify(this.intensityData),
                quiz_progress: (this.currentStep / this.totalSteps) * 100,
                nonce: aihs_ajax.nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.showNotification('Podaci su automatski sačuvani', 'success', 2000);
                    }
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri čuvanju podataka', 'error', 3000);
                });
        },

        loadSavedData: function() {
            // Load from localStorage as fallback
            const savedResponses = localStorage.getItem('aihs_quiz_responses');
            const savedIntensity = localStorage.getItem('aihs_quiz_intensity');

            if (savedResponses) {
                this.responses = JSON.parse(savedResponses);
                this.restoreAnswers();
            }

            if (savedIntensity) {
                this.intensityData = JSON.parse(savedIntensity);
                this.restoreIntensity();
            }
        },

        restoreAnswers: function() {
            for (const questionId in this.responses) {
                const answer = this.responses[questionId];
                const $question = $(`.aihs-question-card[data-question-id="${questionId}"]`);
                const $option = $question.find(`input[value="${answer}"]`).closest('.aihs-answer-option');

                $option.addClass('selected');
                $option.find('input').prop('checked', true);

                if (answer === 'yes') {
                    $question.find('.aihs-intensity-section').addClass('visible');
                }
            }
        },

        restoreIntensity: function() {
            for (const questionId in this.intensityData) {
                const intensity = this.intensityData[questionId];
                const $question = $(`.aihs-question-card[data-question-id="${questionId}"]`);
                $question.find(`.aihs-intensity-option input[value="${intensity}"]`).prop('checked', true);
            }
        },

        submitQuiz: function(e) {
            if (e) e.preventDefault();

            const $button = $(e.target);
            $button.prop('disabled', true).text('Slanje...');

            // Save to localStorage
            localStorage.setItem('aihs_quiz_responses', JSON.stringify(this.responses));
            localStorage.setItem('aihs_quiz_intensity', JSON.stringify(this.intensityData));

            // Submit via AJAX or form
            if (typeof aihs_ajax !== 'undefined') {
                this.submitViaAjax($button);
            } else {
                this.submitViaForm();
            }
        },

        submitViaAjax: function($button) {
            const data = {
                action: 'aihs_submit_quiz',
                answers: JSON.stringify(this.responses),
                intensity_data: JSON.stringify(this.intensityData),
                completion_status: 'questions_completed',
                nonce: aihs_ajax.nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            AIHS.Utils.showNotification('Upitnik je uspešno poslat!', 'success');
                            $('.aihs-quiz-container').html('<div class="aihs-alert aihs-alert-success"><h3>Hvala vam!</h3><p>Vaš upitnik je uspešno poslat. Uskoro ćete dobiti rezultate.</p></div>');
                        }
                    } else {
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri slanju upitnika', 'error');
                        $button.prop('disabled', false).text('Pošalji Upitnik');
                    }
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri slanju upitnika. Molimo pokušajte ponovo.', 'error');
                    $button.prop('disabled', false).text('Pošalji Upitnik');
                });
        },

        submitViaForm: function() {
            // Create hidden form with data
            const $form = $('<form>', {
                method: 'POST',
                action: window.location.href
            });

            $form.append($('<input>', {
                type: 'hidden',
                name: 'aihs_quiz_answers',
                value: JSON.stringify(this.responses)
            }));

            $form.append($('<input>', {
                type: 'hidden',
                name: 'aihs_quiz_intensity',
                value: JSON.stringify(this.intensityData)
            }));

            $form.append($('<input>', {
                type: 'hidden',
                name: 'aihs_quiz_submit',
                value: '1'
            }));

            $('body').append($form);
            $form.submit();
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            this.submitQuiz();
        },

        scrollToTop: function() {
            $('html, body').animate({
                scrollTop: $('.aihs-quiz-container').offset().top - 100
            }, 500);
        }
    };

    /**
     * Forms functionality
     */
    AIHS.Forms = {
        init: function() {
            this.bindEvents();
            this.initValidation();
        },

        bindEvents: function() {
            $(document).on('submit', '.aihs-form', this.handleSubmit.bind(this));
            $(document).on('blur', '.aihs-input, .aihs-textarea, .aihs-select', this.validateField.bind(this));
            $(document).on('input', '.aihs-input, .aihs-textarea', this.clearErrors.bind(this));
        },

        initValidation: function() {
            // Add real-time validation for required fields
            $('.aihs-input[required], .aihs-textarea[required], .aihs-select[required]').each(function() {
                $(this).on('blur', AIHS.Forms.validateField.bind(AIHS.Forms));
            });
        },

        validateField: function(e) {
            const $field = $(e.target);
            const value = $field.val();
            const isRequired = $field.prop('required');
            let isValid = true;
            let errorMessage = '';

            // Required validation
            if (isRequired && !value) {
                isValid = false;
                errorMessage = 'Ovo polje je obavezno.';
            }

            // Email validation
            if ($field.attr('type') === 'email' && value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Unesite ispravnu email adresu.';
                }
            }

            // Phone validation
            if ($field.attr('type') === 'tel' && value) {
                const phoneRegex = /^[\+]?[\d\s\-\(\)]+$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    errorMessage = 'Unesite ispravan broj telefona.';
                }
            }

            this.setFieldState($field, isValid, errorMessage);
            return isValid;
        },

        setFieldState: function($field, isValid, errorMessage) {
            $field.removeClass('error success');
            $field.siblings('.aihs-form-error').remove();

            if (isValid) {
                $field.addClass('success');
            } else {
                $field.addClass('error');
                if (errorMessage) {
                    $field.after(`<div class="aihs-form-error">${errorMessage}</div>`);
                }
            }
        },

        clearErrors: function(e) {
            const $field = $(e.target);
            $field.removeClass('error');
            $field.siblings('.aihs-form-error').remove();
        },

        validateForm: function($form) {
            let isValid = true;
            const $fields = $form.find('.aihs-input, .aihs-textarea, .aihs-select');

            $fields.each((index, field) => {
                const fieldValid = this.validateField({ target: field });
                if (!fieldValid) {
                    isValid = false;
                }
            });

            return isValid;
        },

        handleSubmit: function(e) {
            e.preventDefault();
            const $form = $(e.target);

            if (!this.validateForm($form)) {
                AIHS.Utils.showNotification('Molimo ispravite greške u formi.', 'error');
                return;
            }

            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Slanje...');

            // Submit form via AJAX if action is set
            const ajaxAction = $form.data('ajax-action');
            if (ajaxAction && typeof aihs_ajax !== 'undefined') {
                this.submitViaAjax($form, ajaxAction, $submitBtn, originalText);
            } else {
                $form.get(0).submit();
            }
        },

        submitViaAjax: function($form, action, $submitBtn, originalText) {
            const formData = new FormData($form.get(0));
            formData.append('action', action);
            formData.append('nonce', aihs_ajax.nonce);

            $.ajax({
                url: aihs_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AIHS.Utils.showNotification(response.data.message || 'Forma je uspešno poslata!', 'success');
                        if (response.data.redirect_url) {
                            setTimeout(() => {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                        } else {
                            $form.get(0).reset();
                        }
                    } else {
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri slanju forme.', 'error');
                    }
                },
                error: function() {
                    AIHS.Utils.showNotification('Greška pri slanju forme. Molimo pokušajte ponovo.', 'error');
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    /**
     * Products functionality
     */
    AIHS.Products = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.aihs-view-product', this.viewProduct.bind(this));
            $(document).on('click', '.aihs-add-to-cart', this.addToCart.bind(this));
            $(document).on('click', '.aihs-quick-view', this.quickView.bind(this));
        },

        viewProduct: function(e) {
            e.preventDefault();
            const productUrl = $(e.target).data('product-url');
            if (productUrl) {
                window.open(productUrl, '_blank');
            }
        },

        addToCart: function(e) {
            e.preventDefault();
            const $button = $(e.target);
            const productId = $button.data('product-id');

            if (!productId) return;

            const originalText = $button.text();
            $button.prop('disabled', true).text('Dodajem...');

            // Use WooCommerce AJAX add to cart if available
            if (typeof wc_add_to_cart_params !== 'undefined') {
                this.addToCartWC(productId, $button, originalText);
            } else {
                this.addToCartCustom(productId, $button, originalText);
            }
        },

        addToCartWC: function(productId, $button, originalText) {
            const data = {
                action: 'woocommerce_add_to_cart',
                product_id: productId,
                quantity: 1
            };

            $.post(wc_add_to_cart_params.ajax_url, data)
                .done(function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }

                    // Trigger WooCommerce events
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

                    AIHS.Utils.showNotification('Proizvod je dodat u korpu!', 'success');
                    $button.text('Dodato u korpu');

                    setTimeout(() => {
                        $button.prop('disabled', false).text(originalText);
                    }, 2000);
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri dodavanju u korpu.', 'error');
                    $button.prop('disabled', false).text(originalText);
                });
        },

        addToCartCustom: function(productId, $button, originalText) {
            if (typeof aihs_ajax === 'undefined') return;

            const data = {
                action: 'aihs_add_to_cart',
                product_id: productId,
                quantity: 1,
                nonce: aihs_ajax.nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.showNotification('Proizvod je dodat u korpu!', 'success');
                        $button.text('Dodato u korpu');

                        setTimeout(() => {
                            $button.prop('disabled', false).text(originalText);
                        }, 2000);
                    } else {
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri dodavanju u korpu.', 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri dodavanju u korpu.', 'error');
                    $button.prop('disabled', false).text(originalText);
                });
        },

        quickView: function(e) {
            e.preventDefault();
            const productId = $(e.target).data('product-id');

            if (!productId || typeof aihs_ajax === 'undefined') return;

            // Show modal loading
            AIHS.Utils.showModal('Učitavanje...', '<div class="aihs-loading"><div class="aihs-spinner"></div><span>Učitavanje proizvoda...</span></div>');

            const data = {
                action: 'aihs_get_product_quick_view',
                product_id: productId,
                nonce: aihs_ajax.nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.showModal(response.data.title, response.data.content);
                    } else {
                        AIHS.Utils.closeModal();
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri učitavanju proizvoda.', 'error');
                    }
                })
                .fail(function() {
                    AIHS.Utils.closeModal();
                    AIHS.Utils.showNotification('Greška pri učitavanju proizvoda.', 'error');
                });
        }
    };

    /**
     * Packages functionality
     */
    AIHS.Packages = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.aihs-add-package-to-cart', this.addPackageToCart.bind(this));
            $(document).on('click', '.aihs-save-package', this.savePackage.bind(this));
            $(document).on('click', '.aihs-remove-saved-package', this.removeSavedPackage.bind(this));
        },

        addPackageToCart: function(e) {
            e.preventDefault();
            const $button = $(e.target);
            const packageId = $button.data('package-id');
            const nonce = $button.data('nonce');

            if (!packageId || typeof aihs_ajax === 'undefined') return;

            const originalText = $button.text();
            $button.prop('disabled', true).text('Dodajem...');

            const data = {
                action: 'aihs_add_package_to_cart',
                package_id: packageId,
                nonce: nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.showNotification('Paket je dodat u korpu!', 'success');
                        $button.text('Dodato u korpu');

                        if (response.data.cart_url) {
                            setTimeout(() => {
                                window.location.href = response.data.cart_url;
                            }, 1500);
                        }
                    } else {
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri dodavanju paketa.', 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri dodavanju paketa.', 'error');
                    $button.prop('disabled', false).text(originalText);
                });
        },

        savePackage: function(e) {
            e.preventDefault();
            const $button = $(e.target);
            const packageId = $button.data('package-id');
            const nonce = $button.data('nonce');

            if (!packageId || typeof aihs_ajax === 'undefined') return;

            const data = {
                action: 'aihs_save_package',
                package_id: packageId,
                nonce: nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.showNotification('Paket je sačuvan!', 'success');
                        $button.html('<i class="aihs-icon-heart-filled"></i> Sačuvano').addClass('saved');
                    } else {
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri čuvanju paketa.', 'error');
                    }
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri čuvanju paketa.', 'error');
                });
        },

        removeSavedPackage: function(e) {
            e.preventDefault();

            if (!confirm('Da li ste sigurni da želite da uklonite ovaj paket?')) {
                return;
            }

            const $button = $(e.target);
            const packageId = $button.data('package-id');
            const nonce = $button.data('nonce');

            if (!packageId || typeof aihs_ajax === 'undefined') return;

            const data = {
                action: 'aihs_remove_saved_package',
                package_id: packageId,
                nonce: nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.showNotification('Paket je uklonjen.', 'success');
                        $button.closest('.aihs-package-card-mini, .aihs-package-card').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri uklanjanju paketa.', 'error');
                    }
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri uklanjanju paketa.', 'error');
                });
        }
    };

    /**
     * Dashboard functionality
     */
    AIHS.Dashboard = {
        init: function() {
            this.bindEvents();
            this.initCharts();
        },

        bindEvents: function() {
            $(document).on('click', '.aihs-view-analysis', this.viewAnalysis.bind(this));
            $(document).on('click', '.aihs-regenerate-packages', this.regeneratePackages.bind(this));
        },

        viewAnalysis: function(e) {
            e.preventDefault();
            const responseId = $(e.target).data('response-id');

            if (!responseId || typeof aihs_ajax === 'undefined') return;

            AIHS.Utils.showModal('AI Analiza', '<div class="aihs-loading"><div class="aihs-spinner"></div><span>Učitavanje analize...</span></div>');

            const data = {
                action: 'aihs_get_analysis',
                response_id: responseId,
                nonce: aihs_ajax.nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.updateModal('AI Analiza zdravlja', response.data.analysis);
                    } else {
                        AIHS.Utils.closeModal();
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri učitavanju analize.', 'error');
                    }
                })
                .fail(function() {
                    AIHS.Utils.closeModal();
                    AIHS.Utils.showNotification('Greška pri učitavanju analize.', 'error');
                });
        },

        regeneratePackages: function(e) {
            e.preventDefault();

            if (!confirm('Da li ste sigurni da želite da regenerišete pakete? Postojeći paketi će biti obrisani.')) {
                return;
            }

            const $button = $(e.target);
            const responseId = $button.data('response-id');

            if (!responseId || typeof aihs_ajax === 'undefined') return;

            const originalText = $button.text();
            $button.prop('disabled', true).text('Regenerišem...');

            const data = {
                action: 'aihs_regenerate_packages',
                response_id: responseId,
                nonce: aihs_ajax.nonce
            };

            $.post(aihs_ajax.ajax_url, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS.Utils.showNotification('Paketi su uspešno regenerisani!', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        AIHS.Utils.showNotification(response.data.message || 'Greška pri regenerisanju paketa.', 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    AIHS.Utils.showNotification('Greška pri regenerisanju paketa.', 'error');
                    $button.prop('disabled', false).text(originalText);
                });
        },

        initCharts: function() {
            // Initialize any dashboard charts if needed
            this.animateScoreGauges();
        },

        animateScoreGauges: function() {
            $('.aihs-score-gauge').each(function() {
                const $gauge = $(this);
                const score = parseInt($gauge.data('score')) || 0;
                const $circle = $gauge.find('circle:last-child');
                const radius = 45;
                const circumference = 2 * Math.PI * radius;
                const progress = (score / 100) * circumference;

                $circle.css({
                    'stroke-dasharray': circumference,
                    'stroke-dashoffset': circumference
                });

                // Animate
                setTimeout(() => {
                    $circle.css({
                        'stroke-dashoffset': circumference - progress,
                        'transition': 'stroke-dashoffset 1.5s ease-in-out'
                    });
                }, 500);
            });
        }
    };

    /**
     * Utility functions
     */
    AIHS.Utils = {
        init: function() {
            this.createModal();
            this.bindGlobalEvents();
        },

        bindGlobalEvents: function() {
            // Close modal events
            $(document).on('click', '.aihs-modal-overlay, .aihs-modal-close', this.closeModal.bind(this));
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    AIHS.Utils.closeModal();
                }
            });

            // Smooth scrolling for anchor links
            $(document).on('click', 'a[href^="#"]', function(e) {
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    e.preventDefault();
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });
        },

        createModal: function() {
            if ($('#aihs-modal').length) return;

            const modalHtml = `
                <div id="aihs-modal" class="aihs-modal" style="display: none;">
                    <div class="aihs-modal-overlay"></div>
                    <div class="aihs-modal-content">
                        <div class="aihs-modal-header">
                            <h3 class="aihs-modal-title"></h3>
                            <button class="aihs-modal-close">&times;</button>
                        </div>
                        <div class="aihs-modal-body"></div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
        },

        showModal: function(title, content) {
            this.createModal();
            $('#aihs-modal .aihs-modal-title').text(title);
            $('#aihs-modal .aihs-modal-body').html(content);
            $('#aihs-modal').fadeIn(300);
            $('body').addClass('aihs-modal-open');
        },

        updateModal: function(title, content) {
            $('#aihs-modal .aihs-modal-title').text(title);
            $('#aihs-modal .aihs-modal-body').html(content);
        },

        closeModal: function() {
            $('#aihs-modal').fadeOut(300);
            $('body').removeClass('aihs-modal-open');
        },

        showNotification: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;

            const notificationHtml = `
                <div class="aihs-notification aihs-notification-${type}">
                    <span class="aihs-notification-message">${message}</span>
                    <button class="aihs-notification-close">&times;</button>
                </div>
            `;

            const $notification = $(notificationHtml);
            $('body').append($notification);

            // Show notification
            setTimeout(() => {
                $notification.addClass('show');
            }, 100);

            // Auto hide
            setTimeout(() => {
                this.hideNotification($notification);
            }, duration);

            // Manual close
            $notification.find('.aihs-notification-close').on('click', () => {
                this.hideNotification($notification);
            });
        },

        hideNotification: function($notification) {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        },

        formatCurrency: function(amount, currency) {
            currency = currency || 'RSD';
            return new Intl.NumberFormat('sr-RS', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 0
            }).format(amount);
        },

        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            }
        }
    };

})(jQuery);

// Additional CSS for notifications (if not in CSS file)
jQuery(document).ready(function($) {
    if (!$('#aihs-notification-styles').length) {
        $('head').append(`
            <style id="aihs-notification-styles">
                .aihs-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                    padding: 15px 20px;
                    z-index: 10001;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    min-width: 300px;
                    transform: translateX(100%);
                    transition: transform 0.3s ease;
                    border-left: 4px solid #007cba;
                }

                .aihs-notification.show {
                    transform: translateX(0);
                }

                .aihs-notification-success {
                    border-left-color: #28a745;
                }

                .aihs-notification-error {
                    border-left-color: #dc3545;
                }

                .aihs-notification-warning {
                    border-left-color: #ffc107;
                }

                .aihs-notification-message {
                    flex: 1;
                    font-weight: 500;
                }

                .aihs-notification-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: #6c757d;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                body.aihs-modal-open {
                    overflow: hidden;
                }

                .aihs-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 10000;
                }

                .aihs-modal-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                }

                .aihs-modal-content {
                    position: relative;
                    background: white;
                    max-width: 600px;
                    margin: 50px auto;
                    border-radius: 15px;
                    overflow: hidden;
                    max-height: calc(100vh - 100px);
                    display: flex;
                    flex-direction: column;
                }

                .aihs-modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #dee2e6;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .aihs-modal-title {
                    margin: 0;
                    font-size: 1.25rem;
                    font-weight: 600;
                }

                .aihs-modal-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6c757d;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .aihs-modal-body {
                    padding: 20px;
                    overflow-y: auto;
                }

                @media (max-width: 768px) {
                    .aihs-notification {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                        min-width: auto;
                    }

                    .aihs-modal-content {
                        margin: 20px;
                        max-height: calc(100vh - 40px);
                    }
                }
            </style>
        `);
    }
});