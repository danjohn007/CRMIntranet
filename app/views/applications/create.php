<?php 
$title = 'Crear Solicitud';
ob_start(); 
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Nueva Solicitud</h2>
    <p class="text-gray-600">Complete el formulario para crear una nueva solicitud de trámite</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/solicitudes/crear" id="applicationForm">
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Seleccione el tipo de formulario <span class="text-red-500">*</span>
            </label>
            <select name="form_id" id="form_id" required 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">-- Seleccione un formulario --</option>
                <?php foreach ($forms as $form): ?>
                <option value="<?= $form['id'] ?>" 
                        data-fields='<?= htmlspecialchars($form['fields_json']) ?>'>
                    <?= htmlspecialchars($form['name']) ?> (<?= htmlspecialchars($form['type']) ?> - <?= htmlspecialchars($form['subtype']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="dynamic-fields" class="space-y-4">
            <!-- Los campos se cargarán dinámicamente aquí -->
        </div>
        
        <div class="mt-8 flex gap-4">
            <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition">
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
    const selectedOption = this.options[this.selectedIndex];
    const fieldsJson = selectedOption.getAttribute('data-fields');
    const container = document.getElementById('dynamic-fields');
    
    container.innerHTML = '';
    
    if (!fieldsJson) return;
    
    try {
        const formConfig = JSON.parse(fieldsJson);
        const fields = formConfig.fields || [];
        
        if (fields.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-center py-8">Este formulario no tiene campos configurados.</p>';
            return;
        }
        
        container.innerHTML = '<h3 class="text-xl font-bold text-gray-800 mb-4">Información del Solicitante</h3>';
        
        fields.forEach(field => {
            const fieldDiv = document.createElement('div');
            fieldDiv.className = 'mb-4';
            
            const label = document.createElement('label');
            label.className = 'block text-sm font-medium text-gray-700 mb-2';
            label.textContent = field.label;
            if (field.required) {
                const required = document.createElement('span');
                required.className = 'text-red-500';
                required.textContent = ' *';
                label.appendChild(required);
            }
            
            let input;
            
            switch (field.type) {
                case 'text':
                case 'email':
                    input = document.createElement('input');
                    input.type = field.type;
                    input.className = 'w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                    break;
                    
                case 'number':
                    input = document.createElement('input');
                    input.type = 'number';
                    input.className = 'w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                    break;
                    
                case 'date':
                    input = document.createElement('input');
                    input.type = 'date';
                    input.className = 'w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                    break;
                    
                case 'textarea':
                    input = document.createElement('textarea');
                    input.rows = 4;
                    input.className = 'w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                    break;
                    
                case 'select':
                    input = document.createElement('select');
                    input.className = 'w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                    
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = '-- Seleccione --';
                    input.appendChild(defaultOption);
                    
                    if (field.options && Array.isArray(field.options)) {
                        field.options.forEach(opt => {
                            const option = document.createElement('option');
                            option.value = opt;
                            option.textContent = opt;
                            input.appendChild(option);
                        });
                    }
                    break;
                    
                case 'file':
                    input = document.createElement('input');
                    input.type = 'file';
                    input.className = 'w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
                    break;
                    
                default:
                    input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';
            }
            
            input.name = 'form_data[' + field.id + ']';
            input.id = 'field_' + field.id;
            if (field.required) {
                input.required = true;
            }
            
            fieldDiv.appendChild(label);
            fieldDiv.appendChild(input);
            container.appendChild(fieldDiv);
        });
        
    } catch (e) {
        console.error('Error parsing form fields:', e);
        container.innerHTML = '<p class="text-red-500">Error al cargar los campos del formulario.</p>';
    }
});
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
