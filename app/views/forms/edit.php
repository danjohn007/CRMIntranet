<?php 
$title = 'Editar Formulario';
ob_start(); 

$currentSubtypeRaw = trim((string) ($form['subtype'] ?? ''));
$visaCategoryCurrent = 'Americana';
$visaSubtypeCurrent = $currentSubtypeRaw;

if (stripos($currentSubtypeRaw, 'canadiense - ') === 0) {
    $visaCategoryCurrent = 'Canadiense';
    $visaSubtypeCurrent = trim(substr($currentSubtypeRaw, strlen('Canadiense - ')));
} elseif (stripos($currentSubtypeRaw, 'americana - ') === 0) {
    $visaCategoryCurrent = 'Americana';
    $visaSubtypeCurrent = trim(substr($currentSubtypeRaw, strlen('Americana - ')));
} elseif (stripos($currentSubtypeRaw, 'canadiense') !== false) {
    $visaCategoryCurrent = 'Canadiense';
    $visaSubtypeCurrent = trim(str_ireplace('canadiense', '', $currentSubtypeRaw));
    $visaSubtypeCurrent = trim($visaSubtypeCurrent, " -");
} elseif (stripos($currentSubtypeRaw, 'americana') !== false) {
    $visaCategoryCurrent = 'Americana';
    $visaSubtypeCurrent = trim(str_ireplace('americana', '', $currentSubtypeRaw));
    $visaSubtypeCurrent = trim($visaSubtypeCurrent, " -");
}

if ($visaSubtypeCurrent === '') {
    $visaSubtypeCurrent = 'PRIMERA VEZ - SIN TRASLADO';
}

$passportCategoryCurrent = 'Americano';
$passportSubtypeCurrent = $currentSubtypeRaw;

if (stripos($currentSubtypeRaw, 'mexicano - ') === 0) {
    $passportCategoryCurrent = 'Mexicano';
    $passportSubtypeCurrent = trim(substr($currentSubtypeRaw, strlen('Mexicano - ')));
} elseif (stripos($currentSubtypeRaw, 'americano - ') === 0) {
    $passportCategoryCurrent = 'Americano';
    $passportSubtypeCurrent = trim(substr($currentSubtypeRaw, strlen('Americano - ')));
} elseif (stripos($currentSubtypeRaw, 'mexicano') !== false) {
    $passportCategoryCurrent = 'Mexicano';
    $passportSubtypeCurrent = trim(str_ireplace('mexicano', '', $currentSubtypeRaw));
    $passportSubtypeCurrent = trim($passportSubtypeCurrent, " -");
} elseif (stripos($currentSubtypeRaw, 'americano') !== false) {
    $passportCategoryCurrent = 'Americano';
    $passportSubtypeCurrent = trim(str_ireplace('americano', '', $currentSubtypeRaw));
    $passportSubtypeCurrent = trim($passportSubtypeCurrent, " -");
}

if ($passportSubtypeCurrent === '') {
    $passportSubtypeCurrent = 'Primera vez';
}

if ($passportCategoryCurrent === 'Mexicano') {
    if (strcasecmp($passportSubtypeCurrent, 'Reposición por robo') === 0) {
        $passportSubtypeCurrent = 'Robo/ extravío';
    } elseif (strcasecmp($passportSubtypeCurrent, 'Pasaporte dañado') === 0) {
        $passportSubtypeCurrent = 'Corrección de Datos';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="<?= BASE_URL ?>/formularios" class="text-primary hover:underline">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        <h2 class="text-3xl font-bold text-gray-800">Editar Formulario</h2>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/formularios/actualizar/<?= $form['id'] ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre del Formulario <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" required value="<?= htmlspecialchars($form['name']) ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($form['description'] ?? '') ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select name="type" id="formTypeSelect" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="Visa" <?= $form['type'] === 'Visa' ? 'selected' : '' ?>>Visa</option>
                    <option value="Pasaporte" <?= $form['type'] === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                </select>
            </div>

            <div id="visaCategoryWrapper" class="<?= $form['type'] === 'Visa' ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo de visa</label>
                <select id="visaCategorySelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="Americana" <?= $visaCategoryCurrent === 'Americana' ? 'selected' : '' ?>>Americana</option>
                    <option value="Canadiense" <?= $visaCategoryCurrent === 'Canadiense' ? 'selected' : '' ?>>Canadiense</option>
                </select>
            </div>

            <div id="visaSubtypeSelectWrapper" class="<?= $form['type'] === 'Visa' ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de trámite</label>
                <select id="visaSubtypeSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                        data-current-value="<?= htmlspecialchars($visaSubtypeCurrent, ENT_QUOTES, 'UTF-8') ?>"></select>
            </div>
            
            <div id="passportCategoryWrapper" class="<?= $form['type'] === 'Pasaporte' ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo de pasaporte</label>
                <select id="passportCategorySelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <option value="Americano" <?= $passportCategoryCurrent === 'Americano' ? 'selected' : '' ?>>Americano</option>
                    <option value="Mexicano" <?= $passportCategoryCurrent === 'Mexicano' ? 'selected' : '' ?>>Mexicano</option>
                </select>
            </div>

            <div id="passportSubtypeSelectWrapper" class="<?= $form['type'] === 'Pasaporte' ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <select id="passportSubtypeSelect" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                        data-current-value="<?= htmlspecialchars($passportSubtypeCurrent, ENT_QUOTES, 'UTF-8') ?>"></select>
            </div>

            <div id="genericSubtypeInputWrapper" class="<?= in_array($form['type'], ['Pasaporte', 'Visa'], true) ? 'hidden' : '' ?>">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <input type="text" id="genericSubtypeInput" value="<?= in_array($form['type'], ['Pasaporte', 'Visa'], true) ? '' : htmlspecialchars($form['subtype'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       placeholder="Ej: Primera vez, Renovación, etc.">
            </div>

            <input type="hidden" name="subtype" id="subtypeValue" value="<?= htmlspecialchars($form['subtype'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

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
                           class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500"
                           <?= !empty($form['pagination_enabled']) ? 'checked' : '' ?>>
                </div>

                <div id="pagination-config" style="display: <?= !empty($form['pagination_enabled']) ? 'block' : 'none' ?>;" class="bg-gray-50 rounded-lg p-4 mt-3">
                    <p class="text-sm text-gray-600 mb-3">
                        <i class="fas fa-layer-group mr-1"></i>
                        Al habilitar paginación, podrás dividir tus campos en secciones.
                        Los solicitantes podrán guardar su progreso y continuar después.
                    </p>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 text-sm text-blue-700">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Tip:</strong> Puedes configurar las páginas y asignar campos a cada sección directamente aquí.
                    </div>
                </div>
            </div>
            
            <div class="md:col-span-2">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Campos del Formulario <span class="text-red-500">*</span>
                    </label>
                    <span class="text-sm text-gray-500">Versión actual: v<?= $form['version'] ?></span>
                </div>
                
                <!-- Visual Form Builder -->
                <div id="form-builder-container"
                     data-initial-data="<?= htmlspecialchars($form['fields_json']) ?>"
                     data-initial-pages="<?= htmlspecialchars($form['pages_json'] ?? '') ?>"></div>
                
                <!-- Hidden field to store JSON -->
                <input type="hidden" name="fields_json" id="fields_json_hidden" required value="<?= htmlspecialchars($form['fields_json']) ?>">
                <input type="hidden" name="pages_json" id="pages_json_hidden" value="<?= htmlspecialchars($form['pages_json'] ?? '') ?>">
                
                <p class="text-sm text-yellow-600 mt-2">
                    <i class="fas fa-exclamation-triangle"></i> Al guardar, la versión se incrementará automáticamente
                </p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-4">
            <a href="<?= BASE_URL ?>/formularios" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90">
                <i class="fas fa-save mr-2"></i>Actualizar Formulario
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
        const preferredCurrentValue = visaSubtypeSelect.dataset.currentValue || '';
        const previousValue = visaSubtypeSelect.value || preferredCurrentValue;

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
        const preferredCurrentValue = passportSubtypeSelect.dataset.currentValue || '';
        const previousValue = passportSubtypeSelect.value || preferredCurrentValue;

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
            } else if (passportSubtypeSelect && passportSubtypeSelect.value === '') {
                // keep current typed value when switching back from passport
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
