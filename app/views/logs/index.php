<?php 
$title = 'Logs de Errores';
ob_start(); 
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Logs de Errores</h2>
            <p class="text-gray-600">Visualización y gestión de logs del sistema</p>
        </div>
        <div class="space-x-2">
            <a href="<?= BASE_URL ?>/logs/descargar" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 inline-block">
                <i class="fas fa-download mr-2"></i>Descargar Log
            </a>
            <form method="POST" action="<?= BASE_URL ?>/logs/limpiar" class="inline"
                  onsubmit="return confirm('¿Está seguro de limpiar el log? Se creará un backup.')">
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                    <i class="fas fa-trash mr-2"></i>Limpiar Log
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="<?= BASE_URL ?>/logs" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2"
                   placeholder="Buscar en logs...">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nivel</label>
            <select name="level" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <option value="">Todos</option>
                <option value="error" <?= $level === 'error' ? 'selected' : '' ?>>Error</option>
                <option value="warning" <?= $level === 'warning' ? 'selected' : '' ?>>Warning</option>
                <option value="notice" <?= $level === 'notice' ? 'selected' : '' ?>>Notice</option>
                <option value="info" <?= $level === 'info' ? 'selected' : '' ?>>Info</option>
            </select>
        </div>
        
        <div class="flex items-end">
            <button type="submit" class="w-full bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Info del archivo -->
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
    <div class="flex items-center">
        <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
        <div>
            <p class="text-sm text-blue-700">
                <strong>Archivo:</strong> <?= htmlspecialchars($logFile) ?>
            </p>
            <p class="text-sm text-blue-700">
                <strong>Total de entradas:</strong> <?= $total ?> 
                <?php if (!empty($search) || !empty($level)): ?>
                (filtradas)
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- Tabla de Logs -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <?php if (!empty($logs)): ?>
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Fecha/Hora</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Nivel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mensaje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($logs as $log): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-xs font-mono text-gray-600"><?= htmlspecialchars($log['date']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full font-medium <?= 
                            $log['level'] === 'error' ? 'bg-red-100 text-red-800' :
                            ($log['level'] === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                            ($log['level'] === 'notice' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))
                        ?>">
                            <?= strtoupper(htmlspecialchars($log['level'])) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 font-mono break-words">
                            <?= htmlspecialchars($log['message']) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="p-12 text-center">
            <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">No hay logs para mostrar</p>
            <?php if (!empty($search) || !empty($level)): ?>
            <p class="text-sm text-gray-400 mt-2">Intenta cambiar los filtros</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
        <div class="text-sm text-gray-700">
            Página <span class="font-semibold"><?= $page ?></span> de <span class="font-semibold"><?= $totalPages ?></span>
            (Total: <?= $total ?> entradas)
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&level=<?= urlencode($level) ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&level=<?= urlencode($level) ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Información adicional -->
<div class="mt-6 bg-yellow-50 border-l-4 border-yellow-500 p-4">
    <div class="flex items-start">
        <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mr-3 mt-1"></i>
        <div>
            <p class="text-sm text-yellow-700 font-medium mb-2">Consideraciones sobre los logs:</p>
            <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                <li>Los logs se ordenan de más reciente a más antiguo</li>
                <li>Al limpiar el log, se crea automáticamente un backup con fecha y hora</li>
                <li>Los logs pueden contener información sensible, manéjalos con cuidado</li>
                <li>Se recomienda limpiar los logs periódicamente para mantener el rendimiento</li>
            </ul>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
