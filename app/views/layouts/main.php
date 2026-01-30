<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'CRM Visas y Pasaportes' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-link:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        .sidebar-link.active {
            background-color: rgba(59, 130, 246, 0.2);
            border-left: 4px solid #3b82f6;
        }
        
        /* Mobile menu styles */
        #sidebar {
            transition: transform 0.3s ease-in-out;
        }
        
        #sidebar-overlay {
            transition: opacity 0.3s ease-in-out;
        }
        
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 40;
                height: 100vh;
                overflow-y: auto;
            }
            
            #sidebar.open {
                transform: translateX(0);
            }
            
            #sidebar-overlay {
                opacity: 0;
                pointer-events: none;
            }
            
            #sidebar-overlay.open {
                opacity: 1;
                pointer-events: auto;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <!-- Mobile menu button -->
                    <button id="mobile-menu-button" 
                            class="md:hidden text-white hover:text-blue-200 focus:outline-none"
                            aria-label="Abrir menú de navegación"
                            aria-expanded="false"
                            aria-controls="sidebar">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                    
                    <?php 
                    $siteLogo = getSiteLogo();
                    if ($siteLogo): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($siteLogo) ?>" alt="Logo" class="h-10 object-contain">
                    <?php else: ?>
                        <i class="fas fa-passport text-2xl"></i>
                    <?php endif; ?>
                    <h1 class="text-xl font-bold"><?= htmlspecialchars(getSiteName()) ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <p class="font-semibold"><?= $_SESSION['user_name'] ?? 'Usuario' ?></p>
                        <p class="text-blue-200 text-xs"><?= $_SESSION['user_role'] ?? '' ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>/logout" class="bg-blue-700 hover:bg-blue-800 px-4 py-2 rounded transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden"></div>

    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white shadow-lg min-h-screen">
            <nav class="py-4">
                <a href="<?= BASE_URL ?>/dashboard" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="<?= BASE_URL ?>/solicitudes" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-file-alt w-6"></i>
                    <span>Solicitudes</span>
                </a>
                
                <?php if (in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN])): ?>
                <a href="<?= BASE_URL ?>/formularios" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-edit w-6"></i>
                    <span>Constructor de Formularios</span>
                </a>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN, ROLE_GERENTE])): ?>
                <a href="<?= BASE_URL ?>/financiero" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-dollar-sign w-6"></i>
                    <span>Módulo Financiero</span>
                </a>
                
                <a href="<?= BASE_URL ?>/reportes" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-chart-bar w-6"></i>
                    <span>Reportes</span>
                </a>
                <?php endif; ?>
                
                <?php if (in_array($_SESSION['user_role'] ?? '', [ROLE_ADMIN])): ?>
                <a href="<?= BASE_URL ?>/usuarios" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-users w-6"></i>
                    <span>Usuarios</span>
                </a>
                
                <a href="<?= BASE_URL ?>/configuracion" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-cog w-6"></i>
                    <span>Configuración</span>
                </a>
                
                <a href="<?= BASE_URL ?>/auditoria" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-clipboard-list w-6"></i>
                    <span>Auditoría</span>
                </a>
                
                <a href="<?= BASE_URL ?>/logs" class="sidebar-link flex items-center px-6 py-3 text-gray-700 hover:text-blue-600">
                    <i class="fas fa-exclamation-triangle w-6"></i>
                    <span>Logs de Errores</span>
                </a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                <p><?= htmlspecialchars($_SESSION['success']) ?></p>
            </div>
            <?php unset($_SESSION['success']); endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                <p><?= htmlspecialchars($_SESSION['error']) ?></p>
            </div>
            <?php unset($_SESSION['error']); endif; ?>
            
            <?= $content ?? '' ?>
        </main>
    </div>

    <script>
        // Marcar enlace activo
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const links = document.querySelectorAll('.sidebar-link');
            
            links.forEach(link => {
                const href = link.getAttribute('href');
                if (href && currentPath.startsWith(href.replace('<?= BASE_URL ?>', ''))) {
                    link.classList.add('active');
                }
            });
            
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', function() {
                    const isOpen = sidebar.classList.toggle('open');
                    overlay.classList.toggle('open');
                    mobileMenuButton.setAttribute('aria-expanded', isOpen);
                    
                    // Focus trap: focus first link when menu opens
                    if (isOpen && links.length > 0) {
                        links[0].focus();
                    }
                });
                
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('open');
                    mobileMenuButton.setAttribute('aria-expanded', 'false');
                });
                
                // Close sidebar when clicking on a link (mobile)
                links.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 768) {
                            sidebar.classList.remove('open');
                            overlay.classList.remove('open');
                            mobileMenuButton.setAttribute('aria-expanded', 'false');
                        }
                    });
                });
                
                // Close menu on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('open');
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                        mobileMenuButton.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>
