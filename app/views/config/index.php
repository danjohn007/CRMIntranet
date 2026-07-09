<?php
$title = 'Configuracion Global';
$geoEnabled = trim((string)($configs['geo_login_enabled']['config_value'] ?? '0')) === '1';
$geoAddress = $configs['geo_login_address']['config_value'] ?? '';
ob_start();
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-cog text-gray-600 mr-2"></i>Configuracion del Sistema
    </h2>
    <p class="text-gray-500">Personaliza tu sistema CRM</p>
</div>

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
            <p class="text-xs text-gray-500">Atencion y servicio</p>
        </div>
    </a>

    <a href="#section-qr" onclick="showSection('qr')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-indigo-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
            <i class="fas fa-qrcode text-indigo-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Codigos QR</p>
            <p class="text-xs text-gray-500">API y configuracion</p>
        </div>
    </a>

    <a href="#section-seguridad" onclick="showSection('seguridad')"
       class="config-card bg-white rounded-xl shadow hover:shadow-md transition cursor-pointer p-4 flex items-center space-x-4 border-2 border-transparent hover:border-red-400">
        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
            <i class="fas fa-shield-alt text-red-600 text-xl"></i>
        </div>
        <div>
            <p class="font-semibold text-gray-800 text-sm">Seguridad</p>
            <p class="text-xs text-gray-500">Login y ubicacion</p>
        </div>
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/configuracion/guardar" enctype="multipart/form-data">

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

    <div id="section-correo" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                <i class="fas fa-envelope text-green-600 text-sm"></i>
            </span>
            Correo &amp; SMTP
        </h3>
        <p class="text-xs text-gray-500 mb-4">Configuracion del servidor de correo para notificaciones del sistema</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Usuario SMTP</label>
                <input type="text" name="config_smtp_user"
                       value="<?= htmlspecialchars($configs['smtp_user']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       autocomplete="username">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Contrasena SMTP</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Servidor de Salida</label>
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
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto IMAP</label>
                <input type="number" name="config_smtp_imap_port"
                       value="<?= htmlspecialchars($configs['smtp_imap_port']['config_value'] ?? '993') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Puerto POP3</label>
                <input type="number" name="config_smtp_pop3_port"
                       value="<?= htmlspecialchars($configs['smtp_pop3_port']['config_value'] ?? '995') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="65535">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Correo
            </button>
        </div>
    </div>

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

    <div id="section-horarios" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center mr-3">
                <i class="fas fa-clock text-orange-600 text-sm"></i>
            </span>
            Horarios &amp; Contacto
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Telefono 1</label>
                <input type="tel" name="config_contact_phone"
                       value="<?= htmlspecialchars($configs['contact_phone']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Telefono 2</label>
                <input type="tel" name="config_contact_phone_2"
                       value="<?= htmlspecialchars($configs['contact_phone_2']['config_value'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Horario de Atencion</label>
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

    <div id="section-qr" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                <i class="fas fa-qrcode text-indigo-600 text-sm"></i>
            </span>
            Codigos QR
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

    <div id="section-seguridad" class="config-section bg-white rounded-xl shadow p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                <i class="fas fa-shield-alt text-red-600 text-sm"></i>
            </span>
            Seguridad / Ubicacion
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <input type="hidden" name="config_geo_login_enabled" value="0">
                <label class="inline-flex items-center cursor-pointer select-none">
                    <input type="checkbox" name="geo_login_enabled_toggle" value="1"
                           id="geo_login_toggle"
                           class="sr-only peer"
                           <?= $geoEnabled ? 'checked' : '' ?>>
                    <span id="geo_login_switch_track" class="relative inline-flex h-8 w-16 items-center rounded-full <?= $geoEnabled ? 'bg-green-600' : 'bg-gray-300' ?> transition peer-focus:ring-2 peer-focus:ring-blue-500 peer-focus:ring-offset-2">
                        <span id="geo_login_switch_thumb" class="absolute left-1 h-6 w-6 rounded-full bg-white shadow transition <?= $geoEnabled ? 'translate-x-8' : '' ?>"></span>
                    </span>
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        Login geolocalizado para asesores:
                        <span id="geo_login_toggle_label" class="<?= $geoEnabled ? 'text-green-700' : 'text-gray-500' ?>">
                            <?= $geoEnabled ? 'Encendido' : 'Apagado' ?>
                        </span>
                    </span>
                </label>
            </div>
            <div id="geo_login_fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 <?= $geoEnabled ? '' : 'hidden' ?>">
                <div class="md:col-span-2 relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Direccion permitida</label>
                    <input type="text" name="config_geo_login_address" id="geo_login_address"
                           value="<?= htmlspecialchars($geoAddress) ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                           placeholder="Escribe una direccion para buscarla en el mapa"
                           autocomplete="off">
                    <div id="geo_address_suggestions"
                         class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
                    <p class="text-xs text-gray-500 mt-1">Selecciona una sugerencia para completar coordenadas automaticamente.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Latitud permitida</label>
                    <input type="text" name="config_geo_login_latitude" id="geo_login_latitude"
                           value="<?= htmlspecialchars($configs['geo_login_latitude']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                           placeholder="20.5888">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Longitud permitida</label>
                    <input type="text" name="config_geo_login_longitude" id="geo_login_longitude"
                           value="<?= htmlspecialchars($configs['geo_login_longitude']['config_value'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                           placeholder="-100.3899">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Radio permitido (metros)</label>
                    <input type="number" name="config_geo_login_radius_meters"
                           value="<?= htmlspecialchars($configs['geo_login_radius_meters']['config_value'] ?? '100') ?>"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                           min="1" max="100000">
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="useCurrentLocation()"
                            class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition text-sm">
                        <i class="fas fa-location-crosshairs mr-2"></i>Usar mi ubicacion actual
                    </button>
                </div>
                <div class="md:col-span-2">
                    <p id="location_status" class="text-sm text-gray-500"></p>
                </div>
                <div class="md:col-span-2">
                    <div id="geo_login_map" class="h-80 w-full rounded-lg border border-gray-300"></div>
                    <p class="text-xs text-gray-500 mt-2">Puedes hacer clic en el mapa o arrastrar el marcador para ajustar la ubicacion permitida.</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Intentos maximos</label>
                <input type="number" name="config_login_max_attempts"
                       value="<?= htmlspecialchars($configs['login_max_attempts']['config_value'] ?? '5') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="20">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bloqueo por intentos (minutos)</label>
                <input type="number" name="config_login_lockout_minutes"
                       value="<?= htmlspecialchars($configs['login_lockout_minutes']['config_value'] ?? '15') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="1" max="1440">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cerrar sesion por inactividad (minutos)</label>
                <input type="number" name="config_session_idle_timeout_minutes"
                       value="<?= htmlspecialchars($configs['session_idle_timeout_minutes']['config_value'] ?? '30') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500"
                       min="5" max="1440">
            </div>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition text-sm">
                <i class="fas fa-save mr-2"></i>Guardar Seguridad
            </button>
        </div>
    </div>

</form>

<div class="bg-white rounded-xl shadow p-6 mt-2">
    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
        <i class="fas fa-eye text-gray-500 mr-2"></i>Configuracion Actual
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">General</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Sitio:</span> <?= htmlspecialchars($configs['site_name']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Email:</span> <?= htmlspecialchars($configs['email_from']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Telefono 1:</span> <?= htmlspecialchars($configs['contact_phone']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Telefono 2:</span> <?= htmlspecialchars($configs['contact_phone_2']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Horario:</span> <?= htmlspecialchars($configs['business_hours']['config_value'] ?? '-') ?></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Correo SMTP</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Usuario:</span> <?= htmlspecialchars($configs['smtp_user']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Servidor:</span> <?= htmlspecialchars($configs['smtp_host']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">SMTP:</span> <?= htmlspecialchars($configs['smtp_port']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">IMAP:</span> <?= htmlspecialchars($configs['smtp_imap_port']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">POP3:</span> <?= htmlspecialchars($configs['smtp_pop3_port']['config_value'] ?? '-') ?></li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Tema</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li class="flex items-center space-x-2">
                    <span class="font-medium text-gray-700">Primario:</span>
                    <span class="inline-block w-5 h-5 rounded border border-gray-300"
                          style="background-color: <?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?>"></span>
                    <span><?= htmlspecialchars($configs['primary_color']['config_value'] ?? '#3b82f6') ?></span>
                </li>
                <li class="flex items-center space-x-2">
                    <span class="font-medium text-gray-700">Secundario:</span>
                    <span class="inline-block w-5 h-5 rounded border border-gray-300"
                          style="background-color: <?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?>"></span>
                    <span><?= htmlspecialchars($configs['secondary_color']['config_value'] ?? '#1e40af') ?></span>
                </li>
            </ul>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Seguridad</p>
            <ul class="space-y-1 text-sm text-gray-600">
                <li><span class="font-medium text-gray-700">Geo login:</span> <?= $geoEnabled ? 'Activo' : 'Inactivo' ?></li>
                <li><span class="font-medium text-gray-700">Direccion:</span> <?= htmlspecialchars($geoAddress ?: '-') ?></li>
                <li><span class="font-medium text-gray-700">Latitud:</span> <?= htmlspecialchars($configs['geo_login_latitude']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Longitud:</span> <?= htmlspecialchars($configs['geo_login_longitude']['config_value'] ?? '-') ?></li>
                <li><span class="font-medium text-gray-700">Radio:</span> <?= htmlspecialchars($configs['geo_login_radius_meters']['config_value'] ?? '100') ?> m</li>
                <li><span class="font-medium text-gray-700">Intentos:</span> <?= htmlspecialchars($configs['login_max_attempts']['config_value'] ?? '5') ?></li>
                <li><span class="font-medium text-gray-700">Inactividad:</span> <?= htmlspecialchars($configs['session_idle_timeout_minutes']['config_value'] ?? '30') ?> min</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let geoMap = null;
let geoMarker = null;
let addressSearchTimer = null;

function showSection(sectionId) {
    const el = document.getElementById('section-' + sectionId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.classList.add('ring-2', 'ring-blue-400');
        setTimeout(function() { el.classList.remove('ring-2', 'ring-blue-400'); }, 2000);
    }
}

function toggleSmtpPassword() {
    const input = document.getElementById('smtp_password');
    const icon = document.getElementById('smtp_password_icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function updateGeoLoginFields() {
    const toggle = document.getElementById('geo_login_toggle');
    const fields = document.getElementById('geo_login_fields');
    const label = document.getElementById('geo_login_toggle_label');
    const track = document.getElementById('geo_login_switch_track');
    const thumb = document.getElementById('geo_login_switch_thumb');

    if (!toggle || !fields || !label || !track || !thumb) {
        return;
    }

    if (toggle.checked) {
        fields.classList.remove('hidden');
        label.textContent = 'Encendido';
        label.classList.remove('text-gray-500');
        label.classList.add('text-green-700');
        track.classList.remove('bg-gray-300');
        track.classList.add('bg-green-600');
        thumb.classList.add('translate-x-8');
    } else {
        fields.classList.add('hidden');
        label.textContent = 'Apagado';
        label.classList.remove('text-green-700');
        label.classList.add('text-gray-500');
        track.classList.remove('bg-green-600');
        track.classList.add('bg-gray-300');
        thumb.classList.remove('translate-x-8');
    }

    if (toggle.checked) {
        setTimeout(ensureGeoMap, 100);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('geo_login_toggle');
    if (toggle) {
        toggle.addEventListener('change', updateGeoLoginFields);
        updateGeoLoginFields();
    }

    const addressInput = document.getElementById('geo_login_address');
    if (addressInput) {
        addressInput.addEventListener('input', handleAddressInput);
        addressInput.addEventListener('blur', function() {
            setTimeout(hideAddressSuggestions, 200);
        });
    }

    const latInput = document.getElementById('geo_login_latitude');
    const lngInput = document.getElementById('geo_login_longitude');
    if (latInput && lngInput) {
        latInput.addEventListener('change', syncMapFromCoordinateInputs);
        lngInput.addEventListener('change', syncMapFromCoordinateInputs);
    }
});

function useCurrentLocation() {
    const status = document.getElementById('location_status');

    if (!navigator.geolocation) {
        status.textContent = 'Este navegador no permite obtener ubicacion.';
        status.className = 'text-sm text-red-600';
        return;
    }

    status.textContent = 'Obteniendo ubicacion...';
    status.className = 'text-sm text-gray-500';

    navigator.geolocation.getCurrentPosition(
        function(position) {
            setLatLngInputs(position.coords.latitude, position.coords.longitude, true);
            status.textContent = 'Ubicacion capturada.';
            status.className = 'text-sm text-green-600';
        },
        function() {
            status.textContent = 'No se pudo obtener la ubicacion. Revise permisos del navegador.';
            status.className = 'text-sm text-red-600';
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
}

function getConfiguredLatLng() {
    const lat = parseFloat(document.getElementById('geo_login_latitude')?.value || '');
    const lng = parseFloat(document.getElementById('geo_login_longitude')?.value || '');

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        return [lat, lng];
    }

    return [20.5888, -100.3899];
}

function ensureGeoMap() {
    const mapEl = document.getElementById('geo_login_map');
    if (!mapEl || mapEl.offsetParent === null) {
        return;
    }

    if (!window.L) {
        const status = document.getElementById('location_status');
        if (status) {
            status.textContent = 'No se pudo cargar el mapa. Revisa la conexion a OpenStreetMap.';
            status.className = 'text-sm text-red-600';
        }
        return;
    }

    const initialLatLng = getConfiguredLatLng();

    if (!geoMap) {
        geoMap = L.map('geo_login_map').setView(initialLatLng, 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(geoMap);

        geoMarker = L.marker(initialLatLng, { draggable: true }).addTo(geoMap);
        geoMarker.on('dragend', function(event) {
            const position = event.target.getLatLng();
            setLatLngInputs(position.lat, position.lng, false);
        });

        geoMap.on('click', function(event) {
            setLatLngInputs(event.latlng.lat, event.latlng.lng, true);
        });
    } else {
        geoMap.invalidateSize();
    }

    setTimeout(function() {
        geoMap.invalidateSize();
    }, 150);
}

function setLatLngInputs(lat, lng, updateMap) {
    const latInput = document.getElementById('geo_login_latitude');
    const lngInput = document.getElementById('geo_login_longitude');

    if (latInput) {
        latInput.value = Number(lat).toFixed(8);
    }

    if (lngInput) {
        lngInput.value = Number(lng).toFixed(8);
    }

    if (updateMap) {
        setMapMarker(Number(lat), Number(lng), true);
    }
}

function setMapMarker(lat, lng, centerMap) {
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }

    if (!geoMap) {
        ensureGeoMap();
    }

    if (!geoMap || !geoMarker) {
        return;
    }

    const latLng = [lat, lng];
    geoMarker.setLatLng(latLng);

    if (centerMap) {
        geoMap.setView(latLng, Math.max(geoMap.getZoom(), 16));
    }
}

function syncMapFromCoordinateInputs() {
    const lat = parseFloat(document.getElementById('geo_login_latitude')?.value || '');
    const lng = parseFloat(document.getElementById('geo_login_longitude')?.value || '');

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        setMapMarker(lat, lng, true);
    }
}

function handleAddressInput(event) {
    const query = event.target.value.trim();

    clearTimeout(addressSearchTimer);

    if (query.length < 3) {
        hideAddressSuggestions();
        return;
    }

    addressSearchTimer = setTimeout(function() {
        searchAddress(query);
    }, 350);
}

function searchAddress(query) {
    const suggestions = document.getElementById('geo_address_suggestions');
    if (!suggestions) {
        return;
    }

    suggestions.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Buscando...</div>';
    suggestions.classList.remove('hidden');

    const url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=6&countrycodes=mx&q=' + encodeURIComponent(query);

    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'Accept-Language': 'es'
        }
    })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Busqueda no disponible');
            }
            return response.json();
        })
        .then(renderAddressSuggestions)
        .catch(function() {
            suggestions.innerHTML = '<div class="px-4 py-3 text-sm text-red-600">No se pudieron cargar sugerencias.</div>';
        });
}

function renderAddressSuggestions(results) {
    const suggestions = document.getElementById('geo_address_suggestions');
    if (!suggestions) {
        return;
    }

    if (!Array.isArray(results) || results.length === 0) {
        suggestions.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">Sin resultados.</div>';
        suggestions.classList.remove('hidden');
        return;
    }

    suggestions.innerHTML = '';
    results.forEach(function(result) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'block w-full px-4 py-3 text-left text-sm hover:bg-blue-50 focus:bg-blue-50 focus:outline-none border-b border-gray-100 last:border-b-0';
        button.textContent = result.display_name;
        button.addEventListener('pointerdown', function(event) {
            event.preventDefault();
            selectAddressSuggestion(result);
        });
        button.addEventListener('mousedown', function(event) {
            event.preventDefault();
            selectAddressSuggestion(result);
        });
        button.addEventListener('click', function(event) {
            event.preventDefault();
            selectAddressSuggestion(result);
        });
        suggestions.appendChild(button);
    });

    suggestions.classList.remove('hidden');
}

function selectAddressSuggestion(result) {
    const addressInput = document.getElementById('geo_login_address');
    const status = document.getElementById('location_status');
    const lat = parseFloat(result.lat);
    const lng = parseFloat(result.lon);
    const displayName = String(result.display_name || '');

    if (addressInput) {
        addressInput.value = displayName;
    }

    if (Number.isFinite(lat) && Number.isFinite(lng)) {
        setLatLngInputs(lat, lng, true);
        if (status) {
            status.textContent = 'Direccion seleccionada y coordenadas actualizadas.';
            status.className = 'text-sm text-green-600';
        }
    }

    hideAddressSuggestions();
}

function hideAddressSuggestions() {
    const suggestions = document.getElementById('geo_address_suggestions');
    if (suggestions) {
        suggestions.classList.add('hidden');
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
