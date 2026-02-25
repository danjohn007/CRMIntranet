<?php 
$title = 'Configuración Global';
ob_start(); 
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-cog text-gray-600 mr-2"></i>Configuración del Sistema
    </h2>
    <p class="text-gray-500">Personaliza tu sistema CRM</p>
</div>

<!-- Section Navigation Cards Grid -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
    <a href="#section-general" onclick="showSection('general')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-blue-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
            <i class="fas fa-sliders-h text-blue-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">General</p>
            <p class="text-xs text-gray-500">Nombre, logo, contacto</p>
        </div>
    </a>

    <a href="#section-tema" onclick="showSection('tema')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-purple-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
            <i class="fas fa-palette text-purple-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Tema</p>
            <p class="text-xs text-gray-500">Colores y estilos</p>
        </div>
    </a>

    <a href="#section-correo" onclick="showSection('correo')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-green-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
            <i class="fas fa-envelope text-green-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Correo</p>
            <p class="text-xs text-gray-500">SMTP y notificaciones</p>
        </div>
    </a>

    <a href="#section-pagos" onclick="showSection('pagos')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-yellow-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-yellow-100 flex items-center justify-center">
            <i class="fas fa-credit-card text-yellow-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Pagos</p>
            <p class="text-xs text-gray-500">PayPal, cuentas</p>
        </div>
    </a>

    <a href="#section-horarios" onclick="showSection('horarios')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-orange-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-orange-100 flex items-center justify-center">
            <i class="fas fa-clock text-orange-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Horarios</p>
            <p class="text-xs text-gray-500">Atención y servicio</p>
        </div>
    </a>

    <a href="#section-qr" onclick="showSection('qr')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-indigo-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
            <i class="fas fa-qrcode text-indigo-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Códigos QR</p>
            <p class="text-xs text-gray-500">API y configuración</p>
        </div>
    </a>
</div>

<!-- Edit Forms (one per section) -->
<form method="POST" action="<?= BASE_URL ?>/configuracion/guardar" enctype="multipart/form-data">

    <!-- General -->
    <div id="section-general" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                <i class="fas fa-sliders-h text-blue-600 text-sm"></i>
            </span>
            General
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
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Principal</label>
                <input type="email" name="config_email_from"
                       value="<?= htmlspecialchars($configs['email_from']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar General
            </button>
        </div>
    </div>

    <!-- Tema -->
    <div id="section-tema" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                <i class="fas fa-palette text-purple-600 text-sm"></i>
            </span>
            Tema
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Color Primario</label>
                <div class="flex items-center space-x-2">
                    <input type="color" name="config_primary_color" id="primary_color"
                           value="<?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"
                           class="h-10 w-20 border border-gray-300 rounded cursor-pointer"
                           onchange="document.getElementById('primary_color_text').value = this.value">
                    <input type="text" id="primary_color_text"
                           value="<?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"
                           class="flex-1 border border-gray-300 rounded-lg px-4 py-2" readonly>
                </div>
                <p class="text-xs text-gray-500 mt-1">Navbar, botones y enlaces principales</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Color Secundario</label>
                <div class="flex items-center space-x-2">
                    <input type="color" name="config_secondary_color" id="secondary_color"
                           value="<?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"
                           class="h-10 w-20 border border-gray-300 rounded cursor-pointer"
                           onchange="document.getElementById('secondary_color_text').value = this.value">
                    <input type="text" id="secondary_color_text"
                           value="<?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"
                           class="flex-1 border border-gray-300 rounded-lg px-4 py-2" readonly>
                </div>
                <p class="text-xs text-gray-500 mt-1">Hover de botones y elementos secundarios</p>
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Tema
            </button>
        </div>
    </div>

    <!-- Correo / SMTP -->
    <div id="section-correo" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                <i class="fas fa-envelope text-green-600 text-sm"></i>
            </span>
            Correo &amp; SMTP
        </h3>
        <p class="text-xs text-gray-500 mb-4">Configuración del servidor de correo para el envío de notificaciones del sistema (SSL/TLS)</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Usuario SMTP (Username)</label>
                <input type="text" name="config_smtp_user"
                       value="<?= htmlspecialchars($configs['smtp_user']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       autocomplete="username">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contraseña SMTP</label>
                <div class="relative">
                    <input type="password" name="config_smtp_password" id="smtp_password"
                           value="<?= htmlspecialchars($configs['smtp_password']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 focus:ring-2 focus:ring-blue-500"
                           autocomplete="current-password">
                    <button type="button" onclick="toggleSmtpPassword()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                        <i id="smtp_password_icon" class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Servidor de Salida (SMTP Host)</label>
                <input type="text" name="config_smtp_host"
                       value="<?= htmlspecialchars($configs['smtp_host']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto SMTP</label>
                <input type="number" name="config_smtp_port"
                       value="<?= htmlspecialchars($configs['smtp_port']['config_value'] ?? '587') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
                <p class="text-xs text-gray-500 mt-1">Puerto 587 (TLS) o 465 (SSL)</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto IMAP (Entrada)</label>
                <input type="number" name="config_smtp_imap_port"
                       value="<?= htmlspecialchars($configs['smtp_imap_port']['config_value'] ?? '993') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
                <p class="text-xs text-gray-500 mt-1">Servidor: <?= htmlspecialchars($configs['smtp_host']['config_value'] ?? '') ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto POP3 (Entrada)</label>
                <input type="number" name="config_smtp_pop3_port"
                       value="<?= htmlspecialchars($configs['smtp_pop3_port']['config_value'] ?? '995') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
                <p class="text-xs text-gray-500 mt-1">Servidor: <?= htmlspecialchars($configs['smtp_host']['config_value'] ?? '') ?></p>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-3 justify-end items-center">
            <!-- Test Email -->
            <div class="flex items-center gap-2 flex-1 min-w-0 max-w-xs">
                <input type="email" id="test_email_to" placeholder="Correo destinatario de prueba"
                       class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500 min-w-0">
                <button type="button" onclick="sendTestEmail()"
                        class="whitespace-nowrap bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-1">
                    <i class="fas fa-paper-plane"></i><span class="hidden sm:inline">Enviar prueba</span>
                </button>
            </div>
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Correo
            </button>
        </div>
        <div id="test_email_result" class="mt-3 hidden"></div>
    </div>

    <!-- Pagos -->
    <div id="section-pagos" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center mr-3">
                <i class="fas fa-credit-card text-yellow-600 text-sm"></i>
            </span>
            Pagos
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
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Pagos
            </button>
        </div>
    </div>

    <!-- Horarios -->
    <div id="section-horarios" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center mr-3">
                <i class="fas fa-clock text-orange-600 text-sm"></i>
            </span>
            Horarios &amp; Contacto
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Horario de Atención</label>
                <input type="text" name="config_business_hours"
                       value="<?= htmlspecialchars($configs['business_hours']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Horarios
            </button>
        </div>
    </div>

    <!-- Códigos QR -->
    <div id="section-qr" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                <i class="fas fa-qrcode text-indigo-600 text-sm"></i>
            </span>
            Códigos QR
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar QR
            </button>
        </div>
    </div>

</form>

<!-- Configuración Actual (Read-only Summary) -->
<div class="bg-white rounded-xl shadow p-6 mt-2">
    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
        <i class="fas fa-eye text-gray-500 mr-2"></i>Configuración Actual
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- General -->
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">General</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Sitio:</span> <?= htmlspecialchars($configs['site_name']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Email:</span> <?= htmlspecialchars($configs['email_from']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Teléfono 1:</span> <?= htmlspecialchars($configs['contact_phone']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Teléfono 2:</span> <?= htmlspecialchars($configs['contact_phone_2']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Horario:</span> <?= htmlspecialchars($configs['business_hours']['config_value'] ?? '—') ?></li>
            </ul>
        </div>
        <!-- Correo SMTP -->
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Correo SMTP</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Usuario:</span> <?= htmlspecialchars($configs['smtp_user']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Servidor salida:</span> <?= htmlspecialchars($configs['smtp_host']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Puerto SMTP:</span> <?= htmlspecialchars($configs['smtp_port']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Puerto IMAP:</span> <?= htmlspecialchars($configs['smtp_imap_port']['config_value'] ?? '—') ?></li>
                <li><span class="font-medium text-gray-700">Puerto POP3:</span> <?= htmlspecialchars($configs['smtp_pop3_port']['config_value'] ?? '—') ?></li>
            </ul>
        </div>
        <!-- Tema -->
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Tema</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li class="flex items-center space-x-2">
                    <span class="font-medium text-gray-700">Color primario:</span>
                    <span class="inline-block w-5 h-5 rounded border border-gray-300"
                          style="background-color: <?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"></span>
                    <span><?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?></span>
                </li>
                <li class="flex items-center space-x-2">
                    <span class="font-medium text-gray-700">Color secundario:</span>
                    <span class="inline-block w-5 h-5 rounded border border-gray-300"
                          style="background-color: <?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"></span>
                    <span><?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
function showSection(sectionId) {
    const el = document.getElementById('section-' + sectionId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.classList.add('ring-2', 'ring-blue-400');
        setTimeout(function() { el.classList.remove('ring-2', 'ring-blue-400'); }, 2000);
    }
}

function sendTestEmail() {
    const toEmail = document.getElementById('test_email_to').value.trim();
    const resultEl = document.getElementById('test_email_result');
    if (!toEmail) {
        resultEl.innerHTML = '<p class="text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>Ingresa un correo destinatario.</p>';
        resultEl.classList.remove('hidden');
        return;
    }
    const btn = document.querySelector('[onclick="sendTestEmail()"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    resultEl.innerHTML = '<p class="text-sm text-gray-500"><i class="fas fa-spinner fa-spin mr-1"></i>Enviando...</p>';
    resultEl.classList.remove('hidden');

    fetch('<?= BASE_URL ?>/configuracion/test-email', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'to_email=' + encodeURIComponent(toEmail)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            resultEl.innerHTML = '<p class="text-sm text-green-700 bg-green-50 border border-green-200 rounded px-3 py-2"><i class="fas fa-check-circle mr-1"></i>' + data.message + '</p>';
        } else {
            resultEl.innerHTML = '<p class="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><i class="fas fa-times-circle mr-1"></i>' + data.message + '</p>';
        }
    })
    .catch(err => {
        resultEl.innerHTML = '<p class="text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2"><i class="fas fa-times-circle mr-1"></i>Error de red: ' + err.message + '</p>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i><span class="hidden sm:inline">Enviar prueba</span>';
    });
}


function toggleSmtpPassword() {
    const input = document.getElementById('smtp_password');
    const icon  = document.getElementById('smtp_password_icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>

