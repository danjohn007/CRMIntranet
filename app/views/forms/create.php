<?php 
$title = 'Crear Formulario';
ob_start(); 
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="<?= BASE_URL ?>/formularios" class="text-primary hover:underline">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <h2 class="text-3xl font-bold text-gray-800">Crear Formulario</h2>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/formularios/guardar">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre del Formulario <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Visa Americana - Primera Vez">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                          placeholder="Descripción opcional del formulario"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select name="type" id="formTypeSelect" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Seleccione...</option>
                    <option value="Visa">Visa</option>
                    <option value="Pasaporte">Pasaporte</option>
                </select>
            </div>

            <div id="visaCategoryWrapper" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo de visa</label>
                <select id="visaCategorySelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="Americana">Americana</option>
                    <option value="Canadiense">Canadiense</option>
                </select>
            </div>

            <div id="visaSubtypeSelectWrapper" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de trámite</label>
                <select id="visaSubtypeSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"></select>
            </div>
            
            <div id="passportCategoryWrapper" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo de pasaporte</label>
                <select id="passportCategorySelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="Americano">Americano</option>
                    <option value="Mexicano">Mexicano</option>
                </select>
            </div>

            <div id="passportSubtypeSelectWrapper" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <select id="passportSubtypeSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"></select>
            </div>

            <div id="genericSubtypeInputWrapper">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <input type="text" id="genericSubtypeInput"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Primera vez, Renovación, etc.">
            </div>

            <input type="hidden" name="subtype" id="subtypeValue" value="">
            
            <!-- Cost Section -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Costo del Servicio <span class="text-gray-400">(Opcional)</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">$</span>
                    <input type="number" name="cost" step="0.01" min="0" value="0.00"
                           class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2 focus:ring-2 focus:ring-blue-500"
                           placeholder="0.00">
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    <i class="fas fa-info-circle"></i> Deja en 0 si no aplica
                </p>
            </div>
            
            <!-- Pagination Section -->
            <div class="md:col-span-2 border-t pt-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Insertar Paginación
                        </label>
                        <p class="text-xs text-gray-500">
                            Divide el formulario en secciones para guardar el avance
                        </p>
                    </div>
                    <input type="checkbox" name="pagination_enabled" id="pagination_enabled" value="1"
                           class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                </div>
                
                <div id="pagination-config" style="display: none;" class="bg-gray-50 rounded-lg p-4 mt-3">
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-layer-group mr-1"></i> 
                        Al habilitar paginación, podrás dividir tus campos en secciones. 
                        Los solicitantes podrán guardar su progreso y continuar después.
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 text-sm text-blue-700">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Tip:</strong> Después de crear el formulario, podrás editar las páginas 
                        y asignar campos a cada sección.
                    </div>
                </div>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Campos del Formulario <span class="text-red-500">*</span>
                </label>
                
                <!-- Visual Form Builder -->
                <div id="form-builder-container" data-initial-data=""></div>
                
                <!-- Hidden field to store JSON -->
                <input type="hidden" name="fields_json" id="fields_json_hidden" required>
                
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> Arrastra y suelta campos para construir tu formulario
                </p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-4">
            <a href="<?= BASE_URL ?>/formularios" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90">
                <i class="fas fa-save mr-2"></i>Guardar Formulario
            </button>
        </div>
    </form>
</div>

<script src="<?= BASE_URL ?>/js/form-builder.js"></script>
<script>
// Toggle pagination configuration visibility
document.getElementById('pagination_enabled').addEventListener('change', function() {
    const paginationConfig = document.getElementById('pagination-config');
    paginationConfig.style.display = this.checked ? 'block' : 'none';
});

(function() {
    const typeSelect = document.getElementById('formTypeSelect');
    const visaCategoryWrapper = document.getElementById('visaCategoryWrapper');
    const visaCategorySelect = document.getElementById('visaCategorySelect');
    const visaSubtypeSelectWrapper = document.getElementById('visaSubtypeSelectWrapper');
    const visaSubtypeSelect = document.getElementById('visaSubtypeSelect');
    const passportCategoryWrapper = document.getElementById('passportCategoryWrapper');
    const passportCategorySelect = document.getElementById('passportCategorySelect');
    const passportSubtypeSelectWrapper = document.getElementById('passportSubtypeSelectWrapper');
    const passportSubtypeSelect = document.getElementById('passportSubtypeSelect');
    const genericSubtypeInputWrapper = document.getElementById('genericSubtypeInputWrapper');
    const genericSubtypeInput = document.getElementById('genericSubtypeInput');
    const subtypeValue = document.getElementById('subtypeValue');
    const formEl = typeSelect ? typeSelect.closest('form') : null;

    const passportSubtypeOptions = {
        Americano: ['Primera vez', 'Renovación', 'Menor de edad', 'Reposición por robo', 'Pasaporte dañado'],
        Mexicano: ['Primera vez', 'Renovación', 'Menor de edad', 'Robo/ extravío', 'Corrección de Datos']
    };

    const visaSubtypeOptions = {
        Americana: ['PRIMERA VEZ - SIN TRASLADO', 'PRIMERA VEZ - CON TRASLADO', 'RENOVACIÓN - CON TRASLADO'],
        Canadiense: ['PRIMERA VEZ - SIN TRASLADO', 'PRIMERA VEZ - CON TRASLADO', 'RENOVACIÓN - CON TRASLADO']
    };

    function renderVisaSubtypeOptions() {
        if (!visaSubtypeSelect || !visaCategorySelect) {
            return;
        }

        const category = visaCategorySelect.value || 'Americana';
        const options = visaSubtypeOptions[category] || [];
        const previousValue = visaSubtypeSelect.value;

        visaSubtypeSelect.innerHTML = '<option value="">Seleccione...</option>';
        options.forEach(function(option) {
            const optionEl = document.createElement('option');
            optionEl.value = option;
            optionEl.textContent = option;
            visaSubtypeSelect.appendChild(optionEl);
        });

        if (options.indexOf(previousValue) !== -1) {
            visaSubtypeSelect.value = previousValue;
        }
    }

    function renderPassportSubtypeOptions() {
        if (!passportSubtypeSelect || !passportCategorySelect) {
            return;
        }

        const category = passportCategorySelect.value || 'Americano';
        const options = passportSubtypeOptions[category] || [];
        const previousValue = passportSubtypeSelect.value;

        passportSubtypeSelect.innerHTML = '<option value="">Seleccione...</option>';
        options.forEach(function(option) {
            const optionEl = document.createElement('option');
            optionEl.value = option;
            optionEl.textContent = option;
            passportSubtypeSelect.appendChild(optionEl);
        });

        if (options.indexOf(previousValue) !== -1) {
            passportSubtypeSelect.value = previousValue;
        }
    }

    function syncSubtypeValue() {
        if (!subtypeValue) {
            return;
        }

        const isPassport = typeSelect && typeSelect.value === 'Pasaporte';
        const isVisa = typeSelect && typeSelect.value === 'Visa';
        if (isPassport) {
            const category = passportCategorySelect ? passportCategorySelect.value : '';
            const subtype = passportSubtypeSelect ? passportSubtypeSelect.value : '';
            subtypeValue.value = subtype ? (category + ' - ' + subtype) : '';
            return;
        }

        if (isVisa) {
            const category = visaCategorySelect ? visaCategorySelect.value : '';
            const subtype = visaSubtypeSelect ? visaSubtypeSelect.value : '';
            subtypeValue.value = subtype ? (category + ' - ' + subtype) : '';
            return;
        }

        subtypeValue.value = genericSubtypeInput ? genericSubtypeInput.value.trim() : '';
    }

    function syncSubtypeField() {
        const isPassport = typeSelect && typeSelect.value === 'Pasaporte';
        const isVisa = typeSelect && typeSelect.value === 'Visa';

        if (visaCategoryWrapper) {
            visaCategoryWrapper.classList.toggle('hidden', !isVisa);
        }

        if (visaSubtypeSelectWrapper) {
            visaSubtypeSelectWrapper.classList.toggle('hidden', !isVisa);
        }

        if (passportCategoryWrapper) {
            passportCategoryWrapper.classList.toggle('hidden', !isPassport);
        }

        if (passportSubtypeSelectWrapper) {
            passportSubtypeSelectWrapper.classList.toggle('hidden', !isPassport);
        }
        if (genericSubtypeInputWrapper) {
            genericSubtypeInputWrapper.classList.toggle('hidden', isPassport);
        }

        if (passportSubtypeSelect) {
            passportSubtypeSelect.required = isPassport;
            if (isPassport) {
                renderPassportSubtypeOptions();
            } else {
                passportSubtypeSelect.value = '';
            }
        }

        if (visaSubtypeSelect) {
            visaSubtypeSelect.required = isVisa;
            if (isVisa) {
                renderVisaSubtypeOptions();
            } else {
                visaSubtypeSelect.value = '';
            }
        }

        if (visaCategorySelect && !isVisa) {
            visaCategorySelect.value = 'Americana';
        }

        if (genericSubtypeInput) {
            genericSubtypeInput.required = !isPassport && !isVisa;
            if (isPassport || isVisa) {
                genericSubtypeInput.value = '';
            } else {
                if (passportSubtypeSelect) {
                    passportSubtypeSelect.value = '';
                }
                if (visaSubtypeSelect) {
                    visaSubtypeSelect.value = '';
                }
            }
        }

        if (genericSubtypeInputWrapper) {
            genericSubtypeInputWrapper.classList.toggle('hidden', isPassport || isVisa);
        }

        syncSubtypeValue();
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', syncSubtypeField);
        if (passportCategorySelect) {
            passportCategorySelect.addEventListener('change', function() {
                renderPassportSubtypeOptions();
                syncSubtypeValue();
            });
        }
        if (visaCategorySelect) {
            visaCategorySelect.addEventListener('change', function() {
                renderVisaSubtypeOptions();
                syncSubtypeValue();
            });
        }
        if (passportSubtypeSelect) {
            passportSubtypeSelect.addEventListener('change', syncSubtypeValue);
        }
        if (visaSubtypeSelect) {
            visaSubtypeSelect.addEventListener('change', syncSubtypeValue);
        }
        if (genericSubtypeInput) {
            genericSubtypeInput.addEventListener('input', syncSubtypeValue);
        }
        if (formEl) {
            formEl.addEventListener('submit', syncSubtypeValue);
        }
        syncSubtypeField();
    }
})();
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
