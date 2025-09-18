/**
 * AI Health Savetnik - Admin JavaScript
 *
 * Admin interface functionality
 */

(function($) {
    'use strict';

    // Global admin object
    window.AIHS_Admin = window.AIHS_Admin || {};

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function() {
        AIHS_Admin.init();
    });

    /**
     * Main initialization
     */
    AIHS_Admin.init = function() {
        AIHS_Admin.Questions.init();
        AIHS_Admin.Products.init();
        AIHS_Admin.Packages.init();
        AIHS_Admin.Reports.init();
        AIHS_Admin.Settings.init();
        AIHS_Admin.Utils.init();
    };

    /**
     * Questions management
     */
    AIHS_Admin.Questions = {
        init: function() {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function() {
            $(document).on('click', '.aihs-add-question', this.addQuestion.bind(this));
            $(document).on('click', '.aihs-edit-question', this.editQuestion.bind(this));
            $(document).on('click', '.aihs-delete-question', this.deleteQuestion.bind(this));
            $(document).on('click', '.aihs-save-question', this.saveQuestion.bind(this));
            $(document).on('click', '.aihs-cancel-edit', this.cancelEdit.bind(this));
            $(document).on('submit', '.aihs-question-form', this.handleFormSubmit.bind(this));
            $(document).on('change', '.aihs-question-type', this.handleTypeChange.bind(this));
        },

        initSortable: function() {
            if ($.fn.sortable) {
                $('.aihs-questions-container').sortable({
                    items: '.aihs-question-item',
                    handle: '.aihs-question-drag-handle',
                    placeholder: 'aihs-question-placeholder',
                    update: this.updateOrder.bind(this)
                });
            }
        },

        addQuestion: function(e) {
            e.preventDefault();

            const questionHtml = this.getQuestionTemplate();
            $('.aihs-questions-container').append(questionHtml);

            // Focus on the new question text field
            $('.aihs-question-item:last-child .aihs-question-text-input').focus();
        },

        editQuestion: function(e) {
            e.preventDefault();
            const $questionItem = $(e.target).closest('.aihs-question-item');
            const questionData = this.getQuestionData($questionItem);

            this.showEditForm($questionItem, questionData);
        },

        deleteQuestion: function(e) {
            e.preventDefault();

            if (!confirm('Da li ste sigurni da želite da obrišete ovo pitanje?')) {
                return;
            }

            const $questionItem = $(e.target).closest('.aihs-question-item');
            const questionId = $questionItem.data('question-id');

            if (questionId) {
                this.deleteQuestionAjax(questionId, $questionItem);
            } else {
                $questionItem.fadeOut(300, function() {
                    $(this).remove();
                    AIHS_Admin.Questions.reorderQuestions();
                });
            }
        },

        saveQuestion: function(e) {
            e.preventDefault();
            const $questionItem = $(e.target).closest('.aihs-question-item');
            const questionData = this.getFormData($questionItem);

            if (!this.validateQuestion(questionData)) {
                return;
            }

            this.saveQuestionAjax(questionData, $questionItem);
        },

        cancelEdit: function(e) {
            e.preventDefault();
            const $questionItem = $(e.target).closest('.aihs-question-item');

            if ($questionItem.data('question-id')) {
                this.showQuestionDisplay($questionItem);
            } else {
                $questionItem.remove();
            }
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            const $form = $(e.target);
            const $questionItem = $form.closest('.aihs-question-item');

            this.saveQuestion({ target: $questionItem.find('.aihs-save-question')[0] });
        },

        handleTypeChange: function(e) {
            const $select = $(e.target);
            const $questionItem = $select.closest('.aihs-question-item');
            const $intensitySection = $questionItem.find('.aihs-intensity-section');

            if ($select.val() === 'binary') {
                $intensitySection.show();
            } else {
                $intensitySection.hide();
            }
        },

        getQuestionTemplate: function() {
            return `
                <div class="aihs-question-item" data-question-id="">
                    <div class="aihs-question-form">
                        <div class="aihs-form-row">
                            <div class="aihs-form-group">
                                <label>Tekst pitanja:</label>
                                <input type="text" class="aihs-question-text-input" required>
                            </div>
                            <div class="aihs-form-group">
                                <label>Tip pitanja:</label>
                                <select class="aihs-question-type">
                                    <option value="binary">Da/Ne</option>
                                    <option value="scale">Skala 1-5</option>
                                    <option value="choice">Izbor opcija</option>
                                </select>
                            </div>
                        </div>
                        <div class="aihs-form-row">
                            <div class="aihs-form-group">
                                <label>Težina pitanja (1-20):</label>
                                <input type="number" class="aihs-question-weight" min="1" max="20" value="10">
                            </div>
                            <div class="aihs-form-group">
                                <label>AI podsetnik:</label>
                                <input type="text" class="aihs-question-ai-hint" placeholder="Ključne reči za AI analizu">
                            </div>
                        </div>
                        <div class="aihs-intensity-section">
                            <label>Tekst intenziteta:</label>
                            <input type="text" class="aihs-intensity-text" placeholder="Koliko često...?" value="Koliko često se to dešava?">
                            <label>Opcije intenziteta (odvojene zarezom):</label>
                            <input type="text" class="aihs-intensity-options" value="Retko,Ponekad,Često">
                            <label>Težine intenziteta (odvojene zarezom):</label>
                            <input type="text" class="aihs-intensity-weights" value="5,10,15">
                        </div>
                        <div class="aihs-question-actions">
                            <button type="button" class="aihs-btn aihs-btn-primary aihs-save-question">Sačuvaj</button>
                            <button type="button" class="aihs-btn aihs-btn-secondary aihs-cancel-edit">Otkaži</button>
                        </div>
                    </div>
                </div>
            `;
        },

        showEditForm: function($questionItem, questionData) {
            const formHtml = this.getEditFormHtml(questionData);
            $questionItem.html(formHtml);
            $questionItem.addClass('aihs-question-editing');
        },

        showQuestionDisplay: function($questionItem) {
            const questionData = this.getQuestionData($questionItem);
            const displayHtml = this.getDisplayHtml(questionData);
            $questionItem.html(displayHtml);
            $questionItem.removeClass('aihs-question-editing');
        },

        getQuestionData: function($questionItem) {
            return {
                id: $questionItem.data('question-id'),
                text: $questionItem.find('.aihs-question-text').text(),
                type: $questionItem.data('question-type'),
                weight: $questionItem.data('question-weight'),
                aiHint: $questionItem.data('ai-hint'),
                intensityText: $questionItem.data('intensity-text'),
                intensityOptions: $questionItem.data('intensity-options'),
                intensityWeights: $questionItem.data('intensity-weights')
            };
        },

        getFormData: function($questionItem) {
            return {
                id: $questionItem.data('question-id'),
                text: $questionItem.find('.aihs-question-text-input').val(),
                type: $questionItem.find('.aihs-question-type').val(),
                weight: parseInt($questionItem.find('.aihs-question-weight').val()),
                aiHint: $questionItem.find('.aihs-question-ai-hint').val(),
                intensityText: $questionItem.find('.aihs-intensity-text').val(),
                intensityOptions: $questionItem.find('.aihs-intensity-options').val(),
                intensityWeights: $questionItem.find('.aihs-intensity-weights').val()
            };
        },

        validateQuestion: function(questionData) {
            if (!questionData.text.trim()) {
                alert('Tekst pitanja je obavezan.');
                return false;
            }

            if (questionData.weight < 1 || questionData.weight > 20) {
                alert('Težina pitanja mora biti između 1 i 20.');
                return false;
            }

            return true;
        },

        saveQuestionAjax: function(questionData, $questionItem) {
            const data = {
                action: 'aihs_save_question',
                question_data: questionData,
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS_Admin.Utils.showNotice('Pitanje je uspešno sačuvano.', 'success');

                        // Update question item with saved data
                        $questionItem.data('question-id', response.data.question_id);
                        AIHS_Admin.Questions.showQuestionDisplay($questionItem);
                    } else {
                        AIHS_Admin.Utils.showNotice(response.data.message || 'Greška pri čuvanju pitanja.', 'error');
                    }
                })
                .fail(function() {
                    AIHS_Admin.Utils.showNotice('Greška pri čuvanju pitanja.', 'error');
                });
        },

        deleteQuestionAjax: function(questionId, $questionItem) {
            const data = {
                action: 'aihs_delete_question',
                question_id: questionId,
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS_Admin.Utils.showNotice('Pitanje je obrisano.', 'success');
                        $questionItem.fadeOut(300, function() {
                            $(this).remove();
                            AIHS_Admin.Questions.reorderQuestions();
                        });
                    } else {
                        AIHS_Admin.Utils.showNotice(response.data.message || 'Greška pri brisanju pitanja.', 'error');
                    }
                })
                .fail(function() {
                    AIHS_Admin.Utils.showNotice('Greška pri brisanju pitanja.', 'error');
                });
        },

        updateOrder: function() {
            const questionOrder = [];
            $('.aihs-question-item').each(function(index) {
                const questionId = $(this).data('question-id');
                if (questionId) {
                    questionOrder.push({
                        id: questionId,
                        priority: index + 1
                    });
                }
            });

            if (questionOrder.length > 0) {
                this.saveQuestionOrder(questionOrder);
            }

            this.reorderQuestions();
        },

        reorderQuestions: function() {
            $('.aihs-question-item').each(function(index) {
                $(this).find('.aihs-question-number').text(index + 1);
            });
        },

        saveQuestionOrder: function(questionOrder) {
            const data = {
                action: 'aihs_update_question_order',
                question_order: questionOrder,
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data);
        }
    };

    /**
     * Products management
     */
    AIHS_Admin.Products = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.aihs-bulk-assign-categories', this.bulkAssignCategories.bind(this));
            $(document).on('click', '.aihs-test-product-recommendations', this.testRecommendations.bind(this));
            $(document).on('submit', '.aihs-product-search-form', this.searchProducts.bind(this));
        },

        bulkAssignCategories: function(e) {
            e.preventDefault();

            const selectedProducts = $('.aihs-product-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedProducts.length === 0) {
                alert('Molimo odaberite proizvode.');
                return;
            }

            this.showCategoryAssignModal(selectedProducts);
        },

        showCategoryAssignModal: function(productIds) {
            const modalHtml = `
                <div class="aihs-modal-backdrop">
                    <div class="aihs-modal">
                        <div class="aihs-modal-header">
                            <h3 class="aihs-modal-title">Dodeli kategorije</h3>
                            <button class="aihs-modal-close">&times;</button>
                        </div>
                        <div class="aihs-modal-body">
                            <form class="aihs-category-assign-form">
                                <p>Odaberite kategorije za ${productIds.length} proizvoda:</p>
                                <div class="aihs-checkbox-group">
                                    <label><input type="checkbox" value="cardiovascular"> Kardiovaskularno zdravlje</label>
                                    <label><input type="checkbox" value="digestive"> Digestivno zdravlje</label>
                                    <label><input type="checkbox" value="immune"> Imunitet</label>
                                    <label><input type="checkbox" value="mental"> Mentalno zdravlje</label>
                                    <label><input type="checkbox" value="energy"> Energija i vitalnost</label>
                                    <label><input type="checkbox" value="sleep"> Kvalitet sna</label>
                                    <label><input type="checkbox" value="stress"> Upravljanje stresom</label>
                                    <label><input type="checkbox" value="weight"> Kontrola težine</label>
                                    <label><input type="checkbox" value="joints"> Zdravlje zglobova</label>
                                    <label><input type="checkbox" value="skin"> Zdravlje kože</label>
                                </div>
                            </form>
                        </div>
                        <div class="aihs-modal-footer">
                            <button class="aihs-btn aihs-btn-primary aihs-assign-categories" data-products="${productIds.join(',')}">Dodeli</button>
                            <button class="aihs-btn aihs-btn-secondary aihs-modal-close">Otkaži</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('.aihs-modal-backdrop').fadeIn(300);
        },

        testRecommendations: function(e) {
            e.preventDefault();

            const score = prompt('Unesite test skor (0-100):');
            if (!score || isNaN(score) || score < 0 || score > 100) {
                alert('Molimo unesite validan skor između 0 i 100.');
                return;
            }

            this.getTestRecommendations(parseInt(score));
        },

        getTestRecommendations: function(score) {
            const data = {
                action: 'aihs_test_product_recommendations',
                score: score,
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS_Admin.Products.showRecommendationsModal(response.data.products, score);
                    } else {
                        AIHS_Admin.Utils.showNotice(response.data.message || 'Greška pri testiranju preporuka.', 'error');
                    }
                })
                .fail(function() {
                    AIHS_Admin.Utils.showNotice('Greška pri testiranju preporuka.', 'error');
                });
        },

        showRecommendationsModal: function(products, score) {
            let productsHtml = '';

            if (products.length > 0) {
                productsHtml = '<ul>';
                products.forEach(function(product) {
                    productsHtml += `<li>${product.name} - Confidence: ${product.ai_confidence}%</li>`;
                });
                productsHtml += '</ul>';
            } else {
                productsHtml = '<p>Nema preporučenih proizvoda za ovaj skor.</p>';
            }

            const modalHtml = `
                <div class="aihs-modal-backdrop">
                    <div class="aihs-modal">
                        <div class="aihs-modal-header">
                            <h3 class="aihs-modal-title">Test preporuke za skor ${score}</h3>
                            <button class="aihs-modal-close">&times;</button>
                        </div>
                        <div class="aihs-modal-body">
                            ${productsHtml}
                        </div>
                        <div class="aihs-modal-footer">
                            <button class="aihs-btn aihs-btn-secondary aihs-modal-close">Zatvori</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('.aihs-modal-backdrop').fadeIn(300);
        }
    };

    /**
     * Packages management
     */
    AIHS_Admin.Packages = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.aihs-create-package', this.createPackage.bind(this));
            $(document).on('click', '.aihs-regenerate-packages', this.regeneratePackages.bind(this));
            $(document).on('click', '.aihs-preview-package', this.previewPackage.bind(this));
            $(document).on('change', '.aihs-package-products', this.updatePackagePreview.bind(this));
            $(document).on('change', '.aihs-package-discount', this.updatePackagePreview.bind(this));
        },

        createPackage: function(e) {
            e.preventDefault();
            this.showCreatePackageModal();
        },

        showCreatePackageModal: function() {
            // Get available products via AJAX
            this.getAvailableProducts().then(function(products) {
                let productOptions = '';
                products.forEach(function(product) {
                    productOptions += `<option value="${product.id}" data-price="${product.price}">${product.name} - ${product.price}</option>`;
                });

                const modalHtml = `
                    <div class="aihs-modal-backdrop">
                        <div class="aihs-modal">
                            <div class="aihs-modal-header">
                                <h3 class="aihs-modal-title">Kreiraj novi paket</h3>
                                <button class="aihs-modal-close">&times;</button>
                            </div>
                            <div class="aihs-modal-body">
                                <form class="aihs-create-package-form">
                                    <div class="aihs-form-group">
                                        <label>Naziv paketa:</label>
                                        <input type="text" name="package_name" required>
                                    </div>
                                    <div class="aihs-form-group">
                                        <label>Opis paketa:</label>
                                        <textarea name="package_description" rows="3"></textarea>
                                    </div>
                                    <div class="aihs-form-group">
                                        <label>Proizvodi:</label>
                                        <select name="package_products[]" multiple class="aihs-package-products" size="8">
                                            ${productOptions}
                                        </select>
                                        <small>Držite Ctrl/Cmd za višestruki izbor</small>
                                    </div>
                                    <div class="aihs-form-group">
                                        <label>Popust (%):</label>
                                        <input type="number" name="package_discount" class="aihs-package-discount" min="0" max="50" value="15">
                                    </div>
                                    <div class="aihs-package-preview"></div>
                                </form>
                            </div>
                            <div class="aihs-modal-footer">
                                <button class="aihs-btn aihs-btn-primary aihs-save-package">Kreiraj paket</button>
                                <button class="aihs-btn aihs-btn-secondary aihs-modal-close">Otkaži</button>
                            </div>
                        </div>
                    </div>
                `;

                $('body').append(modalHtml);
                $('.aihs-modal-backdrop').fadeIn(300);
            });
        },

        getAvailableProducts: function() {
            return $.post(aihsAdmin.ajaxurl, {
                action: 'aihs_get_available_products',
                nonce: aihsAdmin.nonce
            }).then(function(response) {
                return response.success ? response.data.products : [];
            });
        },

        updatePackagePreview: function(e) {
            const $form = $(e.target).closest('.aihs-create-package-form');
            const selectedProducts = $form.find('.aihs-package-products').val() || [];
            const discount = parseFloat($form.find('.aihs-package-discount').val()) || 0;

            if (selectedProducts.length === 0) {
                $form.find('.aihs-package-preview').html('');
                return;
            }

            let totalPrice = 0;
            let productsHtml = '<h4>Pregled paketa:</h4><ul>';

            selectedProducts.forEach(function(productId) {
                const $option = $form.find(`option[value="${productId}"]`);
                const productName = $option.text().split(' - ')[0];
                const productPrice = parseFloat($option.data('price'));
                totalPrice += productPrice;
                productsHtml += `<li>${productName} - ${productPrice} RSD</li>`;
            });

            productsHtml += '</ul>';

            const discountAmount = (totalPrice * discount) / 100;
            const finalPrice = totalPrice - discountAmount;

            productsHtml += `
                <div class="aihs-pricing-summary">
                    <p><strong>Ukupna cena:</strong> ${totalPrice.toFixed(2)} RSD</p>
                    <p><strong>Popust (${discount}%):</strong> -${discountAmount.toFixed(2)} RSD</p>
                    <p><strong>Finalna cena:</strong> ${finalPrice.toFixed(2)} RSD</p>
                </div>
            `;

            $form.find('.aihs-package-preview').html(productsHtml);
        },

        regeneratePackages: function(e) {
            e.preventDefault();

            if (!confirm('Da li ste sigurni da želite da regenerišete sve pakete? Postojeći paketi će biti obrisani.')) {
                return;
            }

            const $button = $(e.target);
            const originalText = $button.text();
            $button.prop('disabled', true).text('Regenerišem...');

            const data = {
                action: 'aihs_regenerate_all_packages',
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS_Admin.Utils.showNotice('Paketi su uspešno regenerisani!', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        AIHS_Admin.Utils.showNotice(response.data.message || 'Greška pri regenerisanju paketa.', 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                })
                .fail(function() {
                    AIHS_Admin.Utils.showNotice('Greška pri regenerisanju paketa.', 'error');
                    $button.prop('disabled', false).text(originalText);
                });
        }
    };

    /**
     * Reports & Analytics
     */
    AIHS_Admin.Reports = {
        charts: {},

        init: function() {
            this.bindEvents();
            this.loadCharts();
        },

        bindEvents: function() {
            $(document).on('change', '.aihs-report-filter', this.updateReports.bind(this));
            $(document).on('click', '.aihs-export-report', this.exportReport.bind(this));
        },

        loadCharts: function() {
            this.loadResponsesChart();
            this.loadScoreDistributionChart();
            this.loadPackageConversionChart();
        },

        loadResponsesChart: function() {
            const data = {
                action: 'aihs_get_responses_chart_data',
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success && $('#aihs-responses-chart').length) {
                        AIHS_Admin.Reports.renderResponsesChart(response.data);
                    }
                });
        },

        loadScoreDistributionChart: function() {
            const data = {
                action: 'aihs_get_score_distribution_data',
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success && $('#aihs-score-distribution-chart').length) {
                        AIHS_Admin.Reports.renderScoreDistributionChart(response.data);
                    }
                });
        },

        renderResponsesChart: function(data) {
            // Basic canvas-based chart rendering
            const canvas = document.getElementById('aihs-responses-chart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;

            // Clear canvas
            ctx.clearRect(0, 0, width, height);

            // Draw simple line chart
            this.drawLineChart(ctx, data, width, height);
        },

        drawLineChart: function(ctx, data, width, height) {
            const padding = 40;
            const chartWidth = width - 2 * padding;
            const chartHeight = height - 2 * padding;

            // Draw axes
            ctx.strokeStyle = '#ccc';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, height - padding);
            ctx.lineTo(width - padding, height - padding);
            ctx.stroke();

            // Draw data points
            if (data.length > 0) {
                const maxValue = Math.max(...data.map(d => d.value));
                const stepX = chartWidth / (data.length - 1);

                ctx.strokeStyle = '#0073aa';
                ctx.lineWidth = 2;
                ctx.beginPath();

                data.forEach((point, index) => {
                    const x = padding + index * stepX;
                    const y = height - padding - (point.value / maxValue) * chartHeight;

                    if (index === 0) {
                        ctx.moveTo(x, y);
                    } else {
                        ctx.lineTo(x, y);
                    }
                });

                ctx.stroke();
            }
        },

        exportReport: function(e) {
            e.preventDefault();

            const reportType = $(e.target).data('report-type');
            const format = $(e.target).data('format') || 'csv';

            const data = {
                action: 'aihs_export_report',
                report_type: reportType,
                format: format,
                nonce: aihsAdmin.nonce
            };

            // Create form and submit for download
            const $form = $('<form>', {
                method: 'POST',
                action: aihsAdmin.ajaxurl
            });

            $.each(data, function(key, value) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: value
                }));
            });

            $('body').append($form);
            $form.submit();
            $form.remove();
        }
    };

    /**
     * Settings management
     */
    AIHS_Admin.Settings = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.aihs-test-openai', this.testOpenAI.bind(this));
            $(document).on('click', '.aihs-reset-settings', this.resetSettings.bind(this));
            $(document).on('submit', '.aihs-settings-form', this.saveSettings.bind(this));
        },

        testOpenAI: function(e) {
            e.preventDefault();

            const apiKey = $('#aihs_openai_api_key').val();
            if (!apiKey) {
                alert('Molimo unesite OpenAI API ključ.');
                return;
            }

            const $button = $(e.target);
            const originalText = $button.text();
            $button.prop('disabled', true).text('Testiram...');

            const data = {
                action: 'aihs_test_openai_connection',
                api_key: apiKey,
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS_Admin.Utils.showNotice('OpenAI konekcija je uspešna!', 'success');
                    } else {
                        AIHS_Admin.Utils.showNotice(response.data.message || 'Greška pri testiranju OpenAI konekcije.', 'error');
                    }
                })
                .fail(function() {
                    AIHS_Admin.Utils.showNotice('Greška pri testiranju OpenAI konekcije.', 'error');
                })
                .always(function() {
                    $button.prop('disabled', false).text(originalText);
                });
        },

        resetSettings: function(e) {
            e.preventDefault();

            if (!confirm('Da li ste sigurni da želite da resetujete sve postavke na fabričke vrednosti?')) {
                return;
            }

            const data = {
                action: 'aihs_reset_settings',
                nonce: aihsAdmin.nonce
            };

            $.post(aihsAdmin.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        AIHS_Admin.Utils.showNotice('Postavke su resetovane.', 'success');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        AIHS_Admin.Utils.showNotice(response.data.message || 'Greška pri resetovanju postavki.', 'error');
                    }
                })
                .fail(function() {
                    AIHS_Admin.Utils.showNotice('Greška pri resetovanju postavki.', 'error');
                });
        },

        saveSettings: function(e) {
            e.preventDefault();

            const $form = $(e.target);
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.text();

            $button.prop('disabled', true).text('Čuvam...');

            const formData = new FormData($form[0]);
            formData.append('action', 'aihs_save_settings');
            formData.append('nonce', aihsAdmin.nonce);

            $.ajax({
                url: aihsAdmin.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        AIHS_Admin.Utils.showNotice('Postavke su sačuvane!', 'success');
                    } else {
                        AIHS_Admin.Utils.showNotice(response.data.message || 'Greška pri čuvanju postavki.', 'error');
                    }
                },
                error: function() {
                    AIHS_Admin.Utils.showNotice('Greška pri čuvanju postavki.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    /**
     * Utility functions
     */
    AIHS_Admin.Utils = {
        init: function() {
            this.bindGlobalEvents();
        },

        bindGlobalEvents: function() {
            // Modal close events
            $(document).on('click', '.aihs-modal-close, .aihs-modal-backdrop', function(e) {
                if (e.target === this) {
                    $(this).closest('.aihs-modal-backdrop').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });

            // Escape key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('.aihs-modal-backdrop').is(':visible')) {
                    $('.aihs-modal-backdrop').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });

            // Auto-hide notices
            setTimeout(function() {
                $('.aihs-notice').fadeOut();
            }, 5000);
        },

        showNotice: function(message, type, permanent) {
            type = type || 'info';
            permanent = permanent || false;

            const noticeClass = 'notice-' + type;
            const noticeHtml = `
                <div class="aihs-notice notice ${noticeClass} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Zatvori obaveštenje.</span>
                    </button>
                </div>
            `;

            $('.aihs-admin-container').prepend(noticeHtml);

            if (!permanent) {
                setTimeout(function() {
                    $('.aihs-notice').last().fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }

            // Scroll to top to show notice
            $('html, body').animate({ scrollTop: 0 }, 300);
        },

        showModal: function(title, content, buttons) {
            let buttonsHtml = '';
            if (buttons) {
                buttonsHtml = '<div class="aihs-modal-footer">';
                buttons.forEach(function(button) {
                    buttonsHtml += `<button class="aihs-btn ${button.class}" ${button.attributes || ''}>${button.text}</button>`;
                });
                buttonsHtml += '</div>';
            }

            const modalHtml = `
                <div class="aihs-modal-backdrop">
                    <div class="aihs-modal">
                        <div class="aihs-modal-header">
                            <h3 class="aihs-modal-title">${title}</h3>
                            <button class="aihs-modal-close">&times;</button>
                        </div>
                        <div class="aihs-modal-body">
                            ${content}
                        </div>
                        ${buttonsHtml}
                    </div>
                </div>
            `;

            $('body').append(modalHtml);
            $('.aihs-modal-backdrop').fadeIn(300);
        },

        closeModal: function() {
            $('.aihs-modal-backdrop').fadeOut(300, function() {
                $(this).remove();
            });
        },

        formatCurrency: function(amount, currency) {
            currency = currency || 'RSD';
            return new Intl.NumberFormat('sr-RS', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: 2
            }).format(amount);
        },

        formatDate: function(date) {
            return new Intl.DateTimeFormat('sr-RS', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date(date));
        }
    };

})(jQuery);