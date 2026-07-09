<?php
$title = 'Mi trámite';
ob_start();
?>

<div class="mb-6">
    <h2 class="text-3xl font-bold text-gray-800">Portal del Cliente</h2>
    <p class="text-gray-600 mt-1">Consulta tu avance, guarda información por partes y mantén comunicación con el equipo.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-passport text-blue-600 mr-2"></i>Mis trámites</h3>
            <?php if (!empty($applications)): ?>
                <div class="space-y-4">
                <?php foreach ($applications as $app): ?>
                    <?php $progress = isset($app['progress_percentage']) ? (float)$app['progress_percentage'] : 0; ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                            <div>
                                <p class="font-bold text-lg text-gray-800"><?= htmlspecialchars($app['folio']) ?></p>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($app['form_name'] ?? $app['type'] ?? 'Trámite') ?></p>
                                <p class="text-sm text-gray-600 mt-2"><strong>Solicitante:</strong> <?= htmlspecialchars($app['client_name'] ?? 'Sin nombre') ?></p>
                                <p class="text-sm text-gray-600"><strong>Asesor:</strong> <?= htmlspecialchars($app['creator_name'] ?? 'Por asignar') ?></p>
                            </div>
                            <div class="text-left md:text-right space-y-2">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full"><?= htmlspecialchars($app['status']) ?></span>
                                <?php if (!empty($app['client_update_pending'])): ?>
                                    <span class="block bg-yellow-100 text-yellow-800 text-xs font-semibold px-3 py-1 rounded-full">Pendiente de revisión</span>
                                <?php endif; ?>
                                <?php if (!empty($app['unread_messages'])): ?>
                                    <span class="block bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full"><?= (int)$app['unread_messages'] ?> mensaje(s) nuevo(s)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Avance de formulario</span>
                                <span><?= number_format($progress, 0) ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min(100, max(0, $progress)) ?>%"></div>
                            </div>
                        </div>
                        <?php if (!empty($app['latest_message'])): ?>
                            <div class="mt-4 bg-gray-50 border border-gray-100 rounded-lg p-3 text-sm">
                                <p class="text-xs text-gray-500 mb-1">Último mensaje <?= !empty($app['latest_message_at']) ? '· ' . date('d/m/Y H:i', strtotime($app['latest_message_at'])) : '' ?></p>
                                <p class="text-gray-700"><span class="font-semibold"><?= htmlspecialchars($app['latest_message_role'] === 'Cliente' ? 'Tú:' : 'Equipo:') ?></span> <?= htmlspecialchars(strlen($app['latest_message']) > 120 ? substr($app['latest_message'], 0, 120) . '...' : $app['latest_message']) ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="<?= BASE_URL ?>/mi-tramite/ver/<?= $app['id'] ?>" class="btn-primary text-white px-4 py-2 rounded-lg hover:opacity-90 text-sm">
                                <i class="fas fa-eye mr-1"></i>Ver y actualizar
                            </a>
                            <span class="text-sm text-gray-500 self-center"><i class="fas fa-file mr-1"></i><?= (int)$app['documents_count'] ?> documento(s)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-10 text-gray-500">
                    <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                    <p class="font-semibold">Aún no tienes trámites asignados.</p>
                    <p class="text-sm mt-1">Cuando el equipo vincule tu usuario a un trámite, aparecerá aquí.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-user text-green-600 mr-2"></i>Mi información</h3>
            <form method="POST" action="<?= BASE_URL ?>/mi-tramite/actualizar-perfil" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
                    <input type="text" name="full_name" required value="<?= htmlspecialchars($client['full_name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($client['email'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($client['phone'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <button type="submit" class="w-full btn-primary text-white py-2 rounded-lg hover:opacity-90">
                    <i class="fas fa-save mr-1"></i>Actualizar información
                </button>
            </form>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 text-blue-800">
            <h4 class="font-bold mb-2"><i class="fas fa-info-circle mr-1"></i>¿Qué puedes hacer aquí?</h4>
            <ul class="text-sm space-y-1 list-disc ml-5">
                <li>Ver el avance de tu trámite.</li>
                <li>Actualizar formularios.</li>
                <li>Subir documentos.</li>
                <li>Responder observaciones.</li>
                <li>Enviar mensajes al equipo.</li>
            </ul>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
