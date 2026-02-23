<?php 
$title = 'Crear Solicitud';
ob_start(); 
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Nueva Solicitud</h2>
    <p class="text-gray-600">Complete los datos básicos del solicitante para crear un nuevo trámite</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/solicitudes/crear" id="applicationForm">
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Tipo de trámite <span class="text-red-500">*</span>
            </label>
            <select name="form_id" id="form_id" required 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">-- Seleccione el tipo de trámite --</option>
                <?php foreach ($forms as $form): ?>
                <option value="<?= $form['id'] ?>">
                    <?= htmlspecialchars($form['name']) ?> (<?= htmlspecialchars($form['type']) ?> - <?= htmlspecialchars($form['subtype']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Datos básicos del solicitante (siempre visibles al seleccionar trámite) -->
        <div id="basic-fields" class="hidden space-y-4">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Datos básicos del solicitante</h3>
            <p class="text-sm text-gray-500 mb-4">
                <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                El cuestionario completo será enviado al cliente vía enlace para que lo llene directamente.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                    <input type="text" name="form_data[nombre]" id="field_nombre" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Nombre(s)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellidos <span class="text-red-500">*</span></label>
                    <input type="text" name="form_data[apellidos]" id="field_apellidos" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Apellido paterno y materno">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="form_data[email]" id="field_email" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="correo@ejemplo.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono <span class="text-red-500">*</span></label>
                    <input type="tel" name="form_data[telefono]" id="field_telefono" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Número de teléfono">
                </div>
            </div>
        </div>

        <div class="mt-8 flex gap-4">
            <button type="submit" id="submit-btn" disabled
                    class="btn-primary text-white px-8 py-3 rounded-lg hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-save mr-2"></i>Crear Solicitud
            </button>
            <a href="<?= BASE_URL ?>/solicitudes" class="bg-gray-500 text-white px-8 py-3 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('form_id').addEventListener('change', function() {
    const basicFields = document.getElementById('basic-fields');
    const submitBtn  = document.getElementById('submit-btn');
    if (this.value) {
        basicFields.classList.remove('hidden');
        submitBtn.disabled = false;
    } else {
        basicFields.classList.add('hidden');
        submitBtn.disabled = true;
    }
});
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
