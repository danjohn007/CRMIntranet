<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CRM Visas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-700 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <?php 
            $siteLogo = getSiteLogo();
            if ($siteLogo): ?>
                <img src="<?= BASE_URL . htmlspecialchars($siteLogo) ?>" alt="Logo" class="h-24 mx-auto mb-4 object-contain">
            <?php else: ?>
                <i class="fas fa-passport text-6xl text-blue-600 mb-4"></i>
            <?php endif; ?>
            <h1 class="text-3xl font-bold text-gray-800"><?= htmlspecialchars(getSiteName()) ?></h1>
            <p class="text-gray-600 mt-2">Inicie sesión para continuar</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
            <p><?= htmlspecialchars($_SESSION['error']) ?></p>
        </div>
        <?php unset($_SESSION['error']); endif; ?>
        
        <form action="<?= BASE_URL ?>/login" method="POST">
            <div class="mb-6">
                <label for="username" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-user mr-2"></i>Usuario o Email
                </label>
                <input type="text" id="username" name="username" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingrese su usuario">
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-lock mr-2"></i>Contraseña
                </label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingrese su contraseña">
            </div>
            
            <div class="mb-6">
                <label for="captcha" class="block text-gray-700 font-semibold mb-2">
                    <i class="fas fa-shield-alt mr-2"></i>Verificación Humana
                </label>
                <?php 
                // Generate captcha only if not already set for this session
                if (!isset($_SESSION['captcha_answer']) || !isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
                    $_SESSION['captcha_num1'] = rand(1, 10);
                    $_SESSION['captcha_num2'] = rand(1, 10);
                    $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
                }
                ?>
                <div class="bg-gray-100 p-3 rounded-lg mb-2 text-center">
                    <p class="text-lg font-semibold text-gray-700">¿Cuánto es <?= $_SESSION['captcha_num1'] ?> + <?= $_SESSION['captcha_num2'] ?>?</p>
                </div>
                <input type="number" id="captcha" name="captcha" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingrese el resultado">
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200">
                <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
            </button>
        </form>
    </div>
</body>
</html>
