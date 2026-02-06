/**
 * Visual Form Builder with Drag & Drop
 * Replaces JSON textarea with a user-friendly interface
 */
class FormBuilder {
    constructor(containerId, initialData = null) {
        this.container = document.getElementById(containerId);
        this.fields = [];
        this.pages = [{ id: 1, name: 'Página 1', fieldIds: [] }];
        this.currentPage = 1;
        this.paginationEnabled = false;
        this.draggedElement = null;
        this.nextId = 1;
        this.nextPageId = 2;
        
        // Field types available
        this.fieldTypes = [
            { id: 'text', label: 'Texto', icon: 'fa-font' },
            { id: 'email', label: 'Email', icon: 'fa-envelope' },
            { id: 'tel', label: 'Teléfono', icon: 'fa-phone' },
            { id: 'number', label: 'Número', icon: 'fa-hashtag' },
            { id: 'date', label: 'Fecha', icon: 'fa-calendar' },
            { id: 'select', label: 'Selección', icon: 'fa-list' },
            { id: 'textarea', label: 'Área de Texto', icon: 'fa-align-left' },
            { id: 'checkbox', label: 'Casilla', icon: 'fa-check-square' },
            { id: 'file', label: 'Archivo', icon: 'fa-file-upload' }
        ];
        
        // Parse initial data if provided
        if (initialData) {
            try {
                const parsed = typeof initialData === 'string' ? JSON.parse(initialData) : initialData;
                if (parsed && parsed.fields && Array.isArray(parsed.fields)) {
                    this.fields = parsed.fields;
                    this.nextId = this.fields.length + 1;
                }
            } catch (e) {
                console.error('Error parsing initial data:', e);
            }
        }
        
        // Check if pagination is enabled
        this.checkPaginationEnabled();
        
        this.render();
    }
    
    checkPaginationEnabled() {
        const paginationCheckbox = document.getElementById('pagination_enabled');
        if (paginationCheckbox) {
            this.paginationEnabled = paginationCheckbox.checked;
            paginationCheckbox.addEventListener('change', (e) => {
                this.paginationEnabled = e.target.checked;
                this.render();
                this.updateJSON();
            });
        }
    }
    
    render() {
        this.container.innerHTML = `
            <div class="form-builder">
                <!-- Field Types Palette -->
                <div class="field-palette bg-gray-50 p-4 rounded-lg border-2 border-gray-200 mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">
                        <i class="fas fa-tools mr-2"></i>Tipos de Campo
                    </h3>
                    <div class="grid grid-cols-3 gap-2">
                        ${this.fieldTypes.map(type => `
                            <button type="button" 
                                    class="field-type-btn bg-white border-2 border-gray-300 rounded-lg p-3 hover:border-blue-500 hover:bg-blue-50 transition cursor-move text-center"
                                    data-field-type="${type.id}"
                                    draggable="true">
                                <i class="fas ${type.icon} text-xl text-gray-600 mb-1"></i>
                                <div class="text-xs font-medium text-gray-700">${type.label}</div>
                            </button>
                        `).join('')}
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        <i class="fas fa-info-circle"></i> Arrastra un tipo de campo hacia el área de construcción
                    </p>
                </div>
                
                <!-- Page Management (shown only when pagination is enabled) -->
                ${this.paginationEnabled ? `
                <div class="page-management bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-blue-900">
                            <i class="fas fa-layer-group mr-2"></i>Gestión de Páginas
                        </h3>
                        <button type="button" id="add-page-btn" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                            <i class="fas fa-plus mr-1"></i>Agregar Página
                        </button>
                    </div>
                    <div class="flex space-x-2 overflow-x-auto pb-2" id="pages-tabs">
                        ${this.pages.map(page => `
                            <button type="button" 
                                    class="page-tab px-4 py-2 rounded text-sm whitespace-nowrap ${page.id === this.currentPage ? 'bg-blue-600 text-white' : 'bg-white text-blue-800 border border-blue-300'}"
                                    data-page-id="${page.id}">
                                <i class="fas fa-file-alt mr-1"></i>${page.name} (${page.fieldIds.length})
                            </button>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                <!-- Form Fields Area -->
                <div class="fields-area bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[300px]"
                     id="fields-drop-area">
                    ${this.paginationEnabled ? `
                        <div class="text-sm text-blue-700 mb-3 flex items-center justify-between">
                            <span><i class="fas fa-info-circle mr-1"></i>Mostrando campos de: <strong>${this.getPageName(this.currentPage)}</strong></span>
                            <button type="button" id="show-all-fields-btn" class="text-blue-600 hover:underline text-xs">
                                Ver todos los campos
                            </button>
                        </div>
                    ` : ''}
                    <div class="fields-list" id="fields-list">
                        ${this.fields.length === 0 ? `
                            <div class="empty-state text-center py-12 text-gray-400">
                                <i class="fas fa-arrow-up text-4xl mb-3"></i>
                                <p class="text-sm">Arrastra campos aquí para construir tu formulario</p>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        this.attachEventListeners();
        this.renderFields();
    }
    
    getPageName(pageId) {
        const page = this.pages.find(p => p.id === pageId);
        return page ? page.name : 'Página desconocida';
    }
    
    addPage() {
        const newPage = {
            id: this.nextPageId++,
            name: `Página ${this.pages.length + 1}`,
            fieldIds: []
        };
        this.pages.push(newPage);
        this.currentPage = newPage.id;
        this.render();
        this.updateJSON();
    }
    
    isShowingAllPages() {
        return !this.paginationEnabled || this.currentPage === 0;
    }
    
    switchPage(pageId) {
        this.currentPage = pageId;
        this.renderFields();
        // Update tabs styling
        document.querySelectorAll('.page-tab').forEach(tab => {
            const tabPageId = parseInt(tab.dataset.pageId);
            if (tabPageId === pageId) {
                tab.className = 'page-tab px-4 py-2 rounded text-sm whitespace-nowrap bg-blue-600 text-white';
            } else {
                tab.className = 'page-tab px-4 py-2 rounded text-sm whitespace-nowrap bg-white text-blue-800 border border-blue-300';
            }
        });
    }
    
    attachEventListeners() {
        // Drag start for field types
        const fieldTypeBtns = this.container.querySelectorAll('.field-type-btn');
        fieldTypeBtns.forEach(btn => {
            btn.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('fieldType', btn.dataset.fieldType);
                btn.style.opacity = '0.5';
            });
            
            btn.addEventListener('dragend', (e) => {
                btn.style.opacity = '1';
            });
            
            // Click to add field (mobile friendly)
            btn.addEventListener('click', (e) => {
                this.addField(btn.dataset.fieldType);
            });
        });
        
        // Drop area events
        const dropArea = document.getElementById('fields-drop-area');
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.classList.add('border-blue-500', 'bg-blue-50');
        });
        
        dropArea.addEventListener('dragleave', (e) => {
            dropArea.classList.remove('border-blue-500', 'bg-blue-50');
        });
        
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.classList.remove('border-blue-500', 'bg-blue-50');
            
            const fieldType = e.dataTransfer.getData('fieldType');
            if (fieldType) {
                this.addField(fieldType);
            }
        });
        
        // Page management buttons
        if (this.paginationEnabled) {
            const addPageBtn = document.getElementById('add-page-btn');
            if (addPageBtn) {
                addPageBtn.addEventListener('click', () => this.addPage());
            }
            
            const pageTabs = document.querySelectorAll('.page-tab');
            pageTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const pageId = parseInt(tab.dataset.pageId);
                    this.switchPage(pageId);
                });
            });
            
            const showAllBtn = document.getElementById('show-all-fields-btn');
            if (showAllBtn) {
                showAllBtn.addEventListener('click', () => {
                    this.currentPage = 0; // Use 0 to indicate "show all"
                    this.renderFields();
                });
            }
        }
    }
    
    addField(type) {
        const fieldType = this.fieldTypes.find(ft => ft.id === type);
        if (!fieldType) return;
        
        const newField = {
            id: `campo_${this.nextId++}`,
            type: type,
            label: fieldType.label,
            required: false
        };
        
        // Add options for select fields
        if (type === 'select') {
            newField.options = ['Opción 1', 'Opción 2'];
        }
        
        this.fields.push(newField);
        
        // If pagination is enabled, add field to current page
        if (this.paginationEnabled && this.currentPage) {
            const page = this.pages.find(p => p.id === this.currentPage);
            if (page) {
                page.fieldIds.push(newField.id);
            }
        }
        
        this.renderFields();
        this.updateJSON();
    }
    
    renderFields() {
        const fieldsList = document.getElementById('fields-list');
        if (!fieldsList) return;
        
        // Determine which fields to show
        let fieldsToShow = this.fields;
        if (!this.isShowingAllPages()) {
            const page = this.pages.find(p => p.id === this.currentPage);
            if (page) {
                fieldsToShow = this.fields.filter(f => page.fieldIds.includes(f.id));
            }
        }
        
        if (fieldsToShow.length === 0) {
            fieldsList.innerHTML = `
                <div class="empty-state text-center py-12 text-gray-400">
                    <i class="fas fa-arrow-up text-4xl mb-3"></i>
                    <p class="text-sm">${!this.isShowingAllPages() ? 'Arrastra campos aquí para agregarlos a esta página' : 'Arrastra campos aquí para construir tu formulario'}</p>
                </div>
            `;
            return;
        }
        
        fieldsList.innerHTML = fieldsToShow.map((field) => {
            const index = this.fields.indexOf(field);
            return `
            <div class="field-item bg-gray-50 border border-gray-300 rounded-lg p-4 mb-3" data-index="${index}">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center flex-1">
                        <i class="fas fa-grip-vertical text-gray-400 mr-3 cursor-move"></i>
                        <div>
                            <div class="font-semibold text-gray-800">${field.label}</div>
                            <div class="text-xs text-gray-500">ID: ${field.id} | Tipo: ${field.type}</div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        ${this.paginationEnabled ? `
                            <select class="field-page-select text-xs border border-gray-300 rounded px-2 py-1" 
                                    data-field-id="${field.id}"
                                    aria-label="Asignar campo a página">
                                ${this.pages.map(page => `
                                    <option value="${page.id}" ${page.fieldIds.includes(field.id) ? 'selected' : ''}>
                                        ${page.name}
                                    </option>
                                `).join('')}
                            </select>
                        ` : ''}
                        <button type="button" class="btn-delete-field text-red-600 hover:text-red-800" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Etiqueta</label>
                        <input type="text" value="${field.label}" 
                               class="field-label-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                               data-index="${index}">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">ID del campo</label>
                        <input type="text" value="${field.id}" 
                               class="field-id-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                               data-index="${index}">
                    </div>
                    ${field.type === 'select' ? `
                        <div class="col-span-2">
                            <label class="block text-xs text-gray-600 mb-1">Opciones (separadas por coma)</label>
                            <input type="text" value="${(field.options || []).join(', ')}" 
                                   class="field-options-input w-full border border-gray-300 rounded px-2 py-1 text-sm"
                                   data-index="${index}">
                        </div>
                    ` : ''}
                    <div class="col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" ${field.required ? 'checked' : ''} 
                                   class="field-required-input mr-2"
                                   data-index="${index}">
                            <span class="text-xs text-gray-700">Campo obligatorio</span>
                        </label>
                    </div>
                </div>
            </div>
        `}).join('');
        
        // Attach event listeners after rendering
        this.attachFieldEventListeners();
    }
    
    attachFieldEventListeners() {
        // Label inputs
        document.querySelectorAll('.field-label-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.updateFieldProperty(index, 'label', e.target.value);
            });
        });
        
        // ID inputs
        document.querySelectorAll('.field-id-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const oldId = this.fields[index].id;
                const newId = e.target.value.trim();
                
                // Validate for duplicate IDs
                const isDuplicate = this.fields.some((field, idx) => 
                    idx !== index && field.id === newId
                );
                
                if (isDuplicate) {
                    alert('Ya existe un campo con ese ID. Por favor, elige un ID único.');
                    e.target.value = oldId; // Reset to old value
                    return;
                }
                
                if (!newId) {
                    alert('El ID no puede estar vacío.');
                    e.target.value = oldId; // Reset to old value
                    return;
                }
                
                // Update field ID in pages
                if (this.paginationEnabled) {
                    this.pages.forEach(page => {
                        const idIndex = page.fieldIds.indexOf(oldId);
                        if (idIndex !== -1) {
                            page.fieldIds[idIndex] = newId;
                        }
                    });
                }
                
                this.updateFieldProperty(index, 'id', newId);
            });
        });
        
        // Options inputs
        document.querySelectorAll('.field-options-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                const options = e.target.value.split(',').map(o => o.trim());
                this.updateFieldProperty(index, 'options', options);
            });
        });
        
        // Required checkboxes
        document.querySelectorAll('.field-required-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.updateFieldProperty(index, 'required', e.target.checked);
            });
        });
        
        // Page assignment dropdown
        document.querySelectorAll('.field-page-select').forEach(select => {
            select.addEventListener('change', (e) => {
                const fieldId = e.target.dataset.fieldId;
                const newPageId = parseInt(e.target.value);
                
                // Remove field from all pages
                this.pages.forEach(page => {
                    page.fieldIds = page.fieldIds.filter(id => id !== fieldId);
                });
                
                // Add field to selected page
                const targetPage = this.pages.find(p => p.id === newPageId);
                if (targetPage && !targetPage.fieldIds.includes(fieldId)) {
                    targetPage.fieldIds.push(fieldId);
                }
                
                this.updateJSON();
                // Update page tabs to show field counts
                this.render();
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.btn-delete-field').forEach(button => {
            button.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.index);
                this.deleteField(index);
            });
        });
    }
    
    updateFieldProperty(index, property, value) {
        if (this.fields[index]) {
            this.fields[index][property] = value;
            this.updateJSON();
        }
    }
    
    deleteField(index) {
        if (confirm('¿Estás seguro de eliminar este campo?')) {
            const fieldId = this.fields[index].id;
            
            // Remove from pages if pagination enabled
            if (this.paginationEnabled) {
                this.pages.forEach(page => {
                    page.fieldIds = page.fieldIds.filter(id => id !== fieldId);
                });
            }
            
            this.fields.splice(index, 1);
            this.renderFields();
            this.updateJSON();
        }
    }
    
    updateJSON() {
        const jsonOutput = {
            fields: this.fields
        };
        
        // Update hidden field with JSON
        const hiddenField = document.getElementById('fields_json_hidden');
        if (hiddenField) {
            hiddenField.value = JSON.stringify(jsonOutput);
        }
        
        // Also save pages JSON to a separate hidden field
        if (this.paginationEnabled) {
            let pagesInput = document.getElementById('pages_json_hidden');
            if (!pagesInput) {
                pagesInput = document.createElement('input');
                pagesInput.type = 'hidden';
                pagesInput.id = 'pages_json_hidden';
                pagesInput.name = 'pages_json';
                hiddenField.parentNode.appendChild(pagesInput);
            }
            pagesInput.value = JSON.stringify(this.pages);
        }
        
        // Dispatch event for other components
        const event = new CustomEvent('formbuilder:update', { detail: jsonOutput });
        document.dispatchEvent(event);
    }
    
    getJSON() {
        return JSON.stringify({ fields: this.fields }, null, 2);
    }
    
    getData() {
        return { fields: this.fields };
    }
}

// Global instance
let formBuilder;

// Initialize form builder when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const builderContainer = document.getElementById('form-builder-container');
    if (builderContainer) {
        const initialData = builderContainer.dataset.initialData;
        formBuilder = new FormBuilder('form-builder-container', initialData);
    }
});
