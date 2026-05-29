<?php 
$title = 'Solicitudes';
ob_start(); 
$isAsesorRole = $_SESSION['user_role'] === ROLE_ASESOR;
$isAdminRole = $_SESSION['user_role'] === ROLE_ADMIN;
$searchTerm = $searchTerm ?? '';
$advisors = $advisors ?? [];
?>

<div class="mb-4 md:mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Solicitudes</h2>
        <p class="text-sm md:text-base text-gray-600">Gestión de trámites de visas y pasaportes</p>
    </div>
    <a href="<?= BASE_URL ?>/solicitudes/crear" class="btn-primary text-white px-4 md:px-6 py-2 md:py-3 rounded-lg hover:opacity-90 transition text-sm md:text-base">
        <i class="fas fa-plus mr-2"></i>Nueva Solicitud
    </a>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow p-4 mb-4 md:mb-6">
    <form method="GET" action="<?= BASE_URL ?>/solicitudes" class="grid grid-cols-1 md:grid-cols-<?= $isAdminRole ? '3' : '2' ?> gap-4" id="filterForm">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estatus</label>
            <select name="status" id="statusSelect" class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
                <option value="" <?= $status === '' ? 'selected' : '' ?>>Todos los estatus</option>
                <option value="<?= htmlspecialchars(STATUS_NUEVO) ?>" <?= $status === STATUS_NUEVO ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_NUEVO . ' (Nuevo)') ?></option>
                <option value="<?= htmlspecialchars(STATUS_VALIDANDO_RESPUESTAS) ?>" <?= $status === STATUS_VALIDANDO_RESPUESTAS ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_VALIDANDO_RESPUESTAS) ?></option>
                <option value="<?= htmlspecialchars(STATUS_LISTO_SOLICITUD) ?>" <?= $status === STATUS_LISTO_SOLICITUD ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_LISTO_SOLICITUD) ?></option>
                <option value="<?= htmlspecialchars(STATUS_EN_ESPERA_PAGO) ?>" <?= $status === STATUS_EN_ESPERA_PAGO ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_EN_ESPERA_PAGO) ?></option>
                <option value="<?= htmlspecialchars(STATUS_CITA_PROGRAMADA) ?>" <?= $status === STATUS_CITA_PROGRAMADA ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_CITA_PROGRAMADA) ?></option>
                <option value="<?= htmlspecialchars(STATUS_EN_ESPERA_RESULTADO) ?>" <?= $status === STATUS_EN_ESPERA_RESULTADO ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_EN_ESPERA_RESULTADO) ?></option>
                <?php if (!$isAsesorRole): ?>
                <option value="<?= htmlspecialchars(STATUS_TRAMITE_CERRADO) ?>" <?= $status === STATUS_TRAMITE_CERRADO ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_TRAMITE_CERRADO) ?></option>
                <option value="<?= htmlspecialchars(STATUS_FINALIZADO) ?>" <?= $status === STATUS_FINALIZADO ? 'selected' : '' ?>><?= htmlspecialchars(STATUS_FINALIZADO . ' (legacy)') ?></option>
                <?php endif; ?>
            </select>
        </div>

        <?php if ($isAdminRole): ?>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Folio</label>
            <input
                type="text"
                name="q"
                value="<?= htmlspecialchars($searchTerm) ?>"
                placeholder="Ej. VISA-2026-000021"
                class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base"
            >
        </div>
        <?php endif; ?>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-gray-600 text-white px-4 md:px-6 py-2 rounded-lg hover:bg-gray-700 transition text-sm md:text-base">
                <i class="fas fa-search mr-2"></i>Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Tabla de Solicitudes -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Nombre del solicitante</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Servicio</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Primera vez / Renovación</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Color / Estatus</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Responsable</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Fecha de ingreso</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Expediente</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($applications as $app):
                    // Extract applicant name from data_json or client_name column
                    $appData = json_decode($app['data_json'] ?? '{}', true) ?: [];
                    $clientName = trim($appData['nombre_cliente'] ?? '');
                    if (empty($clientName)) {
                        $clientName = trim(($appData['nombre'] ?? '') . ' ' . ($appData['apellidos'] ?? ''));
                    }
                    if (empty($clientName)) $clientName = $app['client_name'] ?? '-';

                    // Determine primera vez / renovación
                    $subtype    = $app['subtype'] ?? '';
                    $esRenovación = stripos($subtype, 'renov') !== false;
                    $tipoLabel  = $esRenovación ? 'Renovación' : 'Primera vez';

                    // Is Canadian visa flow?
                    $appIsCanadian = !empty($app['is_canadian_visa']);

                    // Principal service subtype shown in list (Americano/Mexicano/Canadiense)
                    $servicePrimarySubtype = '';
                    $typeLower = strtolower((string) ($app['type'] ?? ''));
                    $subtypeLower = strtolower((string) $subtype);
                    $formNameLower = strtolower((string) ($app['form_name'] ?? ''));

                    if (
                        $appIsCanadian
                        || strpos($subtypeLower, 'canad') !== false
                        || strpos($formNameLower, 'canad') !== false
                    ) {
                        $servicePrimarySubtype = 'Canadiense';
                    } elseif (
                        strpos($subtypeLower, 'americ') !== false
                        || strpos($formNameLower, 'americ') !== false
                        || strpos($typeLower, 'americano') !== false
                    ) {
                        $servicePrimarySubtype = 'Americano';
                    } elseif (
                        strpos($subtypeLower, 'mexic') !== false
                        || strpos($formNameLower, 'mexic') !== false
                        || strpos($typeLower, 'mexicano') !== false
                    ) {
                        $servicePrimarySubtype = 'Mexicano';
                    }

                    // Status color class
                    $sc = 'bg-gray-100 text-gray-800';
                    if (in_array($app['status'], [STATUS_TRAMITE_CERRADO, STATUS_FINALIZADO])) $sc = 'bg-green-100 text-green-800';
                    elseif ($app['status'] === STATUS_EN_ESPERA_RESULTADO) $sc = 'bg-purple-100 text-purple-800';
                    elseif ($app['status'] === STATUS_CITA_PROGRAMADA)     $sc = 'bg-blue-100 text-blue-800';
                    elseif ($app['status'] === STATUS_EN_ESPERA_PAGO)      $sc = 'bg-yellow-100 text-yellow-800';
                    elseif ($app['status'] === STATUS_LISTO_SOLICITUD)     $sc = 'bg-red-100 text-red-800';

                    // Status display label (Canadian flow uses different labels)
                    $statusLabel = $app['status'];
                    if ($appIsCanadian) {
                        if ($app['status'] === STATUS_LISTO_SOLICITUD)     $statusLabel = 'Listo para carga en portal';
                        elseif ($app['status'] === STATUS_EN_ESPERA_PAGO)  $statusLabel = 'En espera de cita biométrica';
                        elseif ($app['status'] === STATUS_CITA_PROGRAMADA) $statusLabel = 'Biométricos programados';
                        elseif ($app['status'] === STATUS_EN_ESPERA_RESULTADO) $statusLabel = 'En espera de resolución';
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 md:px-6 py-4">
                        <span class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($clientName) ?></span>
                        <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($app['folio']) ?></p>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900"><?= htmlspecialchars($app['type']) ?></span>
                        <?php if ($appIsCanadian): ?>
                        <span class="ml-1 text-base" title="Visa Canadiense">🍁</span>
                        <?php endif; ?>
                        <?php if (!empty($servicePrimarySubtype)): ?>
                        <p class="text-xs text-gray-500 mt-0.5">Subtipo: <?= htmlspecialchars($servicePrimarySubtype) ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap hidden md:table-cell">
                        <span class="text-sm <?= $esRenovación ? 'text-orange-600' : 'text-blue-600' ?>">
                            <?= $esRenovación ? '<i class="fas fa-redo mr-1"></i>' : '<i class="fas fa-star mr-1"></i>' ?><?= $tipoLabel ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= $sc ?>">
                            <?= htmlspecialchars($statusLabel) ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-700 hidden md:table-cell">
                        <?= htmlspecialchars($app['creator_name']) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= date('d/m/Y', strtotime($app['created_at'])) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-2">
                        <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $app['id'] ?>"
                           class="btn-primary text-white px-3 py-1.5 rounded-lg hover:opacity-90 transition text-xs font-medium">
                            <i class="fas fa-folder-open mr-1"></i>Abrir expediente
                        </a>
                        <?php if ($_SESSION['user_role'] === ROLE_ADMIN): ?>
                        <?php if (($app['status'] ?? '') === STATUS_TRAMITE_CERRADO): ?>
                        <button
                            type="button"
                            class="text-blue-600 hover:text-blue-800"
                            title="Reactivar temporal"
                            data-reactivate-button="1"
                            data-app-id="<?= intval($app['id']) ?>"
                            data-folio="<?= htmlspecialchars($app['folio']) ?>">
                            <i class="fas fa-user-clock"></i>
                        </button>
                        <?php endif; ?>
                        <form method="POST" action="<?= BASE_URL ?>/solicitudes/eliminar/<?= $app['id'] ?>"
                              class="inline" onsubmit="return confirm('Esta accion no se puede deshacer.')">
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                        <p>No se encontraron solicitudes</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
        <div class="text-sm text-gray-700">
            Mostrando página <span class="font-semibold"><?= $page ?></span> de <span class="font-semibold"><?= $totalPages ?></span>
            (Total: <?= $total ?> registros)
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?><?= $isAdminRole && $searchTerm !== '' ? '&q=' . urlencode($searchTerm) : '' ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?><?= $isAdminRole && $searchTerm !== '' ? '&q=' . urlencode($searchTerm) : '' ?>" 
               class="px-4 py-2 btn-primary text-white rounded-lg hover:opacity-90">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($isAdminRole): ?>
<div id="reactivateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h3 class="text-lg font-bold text-gray-800">Reactivar trámite cerrado</h3>
            <button type="button" id="reactivateClose" class="text-gray-400 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="reactivateForm" method="POST" action="">
            <div class="px-6 py-4 space-y-4">
                <p class="text-sm text-gray-600">Expediente: <span id="reactivateFolio" class="font-semibold text-gray-800"></span></p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asesor</label>
                    <select name="advisor_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Selecciona asesor</option>
                        <?php foreach ($advisors as $advisor): ?>
                        <option value="<?= intval($advisor['id']) ?>"><?= htmlspecialchars($advisor['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Inicio</label>
                    <input type="datetime-local" name="start_at" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fin</label>
                    <input type="datetime-local" name="end_at" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-3">
                <button type="button" id="reactivateCancel" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Guardar reactivación</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('reactivateModal');
    var form = document.getElementById('reactivateForm');
    var folioLabel = document.getElementById('reactivateFolio');
    var closeBtn = document.getElementById('reactivateClose');
    var cancelBtn = document.getElementById('reactivateCancel');
    var triggerButtons = document.querySelectorAll('[data-reactivate-button="1"]');

    if (!modal || !form || !folioLabel) {
        return;
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    triggerButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var appId = button.getAttribute('data-app-id');
            var folio = button.getAttribute('data-folio') || '';
            form.action = '<?= BASE_URL ?>/solicitudes/reactivar-temporal/' + appId;
            folioLabel.textContent = folio;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });
});
</script>
<?php endif; ?>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
