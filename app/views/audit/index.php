<?php 
$title = 'Auditoría del Sistema';
ob_start(); 
?>

<div class="mb-6">
    <a href="<?= BASE_URL ?>/dashboard" class="text-primary hover:underline text-sm mb-2 inline-block">
        <i class="fas fa-arrow-left mr-2"></i>Volver a Dashboard
    </a>
    <h2 class="text-3xl font-bold text-gray-800">
        <i class="fas fa-clipboard-list mr-2"></i>Auditoría del Sistema
    </h2>
    <p class="text-gray-600">Registro de actividades y cambios realizados en el sistema</p>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="<?= BASE_URL ?>/auditoria" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-calendar mr-1"></i>Fecha Inicio
            </label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-calendar mr-1"></i>Fecha Fin
            </label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-user mr-1"></i>Usuario
            </label>
            <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Todos los usuarios</option>
                <?php foreach ($users as $user): ?>
                <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['full_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-search mr-1"></i>Acción
            </label>
            <input type="text" name="action" value="<?= htmlspecialchars($action) ?>" 
                   placeholder="Buscar acción..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-cube mr-1"></i>Módulo
            </label>
            <select name="module" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Todos los módulos</option>
                <?php foreach ($modules as $mod): ?>
                <option value="<?= htmlspecialchars($mod) ?>" <?= $module === $mod ? 'selected' : '' ?>>
                    <?= htmlspecialchars($mod) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full btn-primary text-white px-6 py-2 rounded-lg hover:opacity-90 transition">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Registros</p>
                <p class="text-3xl font-bold text-primary"><?= number_format($stats['total_records'] ?? 0) ?></p>
                <p class="text-xs text-gray-500 mt-1">
                    <?= date('d/m/Y', strtotime($startDate)) ?> a <?= date('d/m/Y', strtotime($endDate)) ?>
                </p>
            </div>
            <i class="fas fa-list text-4xl text-blue-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Usuarios Activos</p>
                <p class="text-3xl font-bold text-green-600"><?= $stats['active_users'] ?? 0 ?></p>
            </div>
            <i class="fas fa-users text-4xl text-green-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Páginas</p>
                <p class="text-3xl font-bold text-orange-600"><?= $totalPages ?></p>
            </div>
            <i class="fas fa-file text-4xl text-orange-200"></i>
        </div>
    </div>
</div>

<!-- Tabla de Auditoría -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha/Hora</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Módulo</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($auditLogs as $log): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($log['user_name'] ?? 'Sistema') ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($log['user_email'] ?? '') ?></p>
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= 
                            $log['action'] === 'login' ? 'bg-green-100 text-green-800' :
                            ($log['action'] === 'logout' ? 'bg-gray-100 text-gray-800' :
                            ($log['action'] === 'create' ? 'bg-blue-100 text-blue-800' :
                            ($log['action'] === 'update' ? 'bg-yellow-100 text-yellow-800' :
                            ($log['action'] === 'delete' ? 'bg-red-100 text-red-800' : 'bg-purple-100 text-purple-800'))))
                        ?>">
                            <?= htmlspecialchars($log['action']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        <?= htmlspecialchars($log['module']) ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <button type="button"
                                class="audit-detail-btn inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-primary border border-blue-100 rounded-lg hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                data-date="<?= htmlspecialchars(date('d/m/Y H:i', strtotime($log['created_at'])), ENT_QUOTES, 'UTF-8') ?>"
                                data-user="<?= htmlspecialchars($log['user_name'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?>"
                                data-email="<?= htmlspecialchars($log['user_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-action="<?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?>"
                                data-module="<?= htmlspecialchars($log['module'], ENT_QUOTES, 'UTF-8') ?>"
                                data-description="<?= htmlspecialchars($log['description'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ip="<?= htmlspecialchars($log['ip_address'] ?? '-', ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fas fa-eye mr-2"></i>Ver detalle
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($auditLogs)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                        <p>No hay registros de auditoría para los filtros seleccionados</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Mostrando página <span class="font-medium"><?= $page ?></span> de 
                <span class="font-medium"><?= $totalPages ?></span>
                (<?= number_format($total) ?> registros totales)
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&user_id=<?= urlencode($userId) ?>&action=<?= urlencode($action) ?>&module=<?= urlencode($module) ?>" 
                   class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Anterior
                </a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&user_id=<?= urlencode($userId) ?>&action=<?= urlencode($action) ?>&module=<?= urlencode($module) ?>" 
                   class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50">
                    Siguiente
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div id="audit-detail-modal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="audit-detail-title">
    <div class="absolute inset-0 bg-gray-900 bg-opacity-50" data-audit-detail-close></div>
    <div class="relative flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-2xl bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 id="audit-detail-title" class="text-lg font-bold text-gray-900">Detalle de auditoría</h3>
                    <p id="audit-detail-date" class="text-sm text-gray-500"></p>
                </div>
                <button type="button" id="audit-detail-close" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Cerrar detalle">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">Usuario</p>
                        <p id="audit-detail-user" class="mt-1 text-sm font-semibold text-gray-900"></p>
                        <p id="audit-detail-email" class="text-xs text-gray-500"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">IP</p>
                        <p id="audit-detail-ip" class="mt-1 text-sm font-mono text-gray-900"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">Acción</p>
                        <p id="audit-detail-action" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-gray-500">Módulo</p>
                        <p id="audit-detail-module" class="mt-1 text-sm text-gray-900"></p>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase text-gray-500">Descripción</p>
                    <p id="audit-detail-description" class="mt-2 whitespace-pre-wrap break-words text-sm leading-6 text-gray-800 bg-gray-50 border border-gray-200 rounded-lg p-4"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('audit-detail-modal');
    const closeBtn = document.getElementById('audit-detail-close');
    let lastFocusedButton = null;

    const fields = {
        date: document.getElementById('audit-detail-date'),
        user: document.getElementById('audit-detail-user'),
        email: document.getElementById('audit-detail-email'),
        action: document.getElementById('audit-detail-action'),
        module: document.getElementById('audit-detail-module'),
        description: document.getElementById('audit-detail-description'),
        ip: document.getElementById('audit-detail-ip')
    };

    function openDetail(button) {
        lastFocusedButton = button;
        fields.date.textContent = button.dataset.date || '';
        fields.user.textContent = button.dataset.user || 'Sistema';
        fields.email.textContent = button.dataset.email || '';
        fields.action.textContent = button.dataset.action || '-';
        fields.module.textContent = button.dataset.module || '-';
        fields.description.textContent = button.dataset.description || '-';
        fields.ip.textContent = button.dataset.ip || '-';
        modal.classList.remove('hidden');
        closeBtn.focus();
    }

    function closeDetail() {
        modal.classList.add('hidden');
        if (lastFocusedButton) {
            lastFocusedButton.focus();
        }
    }

    document.querySelectorAll('.audit-detail-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            openDetail(button);
        });
    });

    closeBtn.addEventListener('click', closeDetail);
    modal.querySelectorAll('[data-audit-detail-close]').forEach(function (element) {
        element.addEventListener('click', closeDetail);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeDetail();
        }
    });
});
</script>
<?php $content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
