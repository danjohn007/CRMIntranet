<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class IncomeExpenseController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        try {
            $advisorIncomeEnabled = $this->isAdvisorIncomeEnabled();

            $requestedPeriod = trim((string) ($_GET['period'] ?? 'diario'));
            $allowedPeriods = ['diario', 'semanal', 'mensual'];
            $activePeriod = in_array($requestedPeriod, $allowedPeriods, true) ? $requestedPeriod : 'diario';

            $requestedUser = (int) ($_GET['user_id'] ?? 0);
            $userList = [];
            $selectedUser = null;

            try {
                $usersStmt = $this->db->query("
                    SELECT id, full_name FROM users WHERE active = 1 ORDER BY full_name ASC
                ");
                $userList = $usersStmt->fetchAll();

                if ($requestedUser > 0) {
                    $userCheckStmt = $this->db->prepare("
                        SELECT id, full_name FROM users WHERE id = ? AND active = 1
                    ");
                    $userCheckStmt->execute([$requestedUser]);
                    $selectedUser = $userCheckStmt->fetch() ?: null;
                }
            } catch (PDOException $e) {
                $userList = [];
                $selectedUser = null;
            }

            $periodFilter = $this->buildPeriodFilter($activePeriod);
            $userFilter = $selectedUser ? "AND created_by = {$requestedUser}" : "";

            if ($advisorIncomeEnabled) {
                $stmt = $this->db->query("\n                    SELECT
                        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE {$periodFilter['payments']} {$userFilter}) +
                        (SELECT COALESCE(SUM(amount), 0) FROM advisor_income_records WHERE {$periodFilter['advisor_incomes']} {$userFilter}) AS total_income,
                        (SELECT COALESCE(SUM(amount), 0) FROM advisor_income_records WHERE {$periodFilter['advisor_incomes']} {$userFilter}) AS total_extra_income,
                        (SELECT COALESCE(SUM(amount), 0) FROM financial_expenses WHERE {$periodFilter['expenses']} {$userFilter}) AS total_expenses
                ");
            } else {
                $stmt = $this->db->query("\n                    SELECT
                        (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE {$periodFilter['payments']} {$userFilter}) AS total_income,
                        0 AS total_extra_income,
                        (SELECT COALESCE(SUM(amount), 0) FROM financial_expenses WHERE {$periodFilter['expenses']} {$userFilter}) AS total_expenses
                ");
            }

            $summary = $stmt->fetch() ?: ['total_income' => 0, 'total_extra_income' => 0, 'total_expenses' => 0];
            $summary['total_income_requests'] = (float) ($summary['total_income'] ?? 0) - (float) ($summary['total_extra_income'] ?? 0);
            $summary['balance'] = (float) ($summary['total_income'] ?? 0) - (float) ($summary['total_expenses'] ?? 0);

            $evolutionSql = $this->buildEvolutionQuery($activePeriod, $advisorIncomeEnabled, $periodFilter, $userFilter);
            $stmt = $this->db->query($evolutionSql);
            $dailyEvolution = $stmt->fetchAll();

            $stmt = $this->db->query("\n                SELECT concept, SUM(amount) AS total
                FROM financial_expenses
                WHERE {$periodFilter['expenses']} {$userFilter}
                GROUP BY concept
                ORDER BY total DESC, concept ASC
                LIMIT 5
            ");
            $topExpenses = $stmt->fetchAll();

            $sourceBreakdown = [
                'extra_income' => (float) ($summary['total_extra_income'] ?? 0),
                'requests_income' => (float) ($summary['total_income_requests'] ?? 0),
                'expenses' => (float) ($summary['total_expenses'] ?? 0)
            ];

            $stmt = $this->db->query("\n                SELECT fe.*, u.full_name AS created_by_name
                FROM financial_expenses fe
                LEFT JOIN users u ON u.id = fe.created_by
                ORDER BY fe.expense_date DESC, fe.created_at DESC
                LIMIT 10
            ");
            $recentExpenses = $stmt->fetchAll();

            $this->view('income-expense/index', [
                'summary' => $summary,
                'activePeriod' => $activePeriod,
                'periodLabel' => $this->getPeriodLabel($activePeriod),
                'dailyEvolution' => $dailyEvolution,
                'topExpenses' => $topExpenses,
                'sourceBreakdown' => $sourceBreakdown,
                'recentExpenses' => $recentExpenses,
                'userList' => $userList,
                'selectedUser' => $selectedUser,
                'selectedUserId' => $requestedUser
            ]);
        } catch (PDOException $e) {
            error_log('Error en ingresos vs egresos: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el módulo de ingresos vs egresos';
            $this->redirect('/dashboard');
        }
    }

    private function isAdvisorIncomeEnabled(): bool
    {
        try {
            $tablesStmt = $this->db->query("\n                SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('advisor_income_catalog', 'advisor_income_records')
            ");
            return ((int) ($tablesStmt->fetch()['total'] ?? 0)) === 2;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function buildPeriodFilter(string $period): array
    {
        if ($period === 'semanal') {
            return [
                'payments' => "YEARWEEK(payment_date, 1) = YEARWEEK(CURDATE(), 1)",
                'advisor_incomes' => "YEARWEEK(DATE(income_datetime), 1) = YEARWEEK(CURDATE(), 1)",
                'expenses' => "YEARWEEK(expense_date, 1) = YEARWEEK(CURDATE(), 1)"
            ];
        }

        if ($period === 'mensual') {
            return [
                'payments' => "YEAR(payment_date) = YEAR(CURDATE()) AND MONTH(payment_date) = MONTH(CURDATE())",
                'advisor_incomes' => "YEAR(income_datetime) = YEAR(CURDATE()) AND MONTH(income_datetime) = MONTH(CURDATE())",
                'expenses' => "YEAR(expense_date) = YEAR(CURDATE()) AND MONTH(expense_date) = MONTH(CURDATE())"
            ];
        }

        return [
            'payments' => "payment_date = CURDATE()",
            'advisor_incomes' => "DATE(income_datetime) = CURDATE()",
            'expenses' => "expense_date = CURDATE()"
        ];
    }

    private function buildEvolutionQuery(string $period, bool $advisorIncomeEnabled, array $periodFilter, string $userFilter = ""): string
    {
        if ($period === 'diario') {
            if ($advisorIncomeEnabled) {
                return "
                    SELECT movement_label, SUM(income_amount) AS total_income, SUM(expense_amount) AS total_expenses
                    FROM (
                        SELECT 'Hoy' AS movement_label, amount AS income_amount, 0 AS expense_amount
                        FROM payments
                        WHERE {$periodFilter['payments']} {$userFilter}

                        UNION ALL

                        SELECT 'Hoy' AS movement_label, amount AS income_amount, 0 AS expense_amount
                        FROM advisor_income_records
                        WHERE {$periodFilter['advisor_incomes']} {$userFilter}

                        UNION ALL

                        SELECT 'Hoy' AS movement_label, 0 AS income_amount, amount AS expense_amount
                        FROM financial_expenses
                        WHERE {$periodFilter['expenses']} {$userFilter}
                    ) AS movement_data
                    GROUP BY movement_label
                    ORDER BY movement_label ASC
                ";
            }

            return "
                SELECT movement_label, SUM(income_amount) AS total_income, SUM(expense_amount) AS total_expenses
                FROM (
                    SELECT 'Hoy' AS movement_label, amount AS income_amount, 0 AS expense_amount
                    FROM payments
                    WHERE {$periodFilter['payments']} {$userFilter}

                    UNION ALL

                    SELECT 'Hoy' AS movement_label, 0 AS income_amount, amount AS expense_amount
                    FROM financial_expenses
                    WHERE {$periodFilter['expenses']} {$userFilter}
                ) AS movement_data
                GROUP BY movement_label
                ORDER BY movement_label ASC
            ";
        }

        if ($advisorIncomeEnabled) {
            return "
                SELECT movement_date, SUM(income_amount) AS total_income, SUM(expense_amount) AS total_expenses
                FROM (
                    SELECT payment_date AS movement_date, amount AS income_amount, 0 AS expense_amount
                    FROM payments
                    WHERE {$periodFilter['payments']} {$userFilter}

                    UNION ALL

                    SELECT DATE(income_datetime) AS movement_date, amount AS income_amount, 0 AS expense_amount
                    FROM advisor_income_records
                    WHERE {$periodFilter['advisor_incomes']} {$userFilter}

                    UNION ALL

                    SELECT expense_date AS movement_date, 0 AS income_amount, amount AS expense_amount
                    FROM financial_expenses
                    WHERE {$periodFilter['expenses']} {$userFilter}
                ) AS movement_data
                GROUP BY movement_date
                ORDER BY movement_date ASC
            ";
        }

        return "
            SELECT movement_date, SUM(income_amount) AS total_income, SUM(expense_amount) AS total_expenses
            FROM (
                SELECT payment_date AS movement_date, amount AS income_amount, 0 AS expense_amount
                FROM payments
                WHERE {$periodFilter['payments']} {$userFilter}

                UNION ALL

                SELECT expense_date AS movement_date, 0 AS income_amount, amount AS expense_amount
                FROM financial_expenses
                WHERE {$periodFilter['expenses']} {$userFilter}
            ) AS movement_data
            GROUP BY movement_date
            ORDER BY movement_date ASC
        ";
    }

    private function getPeriodLabel(string $period): string
    {
        if ($period === 'semanal') {
            return 'de la semana actual';
        }

        if ($period === 'mensual') {
            return 'del mes actual';
        }

        return 'del dia actual';
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
            $stmt = $this->db->prepare("\n                INSERT INTO financial_expenses (concept, amount, notes, expense_date, created_by)
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
}
