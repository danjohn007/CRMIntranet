<?php
$title = 'Ingresos vs Egresos';
ob_start();

$dailyLabels = [];
$dailyIncome = [];
$dailyExpenses = [];
foreach ($dailyEvolution ?? [] as $row) {
    $movementLabel = $row['movement_label'] ?? '';
    if ($movementLabel !== '') {
        $dailyLabels[] = $movementLabel;
    } else {
        $movementDate = $row['movement_date'] ?? '';
        $dateObject = $movementDate !== '' ? DateTime::createFromFormat('Y-m-d', $movementDate) : false;
        $dailyLabels[] = $dateObject !== false ? $dateObject->format('d/m') : $movementDate;
    }
    $dailyIncome[] = (float) ($row['total_income'] ?? 0);
    $dailyExpenses[] = (float) ($row['total_expenses'] ?? 0);
}

$topExpenseLabels = [];
$topExpenseTotals = [];
foreach ($topExpenses ?? [] as $row) {
    $topExpenseLabels[] = $row['concept'] ?? 'Sin concepto';
    $topExpenseTotals[] = (float) ($row['total'] ?? 0);
}

$sourceLabels = ['Ingresos extra', 'Ingresos solicitudes', 'Egresos'];
$sourceTotals = [
    (float) ($sourceBreakdown['extra_income'] ?? 0),
    (float) ($sourceBreakdown['requests_income'] ?? 0),
    (float) ($sourceBreakdown['expenses'] ?? 0)
];
?>

<div class="mb-4 md:mb-6">
    <h2 class="text-2xl md:text-3xl font-bold text-gray-800">Ingresos vs Egresos</h2>
    <p class="text-sm md:text-base text-gray-600">Control de ingresos, registro de egresos y comparación financiera</p>
</div>

<div class="bg-white rounded-lg shadow p-3 mb-4 md:mb-6">
    <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-6">
        <div class="flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/ingresos-egresos?period=diario<?= isset($selectedUserId) && $selectedUserId > 0 ? '&user_id=' . $selectedUserId : '' ?>" class="px-4 py-2 rounded-lg text-sm font-semibold <?= ($activePeriod ?? 'diario') === 'diario' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Diario</a>
            <a href="<?= BASE_URL ?>/ingresos-egresos?period=semanal<?= isset($selectedUserId) && $selectedUserId > 0 ? '&user_id=' . $selectedUserId : '' ?>" class="px-4 py-2 rounded-lg text-sm font-semibold <?= ($activePeriod ?? 'diario') === 'semanal' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Semanal</a>
            <a href="<?= BASE_URL ?>/ingresos-egresos?period=mensual<?= isset($selectedUserId) && $selectedUserId > 0 ? '&user_id=' . $selectedUserId : '' ?>" class="px-4 py-2 rounded-lg text-sm font-semibold <?= ($activePeriod ?? 'diario') === 'mensual' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">Por mes</a>
        </div>
        
        <div class="md:ml-auto">
            <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1">Filtrar por usuario:</label>
            <select id="userFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-transparent" onchange="updateUserFilter()">
                <option value="">-- Todos los usuarios --</option>
                <?php foreach ($userList ?? [] as $user): ?>
                <option value="<?= (int) $user['id'] ?>" <?= (isset($selectedUserId) && $selectedUserId == $user['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($user['full_name'] ?? '') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-4 md:p-6 mb-4 md:mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h3 class="text-lg md:text-xl font-bold text-gray-800">Registrar egreso</h3>
            <p class="text-sm text-gray-500">Captura concepto, cantidad y nota del egreso.</p>
        </div>
        <button type="button" id="toggleExpenseForm" class="inline-flex items-center justify-center px-4 py-2 btn-primary text-white rounded-lg hover:opacity-90">
            <i class="fas fa-plus mr-2"></i>Nuevo egreso
        </button>
    </div>

    <form id="expenseForm" action="<?= BASE_URL ?>/ingresos-egresos/nuevo-egreso" method="POST" class="hidden mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Concepto *</label>
            <input type="text" name="concept" required maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad *</label>
            <input type="number" name="amount" required min="0.01" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
            <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
        </div>
        <div class="md:col-span-1 flex items-end">
            <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                Guardar egreso
            </button>
        </div>
        <div class="md:col-span-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nota</label>
            <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ejemplo: vendrá cada semana"></textarea>
        </div>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <p class="text-gray-600 text-sm">Total Ingresos <?= htmlspecialchars($periodLabel ?? '') ?></p>
        <p class="text-2xl md:text-3xl font-bold text-green-600">$<?= number_format((float) ($summary['total_income'] ?? 0), 2) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <p class="text-gray-600 text-sm">Total Ingresos Extra <?= htmlspecialchars($periodLabel ?? '') ?></p>
        <p class="text-2xl md:text-3xl font-bold text-emerald-600">$<?= number_format((float) ($summary['total_extra_income'] ?? 0), 2) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <p class="text-gray-600 text-sm">Total Ingresos Solicitudes <?= htmlspecialchars($periodLabel ?? '') ?></p>
        <p class="text-2xl md:text-3xl font-bold text-blue-600">$<?= number_format((float) ($summary['total_income_requests'] ?? 0), 2) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <p class="text-gray-600 text-sm">Total Egresos <?= htmlspecialchars($periodLabel ?? '') ?></p>
        <p class="text-2xl md:text-3xl font-bold text-red-600">$<?= number_format((float) ($summary['total_expenses'] ?? 0), 2) ?></p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <p class="text-gray-600 text-sm">Balance <?= htmlspecialchars($periodLabel ?? '') ?></p>
        <p class="text-2xl md:text-3xl font-bold <?= ((float) ($summary['balance'] ?? 0)) >= 0 ? 'text-primary' : 'text-red-600' ?>">
            $<?= number_format((float) ($summary['balance'] ?? 0), 2) ?>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Evolución <?= htmlspecialchars($periodLabel ?? '') ?> (Ingresos vs Egresos)</h3>
        <div class="h-72">
            <canvas id="dailyEvolutionChart"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Top 5 Egresos por Concepto</h3>
        <div class="h-72">
            <canvas id="topExpensesChart"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-6">
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Composición financiera <?= htmlspecialchars($periodLabel ?? '') ?></h3>
        <div class="h-72">
            <canvas id="sourceBreakdownChart"></canvas>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4 md:p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Últimos egresos</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nota</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($recentExpenses ?? [] as $expense): ?>
                    <tr>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= htmlspecialchars($expense['expense_date']) ?></td>
                        <td class="px-3 py-2 text-sm font-medium text-gray-800"><?= htmlspecialchars($expense['concept']) ?></td>
                        <td class="px-3 py-2 text-sm font-semibold text-red-600">$<?= number_format((float) $expense['amount'], 2) ?></td>
                        <td class="px-3 py-2 text-sm text-gray-600"><?= htmlspecialchars($expense['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentExpenses)): ?>
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-gray-500">Sin egresos registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleExpenseForm');
    const expenseForm = document.getElementById('expenseForm');
    if (toggleBtn && expenseForm) {
        toggleBtn.addEventListener('click', function () {
            expenseForm.classList.toggle('hidden');
        });
    }

    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#3b82f6';
    const incomeColor = '#10b981';
    const expenseColor = '#ef4444';

    const dailyCtx = document.getElementById('dailyEvolutionChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dailyLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [
                    {
                        label: 'Ingresos',
                        data: <?= json_encode($dailyIncome, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        borderColor: incomeColor,
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        tension: 0.3
                    },
                    {
                        label: 'Egresos',
                        data: <?= json_encode($dailyExpenses, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        borderColor: expenseColor,
                        backgroundColor: 'rgba(239, 68, 68, 0.12)',
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const topCtx = document.getElementById('topExpensesChart');
    if (topCtx) {
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($topExpenseLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [{
                    label: 'Egresos',
                    data: <?= json_encode($topExpenseTotals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    backgroundColor: [expenseColor, '#f87171', '#fca5a5', '#7c3aed', primaryColor]
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    const sourceCtx = document.getElementById('sourceBreakdownChart');
    if (sourceCtx) {
        new Chart(sourceCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($sourceLabels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [{
                    data: <?= json_encode($sourceTotals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    backgroundColor: [incomeColor, '#2563eb', expenseColor]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function updateUserFilter() {
        const userFilter = document.getElementById('userFilter');
        const selectedUserId = userFilter.value;
        const currentPeriod = '<?= htmlspecialchars($activePeriod ?? 'diario') ?>';
        
        if (selectedUserId) {
            window.location.href = '<?= BASE_URL ?>/ingresos-egresos?period=' + currentPeriod + '&user_id=' + selectedUserId;
        } else {
            window.location.href = '<?= BASE_URL ?>/ingresos-egresos?period=' + currentPeriod;
        }
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/views/layouts/main.php';
?>
