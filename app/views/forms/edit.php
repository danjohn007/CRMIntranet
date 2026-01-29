<?php 
$title = 'Editar Formulario';
ob_start(); 
?>

<div class="mb-6">
    <div class="flex items-center space-x-4 mb-4">
        <a href="<?= BASE_URL ?>/formularios" class="text-blue-600 hover:text-blue-800">
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
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <option value="Visa" <?= $form['type'] === 'Visa' ? 'selected' : '' ?>>Visa</option>
                    <option value="Pasaporte" <?= $form['type'] === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                <input type="text" name="subtype" value="<?= htmlspecialchars($form['subtype'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            
            <div class="md:col-span-2">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Campos del Formulario (JSON) <span class="text-red-500">*</span>
                    </label>
                    <span class="text-sm text-gray-500">Versión actual: v<?= $form['version'] ?></span>
                </div>
                <textarea name="fields_json" rows="15" required
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 font-mono text-sm"><?= htmlspecialchars($form['fields_json']) ?></textarea>
                <p class="text-sm text-yellow-600 mt-2">
                    <i class="fas fa-exclamation-triangle"></i> Al guardar, la versión se incrementará automáticamente
                </p>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-4">
            <a href="<?= BASE_URL ?>/formularios" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Actualizar Formulario
            </button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
