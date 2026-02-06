<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['name']) ?> - CRM Visas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-field-required:after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-8 px-4 shadow-lg">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl md:text-4xl font-bold mb-2"><?= htmlspecialchars($form['name']) ?></h1>
            <?php if (!empty($form['description'])): ?>
            <p class="text-blue-100"><?= htmlspecialchars($form['description']) ?></p>
            <?php endif; ?>
            <div class="mt-4 flex items-center space-x-4 text-sm">
                <span><i class="fas fa-file-alt mr-2"></i><?= htmlspecialchars($form['type']) ?></span>
                <?php if (!empty($form['subtype'])): ?>
                <span><i class="fas fa-tag mr-2"></i><?= htmlspecialchars($form['subtype']) ?></span>
                <?php endif; ?>
                <?php if ($form['cost'] > 0): ?>
                <span><i class="fas fa-dollar-sign mr-2"></i>$<?= number_format($form['cost'], 2) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Progress Bar (if pagination enabled) -->
        <?php if ($form['pagination_enabled'] && $pages): ?>
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Progreso del Formulario</h2>
                <span id="progress-text" class="text-sm font-semibold text-blue-600">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div id="progress-bar" class="bg-blue-600 h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div class="mt-3 text-sm text-gray-600">
                <span id="page-indicator">Página 1 de <?= count($pages) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Success Message -->
        <div id="success-message" class="hidden bg-green-50 border-l-4 border-green-500 p-6 mb-6 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 text-3xl mr-4"></i>
                <div>
                    <h3 class="text-lg font-bold text-green-800">¡Formulario Enviado Exitosamente!</h3>
                    <p class="text-green-700">Gracias por completar el formulario. Hemos recibido tu información y te contactaremos pronto.</p>
                </div>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-8">
            <form id="public-form" class="space-y-6">
                <input type="hidden" id="submission-id" name="submissionId" value="">
                <input type="hidden" id="current-page" name="currentPage" value="1">
                
                <?php foreach ($fields['fields'] as $field): ?>
                <div class="form-field" data-field-id="<?= htmlspecialchars($field['id']) ?>" data-page="<?php
                    // Find which page this field belongs to
                    if (!empty($form['pagination_enabled']) && !empty($pages)) {
                        foreach ($pages as $page) {
                            if (in_array($field['id'], $page['fieldIds'])) {
                                echo $page['id'];
                                break;
                            }
                        }
                    } else {
                        echo '1';
                    }
                ?>">
                    <label class="block text-sm font-medium text-gray-700 mb-2 <?= !empty($field['required']) ? 'form-field-required' : '' ?>">
                        <?= htmlspecialchars($field['label']) ?>
                    </label>
                    
                    <?php if ($field['type'] === 'text' || $field['type'] === 'email' || $field['type'] === 'tel'): ?>
                        <input type="<?= htmlspecialchars($field['type']) ?>" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="<?= htmlspecialchars($field['label']) ?>">
                    
                    <?php elseif ($field['type'] === 'number'): ?>
                        <input type="number" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    
                    <?php elseif ($field['type'] === 'date'): ?>
                        <input type="date" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= htmlspecialchars($field['id']) ?>"
                                  id="field_<?= htmlspecialchars($field['id']) ?>"
                                  <?= !empty($field['required']) ? 'required' : '' ?>
                                  rows="4"
                                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                  placeholder="<?= htmlspecialchars($field['label']) ?>"></textarea>
                    
                    <?php elseif ($field['type'] === 'select'): ?>
                        <select name="<?= htmlspecialchars($field['id']) ?>"
                                id="field_<?= htmlspecialchars($field['id']) ?>"
                                <?= !empty($field['required']) ? 'required' : '' ?>
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Seleccione...</option>
                            <?php foreach ($field['options'] ?? [] as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    
                    <?php elseif ($field['type'] === 'checkbox'): ?>
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="<?= htmlspecialchars($field['id']) ?>"
                                   id="field_<?= htmlspecialchars($field['id']) ?>"
                                   <?= !empty($field['required']) ? 'required' : '' ?>
                                   class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <label for="field_<?= htmlspecialchars($field['id']) ?>" class="ml-2 text-sm text-gray-700">
                                <?= htmlspecialchars($field['label']) ?>
                            </label>
                        </div>
                    
                    <?php elseif ($field['type'] === 'file'): ?>
                        <input type="file" 
                               name="<?= htmlspecialchars($field['id']) ?>"
                               id="field_<?= htmlspecialchars($field['id']) ?>"
                               <?= !empty($field['required']) ? 'required' : '' ?>
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle"></i> Formatos: PDF, JPG, PNG, DOC, DOCX (Máx. 10MB)
                        </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Auto-save status -->
                <div id="autosave-status" class="text-sm text-gray-500 text-center hidden">
                    <i class="fas fa-cloud-upload-alt mr-1"></i>
                    <span id="autosave-text">Guardando...</span>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 pt-6 border-t">
                    <div class="flex gap-4">
                        <button type="button" id="prev-page-btn" 
                                class="hidden px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-arrow-left mr-2"></i>Anterior
                        </button>
                        
                        <button type="button" id="save-draft-btn" 
                                class="w-full md:w-auto px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            <i class="fas fa-save mr-2"></i>Guardar Borrador
                        </button>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="button" id="next-page-btn"
                                class="hidden px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-arrow-right mr-2"></i>Siguiente
                        </button>
                        
                        <button type="submit" id="submit-btn"
                                class="w-full md:w-auto px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Formulario
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- PayPal Payment Section (if enabled and cost > 0) -->
        <?php if ($form['paypal_enabled'] && $form['cost'] > 0): ?>
        <div class="bg-white rounded-lg shadow-lg p-6 md:p-8 mt-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-credit-card text-blue-600 mr-2"></i>Información de Pago
            </h3>
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                <p class="text-blue-800">
                    <strong>Costo del servicio:</strong> $<?= number_format($form['cost'], 2) ?> MXN
                </p>
                <p class="text-sm text-blue-700 mt-2">
                    Después de enviar el formulario, recibirás un enlace de pago de PayPal en tu correo electrónico.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer Info -->
        <div class="text-center mt-8 text-sm text-gray-600">
            <p><i class="fas fa-lock mr-1"></i>Tus datos están protegidos y serán utilizados únicamente para procesar tu solicitud</p>
            <p class="mt-2">Creado por: <?= htmlspecialchars($form['creator_name']) ?> | <?= htmlspecialchars($form['creator_email']) ?></p>
        </div>
    </div>

    <script>
        const form = document.getElementById('public-form');
        const submitBtn = document.getElementById('submit-btn');
        const saveDraftBtn = document.getElementById('save-draft-btn');
        const prevPageBtn = document.getElementById('prev-page-btn');
        const nextPageBtn = document.getElementById('next-page-btn');
        const autosaveStatus = document.getElementById('autosave-status');
        const autosaveText = document.getElementById('autosave-text');
        const successMessage = document.getElementById('success-message');
        const submissionIdInput = document.getElementById('submission-id');
        const currentPageInput = document.getElementById('current-page');
        
        // Configuration
        const AUTOSAVE_DELAY_MS = 3000; // Auto-save after 3 seconds of no input
        const paginationEnabled = <?= json_encode($form['pagination_enabled'] ?? false) ?>;
        const pages = <?= json_encode($pages ?? []) ?>;
        const totalPages = pages.length || 1;
        
        let currentPage = 1;
        let autosaveTimeout;
        
        // Initialize pagination
        if (paginationEnabled && pages.length > 0) {
            initializePagination();
            showPage(1);
        }
        
        // Auto-save on input change
        form.addEventListener('input', function() {
            clearTimeout(autosaveTimeout);
            autosaveTimeout = setTimeout(autoSave, AUTOSAVE_DELAY_MS);
        });
        
        // Save draft manually
        saveDraftBtn.addEventListener('click', function() {
            saveForm(false);
        });
        
        // Submit form
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveForm(true);
        });
        
        // Previous page button
        prevPageBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                saveForm(false, false, () => {
                    showPage(currentPage - 1);
                });
            }
        });
        
        // Next page button
        nextPageBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                saveForm(false, false, () => {
                    showPage(currentPage + 1);
                });
            }
        });
        
        function initializePagination() {
            // No additional initialization needed for now
        }
        
        function showPage(pageNum) {
            currentPage = pageNum;
            currentPageInput.value = pageNum;
            
            // Hide all fields first
            const allFields = document.querySelectorAll('.form-field');
            allFields.forEach(field => {
                field.style.display = 'none';
            });
            
            // Show fields for current page
            const currentPageData = pages.find(p => p.id === pageNum);
            if (currentPageData) {
                currentPageData.fieldIds.forEach(fieldId => {
                    const fieldElement = document.querySelector(`.form-field[data-field-id="${fieldId}"]`);
                    if (fieldElement) {
                        fieldElement.style.display = 'block';
                    }
                });
            }
            
            // Update page indicator
            const pageIndicator = document.getElementById('page-indicator');
            if (pageIndicator) {
                pageIndicator.textContent = `Página ${pageNum} de ${totalPages}`;
            }
            
            // Update button visibility
            updateNavigationButtons();
            
            // Calculate and update progress
            calculateProgress();
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function updateNavigationButtons() {
            // Previous button
            if (currentPage > 1) {
                prevPageBtn.classList.remove('hidden');
            } else {
                prevPageBtn.classList.add('hidden');
            }
            
            // Next button and Submit button
            if (currentPage < totalPages) {
                nextPageBtn.classList.remove('hidden');
                submitBtn.classList.add('hidden');
                saveDraftBtn.classList.add('hidden');
            } else {
                nextPageBtn.classList.add('hidden');
                submitBtn.classList.remove('hidden');
                saveDraftBtn.classList.remove('hidden');
            }
        }
        
        function calculateProgress() {
            if (!paginationEnabled || pages.length === 0) return;
            
            // Get all fields across all pages
            const allFieldIds = [];
            pages.forEach(page => {
                allFieldIds.push(...page.fieldIds);
            });
            
            // Count filled fields
            let filledCount = 0;
            allFieldIds.forEach(fieldId => {
                const field = document.getElementById(`field_${fieldId}`);
                if (field) {
                    const value = field.type === 'checkbox' ? field.checked : field.value;
                    if (value) {
                        filledCount++;
                    }
                }
            });
            
            const percentage = allFieldIds.length > 0 ? (filledCount / allFieldIds.length) * 100 : 0;
            updateProgress(percentage);
        }
        
        function autoSave() {
            saveForm(false, true);
        }
        
        function saveForm(isCompleted = false, isAutoSave = false, callback = null) {
            if (!isAutoSave) {
                submitBtn.disabled = true;
                saveDraftBtn.disabled = true;
            }
            
            autosaveStatus.classList.remove('hidden');
            autosaveText.textContent = isCompleted ? 'Enviando...' : 'Guardando...';
            
            const formData = new FormData(form);
            const data = {};
            
            for (let [key, value] of formData.entries()) {
                if (key !== 'submissionId' && key !== 'currentPage') {
                    data[key] = value;
                }
            }
            
            const payload = new FormData();
            payload.append('formData', JSON.stringify(data));
            payload.append('currentPage', document.getElementById('current-page').value);
            payload.append('isCompleted', isCompleted);
            
            if (submissionIdInput.value) {
                payload.append('submissionId', submissionIdInput.value);
            }
            
            fetch('<?= BASE_URL ?>/public/form/<?= $token ?>/submit', {
                method: 'POST',
                body: payload
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    if (result.submissionId && !submissionIdInput.value) {
                        submissionIdInput.value = result.submissionId;
                    }
                    
                    if (isCompleted) {
                        form.style.display = 'none';
                        successMessage.classList.remove('hidden');
                        window.scrollTo(0, 0);
                    } else {
                        autosaveText.textContent = '✓ Guardado';
                        setTimeout(() => {
                            autosaveStatus.classList.add('hidden');
                        }, 2000);
                    }
                    
                    // Update progress if available
                    if (result.progressPercentage) {
                        updateProgress(result.progressPercentage);
                    } else if (paginationEnabled) {
                        calculateProgress();
                    }
                    
                    // Execute callback if provided
                    if (callback) {
                        callback();
                    }
                } else {
                    alert('Error: ' + (result.error || 'No se pudo guardar el formulario'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el formulario. Por favor, intenta de nuevo.');
            })
            .finally(() => {
                if (!isAutoSave) {
                    submitBtn.disabled = false;
                    saveDraftBtn.disabled = false;
                }
            });
        }
        
        function updateProgress(percentage) {
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            
            if (progressBar && progressText) {
                progressBar.style.width = percentage + '%';
                progressText.textContent = Math.round(percentage) + '%';
            }
        }
    </script>
</body>
</html>
