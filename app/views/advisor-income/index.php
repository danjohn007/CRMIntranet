<?php
$title = 'Ingresos';
ob_start();
?>

<div class="mb-4 md:mb-6">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Ingresos</h2>
    <p class="text-sm md:text-base text-gray-600">Registra catálogo de ingresos y movimientos adicionales</p>
</div>

<div class="bg-white rounded-lg shadow p-4 md:p-6 mb-4 md:mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <button type="button" id="toggleCatalogForm" class="inline-flex items-center justify-center px-4 py-2 btn-primary text-white rounded-lg hover:opacity-90">
            <i class="fas fa-book mr-2"></i>Catálogo ingresos
        </button>
        <button type="button" id="toggleIncomeForm" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            <i class="fas fa-plus mr-2"></i>Registrar ingreso
        </button>
    </div>

    <form id="catalogForm" action="<?= BASE_URL ?>/ingresos/catalogo" method="POST" class="hidden mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de ingreso *</label>
            <input type="text" name="income_type" required maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ejemplo: Venta de copias">
        </div>
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
            <input type="number" name="amount" required min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ejemplo: 10.00">
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
                Guardar tipo
            </button>
        </div>
    </form>

    <form id="incomeForm" action="<?= BASE_URL ?>/ingresos/registrar" method="POST" class="hidden mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de ingreso *</label>
            <select name="income_type_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="">-- Selecciona --</option>
                <?php foreach ($incomeCatalog ?? [] as $type): ?>
                <option value="<?= (int) $type['id'] ?>">
                    <?= htmlspecialchars($type['income_type']) ?> — $<?= number_format((float) $type['amount'], 2) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora *</label>
            <input type="datetime-local" id="incomeDatetimeField" name="income_datetime" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nota</label>
            <input type="text" name="note" maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Opcional">
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                Guardar ingreso
            </button>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Catálogo de ingresos</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($incomeCatalog ?? [] as $type): ?>
                    <tr>
                        <td class="px-3 py-2 text-sm text-gray-700"><?= htmlspecialchars($type['income_type']) ?></td>
                        <td class="px-3 py-2 text-sm font-semibold text-green-600">$<?= number_format((float) $type['amount'], 2) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600">
                            <button
                                type="button"
                                class="editCatalogBtn inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-yellow-500 text-white rounded-lg hover:bg-yellow-600"
                                data-id="<?= (int) $type['id'] ?>"
                                data-income_type="<?= htmlspecialchars($type['income_type'], ENT_QUOTES, 'UTF-8') ?>"
                                data-amount="<?= number_format((float) $type['amount'], 2, '.', '') ?>"
                            >
                                <i class="fas fa-edit mr-1"></i>Editar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($incomeCatalog)): ?>
                    <tr>
                        <td colspan="3" class="px-3 py-6 text-center text-gray-500">No hay tipos de ingreso registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-3">Últimos ingresos registrados</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Atendido por</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Folio</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nota</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($recentIncomes ?? [] as $income): ?>
                    <tr>
                        <td class="px-3 py-2 text-sm text-gray-700"><?= htmlspecialchars($income['attended_by_name'] ?? '') ?></td>
                        <td class="px-3 py-2 text-xs md:text-sm font-semibold text-primary"><?= htmlspecialchars($income['generated_folio'] ?? '') ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($income['income_datetime'])) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-700"><?= htmlspecialchars($income['income_type']) ?></td>
                        <td class="px-3 py-2 text-sm font-semibold text-green-600">$<?= number_format((float) $income['amount'], 2) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= htmlspecialchars($income['note'] ?? '') ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600">
                            <button
                                type="button"
                                class="generateTicketBtn inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-primary text-white rounded-lg hover:opacity-90"
                                data-folio="<?= htmlspecialchars($income['generated_folio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-fecha="<?= htmlspecialchars(date('d/m/Y H:i', strtotime($income['income_datetime'])), ENT_QUOTES, 'UTF-8') ?>"
                                data-tipo="<?= htmlspecialchars($income['income_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-precio="<?= number_format((float) $income['amount'], 2, '.', '') ?>"
                                data-nota="<?= htmlspecialchars($income['note'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-attended_by="<?= htmlspecialchars($income['attended_by_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <i class="fas fa-receipt mr-1"></i>Generar ticket
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentIncomes)): ?>
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-gray-500">Sin ingresos registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="ticketModal" class="fixed inset-0 z-50 hidden">
    <div id="ticketModalBackdrop" class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-5 md:p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-800">Generar ticket</h3>
                    <p class="text-xs md:text-sm text-gray-500">Completa los campos para imprimir o guardar el ticket en PDF.</p>
                </div>
                <button type="button" id="closeTicketModalBtn" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form id="ticketForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" id="ticketFolio">
                <input type="hidden" id="ticketFecha">
                <input type="hidden" id="ticketTipo">
                <input type="hidden" id="ticketPrecioServicio">
                <input type="hidden" id="ticketNota">
                <input type="hidden" id="ticketAtendidoPor">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del cliente</label>
                    <input type="text" id="ticketCliente" maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Opcional">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" id="ticketTelefono" maxlength="50" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Opcional">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                    <input type="email" id="ticketCorreo" maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Opcional">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Servicio contratado</label>
                    <input type="text" id="ticketServicio" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio del servicio</label>
                    <input type="text" id="ticketPrecio" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-700">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto pagado *</label>
                    <input type="number" id="ticketMontoPagado" required min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="0.00">
                </div>

                <div class="md:col-span-2 flex flex-wrap justify-end gap-2 pt-2">
                    <button type="button" id="cancelTicketBtn" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Generar ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editCatalogModal" class="fixed inset-0 z-50 hidden">
    <div id="editCatalogModalBackdrop" class="absolute inset-0 bg-black bg-opacity-40"></div>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 md:p-6">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-800">Editar tipo de ingreso</h3>
                    <p class="text-xs md:text-sm text-gray-500">Actualiza nombre y monto del catálogo.</p>
                </div>
                <button type="button" id="closeEditCatalogModalBtn" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form action="<?= BASE_URL ?>/ingresos/catalogo/actualizar" method="POST" class="grid grid-cols-1 gap-4">
                <input type="hidden" id="editCatalogId" name="catalog_id">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de ingreso *</label>
                    <input type="text" id="editCatalogIncomeType" name="income_type" required maxlength="200"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Ejemplo: Venta de copias">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
                    <input type="number" id="editCatalogAmount" name="amount" required min="0.01" step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Ejemplo: 10.00">
                </div>

                <div class="flex flex-wrap justify-end gap-2 pt-2">
                    <button type="button" id="cancelEditCatalogBtn" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleCatalogBtn = document.getElementById('toggleCatalogForm');
    const toggleIncomeBtn = document.getElementById('toggleIncomeForm');
    const catalogForm = document.getElementById('catalogForm');
    const incomeForm = document.getElementById('incomeForm');

    if (toggleCatalogBtn && catalogForm) {
        toggleCatalogBtn.addEventListener('click', function () {
            catalogForm.classList.toggle('hidden');
        });
    }

    if (toggleIncomeBtn && incomeForm) {
        toggleIncomeBtn.addEventListener('click', function () {
            incomeForm.classList.toggle('hidden');
        });
    }

    const editCatalogModal = document.getElementById('editCatalogModal');
    const editCatalogModalBackdrop = document.getElementById('editCatalogModalBackdrop');
    const closeEditCatalogModalBtn = document.getElementById('closeEditCatalogModalBtn');
    const cancelEditCatalogBtn = document.getElementById('cancelEditCatalogBtn');
    const editCatalogButtons = document.querySelectorAll('.editCatalogBtn');

    function openEditCatalogModal(data) {
        if (!editCatalogModal) {
            return;
        }

        document.getElementById('editCatalogId').value = data.id || '';
        document.getElementById('editCatalogIncomeType').value = data.income_type || '';
        document.getElementById('editCatalogAmount').value = data.amount || '';

        editCatalogModal.classList.remove('hidden');
    }

    function closeEditCatalogModal() {
        if (!editCatalogModal) {
            return;
        }
        editCatalogModal.classList.add('hidden');
    }

    editCatalogButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            openEditCatalogModal({
                id: button.getAttribute('data-id'),
                income_type: button.getAttribute('data-income_type'),
                amount: button.getAttribute('data-amount')
            });
        });
    });

    if (editCatalogModalBackdrop) {
        editCatalogModalBackdrop.addEventListener('click', closeEditCatalogModal);
    }
    if (closeEditCatalogModalBtn) {
        closeEditCatalogModalBtn.addEventListener('click', closeEditCatalogModal);
    }
    if (cancelEditCatalogBtn) {
        cancelEditCatalogBtn.addEventListener('click', closeEditCatalogModal);
    }

    const incomeDatetimeField = document.getElementById('incomeDatetimeField');
    if (incomeDatetimeField) {
        const now = new Date();
        const local = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
        const localDatetime = local.toISOString().slice(0, 16);
        if (!incomeDatetimeField.value) {
            incomeDatetimeField.value = localDatetime;
        }
        incomeDatetimeField.max = localDatetime;
    }

    const ticketModal = document.getElementById('ticketModal');
    const ticketModalBackdrop = document.getElementById('ticketModalBackdrop');
    const closeTicketModalBtn = document.getElementById('closeTicketModalBtn');
    const cancelTicketBtn = document.getElementById('cancelTicketBtn');
    const ticketForm = document.getElementById('ticketForm');
    const generateTicketButtons = document.querySelectorAll('.generateTicketBtn');

    function openTicketModal(data) {
        if (!ticketModal) {
            return;
        }

        document.getElementById('ticketFolio').value = data.folio || '';
        document.getElementById('ticketFecha').value = data.fecha || '';
        document.getElementById('ticketTipo').value = data.tipo || '';
        document.getElementById('ticketPrecioServicio').value = data.precio || '0.00';
        document.getElementById('ticketNota').value = data.nota || '';
        document.getElementById('ticketAtendidoPor').value = data.attended_by || '';

        document.getElementById('ticketServicio').value = data.tipo || '';
        document.getElementById('ticketPrecio').value = '$' + (data.precio || '0.00');
        document.getElementById('ticketCliente').value = '';
        document.getElementById('ticketTelefono').value = '';
        document.getElementById('ticketCorreo').value = '';
        document.getElementById('ticketMontoPagado').value = data.precio || '';

        ticketModal.classList.remove('hidden');
    }

    function closeTicketModal() {
        if (!ticketModal) {
            return;
        }
        ticketModal.classList.add('hidden');
    }

    generateTicketButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            openTicketModal({
                folio: button.getAttribute('data-folio'),
                fecha: button.getAttribute('data-fecha'),
                tipo: button.getAttribute('data-tipo'),
                precio: button.getAttribute('data-precio'),
                nota: button.getAttribute('data-nota'),
                attended_by: button.getAttribute('data-attended_by')
            });
        });
    });

    if (ticketModalBackdrop) {
        ticketModalBackdrop.addEventListener('click', closeTicketModal);
    }
    if (closeTicketModalBtn) {
        closeTicketModalBtn.addEventListener('click', closeTicketModal);
    }
    if (cancelTicketBtn) {
        cancelTicketBtn.addEventListener('click', closeTicketModal);
    }

    function safeText(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    if (ticketForm) {
        ticketForm.addEventListener('submit', function (event) {
            event.preventDefault();

            const montoPagado = parseFloat(document.getElementById('ticketMontoPagado').value || '0');
            if (!Number.isFinite(montoPagado) || montoPagado <= 0) {
                alert('El monto pagado es obligatorio y debe ser mayor a cero.');
                return;
            }

            const folio = document.getElementById('ticketFolio').value;
            const fecha = document.getElementById('ticketFecha').value;
            const servicio = document.getElementById('ticketTipo').value;
            const precioServicio = document.getElementById('ticketPrecioServicio').value;
            const nota = document.getElementById('ticketNota').value;
            const cliente = document.getElementById('ticketCliente').value;
            const telefono = document.getElementById('ticketTelefono').value;
            const correo = document.getElementById('ticketCorreo').value;
            const atendidoPor = document.getElementById('ticketAtendidoPor').value;

            const ticketWindow = window.open('', '_blank', 'width=900,height=800');
            if (!ticketWindow) {
                alert('No se pudo abrir la ventana de ticket. Verifica el bloqueador de ventanas emergentes.');
                return;
            }

            const html = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket ${safeText(folio)}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #1f2937; }
        .ticket { max-width: 760px; margin: 0 auto; border: 1px solid #d1d5db; border-radius: 10px; overflow: hidden; }
        .header { background: #0f172a; color: #fff; padding: 16px 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.9; }
        .body { padding: 20px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .item { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .item .label { font-size: 12px; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; }
        .item .value { font-size: 15px; font-weight: 600; color: #111827; word-break: break-word; }
        .totals { margin-top: 14px; border-top: 1px dashed #d1d5db; padding-top: 14px; }
        .total-line { display: flex; justify-content: space-between; margin: 5px 0; font-size: 15px; }
        .total-line strong { font-size: 17px; }
        .footer { padding: 14px 20px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
        @media print {
            body { padding: 0; }
            .ticket { border: none; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>Ticket de ingreso</h1>
            <p>Folio: ${safeText(folio)} | Fecha: ${safeText(fecha)}</p>
        </div>
        <div class="body">
            <div class="grid">
                <div class="item"><div class="label">Cliente</div><div class="value">${safeText(cliente) || 'N/D'}</div></div>
                <div class="item"><div class="label">Telefono</div><div class="value">${safeText(telefono) || 'N/D'}</div></div>
                <div class="item"><div class="label">Correo</div><div class="value">${safeText(correo) || 'N/D'}</div></div>
                <div class="item"><div class="label">Servicio contratado</div><div class="value">${safeText(servicio)}</div></div>
                <div class="item"><div class="label">Atendido por</div><div class="value">${safeText(atendidoPor) || 'N/D'}</div></div>
            </div>
            <div class="totals">
                <div class="total-line"><span>Precio del servicio</span><span>$${safeText(Number(precioServicio).toFixed(2))}</span></div>
                <div class="total-line"><strong>Monto pagado</strong><strong>$${safeText(montoPagado.toFixed(2))}</strong></div>
            </div>
            ${safeText(nota) ? `<div style="margin-top:12px;"><span style="font-size:12px;color:#6b7280;">Nota:</span><div style="font-size:14px;">${safeText(nota)}</div></div>` : ''}
        </div>
        <div class="footer">Generado desde el módulo de Ingresos. Usa Imprimir para guardar en PDF.</div>
    </div>
    <script>
        window.onload = function () {
            window.print();
        };
    <\/script>
</body>
</html>`;

            ticketWindow.document.open();
            ticketWindow.document.write(html);
            ticketWindow.document.close();

            closeTicketModal();
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
