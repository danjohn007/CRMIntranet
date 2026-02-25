<?php
$title = 'Detalle de Solicitud - ' . $application['folio'];
ob_start();

$role          = $_SESSION['user_role'];
$isAdmin       = in_array($role, [ROLE_ADMIN, ROLE_GERENTE]);
$isAsesor      = $role === ROLE_ASESOR;
$status        = $application['status'];
$isRenovacion  = stripos($application['subtype'] ?? '', 'renov') !== false;
$isCanadianVisa = !empty($application['is_canadian_visa']);

// Classify documents by type for quick access
$pasaporteDoc          = null;
$visaAnteriorDoc       = null;
$fichaPagoDoc          = null;
$ds160Doc              = null;
$consularPaymentDoc    = null;
$appointmentConfirmDoc = null;
$officialFinalDoc      = null;
$visaCanadiensPrevDoc  = null;
$etaAnteriorDoc        = null;
$canadianVacConfirmDoc = null;
$canadianPortalCapDoc  = null;
foreach ($documents as $doc) {
    $dt = $doc['doc_type'] ?? 'adicional';
    if ($dt === 'pasaporte_vigente'          && !$pasaporteDoc)          $pasaporteDoc          = $doc;
    if ($dt === 'visa_anterior'              && !$visaAnteriorDoc)       $visaAnteriorDoc       = $doc;
    if ($dt === 'ficha_pago_consular'        && !$fichaPagoDoc)          $fichaPagoDoc          = $doc;
    if ($dt === 'ds160'                      && !$ds160Doc)              $ds160Doc              = $doc;
    if ($dt === 'consular_payment_evidence'  && !$consularPaymentDoc)    $consularPaymentDoc    = $doc;
    if ($dt === 'appointment_confirmation'   && !$appointmentConfirmDoc) $appointmentConfirmDoc = $doc;
    if ($dt === 'official_application_final' && !$officialFinalDoc)      $officialFinalDoc      = $doc;
    if ($dt === 'visa_canadiense_anterior'   && !$visaCanadiensPrevDoc)  $visaCanadiensPrevDoc  = $doc;
    if ($dt === 'eta_anterior'               && !$etaAnteriorDoc)        $etaAnteriorDoc        = $doc;
    if ($dt === 'canadian_vac_confirmation'  && !$canadianVacConfirmDoc) $canadianVacConfirmDoc = $doc;
    if ($dt === 'canadian_portal_capture'    && !$canadianPortalCapDoc)  $canadianPortalCapDoc  = $doc;
}

// Canadian visa flags
$canadianIsRenovacion = $isCanadianVisa && stripos($application['canadian_modalidad'] ?? '', 'renov') !== false;
$canadianIsETA        = $isCanadianVisa && stripos($application['canadian_tipo'] ?? '', 'ETA') !== false;
$isClosedStatus       = $status === STATUS_TRAMITE_CERRADO || $status === STATUS_FINALIZADO;

// Human-readable labels for each status in the Canadian visa flow
$canadianStatusLabels = [
    STATUS_LISTO_SOLICITUD     => 'Expediente interno completo, listo para cargar a sistema canadiense',
    STATUS_EN_ESPERA_PAGO      => 'Documentos cargados en sistema canadiense, en espera de cita biométrica',
    STATUS_CITA_PROGRAMADA     => 'Cita biométrica generada',
    STATUS_EN_ESPERA_RESULTADO => 'En espera de resolución',
    STATUS_TRAMITE_CERRADO     => 'Trámite cerrado',
];
?>

<div class="mb-6">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <div>
            <h2 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($application['folio']) ?></h2>
            <p class="text-gray-600"><?= htmlspecialchars($application['form_name'] ?? '') ?></p>
        </div>
        <div class="flex space-x-3 flex-wrap gap-2">
            <?php if (!$infoSheet || $isAdmin): ?>
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

<?php /* ── STATUS BANNERS ─────────────────────────────────────────────────── */ ?>
<?php if ($status === STATUS_LISTO_SOLICITUD): ?>
<div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6 flex items-start gap-3">
    <i class="fas fa-exclamation-circle text-red-500 text-2xl mt-0.5"></i>
    <div>
        <?php if ($isCanadianVisa): ?>
        <p class="font-bold text-red-800 text-lg">Listo para carga en portal canadiense.</p>
        <p class="text-red-700 text-sm">Carga los documentos en el portal de Canadá para continuar.</p>
        <?php else: ?>
        <p class="font-bold text-red-800 text-lg">Listo para llenar solicitud oficial y enviar ficha</p>
        <p class="text-red-700 text-sm">Completa el DS-160 y envía la ficha de pago al solicitante.</p>
        <?php endif; ?>
    </div>
</div>
<?php elseif ($status === STATUS_EN_ESPERA_PAGO): ?>
<div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4 mb-6 flex items-start gap-3">
    <i class="fas fa-hourglass-half text-yellow-500 text-2xl mt-0.5"></i>
    <div>
        <?php if ($isCanadianVisa): ?>
        <p class="font-bold text-yellow-800 text-lg">En espera de cita biométrica</p>
        <p class="text-yellow-700 text-sm">Generar cita de biométricos para avanzar a AZUL.</p>
        <?php else: ?>
        <p class="font-bold text-yellow-800 text-lg">En espera de pago consular</p>
        <p class="text-yellow-700 text-sm">Espera la confirmación del pago consular para avanzar a AZUL.</p>
        <?php endif; ?>
    </div>
</div>
<?php elseif ($status === STATUS_CITA_PROGRAMADA): ?>
<div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4 mb-6 flex items-start gap-3">
    <i class="fas fa-calendar-check text-blue-500 text-2xl mt-0.5"></i>
    <div>
        <?php if ($isCanadianVisa): ?>
        <p class="font-bold text-blue-800 text-lg">Biométricos programados</p>
        <p class="text-blue-700 text-sm">Confirma la asistencia del cliente a biométricos.</p>
        <?php else: ?>
        <p class="font-bold text-blue-800 text-lg">LISTO y programado</p>
        <p class="text-blue-700 text-sm">Cita programada. Confirma la asistencia del cliente.</p>
        <?php endif; ?>
    </div>
</div>

<?php /* ── OFFICE APPOINTMENT SECTION (only shown in AZUL state) ─── */ ?>
<?php if ($status === STATUS_CITA_PROGRAMADA && ($isAsesor || $isAdmin)): ?>
<div class="bg-white border border-blue-200 rounded-lg p-5 mb-6 shadow-sm">
    <h4 class="text-base font-bold text-blue-800 mb-3"><i class="fas fa-building text-blue-500 mr-2"></i>Cita a oficinas — Cita a oficinas para recibir indicaciones previas</h4>
    <form method="POST" action="<?= BASE_URL ?>/solicitudes/guardar-cita-oficina/<?= $application['id'] ?>" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora</label>
            <input type="datetime-local" name="office_appointment_date"
                   value="<?= !empty($application['office_appointment_date']) ? date('Y-m-d\TH:i', strtotime($application['office_appointment_date'])) : '' ?>"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Modalidad</label>
            <select name="office_appointment_modality"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">-- Seleccione --</option>
                <option value="Zoom" <?= ($application['office_appointment_modality'] ?? '') === 'Zoom' ? 'selected' : '' ?>>Zoom</option>
                <option value="Presencial" <?= ($application['office_appointment_modality'] ?? '') === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
            </select>
        </div>
        <div>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                <i class="fas fa-save mr-1"></i>Guardar cita
            </button>
        </div>
    </form>
    <?php if (!empty($application['office_appointment_date'])): ?>
    <div class="mt-3 p-3 bg-blue-50 rounded-lg text-sm text-blue-800">
        <i class="fas fa-calendar-check mr-1"></i>
        <strong>Cita agendada:</strong>
        <?= date('d/m/Y H:i', strtotime($application['office_appointment_date'])) ?>
        <?php if (!empty($application['office_appointment_modality'])): ?>
        — <span class="font-semibold"><?= htmlspecialchars($application['office_appointment_modality']) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php elseif ($status === STATUS_EN_ESPERA_RESULTADO): ?>
<div class="bg-purple-50 border-l-4 border-purple-500 rounded-lg p-4 mb-6 flex items-start gap-3">
    <i class="fas fa-clock text-purple-500 text-2xl mt-0.5"></i>
    <div>
        <?php if ($isCanadianVisa): ?>
        <p class="font-bold text-purple-800 text-lg">En espera de resolución (aprox. 1 mes)</p>
        <p class="text-purple-700 text-sm">Biométricos realizados. En espera de resolución de visa.</p>
        <?php else: ?>
        <p class="font-bold text-purple-800 text-lg">EN ESPERA de entrega/resultado</p>
        <p class="text-purple-700 text-sm">Cliente asistió a la cita. En espera del resultado.</p>
        <?php endif; ?>
    </div>
</div>
<?php elseif ($isClosedStatus): ?>
<div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 mb-6 flex items-start gap-3">
    <i class="fas fa-check-circle text-green-500 text-2xl mt-0.5"></i>
    <div><p class="font-bold text-green-800 text-lg">Trámite cerrado / Finalizado</p></div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- COLUMNA PRINCIPAL -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Informacion General -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Informacion General</h3>
            <div class="grid grid-cols-2 gap-4">
                <div><p class="text-sm text-gray-600">Tipo de Trámite</p><p class="text-lg font-semibold"><?= htmlspecialchars($application['type']) ?><?= $isCanadianVisa ? ' <span class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full ml-1">CANADIENSE</span>' : '' ?></p></div>
                <?php if ($isCanadianVisa): ?>
                <div><p class="text-sm text-gray-600">Tipo Canadiense</p><p class="text-lg font-semibold"><?= htmlspecialchars($application['canadian_tipo'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Modalidad</p><p class="text-lg font-semibold"><?= htmlspecialchars($application['canadian_modalidad'] ?? '-') ?></p></div>
                <?php else: ?>
                <div><p class="text-sm text-gray-600">Subtipo</p><p class="text-lg font-semibold"><?= htmlspecialchars($application['subtype'] ?? '-') ?></p></div>
                <?php endif; ?>
                <div>
                    <p class="text-sm text-gray-600">Estatus Actual</p>
                    <?php
                    $sc = 'bg-gray-100 text-gray-800';
                    if ($isClosedStatus) $sc = 'bg-green-100 text-green-800';
                    elseif ($status === STATUS_EN_ESPERA_RESULTADO) $sc = 'bg-purple-100 text-purple-800';
                    elseif ($status === STATUS_CITA_PROGRAMADA)     $sc = 'bg-blue-100 text-blue-800';
                    elseif ($status === STATUS_EN_ESPERA_PAGO)      $sc = 'bg-yellow-100 text-yellow-800';
                    elseif ($status === STATUS_LISTO_SOLICITUD)     $sc = 'bg-red-100 text-red-800';
                    if ($isCanadianVisa) {
                        $displayStatus = $canadianStatusLabels[$status] ?? $status;
                    } else {
                        $displayStatus = $status;
                    }
                    ?>
                    <span class="px-3 py-1 text-sm rounded-full font-medium <?= $sc ?>"><?= htmlspecialchars($displayStatus) ?></span>
                </div>
                <div><p class="text-sm text-gray-600">Creado por</p><p class="text-lg font-semibold"><?= htmlspecialchars($application['creator_name']) ?></p></div>
                <div><p class="text-sm text-gray-600">Fecha de Creacion</p><p class="text-lg font-semibold"><?= date('d/m/Y H:i', strtotime($application['created_at'])) ?></p></div>
                <div><p class="text-sm text-gray-600">Ultima Actualizacion</p><p class="text-lg font-semibold"><?= date('d/m/Y H:i', strtotime($application['updated_at'])) ?></p></div>
            </div>
        </div>

        <!-- Datos basicos del solicitante -->
        <?php
        $basicData   = json_decode($application['data_json'], true) ?: [];
        $basicFields = ['nombre' => 'Nombre', 'apellidos' => 'Apellidos', 'email' => 'Email', 'telefono' => 'Telefono'];
        // If basic fields are missing from data_json (overwritten after form submission),
        // reconstruct from client_name stored at creation time
        if (empty($basicData['nombre']) && !empty($application['client_name'])) {
            $nameParts = explode(' ', $application['client_name'], 2);
            $basicData['nombre']    = $nameParts[0] ?? $application['client_name'];
            $basicData['apellidos'] = $nameParts[1] ?? '';
        }
        ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Datos basicos del solicitante</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($basicFields as $key => $label): ?>
                <?php if (!empty($basicData[$key])): ?>
                <div class="border-l-4 border-blue-500 pl-4">
                    <p class="text-sm text-gray-600"><?= $label ?></p>
                    <p class="text-lg font-semibold"><?= htmlspecialchars($basicData[$key]) ?></p>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Respuestas del cuestionario del cliente -->
        <?php if ($application['form_link_status'] === 'completado'): ?>
        <?php
        $formFieldsJson = json_decode($application['fields_json'] ?? '{}', true);
        $fieldTypes  = [];
        $fieldLabels = [];
        if ($formFieldsJson && isset($formFieldsJson['fields'])) {
            foreach ($formFieldsJson['fields'] as $f) {
                $fieldTypes[$f['id']]  = $f['type']  ?? 'text';
                $fieldLabels[$f['id']] = $f['label'] ?? $f['id'];
            }
        }
        $basicKeys = ['nombre', 'apellidos', 'email', 'telefono'];
        ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2">
                <i class="fas fa-clipboard-check text-green-600 mr-2"></i>Respuestas del cuestionario del cliente
            </h3>
            <?php if ($isAdmin): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($basicData as $key => $value):
                    if (in_array($key, $basicKeys)) continue;
                    $isFileField = isset($fieldTypes[$key]) && $fieldTypes[$key] === 'file';
                    if ($isFileField && empty($value)) continue;
                    $displayLabel = $fieldLabels[$key] ?? str_replace('_', ' ', $key);
                ?>
                <div class="border-l-4 border-green-500 pl-4">
                    <p class="text-sm text-gray-600 capitalize"><?= htmlspecialchars($displayLabel) ?></p>
                    <?php if ($isFileField): ?>
                        <p class="text-lg"><?= htmlspecialchars($value) ?></p>
                        <a href="<?= BASE_URL ?>/solicitudes/descargar-archivo/<?= $application['id'] ?>/<?= htmlspecialchars($key) ?>"
                           class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-download mr-1"></i>Descargar</a>
                    <?php else: ?>
                        <p class="text-lg"><?= is_array($value) ? htmlspecialchars(json_encode($value)) : htmlspecialchars($value) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif ($isAsesor): ?>
            <p class="text-gray-500 text-sm italic">
                <i class="fas fa-eye mr-1"></i>El cliente ha completado el cuestionario. Solo visible para Gerente/Admin.
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Hoja de Informacion -->
        <?php if ($infoSheet): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-file-alt text-indigo-600 mr-2"></i>Hoja de Informacion</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><p class="text-sm text-gray-600">Fecha de ingreso</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['entry_date']) ?></p></div>
                <div><p class="text-sm text-gray-600">Residencia</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['residence_place'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Domicilio</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['address'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Email solicitante</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['client_email'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Email embajada</p><p class="font-semibold"><?= htmlspecialchars($infoSheet['embassy_email'] ?? '-') ?></p></div>
                <div><p class="text-sm text-gray-600">Honorarios</p><p class="font-semibold"><?= $infoSheet['amount_paid'] !== null ? '$' . number_format($infoSheet['amount_paid'], 2) : '-' ?></p></div>
                <?php if (!empty($infoSheet['observations'])): ?>
                <div class="md:col-span-2"><p class="text-sm text-gray-600">Observaciones</p><p class="font-semibold"><?= nl2br(htmlspecialchars($infoSheet['observations'])) ?></p></div>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($isAdmin): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2"><i class="fas fa-file-alt text-gray-400 mr-2"></i>Hoja de Informacion</h3>
            <p class="text-gray-500 text-center py-4"><i class="fas fa-times-circle text-red-400 mr-1"></i>No se ha creado aun</p>
        </div>
        <?php endif; ?>

        <!-- Formulario para cliente -->
        <?php
        $formLinkStatus = $application['form_link_status'] ?? null;
        $showFormLinkSection = $isAsesor || ($isAdmin && $formLinkStatus !== 'completado');
        if ($showFormLinkSection):
        ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-paper-plane text-blue-600 mr-2"></i>Formulario para el cliente</h3>
            <?php if ($formLinkStatus === 'completado'): ?>
                <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Cuestionario completado por el cliente</p>
            <?php elseif ($formLinkStatus === 'enviado' && !empty($formLinkToken)): ?>
                <?php $clientFormUrl = BASE_URL . '/public/form/' . htmlspecialchars($formLinkToken) . '?app=' . $application['id']; ?>
                <p class="text-yellow-600 font-semibold mb-3"><i class="fas fa-hourglass-half mr-1"></i>Formulario enviado — esperando respuesta</p>
                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" readonly value="<?= htmlspecialchars($clientFormUrl) ?>"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50" id="clientFormUrlInput">
                    <button onclick="copyClientFormUrl()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-copy mr-1"></i>Copiar enlace
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">Solo puede llenarse una vez.</p>
            <?php else: ?>
                <?php if (!empty($formLinkToken)): ?>
                <?php $clientFormUrl = BASE_URL . '/public/form/' . htmlspecialchars($formLinkToken) . '?app=' . $application['id']; ?>
                <div class="flex items-center gap-3 flex-wrap">
                    <input type="text" readonly value="<?= htmlspecialchars($clientFormUrl) ?>"
                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50" id="clientFormUrlInput">
                    <button onclick="copyClientFormUrl()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-copy mr-1"></i>Copiar enlace
                    </button>
                </div>
                <?php elseif (!empty($application['form_id'])): ?>
                <form method="POST" action="<?= BASE_URL ?>/solicitudes/vincular-formulario/<?= $application['id'] ?>">
                    <input type="hidden" name="form_link_id" value="<?= intval($application['form_id']) ?>">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-link mr-1"></i>Generar y copiar enlace
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-2">Al generar el enlace se marcará como enviado.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Estado ROJO -->
        <?php if ($status === STATUS_LISTO_SOLICITUD && $isAdmin): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <?php if ($isCanadianVisa): ?>
            <!-- ── Estado ROJO: Visa Canadiense ── -->
            <h3 class="text-xl font-bold text-red-800 mb-4"><i class="fas fa-tasks text-red-600 mr-2"></i>Checklist Estado ROJO — Visa Canadiense</h3>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>"
                  enctype="multipart/form-data">
                <input type="hidden" name="status" value="<?= STATUS_LISTO_SOLICITUD ?>">
                <div class="space-y-3 mb-4">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="canadian_docs_uploaded_portal" value="1"
                               <?= !empty($application['canadian_docs_uploaded_portal']) ? 'checked' : '' ?> class="w-5 h-5 text-red-600">
                        <span class="text-gray-800 font-medium">Documentos cargados en portal Canadá</span>
                        <?php if (!empty($application['canadian_docs_uploaded_portal'])): ?><i class="fas fa-check-circle text-green-600"></i><?php endif; ?>
                    </label>
                </div>
                <div class="border-t border-red-200 pt-4 space-y-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Número de aplicación (opcional)</label>
                        <input type="text" name="canadian_application_number"
                               value="<?= htmlspecialchars($application['canadian_application_number'] ?? '') ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Número de aplicación">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Captura o confirmación de envío (opcional)</label>
                        <input type="file" name="canadian_portal_capture" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <?php if ($canadianPortalCapDoc): ?><p class="text-green-600 text-xs mt-1"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($canadianPortalCapDoc['name']) ?></p><?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="mt-4 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm">
                    <i class="fas fa-save mr-1"></i>Guardar
                </button>
            </form>
            <?php if (!empty($application['canadian_docs_uploaded_portal'])): ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>"
                  class="mt-4">
                <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_PAGO ?>">
                <input type="hidden" name="canadian_docs_uploaded_portal" value="1">
                <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="Comentario opcional"></textarea>
                <button type="submit" class="w-full bg-yellow-500 text-white py-2 rounded-lg hover:bg-yellow-600 font-semibold">
                    <i class="fas fa-arrow-right mr-2"></i>Pasar a EN ESPERA DE CITA BIOMÉTRICA (AMARILLO)
                </button>
            </form>
            <?php endif; ?>
            <?php else: ?>
            <!-- ── Estado ROJO: Flujo estándar ── -->
            <h3 class="text-xl font-bold text-red-800 mb-4"><i class="fas fa-tasks text-red-600 mr-2"></i>Checklist Estado ROJO</h3>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>"
                  enctype="multipart/form-data">
                <input type="hidden" name="status" value="<?= STATUS_LISTO_SOLICITUD ?>">
                <div class="space-y-3 mb-4">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="official_application_done" value="1"
                               <?= $application['official_application_done'] ? 'checked' : '' ?> class="w-5 h-5 text-red-600">
                        <span class="text-gray-800 font-medium">Solicitud oficial de visa completada (DS-160)</span>
                        <?php if ($application['official_application_done']): ?><i class="fas fa-check-circle text-green-600"></i><?php endif; ?>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="consular_fee_sent" value="1"
                               <?= $application['consular_fee_sent'] ? 'checked' : '' ?> class="w-5 h-5 text-red-600">
                        <span class="text-gray-800 font-medium">Ficha de pago enviada al solicitante</span>
                        <?php if ($application['consular_fee_sent']): ?><i class="fas fa-check-circle text-green-600"></i><?php endif; ?>
                    </label>
                </div>
                <div class="border-t border-red-200 pt-4 space-y-3">
                    <p class="text-sm font-semibold text-red-800">Confirmación DS-160 (opcional)</p>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Número de confirmación DS-160</label>
                        <input type="text" name="ds160_confirmation_number"
                               value="<?= htmlspecialchars($application['ds160_confirmation_number'] ?? '') ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="AA-00-000000-0">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Subir PDF / Confirmación DS-160</label>
                        <input type="file" name="ds160_file" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <?php if ($ds160Doc): ?><p class="text-green-600 text-xs mt-1"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($ds160Doc['name']) ?></p><?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="mt-4 bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm">
                    <i class="fas fa-save mr-1"></i>Guardar checklist
                </button>
            </form>
            <?php if ($application['official_application_done'] && $application['consular_fee_sent']): ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>"
                  class="mt-4" enctype="multipart/form-data">
                <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_PAGO ?>">
                <input type="hidden" name="official_application_done" value="1">
                <input type="hidden" name="consular_fee_sent" value="1">
                <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="Comentario opcional"></textarea>
                <button type="submit" class="w-full bg-yellow-500 text-white py-2 rounded-lg hover:bg-yellow-600 font-semibold">
                    <i class="fas fa-arrow-right mr-2"></i>Pasar a EN ESPERA DE PAGO (AMARILLO)
                </button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php elseif ($status === STATUS_LISTO_SOLICITUD && $isAsesor): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <h3 class="text-xl font-bold text-red-800 mb-4"><i class="fas fa-tasks text-red-600 mr-2"></i>Estado ROJO</h3>
            <?php if ($isCanadianVisa): ?>
            <p class="flex items-center gap-2 text-sm">
                <i class="fas <?= !empty($application['canadian_docs_uploaded_portal']) ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' ?>"></i>
                Documentos cargados en portal Canadá
            </p>
            <?php else: ?>
            <div class="space-y-2">
                <p class="flex items-center gap-2 text-sm">
                    <i class="fas <?= $application['official_application_done'] ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' ?>"></i>
                    Solicitud oficial de visa (DS-160)
                </p>
                <p class="flex items-center gap-2 text-sm">
                    <i class="fas <?= $application['consular_fee_sent'] ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' ?>"></i>
                    Ficha de pago enviada
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Estado AMARILLO -->
        <?php if ($status === STATUS_EN_ESPERA_PAGO): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <?php if ($isCanadianVisa): ?>
            <!-- ── Estado AMARILLO: Visa Canadiense ── -->
            <h3 class="text-xl font-bold text-yellow-800 mb-4"><i class="fas fa-hourglass-half text-yellow-600 mr-2"></i>En espera de cita biométrica</h3>
            <?php
            $biometricDate     = $application['canadian_biometric_date'] ?? '';
            $biometricLocation = $application['canadian_biometric_location'] ?? '';
            $biometricGenerated = !empty($application['canadian_biometric_appointment_generated']);
            $canAdvanceToAzulCan = $biometricGenerated && $canadianVacConfirmDoc && !empty($biometricDate);
            ?>
            <?php if ($isAdmin): ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>"
                  enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_PAGO ?>">
                <label class="flex items-center gap-3">
                    <input type="checkbox" name="canadian_biometric_appointment_generated" value="1"
                           <?= $biometricGenerated ? 'checked' : '' ?> class="w-5 h-5 text-yellow-600">
                    <span class="font-medium text-gray-800">Cita para biométricos generada</span>
                    <?php if ($biometricGenerated): ?><i class="fas fa-check-circle text-green-600"></i><?php endif; ?>
                </label>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Confirmación de cita VAC (PDF)</label>
                    <?php if ($canadianVacConfirmDoc): ?>
                    <p class="text-green-600 text-xs mb-1"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($canadianVacConfirmDoc['name']) ?></p>
                    <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $canadianVacConfirmDoc['id'] ?>" class="text-blue-600 text-xs hover:underline"><i class="fas fa-download mr-1"></i>Descargar</a>
                    <?php else: ?>
                    <input type="file" name="canadian_vac_confirmation" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Fecha de biométricos (con hora)</label>
                    <input type="datetime-local" name="canadian_biometric_date"
                           value="<?= !empty($biometricDate) ? date('Y-m-d\TH:i', strtotime($biometricDate)) : '' ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Lugar</label>
                    <input type="text" name="canadian_biometric_location"
                           value="<?= htmlspecialchars($biometricLocation) ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Lugar: VAC CDMX">
                </div>
                <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 text-sm">
                    <i class="fas fa-save mr-1"></i>Guardar
                </button>
            </form>
            <?php if ($canAdvanceToAzulCan): ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>" class="mt-4">
                <input type="hidden" name="status" value="<?= STATUS_CITA_PROGRAMADA ?>">
                <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="Comentario opcional"></textarea>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold">
                    <i class="fas fa-arrow-right mr-2"></i>Pasar a BIOMÉTRICOS PROGRAMADOS (AZUL)
                </button>
            </form>
            <?php else: ?>
            <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Para avanzar: marcar cita generada + subir confirmación VAC + establecer fecha.</p>
            <?php endif; ?>
            <?php else: ?>
            <!-- Asesor view of AMARILLO canadiense -->
            <div class="space-y-2 text-sm">
                <p class="flex items-center gap-2"><i class="fas <?= $biometricGenerated ? 'fa-check-circle text-green-600' : 'fa-circle text-gray-400' ?>"></i>Cita para biométricos generada</p>
                <?php if ($canadianVacConfirmDoc): ?><p class="text-green-600 text-xs"><i class="fas fa-check-circle mr-1"></i>Confirmación de cita VAC subida</p><?php endif; ?>
                <?php if (!empty($biometricDate)): ?><p class="text-blue-700 text-xs"><i class="fas fa-calendar-day mr-1"></i><?= date('d/m/Y H:i', strtotime($biometricDate)) ?></p><?php endif; ?>
                <?php if (!empty($biometricLocation)): ?><p class="text-xs text-gray-600"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($biometricLocation) ?></p><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <!-- ── Estado AMARILLO: Flujo estándar ── -->
            <h3 class="text-xl font-bold text-yellow-800 mb-4"><i class="fas fa-hourglass-half text-yellow-600 mr-2"></i>Estado AMARILLO</h3>
            <!-- Read-only ROJO checklist -->
            <div class="mb-4 p-3 bg-white rounded border border-gray-200">
                <p class="text-sm font-semibold text-gray-700 mb-2">Checklist del estado ROJO:</p>
                <p class="flex items-center gap-2 text-sm mb-1">
                    <i class="fas <?= $application['official_application_done'] ? 'fa-check-circle text-green-600' : 'fa-times-circle text-gray-400' ?>"></i>
                    DS-160<?php if (!empty($application['ds160_confirmation_number'])): ?> — N: <?= htmlspecialchars($application['ds160_confirmation_number']) ?><?php endif; ?>
                </p>
                <p class="flex items-center gap-2 text-sm">
                    <i class="fas <?= $application['consular_fee_sent'] ? 'fa-check-circle text-green-600' : 'fa-times-circle text-gray-400' ?>"></i>
                    Ficha de pago enviada
                </p>
            </div>
            <!-- Pago consular (top priority) -->
            <div class="<?= $application['consular_payment_confirmed'] ? 'bg-green-50 border-green-400' : 'bg-white border-yellow-400' ?> border-2 rounded-lg p-4 mb-4">
                <p class="font-bold text-sm mb-2 <?= $application['consular_payment_confirmed'] ? 'text-green-800' : 'text-yellow-800' ?>">
                    <i class="fas <?= $application['consular_payment_confirmed'] ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-yellow-600' ?> mr-1"></i>
                    Pago consular reflejado/confirmado <?= $application['consular_payment_confirmed'] ? '— CONFIRMADO' : '— Pendiente' ?>
                </p>
                <?php if ($consularPaymentDoc): ?><p class="text-green-700 text-xs mb-2"><i class="fas fa-paperclip mr-1"></i><?= htmlspecialchars($consularPaymentDoc['name']) ?></p><?php endif; ?>
                <?php if ($isAdmin): ?>
                <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>"
                      enctype="multipart/form-data" class="space-y-2">
                    <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_PAGO ?>">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="consular_payment_confirmed" value="1"
                               <?= $application['consular_payment_confirmed'] ? 'checked' : '' ?> class="w-5 h-5">
                        <span class="text-sm font-medium">Confirmar pago consular</span>
                    </label>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Evidencia de pago (max. 2MB)</label>
                        <input type="file" name="consular_payment_file" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1 text-sm"
                               onchange="checkFileSize(this, 'fileSizeError')">
                        <p id="fileSizeError" class="text-red-500 text-xs hidden mt-1">El archivo excede el límite permitido de 2MB. Favor de comprimirlo antes de subirlo.</p>
                    </div>
                    <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 text-sm">
                        <i class="fas fa-save mr-1"></i>Guardar pago
                    </button>
                </form>
                <?php elseif ($isAsesor): ?>
                <form method="POST" action="<?= BASE_URL ?>/solicitudes/subir-documento/<?= $application['id'] ?>"
                      enctype="multipart/form-data" class="space-y-2">
                    <input type="hidden" name="doc_type" value="consular_payment_evidence">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Subir evidencia de pago (max. 2MB)</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1 text-sm"
                               onchange="checkFileSize(this, 'fileSizeErrorAsesor')">
                        <p id="fileSizeErrorAsesor" class="text-red-500 text-xs hidden mt-1">El archivo excede el límite permitido de 2MB. Favor de comprimirlo antes de subirlo.</p>
                    </div>
                    <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 text-sm">
                        <i class="fas fa-upload mr-1"></i>Subir evidencia
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <!-- Admin: cita y solicitud oficial -->
            <?php if ($isAdmin): ?>
            <!-- Fecha de cita -->
            <div class="border rounded-lg p-4 mb-4 bg-white">
                <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-calendar-day mr-1 text-yellow-600"></i>Fecha de cita consular</p>
                <?php if (!empty($application['appointment_date'])): ?>
                <p class="text-green-700 text-sm mb-2"><i class="fas fa-check-circle mr-1"></i><?= date('d/m/Y H:i', strtotime($application['appointment_date'])) ?></p>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>">
                    <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_PAGO ?>">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-600 mb-1">Establecer / actualizar fecha de cita</label>
                            <input type="datetime-local" name="appointment_date"
                                   value="<?= !empty($application['appointment_date']) ? date('Y-m-d\TH:i', strtotime($application['appointment_date'])) : '' ?>"
                                   class="w-full border border-gray-300 rounded px-2 py-1 text-sm">
                        </div>
                        <button type="submit" class="bg-yellow-600 text-white px-3 py-1 rounded text-xs hover:bg-yellow-700 whitespace-nowrap">
                            <i class="fas fa-save mr-1"></i>Guardar fecha
                        </button>
                    </div>
                </form>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-calendar-alt mr-1 text-blue-600"></i>Confirmación de cita</p>
                    <?php if ($appointmentConfirmDoc): ?>
                    <p class="text-green-600 text-xs mb-1"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($appointmentConfirmDoc['name']) ?></p>
                    <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $appointmentConfirmDoc['id'] ?>" class="text-blue-600 text-xs hover:underline"><i class="fas fa-download mr-1"></i>Descargar</a>
                    <?php else: ?>
                    <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>" enctype="multipart/form-data">
                        <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_PAGO ?>">
                        <input type="file" name="appointment_confirmation_doc" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-2 py-1 text-xs mb-2">
                        <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700"><i class="fas fa-upload mr-1"></i>Subir</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-file-signature mr-1 text-purple-600"></i>Solicitud oficial lista</p>
                    <?php if ($officialFinalDoc): ?>
                    <p class="text-green-600 text-xs mb-1"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($officialFinalDoc['name']) ?></p>
                    <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $officialFinalDoc['id'] ?>" class="text-blue-600 text-xs hover:underline"><i class="fas fa-download mr-1"></i>Descargar</a>
                    <?php else: ?>
                    <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>" enctype="multipart/form-data">
                        <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_PAGO ?>">
                        <input type="file" name="official_application_final" accept=".pdf,.jpg,.jpeg,.png" class="w-full border rounded px-2 py-1 text-xs mb-2">
                        <button type="submit" class="bg-purple-600 text-white px-3 py-1 rounded text-xs hover:bg-purple-700"><i class="fas fa-upload mr-1"></i>Subir</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php $canAdvanceToAzul = $application['consular_payment_confirmed'] && $appointmentConfirmDoc && $officialFinalDoc; ?>
            <?php if ($canAdvanceToAzul): ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>" class="mt-2">
                <input type="hidden" name="status" value="<?= STATUS_CITA_PROGRAMADA ?>">
                <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="Comentario opcional"></textarea>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-semibold">
                    <i class="fas fa-arrow-right mr-2"></i>Pasar a CITA PROGRAMADA (AZUL)
                </button>
            </form>
            <?php else: ?>
            <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Para avanzar a AZUL: pago confirmado + confirmación de cita + solicitud oficial.</p>
            <?php endif; ?>
            <?php elseif ($isAsesor): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border rounded-lg p-3">
                    <p class="text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-calendar-alt mr-1 text-blue-600"></i>Confirmación de cita</p>
                    <?php if ($appointmentConfirmDoc): ?>
                    <p class="text-green-600 text-xs"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($appointmentConfirmDoc['name']) ?></p>
                    <?php else: ?><p class="text-red-500 text-xs"><i class="fas fa-times-circle mr-1"></i>Pendiente</p><?php endif; ?>
                </div>
                <div class="border rounded-lg p-3">
                    <p class="text-sm font-semibold text-gray-700 mb-1"><i class="fas fa-file-signature mr-1 text-purple-600"></i>Solicitud oficial lista</p>
                    <?php if ($officialFinalDoc): ?>
                    <p class="text-green-600 text-xs"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($officialFinalDoc['name']) ?></p>
                    <?php else: ?><p class="text-red-500 text-xs"><i class="fas fa-times-circle mr-1"></i>Pendiente</p><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; /* end !$isCanadianVisa */ ?>
        </div>
        <?php endif; ?>

        <!-- Estado AZUL: asistencia -->
        <?php if ($status === STATUS_CITA_PROGRAMADA): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <?php if ($isCanadianVisa): ?>
            <!-- ── Estado AZUL: Visa Canadiense ── -->
            <h3 class="text-xl font-bold text-blue-800 mb-4"><i class="fas fa-fingerprint text-blue-600 mr-2"></i>Biométricos programados</h3>
            <?php if (!empty($application['canadian_biometric_date'])): ?>
            <p class="text-blue-700 text-sm mb-3"><i class="fas fa-calendar-day mr-1"></i>Fecha de biométricos: <strong><?= date('d/m/Y H:i', strtotime($application['canadian_biometric_date'])) ?></strong></p>
            <?php endif; ?>
            <?php if (!empty($application['canadian_biometric_location'])): ?>
            <p class="text-blue-600 text-sm mb-3"><i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($application['canadian_biometric_location']) ?></p>
            <?php endif; ?>
            <?php if (!empty($application['canadian_client_attended_biometrics'])): ?>
            <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Asistencia a biométricos registrada<?= !empty($application['canadian_biometric_attended_date']) ? ' — ' . htmlspecialchars($application['canadian_biometric_attended_date']) : '' ?></p>
            <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>">
                <input type="hidden" name="status" value="<?= STATUS_EN_ESPERA_RESULTADO ?>">
                <div class="flex flex-wrap gap-4 items-end mb-3">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="canadian_client_attended_biometrics" value="1" class="w-4 h-4">
                        <span class="text-sm font-medium">Cliente asistió a biométricos</span>
                    </label>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Fecha de asistencia</label>
                        <input type="date" name="canadian_biometric_attended_date"
                               value="<?= !empty($application['canadian_biometric_date']) ? date('Y-m-d', strtotime($application['canadian_biometric_date'])) : '' ?>"
                               class="border border-gray-300 rounded px-3 py-1 text-sm">
                    </div>
                </div>
                <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="Comentario opcional"></textarea>
                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 font-semibold">
                    <i class="fas fa-arrow-right mr-2"></i>Pasar a EN ESPERA DE RESOLUCIÓN (MORADO)
                </button>
            </form>
            <?php endif; ?>
            <?php else: ?>
            <!-- ── Estado AZUL: Flujo estándar ── -->
            <h3 class="text-xl font-bold text-blue-800 mb-4"><i class="fas fa-calendar-check text-blue-600 mr-2"></i>Asistencia a cita</h3>
            <?php if (!empty($application['appointment_date'])): ?>
            <p class="text-blue-700 text-sm mb-3"><i class="fas fa-calendar-day mr-1"></i>Fecha de cita programada: <strong><?= date('d/m/Y H:i', strtotime($application['appointment_date'])) ?></strong></p>
            <?php endif; ?>
            <?php if ($application['client_attended']): ?>
            <p class="text-green-600 font-semibold"><i class="fas fa-check-circle mr-1"></i>Asistencia registrada<?= $application['client_attended_date'] ? ' — ' . htmlspecialchars($application['client_attended_date']) : '' ?></p>
            <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/marcar-asistencia/<?= $application['id'] ?>">
                <div class="flex flex-wrap gap-4 items-end">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="client_attended" value="1" class="w-4 h-4">
                        <span class="text-sm font-medium">Cliente asistió a CAS/Consulado</span>
                    </label>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Fecha de asistencia</label>
                        <input type="date" name="client_attended_date"
                               value="<?= !empty($application['appointment_date']) ? date('Y-m-d', strtotime($application['appointment_date'])) : '' ?>"
                               class="border border-gray-300 rounded px-3 py-1 text-sm">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                </div>
            </form>
            <?php endif; ?>
            <?php endif; /* end !$isCanadianVisa */ ?>
        </div>
        <?php endif; ?>

        <!-- Estado MORADO -->
        <?php if ($status === STATUS_EN_ESPERA_RESULTADO): ?>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
            <?php if ($isCanadianVisa): ?>
            <!-- ── Estado MORADO: Visa Canadiense ── -->
            <h3 class="text-xl font-bold text-purple-800 mb-4"><i class="fas fa-clock text-purple-600 mr-2"></i>En espera de resolución (aprox. 1 mes)</h3>
            <?php if (!empty($application['canadian_biometric_attended_date'])): ?>
            <p class="flex items-center gap-2 text-sm mb-4">
                <i class="fas fa-check-circle text-green-600"></i>
                Biométricos realizados — <?= htmlspecialchars($application['canadian_biometric_attended_date']) ?>
            </p>
            <?php endif; ?>
            <?php if ($isAdmin || $isAsesor): ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>">
                <input type="hidden" name="status" value="<?= STATUS_TRAMITE_CERRADO ?>">
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Resultado de la visa <span class="text-red-500">*</span></p>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="canadian_visa_result" value="aprobada"
                                   <?= ($application['canadian_visa_result'] ?? '') === 'aprobada' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-green-600" required>
                            <span class="text-green-700 font-semibold"><i class="fas fa-check-circle mr-1"></i>Visa aprobada</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="canadian_visa_result" value="negada"
                                   <?= ($application['canadian_visa_result'] ?? '') === 'negada' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-red-600" required>
                            <span class="text-red-700 font-semibold"><i class="fas fa-times-circle mr-1"></i>Visa negada</span>
                        </label>
                    </div>
                </div>
                <div class="mb-3"><label class="block text-xs text-gray-600 mb-1">Fecha de resolución (opcional)</label>
                    <input type="date" name="canadian_resolution_date" value="<?= htmlspecialchars($application['canadian_resolution_date'] ?? '') ?>" class="w-full border rounded px-3 py-1 text-sm"></div>
                <div class="mb-3"><label class="block text-xs text-gray-600 mb-1">Número de guía (si aplica)</label>
                    <input type="text" name="canadian_guide_number" value="<?= htmlspecialchars($application['canadian_guide_number'] ?? '') ?>" class="w-full border rounded px-3 py-1 text-sm" placeholder="Número de guía"></div>
                <div class="mb-3"><label class="block text-xs text-gray-600 mb-1">Observaciones finales (opcional)</label>
                    <textarea name="canadian_final_observations" rows="2" class="w-full border rounded px-3 py-1 text-sm"><?= htmlspecialchars($application['canadian_final_observations'] ?? '') ?></textarea></div>
                <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="Comentario de historial (opcional)"></textarea>
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 font-semibold">
                    <i class="fas fa-check mr-2"></i>Cerrar trámite (VERDE)
                </button>
            </form>
            <?php endif; ?>
            <?php else: ?>
            <!-- ── Estado MORADO: Flujo estándar ── -->
            <h3 class="text-xl font-bold text-purple-800 mb-4"><i class="fas fa-clock text-purple-600 mr-2"></i>En espera de resultado</h3>
            <p class="flex items-center gap-2 text-sm mb-4">
                <i class="fas fa-check-circle text-green-600"></i>
                Cliente asistió <?= $application['client_attended_date'] ? '— ' . htmlspecialchars($application['client_attended_date']) : '' ?>
            </p>
            <?php if ($isAdmin || $isAsesor): ?>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>">
                <input type="hidden" name="status" value="<?= STATUS_TRAMITE_CERRADO ?>">
                <div class="mb-3"><label class="block text-xs text-gray-600 mb-1">Guía DHL (opcional)</label>
                    <input type="text" name="dhl_tracking" value="<?= htmlspecialchars($application['dhl_tracking'] ?? '') ?>" class="w-full border rounded px-3 py-1 text-sm"></div>
                <div class="mb-3"><label class="block text-xs text-gray-600 mb-1">Fecha de entrega (opcional)</label>
                    <input type="date" name="delivery_date" value="<?= htmlspecialchars($application['delivery_date'] ?? '') ?>" class="w-full border rounded px-3 py-1 text-sm"></div>
                <textarea name="comment" rows="2" class="w-full border rounded px-3 py-2 text-sm mb-2" placeholder="Comentario opcional"></textarea>
                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 font-semibold">
                    <i class="fas fa-check mr-2"></i>Cerrar trámite (VERDE)
                </button>
            </form>
            <?php endif; ?>
            <?php endif; /* end !$isCanadianVisa */ ?>
        </div>
        <?php endif; ?>

        <!-- Estado VERDE: solo Admin -->
        <?php if ($isClosedStatus && $isAdmin): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <h3 class="text-xl font-bold text-green-800 mb-4"><i class="fas fa-check-circle text-green-600 mr-2"></i>Trámite cerrado</h3>
            <?php if ($isCanadianVisa): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($application['canadian_visa_result'])): ?>
                <div class="md:col-span-2">
                    <p class="text-sm text-gray-600">Resultado</p>
                    <p class="font-bold text-lg <?= $application['canadian_visa_result'] === 'aprobada' ? 'text-green-700' : 'text-red-700' ?>">
                        <i class="fas <?= $application['canadian_visa_result'] === 'aprobada' ? 'fa-check-circle' : 'fa-times-circle' ?> mr-1"></i>
                        Visa <?= htmlspecialchars(ucfirst($application['canadian_visa_result'])) ?>
                    </p>
                </div>
                <?php endif; ?>
                <?php if (!empty($application['canadian_resolution_date'])): ?>
                <div><p class="text-sm text-gray-600">Fecha de resolución</p><p class="font-semibold"><?= htmlspecialchars($application['canadian_resolution_date']) ?></p></div>
                <?php endif; ?>
                <?php if (!empty($application['canadian_guide_number'])): ?>
                <div><p class="text-sm text-gray-600">Número de guía</p><p class="font-semibold"><?= htmlspecialchars($application['canadian_guide_number']) ?></p></div>
                <?php endif; ?>
                <?php if (!empty($application['canadian_final_observations'])): ?>
                <div class="md:col-span-2"><p class="text-sm text-gray-600">Observaciones finales</p><p class="font-semibold"><?= nl2br(htmlspecialchars($application['canadian_final_observations'])) ?></p></div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if (!empty($application['dhl_tracking'])): ?>
                <div><p class="text-sm text-gray-600">Guía DHL</p><p class="font-semibold"><?= htmlspecialchars($application['dhl_tracking']) ?></p></div>
                <?php endif; ?>
                <?php if (!empty($application['delivery_date'])): ?>
                <div><p class="text-sm text-gray-600">Fecha de entrega</p><p class="font-semibold"><?= htmlspecialchars($application['delivery_date']) ?></p></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Documentos Base (always visible to Admin/Gerente regardless of status; hidden for Asesor in closed state) -->
        <?php if ($isAdmin || !$isClosedStatus): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-passport text-blue-600 mr-2"></i>Documentos Base</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-passport mr-1"></i>Pasaporte vigente</p>
                    <?php if ($pasaporteDoc): ?>
                        <?php if ($isAdmin): ?>
                        <p class="text-green-600 font-semibold mb-2"><i class="fas fa-check-circle mr-1"></i>Subido</p>
                        <div class="flex gap-3">
                            <a href="<?= BASE_URL ?>/solicitudes/ver-documento/<?= $pasaporteDoc['id'] ?>" target="_blank" class="text-blue-600 text-sm"><i class="fas fa-eye mr-1"></i>Ver</a>
                            <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $pasaporteDoc['id'] ?>" class="text-primary text-sm"><i class="fas fa-download mr-1"></i>Descargar</a>
                        </div>
                        <?php else: ?><p class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($pasaporteDoc['name']) ?></p><?php endif; ?>
                    <?php else: ?>
                        <?php if (($isAsesor || $isAdmin) && !$isClosedStatus): ?>
                        <button onclick="openDocUpload('pasaporte_vigente')" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"><i class="fas fa-upload mr-1"></i>Subir</button>
                        <?php else: ?><p class="text-red-500 text-sm"><i class="fas fa-times-circle mr-1"></i>No subido</p><?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($isCanadianVisa): ?>
                <?php /* Canadian visa: visa canadiense anterior (si renovación) */ ?>
                <?php if ($canadianIsRenovacion): ?>
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-id-card mr-1"></i>Visa canadiense anterior</p>
                    <?php if ($visaCanadiensPrevDoc): ?>
                        <?php if ($isAdmin): ?>
                        <p class="text-green-600 font-semibold mb-2"><i class="fas fa-check-circle mr-1"></i>Subido</p>
                        <div class="flex gap-3">
                            <a href="<?= BASE_URL ?>/solicitudes/ver-documento/<?= $visaCanadiensPrevDoc['id'] ?>" target="_blank" class="text-blue-600 text-sm"><i class="fas fa-eye mr-1"></i>Ver</a>
                            <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $visaCanadiensPrevDoc['id'] ?>" class="text-primary text-sm"><i class="fas fa-download mr-1"></i>Descargar</a>
                        </div>
                        <?php else: ?><p class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($visaCanadiensPrevDoc['name']) ?></p><?php endif; ?>
                    <?php else: ?>
                        <?php if (($isAsesor || $isAdmin) && !$isClosedStatus): ?>
                        <button onclick="openDocUpload('visa_canadiense_anterior')" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"><i class="fas fa-upload mr-1"></i>Subir</button>
                        <?php else: ?><p class="text-red-500 text-sm"><i class="fas fa-times-circle mr-1"></i>No subido</p><?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; /* canadianIsRenovacion */ ?>
                <?php /* Canadian visa: ETA anterior (si ETA + renovación) */ ?>
                <?php if ($canadianIsETA && $canadianIsRenovacion): ?>
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-file-alt mr-1"></i>ETA anterior</p>
                    <?php if ($etaAnteriorDoc): ?>
                        <?php if ($isAdmin): ?>
                        <p class="text-green-600 font-semibold mb-2"><i class="fas fa-check-circle mr-1"></i>Subido</p>
                        <div class="flex gap-3">
                            <a href="<?= BASE_URL ?>/solicitudes/ver-documento/<?= $etaAnteriorDoc['id'] ?>" target="_blank" class="text-blue-600 text-sm"><i class="fas fa-eye mr-1"></i>Ver</a>
                            <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $etaAnteriorDoc['id'] ?>" class="text-primary text-sm"><i class="fas fa-download mr-1"></i>Descargar</a>
                        </div>
                        <?php else: ?><p class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($etaAnteriorDoc['name']) ?></p><?php endif; ?>
                    <?php else: ?>
                        <?php if (($isAsesor || $isAdmin) && !$isClosedStatus): ?>
                        <button onclick="openDocUpload('eta_anterior')" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"><i class="fas fa-upload mr-1"></i>Subir</button>
                        <?php else: ?><p class="text-red-500 text-sm"><i class="fas fa-times-circle mr-1"></i>No subido</p><?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; /* canadianIsETA && canadianIsRenovacion */ ?>
                <?php else: ?>
                <?php /* Standard flow: visa anterior (si renovación) */ ?>
                <?php if ($isRenovacion): ?>
                <div class="border rounded-lg p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-id-card mr-1"></i>Visa anterior</p>
                    <?php if ($visaAnteriorDoc): ?>
                        <?php if ($isAdmin): ?>
                        <p class="text-green-600 font-semibold mb-2"><i class="fas fa-check-circle mr-1"></i>Subido</p>
                        <div class="flex gap-3">
                            <a href="<?= BASE_URL ?>/solicitudes/ver-documento/<?= $visaAnteriorDoc['id'] ?>" target="_blank" class="text-blue-600 text-sm"><i class="fas fa-eye mr-1"></i>Ver</a>
                            <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $visaAnteriorDoc['id'] ?>" class="text-primary text-sm"><i class="fas fa-download mr-1"></i>Descargar</a>
                        </div>
                        <?php else: ?><p class="text-green-600 text-sm"><i class="fas fa-check-circle mr-1"></i><?= htmlspecialchars($visaAnteriorDoc['name']) ?></p><?php endif; ?>
                    <?php else: ?>
                        <?php if (($isAsesor || $isAdmin) && !$isClosedStatus): ?>
                        <button onclick="openDocUpload('visa_anterior')" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700"><i class="fas fa-upload mr-1"></i>Subir</button>
                        <?php else: ?><p class="text-red-500 text-sm"><i class="fas fa-times-circle mr-1"></i>No subido</p><?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; /* end isCanadianVisa/else */ ?>
            </div>
        </div>
        <?php endif; /* end Documentos Base */ ?>

        <!-- Documentos generales (always visible to Admin/Gerente) -->
        <?php if ($isAdmin || !$isClosedStatus): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Documentos</h3>
                <?php if (!$isClosedStatus): ?>
                <button onclick="openDocUpload('adicional')"
                        class="btn-primary text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-upload mr-2"></i>Subir
                </button>
                <?php endif; ?>
            </div>
            <?php if (!empty($documents)): ?>
            <div class="space-y-3">
                <?php foreach ($documents as $doc): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-file-<?= $doc['file_type'] === 'pdf' ? 'pdf text-red-500' : 'alt text-blue-500' ?> text-2xl"></i>
                        <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($doc['name']) ?></p>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($doc['uploaded_by_name']) ?> · <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?> · <?= number_format($doc['file_size']/1024, 0) ?> KB</p>
                        </div>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="flex items-center space-x-3">
                        <a href="<?= BASE_URL ?>/solicitudes/ver-documento/<?= $doc['id'] ?>" target="_blank" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        <a href="<?= BASE_URL ?>/solicitudes/descargar-documento/<?= $doc['id'] ?>" class="text-primary hover:underline"><i class="fas fa-download"></i></a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?><p class="text-gray-500 text-center py-6">No hay documentos</p><?php endif; ?>
        </div>
        <?php endif; /* end isAdmin || !closed */ ?>

        <!-- Indicaciones (always visible to Admin/Gerente) -->
        <?php if ($isAdmin || !$isClosedStatus): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Indicaciones</h3>
                <?php if ($isAdmin): ?>
                <button onclick="document.getElementById('noteModal').classList.remove('hidden')"
                        class="btn-primary text-white px-4 py-2 rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-plus mr-2"></i>Agregar
                </button>
                <?php endif; ?>
            </div>
            <?php if (!empty($notes)): ?>
            <div class="space-y-3">
                <?php foreach ($notes as $note): ?>
                <div class="p-4 rounded-lg <?= $note['is_important'] ? 'bg-yellow-50 border-l-4 border-yellow-500' : 'bg-gray-50 border-l-4 border-gray-300' ?>">
                    <div class="flex justify-between items-start mb-2">
                        <?php if ($note['is_important']): ?><span class="text-sm font-semibold text-yellow-800"><i class="fas fa-exclamation-circle text-yellow-600 mr-1"></i>IMPORTANTE</span><?php endif; ?>
                        <span class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($note['created_at'])) ?></span>
                    </div>
                    <p class="text-gray-800 mb-1"><?= nl2br(htmlspecialchars($note['note_text'])) ?></p>
                    <p class="text-sm text-gray-500">Por: <?= htmlspecialchars($note['created_by_name']) ?> (<?= htmlspecialchars($note['created_by_role']) ?>)</p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?><p class="text-gray-500 text-center py-6">No hay indicaciones</p><?php endif; ?>
        </div>
        <?php endif; /* end Indicaciones */ ?>

        <!-- Historial de Estatus -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Historial de Estatus</h3>
            <div class="space-y-4">
                <?php foreach ($history as $item): ?>
                <div class="flex items-start">
                    <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-blue-100">
                        <i class="fas fa-check text-blue-600"></i>
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

    </div><!-- end main col -->

    <!-- COLUMNA LATERAL -->
    <div class="space-y-6">

        <!-- Cambiar Estatus manual (Admin/Gerente) -->
        <?php if ($isAdmin && !$isClosedStatus): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Cambiar Estatus</h3>
            <form method="POST" action="<?= BASE_URL ?>/solicitudes/cambiar-estatus/<?= $application['id'] ?>" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estatus</label>
                    <select name="status" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                        <option value="">-- Seleccione --</option>
                        <option value="<?= STATUS_NUEVO ?>"               <?= $status===STATUS_NUEVO               ? 'selected':'' ?>>Nuevo</option>
                        <?php if ($isCanadianVisa): ?>
                        <option value="<?= STATUS_LISTO_SOLICITUD ?>"     <?= $status===STATUS_LISTO_SOLICITUD     ? 'selected':'' ?>><?= htmlspecialchars($canadianStatusLabels[STATUS_LISTO_SOLICITUD]) ?></option>
                        <option value="<?= STATUS_EN_ESPERA_PAGO ?>"      <?= $status===STATUS_EN_ESPERA_PAGO      ? 'selected':'' ?>><?= htmlspecialchars($canadianStatusLabels[STATUS_EN_ESPERA_PAGO]) ?></option>
                        <option value="<?= STATUS_CITA_PROGRAMADA ?>"     <?= $status===STATUS_CITA_PROGRAMADA     ? 'selected':'' ?>><?= htmlspecialchars($canadianStatusLabels[STATUS_CITA_PROGRAMADA]) ?></option>
                        <option value="<?= STATUS_EN_ESPERA_RESULTADO ?>" <?= $status===STATUS_EN_ESPERA_RESULTADO ? 'selected':'' ?>><?= htmlspecialchars($canadianStatusLabels[STATUS_EN_ESPERA_RESULTADO]) ?></option>
                        <option value="<?= STATUS_TRAMITE_CERRADO ?>"     <?= $status===STATUS_TRAMITE_CERRADO     ? 'selected':'' ?>><?= htmlspecialchars($canadianStatusLabels[STATUS_TRAMITE_CERRADO]) ?></option>
                        <?php else: ?>
                        <option value="<?= STATUS_LISTO_SOLICITUD ?>"     <?= $status===STATUS_LISTO_SOLICITUD     ? 'selected':'' ?>>Listo para solicitud</option>
                        <option value="<?= STATUS_EN_ESPERA_PAGO ?>"      <?= $status===STATUS_EN_ESPERA_PAGO      ? 'selected':'' ?>>En espera de pago consular</option>
                        <option value="<?= STATUS_CITA_PROGRAMADA ?>"     <?= $status===STATUS_CITA_PROGRAMADA     ? 'selected':'' ?>>Cita programada</option>
                        <option value="<?= STATUS_EN_ESPERA_RESULTADO ?>" <?= $status===STATUS_EN_ESPERA_RESULTADO ? 'selected':'' ?>>En espera de resultado</option>
                        <option value="<?= STATUS_TRAMITE_CERRADO ?>"     <?= $status===STATUS_TRAMITE_CERRADO     ? 'selected':'' ?>>Trámite cerrado</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comentario</label>
                    <textarea name="comment" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Opcional"></textarea>
                </div>
                <button type="submit" class="w-full btn-primary text-white py-2 rounded-lg hover:opacity-90">
                    <i class="fas fa-sync-alt mr-2"></i>Actualizar Estatus
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Informacion Financiera (Admin/Gerente) -->
        <?php if ($isAdmin && isset($application['total_costs'])): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Financiero</h3>
                <!-- <a href="<?= BASE_URL ?>/financiero/solicitud/<?= $application['id'] ?>" class="text-primary hover:underline"><i class="fas fa-arrow-right"></i></a> -->
            </div>
            <div class="space-y-3">
                <div class="flex justify-between"><span class="text-gray-600">Total:</span><span class="font-bold">$<?= number_format($application['total_costs'], 2) ?></span></div>
                <div class="flex justify-between"><span class="text-gray-600">Pagado:</span><span class="font-bold text-green-600">$<?= number_format($application['total_paid'], 2) ?></span></div>
                <div class="flex justify-between border-t pt-2"><span class="text-gray-600">Saldo:</span>
                    <span class="font-bold text-<?= $application['balance'] > 0 ? 'red' : 'green' ?>-600">$<?= number_format($application['balance'], 2) ?></span></div>
                <div class="text-center mt-3">
                    <span class="px-3 py-1 text-sm rounded-full font-medium <?= $application['financial_status']===FINANCIAL_PAGADO ? 'bg-green-100 text-green-800' : ($application['financial_status']===FINANCIAL_PARCIAL ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                        <?= htmlspecialchars($application['financial_status']) ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Modal: Subir Documento -->
<div id="uploadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Subir Documento</h3>
            <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/solicitudes/subir-documento/<?= $application['id'] ?>" enctype="multipart/form-data">
            <input type="hidden" id="docTypeHidden" name="doc_type" value="adicional">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Archivo</label>
                <input type="file" name="document" required class="w-full border border-gray-300 rounded-lg px-4 py-2">
                <p class="text-xs text-gray-500 mt-1">PDF, JPG, PNG, DOC, DOCX (Max. 2MB)</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary text-white py-2 rounded-lg hover:opacity-90"><i class="fas fa-upload mr-2"></i>Subir</button>
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Indicacion -->
<?php if ($isAdmin): ?>
<div id="noteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Agregar Indicacion</h3>
            <button onclick="document.getElementById('noteModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/solicitudes/agregar-indicacion/<?= $application['id'] ?>">
            <div class="mb-4"><textarea name="note_text" required rows="4"
                      class="w-full border border-gray-300 rounded-lg px-4 py-2" placeholder="Indicacion..."></textarea></div>
            <div class="mb-4"><label class="flex items-center">
                <input type="checkbox" name="is_important" class="w-4 h-4">
                <span class="ml-2 text-sm"><i class="fas fa-exclamation-circle text-yellow-600 mr-1"></i>Importante</span>
            </label></div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary text-white py-2 rounded-lg hover:opacity-90"><i class="fas fa-save mr-2"></i>Guardar</button>
                <button type="button" onclick="document.getElementById('noteModal').classList.add('hidden')" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Hoja de Informacion -->
<?php if (!$infoSheet || $isAdmin): ?>
<div id="infoSheetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg p-6 w-full max-w-lg my-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Hoja de Informacion</h3>
            <button onclick="document.getElementById('infoSheetModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" action="<?= BASE_URL ?>/solicitudes/guardar-hoja-info/<?= $application['id'] ?>">
            <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                <input type="date" name="entry_date" required value="<?= htmlspecialchars($infoSheet['entry_date'] ?? date('Y-m-d')) ?>" class="w-full border rounded-lg px-4 py-2"></div>
            <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Lugar de residencia del solicitante</label>
                <input type="text" name="residence_place" value="<?= htmlspecialchars($infoSheet['residence_place'] ?? '') ?>" class="w-full border rounded-lg px-4 py-2"></div>
            <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Domicilio completo</label>
                <input type="text" name="address" value="<?= htmlspecialchars($infoSheet['address'] ?? '') ?>" class="w-full border rounded-lg px-4 py-2"></div>
            <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Email del solicitante</label>
                <input type="email" name="client_email" value="<?= htmlspecialchars($infoSheet['client_email'] ?? '') ?>" class="w-full border rounded-lg px-4 py-2"></div>
            <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1"><?= $isCanadianVisa ? 'Email de la embajada canadiense' : 'Email de la embajada' ?></label>
                <input type="email" name="embassy_email" value="<?= htmlspecialchars($infoSheet['embassy_email'] ?? '') ?>" class="w-full border rounded-lg px-4 py-2"></div>
            <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1"><?= $isCanadianVisa ? 'Costo que pagó el cliente' : 'Honorarios pagados' ?></label>
                <input type="number" step="0.01" min="0" name="amount_paid" value="<?= htmlspecialchars($infoSheet['amount_paid'] ?? '') ?>" class="w-full border rounded-lg px-4 py-2"></div>
            <div class="mb-3"><label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                <textarea name="observations" rows="3" class="w-full border rounded-lg px-4 py-2"><?= htmlspecialchars($infoSheet['observations'] ?? '') ?></textarea></div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary text-white py-2 rounded-lg hover:opacity-90"><i class="fas fa-save mr-2"></i>Guardar</button>
                <button type="button" onclick="document.getElementById('infoSheetModal').classList.add('hidden')" class="flex-1 bg-gray-500 text-white py-2 rounded-lg hover:bg-gray-600">Cancelar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openDocUpload(docType) {
    var hidden = document.getElementById('docTypeHidden');
    if (hidden) { hidden.value = docType || 'adicional'; }
    document.getElementById('uploadModal').classList.remove('hidden');
}
function showCopySuccess() {
    var msg = document.createElement('div');
    msg.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    msg.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Enlace copiado';
    document.body.appendChild(msg);
    setTimeout(function() { msg.remove(); }, 3000);
}
function copyFormLink() {
    var formId = document.getElementById('formLinkSelect') ? document.getElementById('formLinkSelect').value : '';
    if (!formId) { alert('Seleccione un formulario'); return; }
    var frm = document.createElement('form');
    frm.method = 'POST';
    frm.action = '<?= BASE_URL ?>/solicitudes/vincular-formulario/<?= $application['id'] ?>';
    var inp = document.createElement('input');
    inp.type='hidden'; inp.name='form_link_id'; inp.value=formId;
    frm.appendChild(inp); document.body.appendChild(frm); frm.submit();
}
function copyClientFormUrl() {
    var input = document.getElementById('clientFormUrlInput');
    if (!input) return;
    var url = input.value;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(showCopySuccess).catch(function() { input.select(); document.execCommand('copy'); showCopySuccess(); });
    } else { input.select(); document.execCommand('copy'); showCopySuccess(); }
}
function checkFileSize(input, errorId) {
    var maxSize = 2*1024*1024;
    var el = document.getElementById(errorId);
    if (input.files && input.files[0]) {
        if (input.files[0].size > maxSize) { if (el) el.classList.remove('hidden'); input.value=''; }
        else { if (el) el.classList.add('hidden'); }
    }
}
document.addEventListener('DOMContentLoaded', function() {
    var p = new URLSearchParams(window.location.search);
    if (p.get('copiar_enlace')==='1') { var i=document.getElementById('clientFormUrlInput'); if(i){i.select();i.focus();} }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
