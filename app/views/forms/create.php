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
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="">Seleccione...</option>
                    <option value="Visa">Visa</option>
                    <option value="Pasaporte">Pasaporte</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <input type="text" name="subtype"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2"
                       placeholder="Ej: Primera Vez, Renovación">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Campos del Formulario (JSON) <span class="text-red-500">*</span>
                </label>
                <textarea name="fields_json" rows="15" required
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                          placeholder='{"fields":[{"id":"nombre","type":"text","label":"Nombre Completo","required":true}]}'></textarea>
                <p class="text-sm text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> Formato: JSON con estructura de campos del formulario
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

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
