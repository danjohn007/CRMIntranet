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

            $recordsStmt = $this->db->prepare("
                SELECT ir.*, ic.income_type
                FROM advisor_income_records ir
                INNER JOIN advisor_income_catalog ic ON ic.id = ir.income_type_id
                WHERE ir.created_by = ?
                ORDER BY ir.income_datetime DESC, ir.created_at DESC
                LIMIT 20
            ");
            $recordsStmt->execute([$_SESSION['user_id']]);
            $recentIncomes = $recordsStmt->fetchAll();

            foreach ($recentIncomes as &$income) {
                $income['folio'] = !empty($income['folio'])
                    ? $income['folio']
                    : $this->buildIncomeFolio((int) ($income['id'] ?? 0), $income['income_datetime'] ?? null);
            }
            unset($income);

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

            $incomeId = (int) $this->db->lastInsertId();
            $folio = $this->buildIncomeFolio($incomeId, $incomeDatetime);

            try {
                $folioStmt = $this->db->prepare("
                    UPDATE advisor_income_records
                    SET folio = ?
                    WHERE id = ?
                ");
                $folioStmt->execute([$folio, $incomeId]);
            } catch (PDOException $folioException) {
                error_log('No se pudo guardar el folio del ingreso: ' . $folioException->getMessage());
            }

            $_SESSION['success'] = 'Ingreso registrado correctamente';
            $this->redirect('/ingresos');
        } catch (PDOException $e) {
            error_log('Error al registrar ingreso: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo registrar el ingreso';
            $this->redirect('/ingresos');
        }
    }

    public function generateTicket($id)
    {
        $this->requireRole([ROLE_ASESOR]);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/ingresos');
        }

        $incomeId = (int) $id;
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $serviceName = trim($_POST['service_name'] ?? '');
        $servicePrice = (float) ($_POST['service_price'] ?? 0);
        $paidAmount = (float) ($_POST['paid_amount'] ?? 0);

        if ($incomeId <= 0) {
            $_SESSION['error'] = 'El ingreso seleccionado no es válido';
            $this->redirect('/ingresos');
        }

        if ($paidAmount <= 0) {
            $_SESSION['error'] = 'El monto pagado es obligatorio';
            $this->redirect('/ingresos');
        }

        if ($customerEmail !== '' && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El correo del ticket no es válido';
            $this->redirect('/ingresos');
        }

        try {
            $income = $this->getIncomeRecordForUser($incomeId, (int) $_SESSION['user_id']);

            if (!$income) {
                $_SESSION['error'] = 'No se encontró el ingreso seleccionado';
                $this->redirect('/ingresos');
            }

            $serviceName = $serviceName !== '' ? $serviceName : ($income['income_type'] ?? '');
            $servicePrice = $servicePrice > 0 ? $servicePrice : (float) ($income['amount'] ?? 0);

            if ($serviceName === '' || $servicePrice <= 0) {
                $_SESSION['error'] = 'No se pudo generar el ticket del ingreso seleccionado';
                $this->redirect('/ingresos');
            }

            $income['folio'] = !empty($income['folio'])
                ? $income['folio']
                : $this->buildIncomeFolio((int) ($income['id'] ?? 0), $income['income_datetime'] ?? null);

            $ticketData = [
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => $customerEmail,
                'service_name' => $serviceName,
                'service_price' => $servicePrice,
                'paid_amount' => $paidAmount,
                'change_amount' => max($paidAmount - $servicePrice, 0),
                'generated_at' => date('Y-m-d H:i:s')
            ];

            $this->view('advisor-income/ticket', [
                'income' => $income,
                'ticketData' => $ticketData
            ]);
        } catch (PDOException $e) {
            error_log('Error al generar ticket de ingreso: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo generar el ticket';
            $this->redirect('/ingresos');
        }
    }

    private function getIncomeRecordForUser($incomeId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT ir.*, ic.income_type
            FROM advisor_income_records ir
            INNER JOIN advisor_income_catalog ic ON ic.id = ir.income_type_id
            WHERE ir.id = ? AND ir.created_by = ?
            LIMIT 1
        ");
        $stmt->execute([$incomeId, $userId]);

        return $stmt->fetch();
    }

    private function buildIncomeFolio($incomeId, $incomeDatetime = null)
    {
        $timestamp = $incomeDatetime ? strtotime($incomeDatetime) : false;
        $year = $timestamp !== false ? date('Y', $timestamp) : date('Y');

        return sprintf('ING-%s-%06d', $year, max(0, (int) $incomeId));
    }
}
