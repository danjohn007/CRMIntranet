<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AuthController extends BaseController {
    private const GEO_DENIED_MESSAGE = 'No se encuentra en la ubicacion permitida. Active su ubicacion e intente nuevamente.';

    public function login() {
        if ($this->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth/login');
    }

    public function authenticate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');
        $ipAddress = $this->getClientIp();

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Por favor, ingrese usuario y contrasena';
            $this->redirect('/login');
        }

        if ($this->isLoginLocked($username, $ipAddress)) {
            logAudit('login_blocked', 'autenticacion', "Login bloqueado temporalmente para: $username");
            $_SESSION['error'] = 'Demasiados intentos fallidos. Intente nuevamente en ' . max(1, (int) getConfig('login_lockout_minutes', 15)) . ' minutos.';
            $this->redirect('/login');
        }

        if (empty($captcha) || !isset($_SESSION['captcha_answer'])) {
            $this->registerFailedLogin($username, $ipAddress);
            $_SESSION['error'] = $this->buildFailedLoginMessage('Por favor, complete la verificacion humana.', $username, $ipAddress);
            unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
            $this->redirect('/login');
        }

        if ((int) $captcha !== (int) $_SESSION['captcha_answer']) {
            $this->registerFailedLogin($username, $ipAddress);
            logAudit('login_failed', 'autenticacion', "Verificacion humana incorrecta para: $username");
            $_SESSION['error'] = $this->buildFailedLoginMessage('Verificacion humana incorrecta. Por favor, intente nuevamente.', $username, $ipAddress);
            unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);
            $this->redirect('/login');
        }

        unset($_SESSION['captcha_answer'], $_SESSION['captcha_num1'], $_SESSION['captcha_num2']);

        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, full_name, role, is_active
                FROM users
                WHERE (username = :username OR email = :email) AND is_active = 1
            ");
            $stmt->execute(['username' => $username, 'email' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (!$this->validateGeoLogin($user)) {
                    $this->registerFailedLogin($username, $ipAddress);
                    logAudit('login_geo_denied', 'autenticacion', "Login denegado por ubicacion para asesor: {$user['username']}");
                    $_SESSION['error'] = self::GEO_DENIED_MESSAGE;
                    $this->redirect('/login');
                }

                $this->clearFailedLogins($username, $ipAddress);
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                $stmt = $this->db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);

                logAudit('login', 'autenticacion', "Usuario {$user['full_name']} inicio sesion");

                $this->redirect('/dashboard');
            }

            $this->registerFailedLogin($username, $ipAddress);
            logAudit('login_failed', 'autenticacion', "Intento de inicio de sesion fallido para: $username");

            $_SESSION['error'] = $this->buildFailedLoginMessage('Usuario o contrasena incorrectos.', $username, $ipAddress);
            $this->redirect('/login');
        } catch (PDOException $e) {
            error_log("Error en autenticacion: " . $e->getMessage());
            $_SESSION['error'] = 'Error al iniciar sesion. Por favor, intente nuevamente.';
            $this->redirect('/login');
        }
    }

    public function logout() {
        if (isset($_SESSION['user_name'])) {
            logAudit('logout', 'autenticacion', "Usuario {$_SESSION['user_name']} cerro sesion");
        }

        session_destroy();
        $this->redirect('/login');
    }

    private function getClientIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function normalizeLoginIdentifier(string $username): string {
        return strtolower(trim($username));
    }

    private function isLoginLocked(string $username, string $ipAddress): bool {
        $maxAttempts = max(1, (int) getConfig('login_max_attempts', 5));
        $lockoutMinutes = max(1, (int) getConfig('login_lockout_minutes', 15));

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM login_attempts
                WHERE username = ?
                  AND ip_address = ?
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$lockoutMinutes} MINUTE)
            ");
            $stmt->execute([$this->normalizeLoginIdentifier($username), $ipAddress]);
            return (int) $stmt->fetchColumn() >= $maxAttempts;
        } catch (PDOException $e) {
            error_log("Error checking login attempts: " . $e->getMessage());
            return false;
        }
    }

    private function getRemainingLoginAttempts(string $username, string $ipAddress): int {
        $maxAttempts = max(1, (int) getConfig('login_max_attempts', 5));
        $lockoutMinutes = max(1, (int) getConfig('login_lockout_minutes', 15));

        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM login_attempts
                WHERE username = ?
                  AND ip_address = ?
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL {$lockoutMinutes} MINUTE)
            ");
            $stmt->execute([$this->normalizeLoginIdentifier($username), $ipAddress]);
            return max(0, $maxAttempts - (int) $stmt->fetchColumn());
        } catch (PDOException $e) {
            error_log("Error calculating remaining login attempts: " . $e->getMessage());
            return $maxAttempts;
        }
    }

    private function buildFailedLoginMessage(string $baseMessage, string $username, string $ipAddress): string {
        $remainingAttempts = $this->getRemainingLoginAttempts($username, $ipAddress);
        $lockoutMinutes = max(1, (int) getConfig('login_lockout_minutes', 15));

        if ($remainingAttempts <= 0) {
            return $baseMessage . ' Demasiados intentos fallidos. Intente nuevamente en ' . $lockoutMinutes . ' minutos.';
        }

        return $baseMessage . ' Intentos restantes: ' . $remainingAttempts . '.';
    }

    private function registerFailedLogin(string $username, string $ipAddress): void {
        $locationData = $this->getLoginLocationAttemptData();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (
                    username,
                    ip_address,
                    latitude,
                    longitude,
                    allowed_latitude,
                    allowed_longitude,
                    distance_meters,
                    location_status,
                    user_agent
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $this->normalizeLoginIdentifier($username),
                $ipAddress,
                $locationData['latitude'],
                $locationData['longitude'],
                $locationData['allowed_latitude'],
                $locationData['allowed_longitude'],
                $locationData['distance_meters'],
                $locationData['location_status'],
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error registering failed login: " . $e->getMessage());
            $this->registerFailedLoginLegacy($username, $ipAddress);
        }
    }

    private function registerFailedLoginLegacy(string $username, string $ipAddress): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (username, ip_address, user_agent)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $this->normalizeLoginIdentifier($username),
                $ipAddress,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error registering failed login legacy fallback: " . $e->getMessage());
        }
    }

    private function clearFailedLogins(string $username, string $ipAddress): void {
        try {
            $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE username = ? AND ip_address = ?");
            $stmt->execute([$this->normalizeLoginIdentifier($username), $ipAddress]);
        } catch (PDOException $e) {
            error_log("Error clearing failed logins: " . $e->getMessage());
        }
    }

    private function getLoginLocationAttemptData(): array {
        $data = [
            'latitude' => null,
            'longitude' => null,
            'allowed_latitude' => null,
            'allowed_longitude' => null,
            'distance_meters' => null,
            'location_status' => getConfig('geo_login_enabled', '0') === '1' ? 'missing' : 'not_required',
        ];

        $userLat = $_POST['latitude'] ?? null;
        $userLng = $_POST['longitude'] ?? null;
        $allowedLat = getConfig('geo_login_latitude', '');
        $allowedLng = getConfig('geo_login_longitude', '');
        $radiusMeters = (float) getConfig('geo_login_radius_meters', 100);

        if ($this->isValidLatitude($userLat) && $this->isValidLongitude($userLng)) {
            $data['latitude'] = round((float) $userLat, 8);
            $data['longitude'] = round((float) $userLng, 8);
        } elseif ($userLat !== null || $userLng !== null) {
            $data['location_status'] = 'invalid';
        }

        if ($this->isValidLatitude($allowedLat) && $this->isValidLongitude($allowedLng)) {
            $data['allowed_latitude'] = round((float) $allowedLat, 8);
            $data['allowed_longitude'] = round((float) $allowedLng, 8);
        }

        if ($data['latitude'] !== null && $data['longitude'] !== null && $data['allowed_latitude'] !== null && $data['allowed_longitude'] !== null) {
            $distance = $this->distanceInMeters(
                (float) $data['allowed_latitude'],
                (float) $data['allowed_longitude'],
                (float) $data['latitude'],
                (float) $data['longitude']
            );

            $data['distance_meters'] = round($distance, 2);
            $data['location_status'] = $distance <= $radiusMeters ? 'inside_range' : 'outside_range';
        }

        return $data;
    }

    private function validateGeoLogin(array $user): bool {
        if (($user['role'] ?? '') !== ROLE_ASESOR) {
            return true;
        }

        if (getConfig('geo_login_enabled', '0') !== '1') {
            return true;
        }

        $allowedLat = getConfig('geo_login_latitude', '');
        $allowedLng = getConfig('geo_login_longitude', '');
        $radiusMeters = (float) getConfig('geo_login_radius_meters', 100);
        $userLat = $_POST['latitude'] ?? null;
        $userLng = $_POST['longitude'] ?? null;

        if (!$this->isValidLatitude($allowedLat) || !$this->isValidLongitude($allowedLng) || $radiusMeters <= 0) {
            return false;
        }

        if (!$this->isValidLatitude($userLat) || !$this->isValidLongitude($userLng)) {
            return false;
        }

        return $this->distanceInMeters((float) $allowedLat, (float) $allowedLng, (float) $userLat, (float) $userLng) <= $radiusMeters;
    }

    private function isValidLatitude($value): bool {
        return is_numeric($value) && (float) $value >= -90 && (float) $value <= 90;
    }

    private function isValidLongitude($value): bool {
        return is_numeric($value) && (float) $value >= -180 && (float) $value <= 180;
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
