<?php
$title = 'Mi trámite - ' . $application['folio'];
ob_start();

$responseByNote = [];
foreach ($responses as $resp) {
    $responseByNote[$resp['note_id']][] = $resp;
}

function clientFieldValue($dataJson, $fieldId) {
    $value = $dataJson[$fieldId] ?? '';
    if (is_array($value)) {
        return implode(', ', $value);
    }
    return (string)$value;
}

function isClientEditableField($field) {
    $type = $field['type'] ?? 'text';
    return !in_array($type, ['label', 'paragraph', 'html', 'heading', 'file']);
}

function buildClientPages($allFields, $application) {
    $fieldMap = [];
    foreach ($allFields as $field) {
        if (!empty($field['id'])) {
            $fieldMap[$field['id']] = $field;
        }
    }

    $pages = [];
    $used = [];
    $pagesRaw = json_decode($application['pages_json'] ?? '[]', true);

    if (!empty($application['pagination_enabled']) && is_array($pagesRaw) && !empty($pagesRaw)) {
        foreach ($pagesRaw as $index => $page) {
            $pageFields = [];
            foreach (($page['fieldIds'] ?? []) as $fieldId) {
                if (isset($fieldMap[$fieldId])) {
                    $pageFields[] = $fieldMap[$fieldId];
                    $used[$fieldId] = true;
                }
            }
            if (!empty($pageFields)) {
                $pages[] = [
                    'name' => $page['name'] ?? ('Página ' . ($index + 1)),
                    'fields' => $pageFields,
                ];
            }
        }

        $missing = [];
        foreach ($allFields as $field) {
            $id = $field['id'] ?? null;
            if ($id && empty($used[$id])) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            $pages[] = ['name' => 'Datos adicionales', 'fields' => $missing];
        }
    }

    if (empty($pages)) {
        $chunks = array_chunk($allFields, 18);
        foreach ($chunks as $index => $chunk) {
            $pages[] = [
                'name' => 'Sección ' . ($index + 1),
                'fields' => $chunk,
            ];
        }
    }

    return $pages;
}

function renderClientField($field, $dataJson) {
    $type = $field['type'] ?? 'text';
    $id = $field['id'] ?? '';
    $label = $field['label'] ?? $id;
    $value = clientFieldValue($dataJson, $id);
    $isRequired = !empty($field['required']);

    if (in_array($type, ['label', 'heading'])) {
        echo '<div class="md:col-span-2 pt-2"><div class="border-b border-gray-100 pb-2"><h4 class="text-base font-semibold text-gray-800">' . htmlspecialchars($label) . '</h4></div></div>';
        return;
    }

    if ($type === 'paragraph' || $type === 'html') {
        echo '<div class="md:col-span-2 text-sm text-gray-600 bg-gray-50 rounded-lg p-3">' . nl2br(htmlspecialchars($label)) . '</div>';
        return;
    }

    if ($type === 'file') {
        echo '<div class="md:col-span-2 rounded-xl border border-dashed border-gray-200 bg-gray-50 p-4">';
        echo '<p class="text-sm font-semibold text-gray-700">' . htmlspecialchars($label) . '</p>';
        echo '<p class="text-xs text-gray-500 mt-1">Este campo requiere archivo. Sube el documento en la sección <strong>Documentación</strong>.</p>';
        echo '</div>';
        return;
    }

    echo '<div class="space-y-1">';
    echo '<label class="block text-sm font-medium text-gray-700">' . htmlspecialchars($label) . ($isRequired ? ' <span class="text-red-500">*</span>' : '') . '</label>';

    $baseClass = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400';

    if ($type === 'textarea') {
        echo '<textarea name="form_data[' . htmlspecialchars($id) . ']" rows="3" class="' . $baseClass . '">' . htmlspecialchars($value) . '</textarea>';
    } elseif ($type === 'select') {
        echo '<select name="form_data[' . htmlspecialchars($id) . ']" class="' . $baseClass . '">';
        echo '<option value="">Seleccione...</option>';
        foreach (($field['options'] ?? []) as $opt) {
            $selected = ((string)$opt === $value) ? 'selected' : '';
            echo '<option value="' . htmlspecialchars($opt) . '" ' . $selected . '>' . htmlspecialchars($opt) . '</option>';
        }
        echo '</select>';
    } elseif (in_array($type, ['radio', 'checkbox'])) {
        $stored = $dataJson[$id] ?? ($type === 'checkbox' ? [] : '');
        if (!is_array($stored)) {
            $stored = [$stored];
        }
        echo '<div class="flex flex-wrap gap-2">';
        foreach (($field['options'] ?? []) as $opt) {
            $checked = in_array((string)$opt, array_map('strval', $stored)) ? 'checked' : '';
            $name = $type === 'checkbox' ? 'form_data[' . htmlspecialchars($id) . '][]' : 'form_data[' . htmlspecialchars($id) . ']';
            echo '<label class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">';
            echo '<input type="' . htmlspecialchars($type) . '" name="' . $name . '" value="' . htmlspecialchars($opt) . '" ' . $checked . ' class="text-blue-600"> ' . htmlspecialchars($opt);
            echo '</label>';
        }
        echo '</div>';
    } else {
        $inputType = in_array($type, ['email','tel','date','number','time']) ? $type : 'text';
        echo '<input type="' . htmlspecialchars($inputType) . '" name="form_data[' . htmlspecialchars($id) . ']" value="' . htmlspecialchars($value) . '" class="' . $baseClass . '">';
    }

    echo '<p class="text-[11px] text-gray-400">' . ($isRequired ? 'Campo solicitado para completar el trámite.' : 'Puedes dejarlo pendiente si aún no tienes el dato.') . '</p>';
    echo '</div>';
}

function documentStatusClass($status) {
    $status = $status ?: 'en_revision';
    $classes = [
        'pendiente' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'en_revision' => 'bg-blue-50 text-blue-700 border-blue-200',
        'aceptado' => 'bg-green-50 text-green-700 border-green-200',
        'rechazado' => 'bg-red-50 text-red-700 border-red-200',
    ];
    return $classes[$status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
}

$allFields = $fields['fields'] ?? [];
$clientPages = buildClientPages($allFields, $application);
$totalPages = max(1, count($clientPages));
$currentPage = isset($currentPage) ? (int)$currentPage : 1;
$currentPage = min($totalPages, max(1, $currentPage));
$currentPageData = $clientPages[$currentPage - 1] ?? ['name' => 'Formulario', 'fields' => $allFields];
$progress = isset($application['progress_percentage']) ? (float)$application['progress_percentage'] : 0;
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="<?= BASE_URL ?>/mi-tramite" class="text-primary hover:underline text-sm"><i class="fas fa-arrow-left mr-1"></i>Volver al portal</a>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mt-2"><?= htmlspecialchars($application['folio']) ?></h2>
            <p class="text-gray-500 text-sm"><?= htmlspecialchars($application['form_name'] ?? $application['type'] ?? 'Trámite') ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="bg-blue-50 text-blue-700 border border-blue-100 px-4 py-2 rounded-full text-sm font-semibold"><?= htmlspecialchars($application['status']) ?></span>
            <?php if (!empty($application['client_update_pending'])): ?>
                <span class="bg-yellow-50 text-yellow-700 border border-yellow-200 px-4 py-2 rounded-full text-sm font-semibold">Pendiente de revisión</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($application['client_update_pending'])): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 text-yellow-800">
    <p class="font-semibold"><i class="fas fa-hourglass-half mr-1"></i>Tu última actualización está pendiente de revisión</p>
    <p class="text-sm mt-1"><?= htmlspecialchars($application['client_last_update_comment'] ?? 'El equipo revisará tus cambios.') ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-chart-line text-blue-600 mr-2"></i>Avance general</h3>
                    <p class="text-sm text-gray-500">Guarda tu avance aunque todavía no termines el formulario.</p>
                </div>
                <span class="text-2xl font-bold text-blue-600"><?= number_format($progress, 0) ?>%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2.5 mb-4"><div class="bg-blue-600 h-2.5 rounded-full" style="width: <?= min(100, max(0, $progress)) ?>%"></div></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-600">
                <p><span class="font-semibold text-gray-800">Solicitante:</span> <?= htmlspecialchars($application['client_name'] ?? 'Sin nombre') ?></p>
                <p><span class="font-semibold text-gray-800">Asesor:</span> <?= htmlspecialchars($application['creator_name'] ?? 'Por asignar') ?></p>
                <p><span class="font-semibold text-gray-800">Tipo:</span> <?= htmlspecialchars($application['type'] ?? '') ?></p>
                <p><span class="font-semibold text-gray-800">Última actualización:</span> <?= !empty($application['updated_at']) ? date('d/m/Y H:i', strtotime($application['updated_at'])) : 'Sin registro' ?></p>
            </div>
        </div>

        <div id="formulario-cliente" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-5">
                <div>
                    <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-clipboard-list text-green-600 mr-2"></i>Formulario del trámite</h3>
                    <p class="text-sm text-gray-500">Sección <?= $currentPage ?> de <?= $totalPages ?>: <?= htmlspecialchars($currentPageData['name']) ?></p>
                </div>
                <span class="text-xs bg-gray-50 text-gray-600 border border-gray-200 rounded-full px-3 py-1">Guardado parcial disponible</span>
            </div>

            <?php if (!empty($allFields)): ?>
            <div class="flex flex-wrap gap-2 mb-5">
                <?php foreach ($clientPages as $index => $page): $pageNum = $index + 1; ?>
                    <a href="<?= BASE_URL ?>/mi-tramite/ver/<?= $application['id'] ?>?pagina=<?= $pageNum ?>#formulario-cliente"
                       class="px-3 py-1.5 rounded-full text-xs border <?= $pageNum === $currentPage ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' ?>">
                        <?= $pageNum ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/mi-tramite/actualizar-formulario/<?= $application['id'] ?>">
                <input type="hidden" name="current_page" value="<?= $currentPage ?>">
                <input type="hidden" name="total_pages" value="<?= $totalPages ?>">
                <?php foreach (($currentPageData['fields'] ?? []) as $field): ?>
                    <?php if (isClientEditableField($field) && !empty($field['id'])): ?>
                        <input type="hidden" name="page_field_ids[]" value="<?= htmlspecialchars($field['id']) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach (($currentPageData['fields'] ?? []) as $field): ?>
                        <?php renderClientField($field, $dataJson); ?>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100 pt-4">
                    <div class="flex gap-2">
                        <?php if ($currentPage > 1): ?>
                            <button type="submit" name="client_action" value="prev" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm">
                                <i class="fas fa-chevron-left mr-1"></i>Guardar y volver
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button type="submit" name="client_action" value="save" class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm">
                            <i class="fas fa-save mr-1"></i>Guardar avance
                        </button>
                        <?php if ($currentPage < $totalPages): ?>
                            <button type="submit" name="client_action" value="next" class="btn-primary text-white px-5 py-2 rounded-xl hover:opacity-90 text-sm">
                                Guardar y continuar<i class="fas fa-chevron-right ml-1"></i>
                            </button>
                        <?php else: ?>
                            <button type="submit" name="client_action" value="save" class="btn-primary text-white px-5 py-2 rounded-xl hover:opacity-90 text-sm">
                                <i class="fas fa-check mr-1"></i>Guardar última sección
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            <?php else: ?>
                <p class="text-gray-500">Este trámite no tiene un formulario editable vinculado.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-file-upload text-purple-600 mr-2"></i>Documentación</h3>
                    <p class="text-sm text-gray-500">Sube archivos por tipo. El equipo los marcará como aceptados o rechazados.</p>
                </div>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/mi-tramite/subir-documento/<?= $application['id'] ?>" enctype="multipart/form-data" class="rounded-xl border border-gray-100 bg-gray-50 p-4 mb-5">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento</label>
                        <select name="doc_type" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400">
                            <option value="cliente_adicional">Documento adicional</option>
                            <option value="pasaporte_vigente">Pasaporte vigente</option>
                            <option value="visa_anterior">Visa anterior</option>
                            <option value="visa_canadiense_anterior">Visa canadiense anterior</option>
                            <option value="eta_anterior">ETA anterior</option>
                            <option value="comprobante_domicilio">Comprobante de domicilio</option>
                            <option value="fotografia">Fotografía</option>
                            <option value="comprobante_ingresos">Comprobante de ingresos</option>
                            <option value="carta_laboral">Carta laboral</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Archivo</label>
                        <input type="file" name="document" required class="w-full border border-gray-200 rounded-xl px-3 py-2 bg-white text-sm">
                        <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG, DOC, DOCX. Máx. 2MB.</p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="btn-primary text-white px-5 py-2 rounded-xl hover:opacity-90 text-sm"><i class="fas fa-upload mr-1"></i>Subir documento</button>
                </div>
            </form>

            <?php if (!empty($documents)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($documents as $doc): ?>
                <div class="border border-gray-100 rounded-xl p-4 bg-white flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-800 text-sm truncate"><?= htmlspecialchars($doc['name']) ?></p>
                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($doc['doc_type'] ?? 'documento') ?> · <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?> · <?= number_format($doc['file_size']/1024, 0) ?> KB</p>
                        <span class="inline-block mt-2 text-xs px-2 py-1 rounded-full border <?= documentStatusClass($doc['review_status'] ?? '') ?>">
                            <?= htmlspecialchars(str_replace('_', ' ', $doc['review_status'] ?? 'en_revision')) ?>
                        </span>
                        <?php if (!empty($doc['review_comment'])): ?>
                            <p class="text-xs text-gray-500 mt-2"><?= htmlspecialchars($doc['review_comment']) ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/solicitudes/ver-documento/<?= $doc['id'] ?>" target="_blank" class="shrink-0 text-blue-600 hover:text-blue-800 rounded-full border border-blue-100 w-9 h-9 flex items-center justify-center"><i class="fas fa-eye"></i></a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-6 bg-gray-50 rounded-xl">Aún no hay documentos registrados.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-comment-dots text-orange-600 mr-2"></i>Observaciones del equipo</h3>
            <?php if (!empty($notes)): ?>
                <div class="space-y-4">
                <?php foreach ($notes as $note): ?>
                    <div class="border rounded-xl p-4 <?= !empty($note['is_important']) ? 'bg-yellow-50 border-yellow-200' : 'bg-gray-50 border-gray-100' ?>">
                        <div class="flex flex-col sm:flex-row sm:justify-between gap-2">
                            <p class="text-gray-800 text-sm"><?= nl2br(htmlspecialchars($note['note_text'])) ?></p>
                            <span class="text-xs text-gray-500 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Por: <?= htmlspecialchars($note['created_by_name'] ?? 'Equipo') ?></p>

                        <?php if (!empty($responseByNote[$note['id']])): ?>
                            <div class="mt-3 space-y-2">
                                <?php foreach ($responseByNote[$note['id']] as $resp): ?>
                                    <div class="bg-white border border-gray-100 rounded-xl p-3 text-sm">
                                        <p><?= nl2br(htmlspecialchars($resp['response_text'])) ?></p>
                                        <p class="text-xs text-gray-500 mt-1">Respondido: <?= date('d/m/Y H:i', strtotime($resp['created_at'])) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($note['requires_client_response'])): ?>
                        <form method="POST" action="<?= BASE_URL ?>/mi-tramite/responder-observacion/<?= $application['id'] ?>" class="mt-3">
                            <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                            <textarea name="response_text" rows="2" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm" placeholder="Escribe tu respuesta..."></textarea>
                            <button type="submit" class="mt-2 bg-orange-600 text-white px-4 py-2 rounded-xl hover:bg-orange-700 text-sm"><i class="fas fa-reply mr-1"></i>Responder</button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-6 bg-gray-50 rounded-xl">No hay observaciones visibles para ti.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-lg font-bold text-gray-800 mb-3"><i class="fas fa-paper-plane text-blue-600 mr-2"></i>Enviar a revisión</h3>
            <p class="text-sm text-gray-500 mb-3">Úsalo cuando termines una sección o subas documentos importantes.</p>
            <form method="POST" action="<?= BASE_URL ?>/mi-tramite/enviar-revision/<?= $application['id'] ?>">
                <textarea name="comment" rows="3" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm mb-3" placeholder="Ej. Ya subí los documentos solicitados..."></textarea>
                <button type="submit" class="w-full btn-primary text-white py-2 rounded-xl hover:opacity-90 text-sm">Solicitar revisión</button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-comments text-green-600 mr-2"></i>Mensajes internos</h3>
            <div class="space-y-3 max-h-96 overflow-y-auto mb-4 pr-1">
                <?php if (!empty($messages)): ?>
                    <?php foreach ($messages as $msg): ?>
                    <div class="p-3 rounded-xl text-sm <?= $msg['sender_role'] === 'Cliente' ? 'bg-blue-50 text-blue-900 border border-blue-100' : 'bg-gray-50 text-gray-800 border border-gray-100' ?>">
                        <p class="text-xs font-semibold mb-1"><?= htmlspecialchars($msg['sender_role'] === 'Cliente' ? 'Tú' : ($msg['sender_name'] ?? 'Equipo')) ?> · <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></p>
                        <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm text-center py-5 bg-gray-50 rounded-xl">Aún no hay mensajes.</p>
                <?php endif; ?>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/mi-tramite/mensajes/<?= $application['id'] ?>">
                <textarea name="message" rows="3" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm" placeholder="Escribe un mensaje para el equipo..."></textarea>
                <button type="submit" class="mt-2 w-full bg-green-600 text-white py-2 rounded-xl hover:bg-green-700 text-sm"><i class="fas fa-paper-plane mr-1"></i>Enviar mensaje</button>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h3 class="text-lg font-bold text-gray-800 mb-4"><i class="fas fa-history text-gray-600 mr-2"></i>Flujo del trámite</h3>
            <div class="space-y-4">
                <?php foreach ($history as $item): ?>
                <div class="border-l-4 border-blue-200 pl-3">
                    <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($item['new_status']) ?></p>
                    <?php if (!empty($item['comment'])): ?><p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($item['comment']) ?></p><?php endif; ?>
                    <p class="text-xs text-gray-500 mt-1"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></p>
                </div>
                <?php endforeach; ?>
                <?php if (empty($history)): ?><p class="text-gray-500 text-sm">Sin historial registrado.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
