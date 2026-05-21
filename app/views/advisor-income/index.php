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
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($incomeCatalog ?? [] as $type): ?>
                    <tr>
                        <td class="px-3 py-2 text-sm text-gray-700"><?= htmlspecialchars($type['income_type']) ?></td>
                        <td class="px-3 py-2 text-sm font-semibold text-green-600">$<?= number_format((float) $type['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($incomeCatalog)): ?>
                    <tr>
                        <td colspan="2" class="px-3 py-6 text-center text-gray-500">No hay tipos de ingreso registrados.</td>
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
                        <td class="px-3 py-2 text-sm font-mono text-primary"><?= htmlspecialchars($income['folio'] ?? '') ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($income['income_datetime'])) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-700"><?= htmlspecialchars($income['income_type']) ?></td>
                        <td class="px-3 py-2 text-sm font-semibold text-green-600">$<?= number_format((float) $income['amount'], 2) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= htmlspecialchars($income['note'] ?? '') ?></td>
                        <td class="px-3 py-2 text-sm">
                            <button
                                type="button"
                                class="ticket-trigger inline-flex items-center px-3 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition"
                                data-ticket-action="<?= BASE_URL ?>/ingresos/ticket/<?= (int) $income['id'] ?>"
                                data-ticket-folio="<?= htmlspecialchars($income['folio'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                data-ticket-service="<?= htmlspecialchars($income['income_type'], ENT_QUOTES, 'UTF-8') ?>"
                                data-ticket-price="<?= htmlspecialchars(number_format((float) $income['amount'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <i class="fas fa-receipt mr-2"></i>Generar ticket
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentIncomes)): ?>
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">Sin ingresos registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="ticketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="min-h-screen flex items-center justify-center px-4 py-6">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Generar ticket</h3>
                    <p class="text-sm text-gray-500">Completa los datos que se mostrarán en el ticket.</p>
                </div>
                <button type="button" id="closeTicketModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form
                id="ticketForm"
                method="POST"
                target="_blank"
                class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4"
            >
                <div class="md:col-span-2 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3">
                    <p class="text-xs uppercase text-gray-500">Folio</p>
                    <p id="ticketFolioLabel" class="text-sm font-semibold text-primary"></p>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del cliente</label>
                    <input type="text" name="customer_name" maxlength="150" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Opcional">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="customer_phone" maxlength="30" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Opcional">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                    <input type="email" name="customer_email" maxlength="150" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Opcional">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Servicio contratado</label>
                    <input type="text" name="service_name" id="ticketServiceName" readonly class="w-full px-3 py-2 border border-gray-300 bg-gray-50 rounded-lg text-gray-700">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio del servicio</label>
                    <input type="number" name="service_price" id="ticketServicePrice" readonly min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 bg-gray-50 rounded-lg text-gray-700">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Monto pagado *</label>
                    <input type="number" name="paid_amount" id="ticketPaidAmount" required min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ejemplo: 10.00">
                </div>

                <div class="md:col-span-2 flex flex-col-reverse md:flex-row md:justify-end gap-3 pt-2">
                    <button type="button" id="cancelTicketModal" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-print mr-2"></i>Generar ticket
                    </button>
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
    const ticketModal = document.getElementById('ticketModal');
    const ticketForm = document.getElementById('ticketForm');
    const ticketFolioLabel = document.getElementById('ticketFolioLabel');
    const ticketServiceName = document.getElementById('ticketServiceName');
    const ticketServicePrice = document.getElementById('ticketServicePrice');
    const ticketPaidAmount = document.getElementById('ticketPaidAmount');
    const ticketTriggers = document.querySelectorAll('.ticket-trigger');
    const closeTicketModal = document.getElementById('closeTicketModal');
    const cancelTicketModal = document.getElementById('cancelTicketModal');

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

    const hideTicketModal = function () {
        if (!ticketModal || !ticketForm) {
            return;
        }

        ticketModal.classList.add('hidden');
        ticketForm.reset();
    };

    const showTicketModal = function (button) {
        if (!ticketModal || !ticketForm || !button) {
            return;
        }

        ticketForm.action = button.dataset.ticketAction || '';
        ticketFolioLabel.textContent = button.dataset.ticketFolio || '';
        ticketServiceName.value = button.dataset.ticketService || '';
        ticketServicePrice.value = button.dataset.ticketPrice || '';
        ticketPaidAmount.value = button.dataset.ticketPrice || '';
        ticketModal.classList.remove('hidden');
    };

    ticketTriggers.forEach(function (button) {
        button.addEventListener('click', function () {
            showTicketModal(button);
        });
    });

    if (closeTicketModal) {
        closeTicketModal.addEventListener('click', hideTicketModal);
    }

    if (cancelTicketModal) {
        cancelTicketModal.addEventListener('click', hideTicketModal);
    }

    if (ticketModal) {
        ticketModal.addEventListener('click', function (event) {
            if (event.target === ticketModal) {
                hideTicketModal();
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
