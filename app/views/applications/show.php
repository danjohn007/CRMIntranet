<?php 
$title = 'Detalle de Solicitud - ' . $application['folio'];
ob_start(); 
?>

<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($application['folio']) ?></h2>
            <p class="text-gray-600"><?= htmlspecialchars($application['form_name']) ?></p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= BASE_URL ?>/customer-journey/<?= $application['id'] ?>" 
               class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-route mr-2"></i>Customer Journey
            </a>
            <a href="<?= BASE_URL ?>/solicitudes" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Columna Principal -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Información General -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Información General</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Tipo de Trámite</p>
                    <p class="text-lg font-semibold"><?= htmlspecialchars($application['type']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Subtipo</p>
                    <p class="text-lg font-semibold"><?= htmlspecialchars($application['subtype'] ?? '-') ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Estatus Actual</p>
                    <p>
                        <span class="px-3 py-1 text-sm rounded-full font-medium <?= 
                            $application['status'] === STATUS_FINALIZADO ? 'bg-green-100 text-green-800' :
                            ($application['status'] === STATUS_APROBADO ? 'bg-blue-100 text-blue-800' :
                            ($application['status'] === STATUS_RECHAZADO ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'))
                        ?>">
                            <?= htmlspecialchars($application['status']) ?>
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Creado por</p>
                    <p class="text-lg font-semibold"><?= htmlspecialchars($application['creator_name']) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Fecha de Creación</p>
                    <p class="text-lg font-semibold"><?= date('d/m/Y H:i', strtotime($application['created_at'])) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Última Actualización</p>
                    <p class="text-lg font-semibold"><?= date('d/m/Y H:i', strtotime($application['updated_at'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Datos del Formulario -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Datos del Solicitante</h3>
            <?php
            $formData = json_decode($application['data_json'], true);
            $formFields = json_decode($application['fields_json'], true);
            
            // Create a map of field IDs to field types for quick lookup
            $fieldTypes = [];
            if ($formFields && isset($formFields['fields'])) {
                foreach ($formFields['fields'] as $field) {
                    $fieldTypes[$field['id']] = $field['type'] ?? 'text';
                }
            }
            
            if ($formData && is_array($formData)):
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($formData as $key => $value): 
                    // Check if this field is a file type
                    $isFileField = isset($fieldTypes[$key]) && $fieldTypes[$key] === 'file';
                    
                    // Skip empty file fields
                    if ($isFileField && empty($value)) {
                        continue;
                    }
                ?>
                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm text-gray-600 capitalize"><?= htmlspecialchars(str_replace('_', ' ', $key)) ?></p>
                    <?php if ($isFileField): ?>
                        <div class="flex items-center space-x-3">
                            <p class="text-lg"><?= htmlspecialchars($value) ?></p>
                            <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
                            <a href="<?= BASE_URL ?>/solicitudes/descargar-archivo/<?= $application['id'] ?>/<?= htmlspecialchars($key) ?>" 
                               class="text-blue-600 hover:text-blue-800 transition">
                                <i class="fas fa-download"></i> Descargar
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-lg"><?= is_array($value) ? htmlspecialchars(json_encode($value)) : htmlspecialchars($value) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500">No hay datos disponibles</p>
            <?php endif; ?>
        </div>
        
        <!-- Documentos -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Documentos</h3>
                <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" 
                        class="btn-primary text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-upload mr-2"></i>Subir Documento
                </button>
            </div>
            
            <?php if (!empty($documents)): ?>
            <div class="space-y-3">
                <?php foreach ($documents as $doc): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-file-<?= $doc['file_type'] === 'pdf' ? 'pdf text-red-500' : 'alt text-blue-500' ?> text-2xl"></i>
                        <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($doc['name']) ?></p>
                            <p class="text-sm text-gray-500">
                                Subido por <?= htmlspecialchars($doc['uploaded_by_name']) ?> 
                                el <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?>
                                (<?= number_format($doc['file_size'] / 1024, 2) ?> KB)
                            </p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL . $doc['file_path'] ?>" target="_blank" 
                       class="text-primary hover:underline">
                        <i class="fas fa-download"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-6">No hay documentos subidos</p>
            <?php endif; ?>
        </div>
        
        <!-- Indicaciones -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Indicaciones</h3>
                <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
                <button onclick="document.getElementById('noteModal').classList.remove('hidden')" 
                        class="btn-primary text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-plus mr-2"></i>Agregar Indicación
                </button>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($notes)): ?>
            <div class="space-y-3">
                <?php foreach ($notes as $note): ?>
                <div class="p-4 rounded-lg <?= $note['is_important'] ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-gray-50 border-l-4 border-gray-300' ?>">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center space-x-2">
                            <?php if ($note['is_important']): ?>
                            <i class="fas fa-exclamation-circle text-yellow-600"></i>
                            <span class="text-sm font-semibold text-yellow-800">IMPORTANTE</span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></span>
                    </div>
                    <p class="text-gray-800 mb-2"><?= nl2br(htmlspecialchars($note['note_text'])) ?></p>
                    <p class="text-sm text-gray-600">
                        Por: <?= htmlspecialchars($note['created_by_name']) ?> 
                        <span class="text-xs">(<?= htmlspecialchars($note['created_by_role']) ?>)</span>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-gray-500 text-center py-6">No hay indicaciones registradas</p>
            <?php endif; ?>
        </div>
        
        <!-- Historial de Estatus -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Historial de Estatus</h3>
            <div class="space-y-4">
                <?php foreach ($history as $item): ?>
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-100">
                            <i class="fas fa-check text-blue-600"></i>
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <div class="flex justify-between">
                            <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['new_status']) ?></p>
                            <span class="text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($item['created_at'])) ?></span>
                        </div>
                        <?php if ($item['previous_status']): ?>
                        <p class="text-sm text-gray-600">De: <?= htmlspecialchars($item['previous_status']) ?></p>
                        <?php endif; ?>
                        <?php if ($item['comment']): ?>
                        <p class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($item['comment']) ?></p>
                        <?php endif; ?>
                        <p class="text-sm text-gray-500">Por: <?= htmlspecialchars($item['changed_by_name']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Columna Lateral -->
    <div class="space-y-6">
        <!-- Cambiar Estatus (Solo Admin y Gerente) -->
        <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Cambiar Estatus</h3>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estatus</label>
                    <select name="status" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="">-- Seleccione --</option>
                        <option value="<?= STATUS_CREADO ?>">Creado</option>
                        <option value="<?= STATUS_RECEPCION_INFO_PAGO ?>">Recepción de información y pago</option>
                        <option value="<?= STATUS_EN_REVISION ?>">En revisión</option>
                        <option value="<?= STATUS_INFO_INCOMPLETA ?>">Información incompleta</option>
                        <option value="<?= STATUS_DOC_VALIDADA ?>">Documentación validada</option>
                        <option value="<?= STATUS_EN_PROCESO ?>">En proceso</option>
                        <option value="<?= STATUS_APROBADO ?>">Aprobado</option>
                        <option value="<?= STATUS_RECHAZADO ?>">Rechazado</option>
                        <option value="<?= STATUS_FINALIZADO ?>">Finalizado (Trámite Entregado)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comentario</label>
                    <textarea name="comment" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2" 
                              placeholder="Opcional (obligatorio para rechazo)"></textarea>
                </div>
                <button type="submit" class="w-full btn-primary text-white py-2 rounded-lg hover:opacity-90">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar Estatus
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Información Financiera (Solo Admin y Gerente) -->
        <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE]) && isset($application['total_costs'])): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Información Financiera</h3>
                <a href="<?= BASE_URL ?>/financiero/solicitud/<?= $application['id'] ?>" 
                   class="text-primary hover:underline">
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Costos:</span>
                    <span class="font-bold text-gray-800">$<?= number_format($application['total_costs'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Pagado:</span>
                    <span class="font-bold text-green-600">$<?= number_format($application['total_paid'], 2) ?></span>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <span class="text-gray-600">Saldo:</span>
                    <span class="font-bold text-<?= $application['balance'] > 0 ? 'red' : 'green' ?>-600">
                        $<?= number_format($application['balance'], 2) ?>
                    </span>
                </div>
                <div class="text-center mt-3">
                    <span class="px-3 py-1 text-sm rounded-full font-medium <?= 
                        $application['financial_status'] === FINANCIAL_PAGADO ? 'bg-green-100 text-green-800' :
                        ($application['financial_status'] === FINANCIAL_PARCIAL ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')
                    ?>">
                        <?= htmlspecialchars($application['financial_status']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Subir Documento -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Subir Documento</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/solicitudes/subir-documento/<?= $application['id'] ?>" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Archivo</label>
                <input type="file" name="document" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: PDF, JPG, PNG, DOC, DOCX (Máx. 10MB)</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary text-white py-2 rounded-lg hover:opacity-90">
                    <i class="fas fa-upload mr-2"></i>Subir
                </button>
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" 
                        class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Agregar Indicación -->
<?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
<div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Agregar Indicación</h3>
            <button onclick="document.getElementById('noteModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/solicitudes/agregar-indicacion/<?= $application['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Indicación</label>
                <textarea name="note_text" required rows="4" 
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Escriba la indicación aquí..."></textarea>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_important" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">
                        <i class="fas fa-exclamation-circle text-yellow-600"></i>
                        Marcar como importante
                    </span>
                </label>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary text-white py-2 rounded-lg hover:opacity-90">
                    <i class="fas fa-save mr-2"></i>Guardar
                </button>
                <button type="button" onclick="document.getElementById('noteModal').classList.add('hidden')" 
                        class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
