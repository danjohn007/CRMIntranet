<?php 
$title = 'Dashboard';
ob_start(); 
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
    <p class="text-gray-600">Bienvenido, <?= $_SESSION['user_name'] ?></p>
</div>

<!-- Estadísticas Generales -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Solicitudes</p>
                <p class="text-3xl font-bold text-blue-600"><?= $stats['total_applications'] ?? 0 ?></p>
            </div>
            <i class="fas fa-file-alt text-4xl text-blue-200"></i>
        </div>
    </div>
    
    <?php if (isset($stats['financial'])): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Costos</p>
                <p class="text-2xl font-bold text-green-600">$<?= number_format($stats['financial']['total_costs'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-dollar-sign text-4xl text-green-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Pagado</p>
                <p class="text-2xl font-bold text-blue-600">$<?= number_format($stats['financial']['total_paid'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-money-bill-wave text-4xl text-blue-200"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Saldo Pendiente</p>
                <p class="text-2xl font-bold text-red-600">$<?= number_format($stats['financial']['total_balance'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-exclamation-circle text-4xl text-red-200"></i>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Solicitudes por Estatus -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Solicitudes por Estatus</h3>
        <div class="space-y-3">
            <?php foreach ($stats['by_status'] ?? [] as $status): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="font-medium text-gray-700"><?= htmlspecialchars($status['status']) ?></span>
                <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                    <?= $status['count'] ?>
                </span>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($stats['by_status'])): ?>
            <p class="text-gray-500 text-center py-4">No hay solicitudes registradas</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isset($stats['recent_payments'])): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Pagos Recientes</h3>
        <div class="space-y-3">
            <?php foreach ($stats['recent_payments'] ?? [] as $payment): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <div>
                    <p class="font-medium text-gray-700"><?= htmlspecialchars($payment['folio']) ?></p>
                    <p class="text-sm text-gray-500"><?= date('d/m/Y', strtotime($payment['payment_date'])) ?></p>
                </div>
                <span class="text-green-600 font-bold">
                    $<?= number_format($payment['amount'], 2) ?>
                </span>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($stats['recent_payments'])): ?>
            <p class="text-gray-500 text-center py-4">No hay pagos registrados</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Solicitudes Recientes -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-xl font-bold text-gray-800">Solicitudes Recientes</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Folio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estatus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creado por</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($stats['recent_applications'] ?? [] as $app): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-sm text-blue-600"><?= htmlspecialchars($app['folio']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-700"><?= htmlspecialchars($app['type']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full <?= 
                            $app['status'] === STATUS_FINALIZADO ? 'bg-green-100 text-green-800' :
                            ($app['status'] === STATUS_APROBADO ? 'bg-blue-100 text-blue-800' :
                            ($app['status'] === STATUS_RECHAZADO ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'))
                        ?>">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <?= htmlspecialchars($app['creator_name']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?= date('d/m/Y', strtotime($app['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $app['id'] ?>" 
                           class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($stats['recent_applications'])): ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No hay solicitudes recientes
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
