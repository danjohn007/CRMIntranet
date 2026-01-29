<?php 
$title = 'Configuración Global';
ob_start(); 
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Configuración Global</h2>
    <p class="text-gray-600">Ajustes generales del sistema</p>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="<?= BASE_URL ?>/configuracion/guardar" enctype="multipart/form-data">
        
        <!-- Información General -->
        <div class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-info-circle text-blue-600"></i> Información General
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Sitio</label>
                    <input type="text" name="config_site_name" 
                           value="<?= htmlspecialchars($configs['site_name']['config_value'] ?? SITE_NAME) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Logo del Sitio</label>
                    <input type="file" name="site_logo" accept="image/*"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                    <?php if (!empty($configs['site_logo']['config_value'])): ?>
                    <div class="mt-2">
                        <img src="<?= BASE_URL . htmlspecialchars($configs['site_logo']['config_value']) ?>" 
                             alt="Logo actual" class="h-16 object-contain">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Información de Contacto -->
        <div class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-phone text-blue-600"></i> Información de Contacto
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Principal</label>
                    <input type="email" name="config_email_from" 
                           value="<?= htmlspecialchars($configs['email_from']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono 1</label>
                    <input type="tel" name="config_contact_phone" 
                           value="<?= htmlspecialchars($configs['contact_phone']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono 2</label>
                    <input type="tel" name="config_contact_phone_2" 
                           value="<?= htmlspecialchars($configs['contact_phone_2']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Horario de Atención</label>
                    <input type="text" name="config_business_hours" 
                           value="<?= htmlspecialchars($configs['business_hours']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
        
        <!-- Apariencia -->
        <div class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-palette text-blue-600"></i> Apariencia
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color Primario</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" name="config_primary_color" 
                               value="<?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"
                               class="h-10 w-20 border border-gray-300 rounded cursor-pointer">
                        <input type="text" 
                               value="<?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"
                               class="flex-1 border border-gray-300 rounded-lg px-4 py-2" readonly>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color Secundario</label>
                    <div class="flex items-center space-x-2">
                        <input type="color" name="config_secondary_color" 
                               value="<?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"
                               class="h-10 w-20 border border-gray-300 rounded cursor-pointer">
                        <input type="text" 
                               value="<?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"
                               class="flex-1 border border-gray-300 rounded-lg px-4 py-2" readonly>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Integraciones -->
        <div class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4 pb-2 border-b">
                <i class="fas fa-plug text-blue-600"></i> Integraciones
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PayPal Client ID</label>
                    <input type="text" name="config_paypal_client_id" 
                           value="<?= htmlspecialchars($configs['paypal_client_id']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">PayPal Secret</label>
                    <input type="password" name="config_paypal_secret" 
                           value="<?= htmlspecialchars($configs['paypal_secret']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">QR API Key</label>
                    <input type="text" name="config_qr_api_key" 
                           value="<?= htmlspecialchars($configs['qr_api_key']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">QR API URL</label>
                    <input type="text" name="config_qr_api_url" 
                           value="<?= htmlspecialchars($configs['qr_api_url']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
        
        <div class="flex justify-end space-x-4">
            <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Guardar Configuración
            </button>
        </div>
    </form>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
