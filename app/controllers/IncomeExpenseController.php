<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class IncomeExpenseController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ADMIN, ROLE_GERENTE]);

        try {
            $stmt = $this->db->query("
                SELECT
                    (SELECT COALESCE(SUM(amount), 0) FROM payments) AS total_income,
                    (SELECT COALESCE(SUM(amount), 0) FROM financial_expenses) AS total_expenses
            ");
            $summary = $stmt->fetch() ?: ['total_income' => 0, 'total_expenses' => 0];
            $summary['balance'] = (float) ($summary['total_income'] ?? 0) - (float) ($summary['total_expenses'] ?? 0);

            $stmt = $this->db->query("
                SELECT movement_date, SUM(income_amount) AS total_income, SUM(expense_amount) AS total_expenses
                FROM (
                    SELECT payment_date AS movement_date, amount AS income_amount, 0 AS expense_amount
                    FROM payments
                    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)

                    UNION ALL

                    SELECT expense_date AS movement_date, 0 AS income_amount, amount AS expense_amount
                    FROM financial_expenses
                    WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                ) AS movement_data
                GROUP BY movement_date
                ORDER BY movement_date ASC
            ");
            $dailyEvolution = $stmt->fetchAll();

            $stmt = $this->db->query("
                SELECT concept, SUM(amount) AS total
                FROM financial_expenses
                GROUP BY concept
                ORDER BY total DESC, concept ASC
                LIMIT 5
            ");
            $topExpenses = $stmt->fetchAll();

            $stmt = $this->db->query("
                SELECT movement_month, SUM(income_amount) AS total_income, SUM(expense_amount) AS total_expenses
                FROM (
                    SELECT DATE_FORMAT(payment_date, '%Y-%m') AS movement_month, amount AS income_amount, 0 AS expense_amount
                    FROM payments
                    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)

                    UNION ALL

                    SELECT DATE_FORMAT(expense_date, '%Y-%m') AS movement_month, 0 AS income_amount, amount AS expense_amount
                    FROM financial_expenses
                    WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
                ) AS monthly_data
                GROUP BY movement_month
                ORDER BY movement_month ASC
            ");
            $monthlyComparison = $stmt->fetchAll();

            $stmt = $this->db->query("
                SELECT fe.*, u.full_name AS created_by_name
                FROM financial_expenses fe
                LEFT JOIN users u ON u.id = fe.created_by
                ORDER BY fe.expense_date DESC, fe.created_at DESC
                LIMIT 10
            ");
            $recentExpenses = $stmt->fetchAll();

            $this->view('income-expense/index', [
                'summary' => $summary,
                'dailyEvolution' => $dailyEvolution,
                'topExpenses' => $topExpenses,
                'monthlyComparison' => $monthlyComparison,
                'recentExpenses' => $recentExpenses
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

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate)) {
            $expenseDate = date('Y-m-d');
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
}
