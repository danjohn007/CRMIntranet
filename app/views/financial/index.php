<?php 
$title = 'Módulo Financiero';
ob_start(); 
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Módulo Financiero</h2>
    <p class="text-gray-600">Control de costos y pagos de solicitudes</p>
</div>

<!-- Resumen Financiero -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Solicitudes</p>
                <p class="text-3xl font-bold text-blue-600"><?= $summary['total_applications'] ?? 0 ?></p>
            </div>
            <i class="fas fa-file-invoice text-4xl text-blue-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Costos</p>
                <p class="text-2xl font-bold text-gray-800">$<?= number_format($summary['total_costs'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-dollar-sign text-4xl text-gray-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Pagado</p>
                <p class="text-2xl font-bold text-green-600">$<?= number_format($summary['total_paid'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-check-circle text-4xl text-green-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Saldo Pendiente</p>
                <p class="text-2xl font-bold text-red-600">$<?= number_format($summary['total_balance'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-exclamation-circle text-4xl text-red-200"></i>
        </div>
    </div>
</div>

<!-- Estadísticas por Estado -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Distribución por Estado Financiero</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg">
            <span class="font-medium text-gray-700">Pendiente</span>
            <span class="bg-red-600 text-white px-4 py-2 rounded-full text-lg font-bold">
                <?= $summary['pending_count'] ?? 0 ?>
            </span>
        </div>
        <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-lg">
            <span class="font-medium text-gray-700">Parcial</span>
            <span class="bg-yellow-600 text-white px-4 py-2 rounded-full text-lg font-bold">
                <?= $summary['partial_count'] ?? 0 ?>
            </span>
        </div>
        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
            <span class="font-medium text-gray-700">Pagado</span>
            <span class="bg-green-600 text-white px-4 py-2 rounded-full text-lg font-bold">
                <?= $summary['paid_count'] ?? 0 ?>
            </span>
        </div>
    </div>
</div>

<!-- Lista de Solicitudes -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">Solicitudes con Información Financiera</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Folio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estatus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Costos</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Pagado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($applications as $app): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-sm font-semibold text-blue-600">
                            <?= htmlspecialchars($app['folio']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?= htmlspecialchars($app['type']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full <?= 
                            $app['status'] === STATUS_FINALIZADO ? 'bg-green-100 text-green-800' :
                            ($app['status'] === STATUS_APROBADO ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')
                        ?>">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold">
                        $<?= number_format($app['total_costs'] ?? 0, 2) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                        $<?= number_format($app['total_paid'] ?? 0, 2) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold <?= ($app['balance'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' ?>">
                        $<?= number_format($app['balance'] ?? 0, 2) ?>
                    </td>
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
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/financiero/solicitud/<?= $app['id'] ?>" 
                           class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye mr-1"></i>Ver Detalles
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                        <p>No hay solicitudes registradas</p>
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
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
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
