<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class IncomeExpenseController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        try {
            $selectedPeriod = $this->resolvePeriodKey($_GET['period'] ?? 'daily');
            $periodRange = $this->buildPeriodRange($selectedPeriod);
            $advisorIncomeEnabled = $this->advisorIncomeModuleEnabled();
            $summary = $this->getSummaryByPeriod($advisorIncomeEnabled, $periodRange);
            $periodEvolution = $this->getEvolutionByPeriod($advisorIncomeEnabled, $periodRange);
            $topExpenses = $this->getTopExpensesByPeriod($periodRange);
            $recentExpenses = $this->getRecentExpenses();

            $this->view('income-expense/index', [
                'summary' => $summary,
                'periodEvolution' => $periodEvolution,
                'topExpenses' => $topExpenses,
                'recentExpenses' => $recentExpenses,
                'selectedPeriod' => $selectedPeriod,
                'periodRange' => $periodRange
            ]);
        } catch (PDOException $e) {
            error_log('Error en ingresos vs egresos: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el módulo de ingresos vs egresos';
            $this->redirect('/dashboard');
        }
    }

    public function storeExpense()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/ingresos-egresos');
        }

        $concept = trim($_POST['concept'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $expenseDate = trim($_POST['expense_date'] ?? date('Y-m-d'));

        if ($concept === '' || $amount <= 0) {
            $_SESSION['error'] = 'Concepto y cantidad son obligatorios';
            $this->redirect('/ingresos-egresos');
        }

        $parsedDate = DateTime::createFromFormat('Y-m-d', $expenseDate);
        $isValidDate = $parsedDate !== false && $parsedDate->format('Y-m-d') === $expenseDate;

        if (!$isValidDate) {
            $_SESSION['error'] = 'La fecha del egreso no es válida';
            $this->redirect('/ingresos-egresos');
        }

        if ($expenseDate > date('Y-m-d')) {
            $_SESSION['error'] = 'La fecha del egreso no puede ser futura';
            $this->redirect('/ingresos-egresos');
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO financial_expenses (concept, amount, notes, expense_date, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$concept, $amount, $notes, $expenseDate, $_SESSION['user_id']]);

            $_SESSION['success'] = 'Egreso registrado correctamente';
            $this->redirect('/ingresos-egresos');
        } catch (PDOException $e) {
            error_log('Error al registrar egreso: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo registrar el egreso';
            $this->redirect('/ingresos-egresos');
        }
    }

    private function advisorIncomeModuleEnabled()
    {
        try {
            $tablesStmt = $this->db->query("
                SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('advisor_income_catalog', 'advisor_income_records')
            ");

            return ((int) ($tablesStmt->fetch()['total'] ?? 0)) === 2;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function resolvePeriodKey($period)
    {
        $allowedPeriods = ['daily', 'weekly', 'monthly'];
        return in_array($period, $allowedPeriods, true) ? $period : 'daily';
    }

    private function buildPeriodRange($period)
    {
        $timezone = new DateTimeZone('America/Mexico_City');
        $today = new DateTimeImmutable('today', $timezone);

        switch ($period) {
            case 'weekly':
                $startDate = $today->modify('monday this week');
                $endDate = $startDate->modify('+6 days');
                return [
                    'key' => 'weekly',
                    'label' => 'Semanal',
                    'summary_suffix' => 'de la semana',
                    'chart_suffix' => 'de la semana',
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ];

            case 'monthly':
                $startDate = $today->modify('first day of this month');
                $endDate = $today->modify('last day of this month');
                return [
                    'key' => 'monthly',
                    'label' => 'Por mes',
                    'summary_suffix' => 'del mes',
                    'chart_suffix' => 'del mes',
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ];

            case 'daily':
            default:
                return [
                    'key' => 'daily',
                    'label' => 'Diario',
                    'summary_suffix' => 'del día',
                    'chart_suffix' => 'del día',
                    'start_date' => $today->format('Y-m-d'),
                    'end_date' => $today->format('Y-m-d')
                ];
        }
    }

    private function getSummaryByPeriod($advisorIncomeEnabled, array $periodRange)
    {
        $summary = [
            'total_income_requests' => 0,
            'total_extra_income' => 0,
            'total_expenses' => 0,
            'total_income' => 0,
            'balance' => 0
        ];

        $paymentsStmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM payments
            WHERE payment_date BETWEEN ? AND ?
        ");
        $paymentsStmt->execute([$periodRange['start_date'], $periodRange['end_date']]);
        $summary['total_income_requests'] = (float) ($paymentsStmt->fetch()['total'] ?? 0);

        if ($advisorIncomeEnabled) {
            $extraIncomeStmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount), 0) AS total
                FROM advisor_income_records
                WHERE DATE(income_datetime) BETWEEN ? AND ?
            ");
            $extraIncomeStmt->execute([$periodRange['start_date'], $periodRange['end_date']]);
            $summary['total_extra_income'] = (float) ($extraIncomeStmt->fetch()['total'] ?? 0);
        }

        $expensesStmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) AS total
            FROM financial_expenses
            WHERE expense_date BETWEEN ? AND ?
        ");
        $expensesStmt->execute([$periodRange['start_date'], $periodRange['end_date']]);
        $summary['total_expenses'] = (float) ($expensesStmt->fetch()['total'] ?? 0);

        $summary['total_income'] = $summary['total_income_requests'] + $summary['total_extra_income'];
        $summary['balance'] = $summary['total_income'] - $summary['total_expenses'];

        return $summary;
    }

    private function getEvolutionByPeriod($advisorIncomeEnabled, array $periodRange)
    {
        if ($advisorIncomeEnabled) {
            $stmt = $this->db->prepare("
                SELECT
                    movement_date,
                    SUM(income_requests) AS total_income_requests,
                    SUM(extra_income) AS total_extra_income,
                    SUM(expense_amount) AS total_expenses
                FROM (
                    SELECT payment_date AS movement_date, amount AS income_requests, 0 AS extra_income, 0 AS expense_amount
                    FROM payments
                    WHERE payment_date BETWEEN ? AND ?

                    UNION ALL

                    SELECT DATE(income_datetime) AS movement_date, 0 AS income_requests, amount AS extra_income, 0 AS expense_amount
                    FROM advisor_income_records
                    WHERE DATE(income_datetime) BETWEEN ? AND ?

                    UNION ALL

                    SELECT expense_date AS movement_date, 0 AS income_requests, 0 AS extra_income, amount AS expense_amount
                    FROM financial_expenses
                    WHERE expense_date BETWEEN ? AND ?
                ) AS movement_data
                GROUP BY movement_date
                ORDER BY movement_date ASC
            ");
            $stmt->execute([
                $periodRange['start_date'],
                $periodRange['end_date'],
                $periodRange['start_date'],
                $periodRange['end_date'],
                $periodRange['start_date'],
                $periodRange['end_date']
            ]);
        } else {
            $stmt = $this->db->prepare("
                SELECT
                    movement_date,
                    SUM(income_requests) AS total_income_requests,
                    0 AS total_extra_income,
                    SUM(expense_amount) AS total_expenses
                FROM (
                    SELECT payment_date AS movement_date, amount AS income_requests, 0 AS expense_amount
                    FROM payments
                    WHERE payment_date BETWEEN ? AND ?

                    UNION ALL

                    SELECT expense_date AS movement_date, 0 AS income_requests, amount AS expense_amount
                    FROM financial_expenses
                    WHERE expense_date BETWEEN ? AND ?
                ) AS movement_data
                GROUP BY movement_date
                ORDER BY movement_date ASC
            ");
            $stmt->execute([
                $periodRange['start_date'],
                $periodRange['end_date'],
                $periodRange['start_date'],
                $periodRange['end_date']
            ]);
        }

        return $stmt->fetchAll();
    }

    private function getTopExpensesByPeriod(array $periodRange)
    {
        $stmt = $this->db->prepare("
            SELECT concept, SUM(amount) AS total
            FROM financial_expenses
            WHERE expense_date BETWEEN ? AND ?
            GROUP BY concept
            ORDER BY total DESC, concept ASC
            LIMIT 5
        ");
        $stmt->execute([$periodRange['start_date'], $periodRange['end_date']]);

        return $stmt->fetchAll();
    }

    private function getRecentExpenses()
    {
        $stmt = $this->db->query("
            SELECT fe.*, u.full_name AS created_by_name
            FROM financial_expenses fe
            LEFT JOIN users u ON u.id = fe.created_by
            ORDER BY fe.expense_date DESC, fe.created_at DESC
            LIMIT 10
        ");

        return $stmt->fetchAll();
    }
}
