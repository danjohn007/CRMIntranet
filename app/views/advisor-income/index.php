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
            <input type="text" name="income_type" required maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ej. Venta de copias">
        </div>
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Monto *</label>
            <input type="number" name="amount" required min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ej. 10.00">
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
            <input type="datetime-local" name="income_datetime" required value="<?= date('Y-m-d\TH:i') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
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
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nota</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($recentIncomes ?? [] as $income): ?>
                    <tr>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($income['income_datetime'])) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-700"><?= htmlspecialchars($income['income_type']) ?></td>
                        <td class="px-3 py-2 text-sm font-semibold text-green-600">$<?= number_format((float) $income['amount'], 2) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= htmlspecialchars($income['note'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentIncomes)): ?>
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">Sin ingresos registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
