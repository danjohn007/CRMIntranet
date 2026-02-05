<?php 
$title = 'Formularios';
ob_start(); 
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h2 class="text-3xl font-bold text-gray-800">Constructor de Formularios</h2>
        <p class="text-gray-600">Gestión de formularios dinámicos</p>
    </div>
    <a href="<?= BASE_URL ?>/formularios/crear" class="btn-primary text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
        <i class="fas fa-plus mr-2"></i>Nuevo Formulario
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="table-container">
        <table class="w-full">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Versión</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creado por</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($forms as $form): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <span class="font-medium text-gray-900"><?= htmlspecialchars($form['name']) ?></span>
                        <?php if ($form['description']): ?>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($form['description']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center space-x-2 mt-1">
                            <?php if ($form['cost'] > 0): ?>
                            <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                <i class="fas fa-dollar-sign"></i> $<?= number_format($form['cost'], 2) ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($form['pagination_enabled']): ?>
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                <i class="fas fa-layer-group"></i> Paginado
                            </span>
                            <?php endif; ?>
                            <?php if ($form['paypal_enabled']): ?>
                            <span class="text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                <i class="fab fa-paypal"></i> PayPal
                            </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900"><?= htmlspecialchars($form['type']) ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-600"><?= htmlspecialchars($form['subtype'] ?? '-') ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-mono text-gray-700">v<?= $form['version'] ?></span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($form['is_published']): ?>
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 font-medium">
                            <i class="fas fa-check-circle"></i> Publicado
                        </span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 font-medium">
                            <i class="fas fa-eye-slash"></i> Borrador
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <?= htmlspecialchars($form['creator_name']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center space-x-3">
                            <?php if ($form['public_token'] && $form['is_published']): ?>
                            <button onclick="copyPublicLink('<?= BASE_URL ?>/public/form/<?= htmlspecialchars($form['public_token']) ?>')"
                                    class="text-blue-600 hover:text-blue-800" title="Copiar enlace público">
                                <i class="fas fa-link"></i>
                            </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/formularios/editar/<?= $form['id'] ?>" 
                               class="text-primary hover:underline" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?= BASE_URL ?>/formularios/publicar/<?= $form['id'] ?>" class="inline">
                                <button type="submit" class="<?= $form['is_published'] ? 'text-gray-600 hover:text-gray-800' : 'text-green-600 hover:text-green-800' ?>" 
                                        title="<?= $form['is_published'] ? 'Despublicar' : 'Publicar' ?>">
                                    <i class="fas fa-<?= $form['is_published'] ? 'eye-slash' : 'eye' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>/formularios/eliminar/<?= $form['id'] ?>" 
                                  class="inline" onsubmit="return confirm('¿Está seguro de eliminar este formulario?')">
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($forms)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                        <p>No hay formularios registrados</p>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between border-t">
        <div class="text-sm text-gray-700">
            Página <span class="font-semibold"><?= $page ?></span> de <span class="font-semibold"><?= $totalPages ?></span>
            (Total: <?= $total ?> registros)
        </div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 btn-primary text-white rounded-lg hover:opacity-90">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyPublicLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        // Show success message
        const message = document.createElement('div');
        message.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        message.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Enlace copiado al portapapeles';
        document.body.appendChild(message);
        
        setTimeout(function() {
            message.remove();
        }, 3000);
    }, function(err) {
        console.error('Error copying to clipboard:', err);
        alert('No se pudo copiar el enlace. Por favor, cópielo manualmente.');
    });
}
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
