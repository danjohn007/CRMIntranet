<?php 
$title = 'Dashboard';
ob_start(); 
?>

<div class="mb-4 md:mb-6">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Dashboard</h2>
    <p class="text-sm md:text-base text-gray-600">Bienvenido, <?= $_SESSION['user_name'] ?></p>
</div>

<!-- Estadísticas Generales -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Solicitudes</p>
                <p class="text-2xl md:text-3xl font-bold text-primary truncate"><?= $stats['total_applications'] ?? 0 ?></p>
            </div>
            <i class="fas fa-file-alt text-3xl md:text-4xl text-gray-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <?php if (isset($stats['financial'])): ?>
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Costos</p>
                <p class="text-lg md:text-2xl font-bold text-green-600 truncate">$<?= number_format($stats['financial']['total_costs'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-dollar-sign text-3xl md:text-4xl text-green-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Total Pagado</p>
                <p class="text-lg md:text-2xl font-bold text-primary truncate">$<?= number_format($stats['financial']['total_paid'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-money-bill-wave text-3xl md:text-4xl text-gray-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <p class="text-gray-600 text-xs md:text-sm">Saldo Pendiente</p>
                <p class="text-lg md:text-2xl font-bold text-red-600 truncate">$<?= number_format($stats['financial']['total_balance'] ?? 0, 2) ?></p>
            </div>
            <i class="fas fa-exclamation-circle text-3xl md:text-4xl text-red-200 flex-shrink-0 ml-2"></i>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Gráficas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
    <!-- Gráfica 1: Solicitudes por Estatus -->
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Solicitudes por Estatus</h3>
        <div class="relative" style="height: 300px;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    
    <!-- Gráfica 2: Tendencia de Solicitudes (últimos 6 meses) -->
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Tendencia de Solicitudes</h3>
        <div class="relative" style="height: 300px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
    
    <!-- Gráfica 3: Tipos de Solicitudes -->
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Tipos de Solicitudes</h3>
        <div class="relative" style="height: 300px;">
            <canvas id="typeChart"></canvas>
        </div>
    </div>
    
    <?php if (isset($stats['payments_by_method']) && !empty($stats['payments_by_method'])): ?>
    <!-- Gráfica 4: Métodos de Pago -->
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Métodos de Pago</h3>
        <div class="relative" style="height: 300px;">
            <canvas id="paymentMethodChart"></canvas>
        </div>
    </div>
    <?php else: ?>
    <!-- Gráfica alternativa: Resumen de status con detalles -->
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-4">Resumen de Solicitudes</h3>
        <div class="space-y-3">
            <?php foreach ($stats['by_status'] ?? [] as $status): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                <span class="font-medium text-gray-700 text-sm md:text-base"><?= htmlspecialchars($status['status']) ?></span>
                <span class="bg-primary text-white px-3 py-1 rounded-full text-sm font-bold">
                    <?= $status['count'] ?>
                </span>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($stats['by_status'])): ?>
            <p class="text-gray-500 text-center py-4 text-sm md:text-base">No hay solicitudes registradas</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Tablas con scroll horizontal -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
    <?php if (isset($stats['recent_payments'])): ?>
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 md:p-6 border-b border-gray-200">
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Pagos Recientes</h3>
        </div>
        <div class="table-container">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Folio</th>
                        <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Fecha</th>
                        <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($stats['recent_payments'] ?? [] as $payment): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-xs md:text-sm text-primary"><?= htmlspecialchars($payment['folio']) ?></span>
                        </td>
                        <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                            <?= date('d/m/Y', strtotime($payment['payment_date'])) ?>
                        </td>
                        <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                            <span class="text-green-600 font-bold text-sm md:text-base">
                                $<?= number_format($payment['amount'], 2) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($stats['recent_payments'])): ?>
                    <tr>
                        <td colspan="3" class="px-3 md:px-6 py-8 text-center text-gray-500 text-sm md:text-base">
                            No hay pagos registrados
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Solicitudes Recientes -->
<div class="bg-white rounded-lg shadow">
    <div class="p-4 md:p-6 border-b border-gray-200">
        <h3 class="text-lg md:text-xl font-bold text-gray-800">Solicitudes Recientes</h3>
    </div>
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Folio</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Tipo</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Estatus</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap hidden md:table-cell">Creado por</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Fecha</th>
                    <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($stats['recent_applications'] ?? [] as $app): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-xs md:text-sm text-primary"><?= htmlspecialchars($app['folio']) ?></span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="text-xs md:text-sm text-gray-700"><?= htmlspecialchars($app['type']) ?></span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full <?= 
                            $app['status'] === STATUS_FINALIZADO ? 'bg-green-100 text-green-800' :
                            ($app['status'] === STATUS_APROBADO ? 'bg-blue-100 text-blue-800' :
                            ($app['status'] === STATUS_RECHAZADO ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'))
                        ?>">
                            <?= htmlspecialchars($app['status']) ?>
                        </span>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-700 hidden md:table-cell">
                        <?= htmlspecialchars($app['creator_name']) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap text-xs md:text-sm text-gray-500">
                        <?= date('d/m/Y', strtotime($app['created_at'])) ?>
                    </td>
                    <td class="px-3 md:px-6 py-4 whitespace-nowrap">
                        <a href="<?= BASE_URL ?>/solicitudes/ver/<?= $app['id'] ?>" 
                           class="text-primary hover:opacity-80 text-xs md:text-sm">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($stats['recent_applications'])): ?>
                <tr>
                    <td colspan="6" class="px-3 md:px-6 py-8 text-center text-gray-500 text-sm md:text-base">
                        No hay solicitudes recientes
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get primary color from CSS variable
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim();
    const secondaryColor = getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim();
    
    // Helper function to generate color palette
    function generateColors(count) {
        const colors = [
            primaryColor,
            secondaryColor,
            '#10b981', // green
            '#f59e0b', // amber
            '#ef4444', // red
            '#8b5cf6', // violet
            '#06b6d4', // cyan
            '#ec4899', // pink
        ];
        return colors.slice(0, count);
    }
    
    // Chart 1: Status Pie Chart
    <?php if (!empty($stats['by_status'])): ?>
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stats['by_status'], 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats['by_status'], 'count')) ?>,
                    backgroundColor: generateColors(<?= count($stats['by_status']) ?>),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Chart 2: Trend Line Chart
    <?php if (!empty($stats['applications_by_month'])): ?>
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_map(function($item) {
                    $date = DateTime::createFromFormat('Y-m', $item['month']);
                    return $date ? $date->format('M Y') : $item['month'];
                }, $stats['applications_by_month'])) ?>,
                datasets: [{
                    label: 'Solicitudes',
                    data: <?= json_encode(array_column($stats['applications_by_month'], 'count')) ?>,
                    borderColor: primaryColor,
                    backgroundColor: primaryColor + '20',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Chart 3: Type Bar Chart
    <?php if (!empty($stats['applications_by_type'])): ?>
    const typeCtx = document.getElementById('typeChart');
    if (typeCtx) {
        new Chart(typeCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($stats['applications_by_type'], 'type')) ?>,
                datasets: [{
                    label: 'Solicitudes',
                    data: <?= json_encode(array_column($stats['applications_by_type'], 'count')) ?>,
                    backgroundColor: [primaryColor, secondaryColor, '#10b981'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    // Chart 4: Payment Methods
    <?php if (!empty($stats['payments_by_method'])): ?>
    const paymentCtx = document.getElementById('paymentMethodChart');
    if (paymentCtx) {
        new Chart(paymentCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($stats['payments_by_method'], 'payment_method')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats['payments_by_method'], 'total')) ?>,
                    backgroundColor: generateColors(<?= count($stats['payments_by_method']) ?>),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': $' + context.parsed.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
