/**
 * Visual Form Builder with Drag & Drop
 * Replaces JSON textarea with a user-friendly interface
 */
class FormBuilder {
    constructor(containerId, initialData = null) {
        this.container = document.getElementById(containerId);
        this.fields = [];
        this.draggedElement = null;
        this.nextId = 1;
        
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
        
        this.render();
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
                
                <!-- Form Fields Area -->
                <div class="fields-area bg-white border-2 border-dashed border-gray-300 rounded-lg p-4 min-h-[300px]"
                     id="fields-drop-area">
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
        this.renderFields();
        this.updateJSON();
    }
    
    renderFields() {
        const fieldsList = document.getElementById('fields-list');
        if (!fieldsList) return;
        
        if (this.fields.length === 0) {
            fieldsList.innerHTML = `
                <div class="empty-state text-center py-12 text-gray-400">
                    <i class="fas fa-arrow-up text-4xl mb-3"></i>
                    <p class="text-sm">Arrastra campos aquí para construir tu formulario</p>
                </div>
            `;
            return;
        }
        
        fieldsList.innerHTML = this.fields.map((field, index) => `
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
        `).join('');
        
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
                this.updateFieldProperty(index, 'id', e.target.value);
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
