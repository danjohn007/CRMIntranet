<?php 
$title = 'Solicitudes';
ob_start(); 
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
    <form method="GET" action="<?= BASE_URL ?>/solicitudes" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estatus</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
                <option value="">Todos los estatus</option>
                <option value="<?= STATUS_NUEVO ?>" <?= $status === STATUS_NUEVO ? 'selected' : '' ?>><?= STATUS_NUEVO ?> (Nuevo)</option>
                <option value="<?= STATUS_LISTO_SOLICITUD ?>" <?= $status === STATUS_LISTO_SOLICITUD ? 'selected' : '' ?>><?= STATUS_LISTO_SOLICITUD ?></option>
                <option value="<?= STATUS_EN_ESPERA_PAGO ?>" <?= $status === STATUS_EN_ESPERA_PAGO ? 'selected' : '' ?>><?= STATUS_EN_ESPERA_PAGO ?></option>
                <option value="<?= STATUS_CITA_PROGRAMADA ?>" <?= $status === STATUS_CITA_PROGRAMADA ? 'selected' : '' ?>><?= STATUS_CITA_PROGRAMADA ?></option>
                <option value="<?= STATUS_EN_ESPERA_RESULTADO ?>" <?= $status === STATUS_EN_ESPERA_RESULTADO ? 'selected' : '' ?>><?= STATUS_EN_ESPERA_RESULTADO ?></option>
                <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
                <option value="<?= STATUS_TRAMITE_CERRADO ?>" <?= $status === STATUS_TRAMITE_CERRADO ? 'selected' : '' ?>><?= STATUS_TRAMITE_CERRADO ?></option>
                <option value="<?= STATUS_FINALIZADO ?>" <?= $status === STATUS_FINALIZADO ? 'selected' : '' ?>><?= STATUS_FINALIZADO ?> (legacy)</option>
                <?php endif; ?>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
            <select name="type" class="w-full border border-gray-300 rounded-lg px-3 md:px-4 py-2 text-sm md:text-base">
                <option value="">Todos los tipos</option>
                <option value="Visa" <?= $type === 'Visa' ? 'selected' : '' ?>>Visa</option>
                <option value="Pasaporte" <?= $type === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
            </select>
        </div>
        
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
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Folio</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Tipo</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Subtipo</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Estatus</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Creado por</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Fecha</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Progreso</th>
                    <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden xl:table-cell">Estado Financiero</th>
                    <?php endif; ?>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($applications as $app): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-xs md:text-sm font-semibold text-primary">
                            <?= htmlspecialchars($app['folio']) ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="text-xs md:text-sm text-gray-900"><?= htmlspecialchars($app['type']) ?></span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                        <span class="text-xs md:text-sm text-gray-600"><?= htmlspecialchars($app['subtype'] ?? '-') ?></span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= 
                            ($app['status'] === STATUS_TRAMITE_CERRADO || $app['status'] === STATUS_FINALIZADO) ? 'bg-green-100 text-green-800' :
                            ($app['status'] === STATUS_EN_ESPERA_RESULTADO ? 'bg-purple-100 text-purple-800' :
                            ($app['status'] === STATUS_CITA_PROGRAMADA ? 'bg-blue-100 text-blue-800' :
                            ($app['status'] === STATUS_EN_ESPERA_PAGO ? 'bg-yellow-100 text-yellow-800' :
                            ($app['status'] === STATUS_LISTO_SOLICITUD ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))))
                        ?>">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-700 hidden md:table-cell">
                        <?= htmlspecialchars($app['creator_name']) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                        <?= date('d/m/Y H:i', strtotime($app['created_at'])) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap hidden xl:table-cell">
                        <?php if ($app['progress_percentage'] > 0): ?>
                        <div class="flex items-center space-x-2">
                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $app['progress_percentage'] ?>%"></div>
                            </div>
                            <span class="text-xs text-gray-600"><?= number_format($app['progress_percentage'], 0) ?>%</span>
                        </div>
                        <?php else: ?>
                        <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($app['financial_status']): ?>
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= 
                            $app['financial_status'] === FINANCIAL_PAGADO ? 'bg-green-100 text-green-800' :
                            ($app['financial_status'] === FINANCIAL_PARCIAL ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')
                        ?>">
                            <?= htmlspecialchars($app['financial_status']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-xs text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $app['id'] ?>" 
                           class="text-primary hover:underline mr-3">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="<?= in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE]) ? '8' : '7' ?>" 
                        class="px-6 py-12 text-center text-gray-500">
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
            <a href="?page=<?= $page - 1 ?>&status=<?= $status ?>&type=<?= $type ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&status=<?= $status ?>&type=<?= $type ?>" 
               class="px-4 py-2 btn-primary text-white rounded-lg hover:opacity-90">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
