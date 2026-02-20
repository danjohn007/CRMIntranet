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
            <?php if ($_SESSION['user_role'] === ROLE_ASESOR): ?>
            <button onclick="document.getElementById('infoSheetModal').classList.remove('hidden')"
                    class="bg-indigo-600 text-white px-4 py-3 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-file-alt mr-2"></i>
                <?= $infoSheet ? 'Editar hoja de información' : 'Crear hoja de información' ?>
            </button>
            <?php endif; ?>
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
                        <?php
                        $statusClass = 'bg-gray-100 text-gray-800'; // GRIS por defecto (Nuevo)
                        if (in_array($application['status'], [STATUS_TRAMITE_CERRADO, STATUS_FINALIZADO])) {
                            $statusClass = 'bg-green-100 text-green-800'; // VERDE
                        } elseif ($application['status'] === STATUS_EN_ESPERA_RESULTADO) {
                            $statusClass = 'bg-purple-100 text-purple-800'; // MORADO
                        } elseif ($application['status'] === STATUS_CITA_PROGRAMADA) {
                            $statusClass = 'bg-blue-100 text-blue-800'; // AZUL
                        } elseif ($application['status'] === STATUS_EN_ESPERA_PAGO) {
                            $statusClass = 'bg-yellow-100 text-yellow-800'; // AMARILLO
                        } elseif ($application['status'] === STATUS_LISTO_SOLICITUD) {
                            $statusClass = 'bg-red-100 text-red-800'; // ROJO
                        }
                        ?>
                        <span class="px-3 py-1 text-sm rounded-full font-medium <?= $statusClass ?>">
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
        
        <!-- Hoja de Información -->
        <?php if ($infoSheet): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-file-alt text-indigo-600 mr-2"></i>Hoja de Información
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><p class="text-sm text-gray-600">Fecha de ingreso</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['entry_date']) ?></p></div>
                <div><p class="text-sm text-gray-600">Residencia (Ciudad, Estado, País)</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['residence_place'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Domicilio</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['address'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Email del solicitante</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['client_email'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Email de la embajada</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['embassy_email'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Honorarios pagados</p><p class="font-semibold"><?= $infoSheet['amount_paid'] !== null ? '$' . number_format($infoSheet['amount_paid'], 2) : '-' ?></p></div>
                <?php if (!empty($infoSheet['observations'])): ?>
                <div class="md:col-span-2"><p class="text-sm text-gray-600">Observaciones</p><p class="font-semibold"><?= nl2br(htmlspecialchars($infoSheet['observations'])) ?></p></div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($_SESSION['user_role'] !== ROLE_ASESOR): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2">
                <i class="fas fa-file-alt text-gray-400 mr-2"></i>Hoja de Información
            </h3>
            <p class="text-gray-500 text-center py-4">
                <i class="fas fa-times-circle text-red-400 mr-1"></i>
                No se ha creado aún
            </p>
        </div>
        <?php endif; ?>

        <!-- Documentos Base (Pasaporte / Visa Anterior) -->
        <?php
        $pasaporteDoc = null;
        $visaAnteriorDoc = null;
        $fichaPagoDoc = null;
        foreach ($documents as $doc) {
            $dt = $doc['doc_type'] ?? '';
            if ($dt === 'pasaporte_vigente' && !$pasaporteDoc) $pasaporteDoc = $doc;
            if ($dt === 'visa_anterior' && !$visaAnteriorDoc) $visaAnteriorDoc = $doc;
            if ($dt === 'ficha_pago_consular' && !$fichaPagoDoc) $fichaPagoDoc = $doc;
        }
        $isRenovacion = stripos($application['subtype'] ?? '', 'renov') !== false;
        ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-passport text-blue-600 mr-2"></i>Documentos Base
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Pasaporte vigente (siempre requerido) -->
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-passport mr-1"></i> Pasaporte vigente
                    </p>
                    <?php if ($pasaporteDoc): ?>
                        <?php if ($_SESSION['user_role'] === ROLE_GERENTE || $_SESSION['user_role'] === ROLE_ADMIN): ?>
                            <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Subido</p>
                        <?php else: ?>
                            <p class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($pasaporteDoc['name']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($_SESSION['user_role'] === ROLE_ASESOR): ?>
                        <button onclick="openDocUpload('pasaporte_vigente')" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                            <i class="fas fa-upload mr-1"></i>Subir pasaporte vigente
                        </button>
                        <?php else: ?>
                            <p class="text-red-500 text-sm"><i class="fas fa-times-circle mr-1"></i>No subido</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <!-- Visa anterior (solo renovaciones) -->
                <?php if ($isRenovacion): ?>
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-1"></i> Visa anterior (renovación)
                    </p>
                    <?php if ($visaAnteriorDoc): ?>
                        <?php if ($_SESSION['user_role'] === ROLE_GERENTE || $_SESSION['user_role'] === ROLE_ADMIN): ?>
                            <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Subido</p>
                        <?php else: ?>
                            <p class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($visaAnteriorDoc['name']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($_SESSION['user_role'] === ROLE_ASESOR): ?>
                        <button onclick="openDocUpload('visa_anterior')" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">
                            <i class="fas fa-upload mr-1"></i>Subir visa anterior
                        </button>
                        <?php else: ?>
                            <p class="text-red-500 text-sm"><i class="fas fa-times-circle mr-1"></i>No subido</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario para cliente (solo asesor) -->
        <?php if ($_SESSION['user_role'] === ROLE_ASESOR && !empty($publishedForms)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-paper-plane text-blue-600 mr-2"></i>Formulario para el cliente
            </h3>
            <?php $formLinkStatus = $application['form_link_status'] ?? null; ?>
            <?php if ($formLinkStatus === 'completado'): ?>
                <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Cuestionario completado por el cliente</p>
            <?php elseif ($formLinkStatus === 'enviado'): ?>
                <p class="text-yellow-600 font-semibold"><i class="fas fa-hourglass-half mr-1"></i>Formulario enviado — esperando respuesta del cliente</p>
            <?php else: ?>
            <div class="flex items-end gap-3 flex-wrap">
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de formulario</label>
                    <select id="formLinkSelect" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">-- Seleccione un formulario --</option>
                        <?php foreach ($publishedForms as $pf): ?>
                        <option value="<?= $pf['id'] ?>"><?= htmlspecialchars($pf['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="copyFormLink()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-copy mr-1"></i>Copiar enlace del formulario
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">Al copiar el enlace se marcará como "enviado". El formulario se vinculará a esta solicitud.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Asistencia a cita (asesor, cuando está en Cita programada) -->
        <?php if ($_SESSION['user_role'] === ROLE_ASESOR && $application['status'] === STATUS_CITA_PROGRAMADA): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-calendar-check text-purple-600 mr-2"></i>Asistencia a cita
            </h3>
            <?php if ($application['client_attended']): ?>
                <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Asistencia registrada
                <?= $application['client_attended_date'] ? ' — ' . htmlspecialchars($application['client_attended_date']) : '' ?></p>
            <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/marcar-asistencia/<?= $application['id'] ?>">
                <div class="flex flex-wrap gap-4 items-end">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="client_attended" value="1" class="w-4 h-4">
                        <span class="text-sm font-medium text-gray-700">Cliente asistió a CAS/Consulado</span>
                    </label>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Fecha de asistencia (opcional)</label>
                        <input type="date" name="client_attended_date" class="border border-gray-300 rounded px-3 py-1 text-sm">
                    </div>
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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
                    <?php if (in_array($_SESSION['user_role'], [ROLE_ADMIN, ROLE_GERENTE])): ?>
                    <a href="<?= BASE_URL . $doc['file_path'] ?>" target="_blank" 
                       class="text-primary hover:underline">
                        <i class="fas fa-download"></i>
                    </a>
                    <?php endif; ?>
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
                        <option value="<?= STATUS_NUEVO ?>" <?= $application['status'] === STATUS_NUEVO ? 'selected' : '' ?>>⬜ Nuevo</option>
                        <option value="<?= STATUS_LISTO_SOLICITUD ?>" <?= $application['status'] === STATUS_LISTO_SOLICITUD ? 'selected' : '' ?>>🔴 Listo para solicitud</option>
                        <option value="<?= STATUS_EN_ESPERA_PAGO ?>" <?= $application['status'] === STATUS_EN_ESPERA_PAGO ? 'selected' : '' ?>>🟡 En espera de pago consular</option>
                        <option value="<?= STATUS_CITA_PROGRAMADA ?>" <?= $application['status'] === STATUS_CITA_PROGRAMADA ? 'selected' : '' ?>>🔵 Cita programada</option>
                        <option value="<?= STATUS_EN_ESPERA_RESULTADO ?>" <?= $application['status'] === STATUS_EN_ESPERA_RESULTADO ? 'selected' : '' ?>>🟣 En espera de resultado</option>
                        <option value="<?= STATUS_TRAMITE_CERRADO ?>" <?= $application['status'] === STATUS_TRAMITE_CERRADO ? 'selected' : '' ?>>🟢 Trámite cerrado</option>
                    </select>
                </div>
                <?php if ($application['status'] === STATUS_EN_ESPERA_PAGO || in_array($application['status'], [STATUS_EN_ESPERA_PAGO, STATUS_CITA_PROGRAMADA])): ?>
                <div class="mb-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <p class="text-sm font-semibold text-yellow-800 mb-2">Checklist estado Amarillo</p>
                    <label class="flex items-center gap-2 mb-2">
                        <input type="checkbox" name="official_application_done" value="1" <?= $application['official_application_done'] ? 'checked' : '' ?> class="w-4 h-4">
                        <span class="text-sm text-gray-700">Solicitud oficial de visa completada (DS-160)</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="consular_fee_sent" value="1" <?= $application['consular_fee_sent'] ? 'checked' : '' ?> class="w-4 h-4">
                        <span class="text-sm text-gray-700">Ficha de pago enviada al solicitante</span>
                    </label>
                </div>
                <?php endif; ?>
                <?php if ($application['status'] === STATUS_TRAMITE_CERRADO): ?>
                <div class="mb-4 p-3 bg-green-50 rounded-lg border border-green-200">
                    <p class="text-sm font-semibold text-green-800 mb-2">Datos opcionales de cierre</p>
                    <div class="mb-2">
                        <label class="block text-xs text-gray-600 mb-1">Número de guía DHL</label>
                        <input type="text" name="dhl_tracking" value="<?= htmlspecialchars($application['dhl_tracking'] ?? '') ?>" class="w-full border rounded px-3 py-1 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Fecha de entrega/recolección</label>
                        <input type="date" name="delivery_date" value="<?= htmlspecialchars($application['delivery_date'] ?? '') ?>" class="w-full border rounded px-3 py-1 text-sm">
                    </div>
                </div>
                <?php endif; ?>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comentario</label>
                    <textarea name="comment" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2" 
                              placeholder="Opcional"></textarea>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de documento</label>
                <select id="docTypeSelect" name="doc_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                    <?php
                    $hasFichaPago = false;
                    foreach ($documents as $doc) {
                        if (($doc['doc_type'] ?? '') === 'ficha_pago_consular') { $hasFichaPago = true; break; }
                    }
                    $isRenovacion = stripos($application['subtype'] ?? '', 'renov') !== false;
                    ?>
                    <option value="adicional">Documento adicional</option>
                    <?php if (!($pasaporteDoc ?? null)): ?>
                    <option value="pasaporte_vigente">Pasaporte vigente</option>
                    <?php endif; ?>
                    <?php if ($isRenovacion && !($visaAnteriorDoc ?? null)): ?>
                    <option value="visa_anterior">Visa anterior</option>
                    <?php endif; ?>
                    <?php if (!$hasFichaPago): ?>
                    <option value="ficha_pago_consular">Ficha de pago consular</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Archivo</label>
                <input type="file" name="document" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-500 mt-1">Formatos permitidos: PDF, JPG, PNG, DOC, DOCX (Máx. 2MB)</p>
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

<!-- Modal de Hoja de Información (solo asesor) -->
<?php if ($_SESSION['user_role'] === ROLE_ASESOR): ?>
<div id="infoSheetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg my-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Hoja de Información</h3>
            <button onclick="document.getElementById('infoSheetModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/solicitudes/guardar-hoja-info/<?= $application['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de ingreso</label>
                <input type="date" name="entry_date" required value="<?= htmlspecialchars($infoSheet['entry_date'] ?? date('Y-m-d')) ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Lugar de residencia <span class="text-gray-400 text-xs">(Ciudad, Estado, País)</span></label>
                <input type="text" name="residence_place" placeholder="Ciudad, Estado, País"
                       value="<?= htmlspecialchars($infoSheet['residence_place'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Domicilio del solicitante</label>
                <input type="text" name="address" value="<?= htmlspecialchars($infoSheet['address'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email del solicitante</label>
                <input type="email" name="client_email" value="<?= htmlspecialchars($infoSheet['client_email'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email de la embajada</label>
                <input type="email" name="embassy_email" value="<?= htmlspecialchars($infoSheet['embassy_email'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Monto que pagó el cliente (honorarios)</label>
                <input type="number" step="0.01" min="0" name="amount_paid"
                       value="<?= htmlspecialchars($infoSheet['amount_paid'] ?? '') ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea name="observations" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2"><?= htmlspecialchars($infoSheet['observations'] ?? '') ?></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary text-white py-2 rounded-lg hover:opacity-90">
                    <i class="fas fa-save mr-2"></i>Guardar
                </button>
                <button type="button" onclick="document.getElementById('infoSheetModal').classList.add('hidden')"
                        class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openDocUpload(docType) {
    var sel = document.getElementById('docTypeSelect');
    if (sel) {
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === docType) { sel.selectedIndex = i; break; }
        }
    }
    document.getElementById('uploadModal').classList.remove('hidden');
}

function copyFormLink() {
    var formId = document.getElementById('formLinkSelect').value;
    if (!formId) { alert('Seleccione un formulario'); return; }
    var baseUrl = '<?= BASE_URL ?>';
    var appId = '<?= $application['id'] ?>';
    // Mark as sent via AJAX or form submit
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = baseUrl + '/solicitudes/vincular-formulario/' + appId;
    var inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'form_link_id'; inp.value = formId;
    form.appendChild(inp);
    document.body.appendChild(form);
    // Copy public form URL to clipboard
    var url = baseUrl + '/public/form/' + formId;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            alert('Enlace copiado: ' + url);
            form.submit();
        }).catch(function() {
            prompt('Copia este enlace:', url);
            form.submit();
        });
    } else {
        prompt('Copia este enlace:', url);
        form.submit();
    }
}
</script>

<?php 
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
