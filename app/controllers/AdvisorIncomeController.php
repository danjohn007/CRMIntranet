<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AdvisorIncomeController extends BaseController
{
    public function index()
    {
        $this->requireRole([ROLE_ASESOR]);

        try {
            $catalogStmt = $this->db->query("
                SELECT ic.*, u.full_name AS created_by_name
                FROM advisor_income_catalog ic
                LEFT JOIN users u ON u.id = ic.created_by
                WHERE ic.is_active = 1
                ORDER BY ic.income_type ASC
            ");
            $incomeCatalog = $catalogStmt->fetchAll();

                $folioColumnExists = false;
                try {
                    $folioColumnStmt = $this->db->query("
                        SELECT COUNT(*) AS total
                        FROM information_schema.columns
                        WHERE table_schema = DATABASE()
                          AND table_name = 'advisor_income_records'
                          AND column_name = 'folio'
                    ");
                    $folioColumnExists = ((int) ($folioColumnStmt->fetch()['total'] ?? 0)) > 0;
                } catch (PDOException $e) {
                    $folioColumnExists = false;
                }

                $folioSelect = $folioColumnExists
                    ? "COALESCE(ir.folio, CONCAT('ING-', DATE_FORMAT(ir.created_at, '%Y%m%d'), '-', LPAD(ir.id, 6, '0')))"
                    : "CONCAT('ING-', DATE_FORMAT(ir.created_at, '%Y%m%d'), '-', LPAD(ir.id, 6, '0'))";

                $recordsStmt = $this->db->prepare("
                    SELECT ir.*, ic.income_type,
                           u.full_name AS attended_by_name,
                           {$folioSelect} AS generated_folio
                    FROM advisor_income_records ir
                    INNER JOIN advisor_income_catalog ic ON ic.id = ir.income_type_id
                    LEFT JOIN users u ON u.id = ir.created_by
                    WHERE ir.created_by = ?
                    ORDER BY ir.income_datetime DESC, ir.created_at DESC
                    LIMIT 20
                ");
            $recordsStmt->execute([$_SESSION['user_id']]);
            $recentIncomes = $recordsStmt->fetchAll();

            $this->view('advisor-income/index', [
                'incomeCatalog' => $incomeCatalog,
                'recentIncomes' => $recentIncomes
            ]);
        } catch (PDOException $e) {
            error_log('Error al cargar módulo de ingresos: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo cargar el módulo de ingresos. Verifica la migración de base de datos.';
            $this->redirect('/dashboard');
        }
    }

    public function storeCatalog()
    {
        $this->requireRole([ROLE_ASESOR]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/ingresos');
        }

        $incomeType = trim($_POST['income_type'] ?? '');
        $amount = (float) ($_POST['amount'] ?? 0);

        if ($incomeType === '' || $amount <= 0) {
            $_SESSION['error'] = 'Tipo de ingreso y monto son obligatorios';
            $this->redirect('/ingresos');
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO advisor_income_catalog (income_type, amount, created_by)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$incomeType, $amount, $_SESSION['user_id']]);

            $_SESSION['success'] = 'Tipo de ingreso registrado correctamente';
            $this->redirect('/ingresos');
        } catch (PDOException $e) {
            error_log('Error al registrar tipo de ingreso: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo registrar el tipo de ingreso';
            $this->redirect('/ingresos');
        }
    }

    public function storeIncome()
    {
        $this->requireRole([ROLE_ASESOR]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/ingresos');
        }

        $incomeTypeId = (int) ($_POST['income_type_id'] ?? 0);
        $incomeDatetimeRaw = trim($_POST['income_datetime'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if ($incomeTypeId <= 0 || $incomeDatetimeRaw === '') {
            $_SESSION['error'] = 'Tipo de ingreso y fecha/hora son obligatorios';
            $this->redirect('/ingresos');
        }

        $timezone = new DateTimeZone('America/Mexico_City');
        $parsedDatetime = DateTime::createFromFormat('Y-m-d\TH:i', $incomeDatetimeRaw, $timezone);
        $isValidDatetime = $parsedDatetime !== false && $parsedDatetime->format('Y-m-d\TH:i') === $incomeDatetimeRaw;

        if (!$isValidDatetime) {
            $_SESSION['error'] = 'La fecha y hora del ingreso no es válida';
            $this->redirect('/ingresos');
        }

        if ($parsedDatetime > new DateTime('now', $timezone)) {
            $_SESSION['error'] = 'La fecha y hora del ingreso no puede ser futura';
            $this->redirect('/ingresos');
        }

        try {
            $catalogStmt = $this->db->prepare("
                SELECT id, amount
                FROM advisor_income_catalog
                WHERE id = ? AND is_active = 1
                LIMIT 1
            ");
            $catalogStmt->execute([$incomeTypeId]);
            $incomeType = $catalogStmt->fetch();

            if (!$incomeType) {
                $_SESSION['error'] = 'El tipo de ingreso seleccionado no existe';
                $this->redirect('/ingresos');
            }

            $incomeDatetime = $parsedDatetime->format('Y-m-d H:i:s');

            $stmt = $this->db->prepare("
                INSERT INTO advisor_income_records (income_type_id, amount, income_datetime, note, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $incomeTypeId,
                (float) $incomeType['amount'],
                $incomeDatetime,
                $note !== '' ? $note : null,
                $_SESSION['user_id']
            ]);

            $recordId = (int) $this->db->lastInsertId();
            $folio = 'ING-' . date('Ymd') . '-' . str_pad((string) $recordId, 6, '0', STR_PAD_LEFT);

            try {
                $folioStmt = $this->db->prepare("UPDATE advisor_income_records SET folio = ? WHERE id = ?");
                $folioStmt->execute([$folio, $recordId]);
            } catch (PDOException $folioException) {
                // Backward compatibility for databases not yet migrated with folio column.
                if (stripos($folioException->getMessage(), 'folio') === false) {
                    throw $folioException;
                }
            }

            $_SESSION['success'] = 'Ingreso registrado correctamente';
            $this->redirect('/ingresos');
        } catch (PDOException $e) {
            error_log('Error al registrar ingreso: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo registrar el ingreso';
            $this->redirect('/ingresos');
        }
    }
}
