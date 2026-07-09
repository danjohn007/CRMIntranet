<?php
$primaryColor = getConfig('primary_color', '#3b82f6');
$secondaryColor = getConfig('secondary_color', '#1e40af');
$chatbotUrl = trim((string) getConfig('landing_chatbot_url', ''));
$chatbotHref = $chatbotUrl !== '' ? $chatbotUrl : '#contacto';
$siteLogo = getSiteLogo();
$siteName = getSiteName();
// Los botones del menú usan anclas relativas para no salir de la landing.
// Así funciona igual si la landing se abre desde /intranet/crm/public/ o desde el dominio principal.
$landingHomeAnchor = '#inicio';

if (!function_exists('landingHexToRgb')) {
    function landingHexToRgb($hex) {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '3b82f6';
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }
}

$primaryRgb = landingHexToRgb($primaryColor);
$secondaryRgb = landingHexToRgb($secondaryColor);

$officeAddress = getConfig('landing_office_address', 'Centro Histórico, Santiago de Querétaro, Querétaro');
$officeHours = getConfig('landing_office_hours', 'Lunes a viernes · 9:00 a 18:00 hrs');
$officePhone = getConfig('landing_contact_phone', '+52 442 000 0000');
$officeEmail = getConfig('landing_contact_email', 'contacto@tramitevisaamericanaqueretaro.com');
$mapsUrl = trim((string) getConfig('landing_maps_url', ''));
if ($mapsUrl === '') {
    $mapsUrl = 'https://maps.google.com/?q=' . rawurlencode($officeAddress);
}
$mapEmbedUrl = trim((string) getConfig('landing_map_embed_url', ''));
if ($mapEmbedUrl === '') {
    $mapEmbedUrl = 'https://www.google.com/maps?q=' . rawurlencode($officeAddress) . '&output=embed';
}

$facebookUrl = trim((string) getConfig('landing_facebook_url', ''));
$instagramUrl = trim((string) getConfig('landing_instagram_url', ''));
$tiktokUrl = trim((string) getConfig('landing_tiktok_url', ''));
$whatsappUrl = trim((string) getConfig('landing_whatsapp_url', $chatbotHref));

$services = [
    ['icon' => 'fa-passport', 'title' => 'Visa americana primera vez', 'description' => 'Acompañamiento inicial, captura de datos y revisión del expediente para nuevos solicitantes.'],
    ['icon' => 'fa-rotate', 'title' => 'Renovación de visa', 'description' => 'Seguimiento para renovación, actualización de información y carga documental ordenada.'],
    ['icon' => 'fa-people-roof', 'title' => 'Trámites familiares', 'description' => 'Solicitud para parejas, padres e hijos con visibilidad de avances y documentos por cliente.'],
    ['icon' => 'fa-plane-departure', 'title' => 'Preparación para cita', 'description' => 'Indicaciones previas, documentos clave y recordatorios antes de acudir al consulado.'],
    ['icon' => 'fa-file-signature', 'title' => 'Formulario DS-160', 'description' => 'Captura guiada por pasos para evitar omisiones y guardar avance cuando lo necesites.'],
    ['icon' => 'fa-id-card', 'title' => 'Pasaporte y documentación', 'description' => 'Carga de pasaporte, fotografías y documentos adicionales con revisión interna.'],
];

$quickAccessCards = [
    ['code' => 'US', 'flag' => 'us', 'title' => 'Visa Americana', 'description' => 'Primera vez y renovación'],
    ['code' => 'MX', 'flag' => 'mx', 'title' => 'Pasaporte Mexicano', 'description' => 'Documentos base y orientación'],
    ['code' => 'DS', 'flag' => 'us', 'title' => 'Formulario DS-160', 'description' => 'Captura guiada por pasos'],
    ['code' => 'CRM', 'flag' => 'mx', 'title' => 'Portal Cliente', 'description' => 'Avance, mensajes y archivos'],
];

$socialLinks = [
    ['label' => 'Facebook', 'icon' => 'fab fa-facebook-f', 'url' => $facebookUrl],
    ['label' => 'Instagram', 'icon' => 'fab fa-instagram', 'url' => $instagramUrl],
    ['label' => 'TikTok', 'icon' => 'fab fa-tiktok', 'url' => $tiktokUrl],
    ['label' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'url' => $whatsappUrl],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Inicio - CRM Visas y Pasaportes') ?></title>
    <meta name="description" content="Portal digital para iniciar, organizar y dar seguimiento a trámites de visa con documentos, formularios, mensajes internos y atención guiada.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/landing.css?v=3">
    <style>
        :root {
            --landing-primary: <?= htmlspecialchars($primaryColor) ?>;
            --landing-secondary: <?= htmlspecialchars($secondaryColor) ?>;
            --landing-primary-rgb: <?= (int) $primaryRgb['r'] ?>, <?= (int) $primaryRgb['g'] ?>, <?= (int) $primaryRgb['b'] ?>;
            --landing-secondary-rgb: <?= (int) $secondaryRgb['r'] ?>, <?= (int) $secondaryRgb['g'] ?>, <?= (int) $secondaryRgb['b'] ?>;
        }
    </style>
</head>
<body class="landing-body">
    <div class="landing-shell">
        <nav class="landing-nav" aria-label="Navegación principal">
            <div class="landing-container landing-nav-inner">
                <a href="<?= htmlspecialchars($landingHomeAnchor) ?>" class="landing-brand" aria-label="Inicio">
                    <?php if ($siteLogo): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="landing-brand-logo">
                    <?php else: ?>
                        <span class="landing-brand-icon"><i class="fas fa-passport"></i></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($siteName) ?></span>
                </a>

                <div class="landing-nav-links">
                    <a href="#tramites">Trámites</a>
                    <a href="#portal">Portal cliente</a>
                    <a href="#proceso">Proceso</a>
                    <a href="#ubicacion">Ubicación</a>
                    <a href="#preguntas">Preguntas</a>
                </div>

                <div class="landing-actions">
                    <a href="<?= BASE_URL ?>/login" class="landing-btn landing-btn-ghost">Entrar al sistema</a>
                    <a href="<?= htmlspecialchars($chatbotHref) ?>" class="landing-btn landing-btn-primary">
                        <i class="fas fa-paper-plane"></i> Iniciar por ChatBot
                    </a>
                </div>

                <button class="landing-mobile-toggle" type="button" data-landing-toggle aria-label="Abrir menú">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>

        <header class="landing-hero" id="inicio">
            <div class="landing-container landing-hero-grid">
                <div class="reveal">
                    <span class="landing-eyebrow"><i class="fas fa-globe-americas"></i> Trámites de visa guiados</span>
                    <h1 class="landing-title">
                        Tu trámite de visa,<br>
                        <span class="landing-gradient-text">claro, moderno y seguro.</span>
                    </h1>
                    <p class="landing-hero-copy">
                        Inicia tu solicitud desde la landing o desde el chatbot, sube documentos, responde observaciones y consulta tu avance desde un portal hecho para mantener cada paso ordenado y visible.
                    </p>
                    <div class="landing-hero-cta">
                        <a href="<?= htmlspecialchars($chatbotHref) ?>" class="landing-btn landing-btn-primary">
                            <i class="fas fa-comments"></i> Empezar trámite
                        </a>
                        <a href="<?= BASE_URL ?>/login" class="landing-btn landing-btn-ghost">
                            <i class="fas fa-right-to-bracket"></i> Ya tengo cuenta
                        </a>
                    </div>
                    <div class="landing-trust-row">
                        <span><i class="fas fa-shield-check"></i> Portal protegido</span>
                        <span><i class="fas fa-cloud-arrow-up"></i> Documentos en línea</span>
                        <span><i class="fas fa-message"></i> Seguimiento interno</span>
                    </div>
                </div>

                <div class="landing-hero-panel reveal" aria-hidden="true">
                    <div class="landing-orb"></div>
                    <div class="landing-dashboard-card">
                        <div class="landing-mini-bar">
                            <div class="landing-dot-group"><span class="landing-dot"></span><span class="landing-dot"></span><span class="landing-dot"></span></div>
                            <span class="landing-mini-pill">Portal cliente</span>
                        </div>
                        <div class="landing-progress-card">
                            <div class="landing-progress-header">
                                <div>
                                    <p class="landing-progress-title">VISA-2026-000001</p>
                                    <p class="mt-2 text-sm text-blue-100">Solicitud en revisión documental</p>
                                </div>
                                <span class="landing-status-pill">72%</span>
                            </div>
                            <div class="landing-progress-line"><span></span></div>
                            <div class="flex justify-between text-xs font-bold text-blue-100">
                                <span>Formulario</span><span>Documentos</span><span>Cita</span>
                            </div>
                        </div>
                        <div class="landing-doc-grid">
                            <div class="landing-doc-item"><span class="landing-doc-icon"><i class="fas fa-passport"></i></span><div><strong>Pasaporte</strong><small>Validado</small></div></div>
                            <div class="landing-doc-item"><span class="landing-doc-icon"><i class="fas fa-file-lines"></i></span><div><strong>Formulario</strong><small>Guardado parcial</small></div></div>
                            <div class="landing-doc-item"><span class="landing-doc-icon"><i class="fas fa-cloud-arrow-up"></i></span><div><strong>Documentos</strong><small>Subidos por cliente</small></div></div>
                            <div class="landing-doc-item"><span class="landing-doc-icon"><i class="fas fa-shield-halved"></i></span><div><strong>Seguridad</strong><small>Sesión protegida</small></div></div>
                        </div>
                    </div>
                    <div class="landing-floating-card">
                        <div class="landing-message-bubble">Hola, quiero empezar mi trámite de visa.</div>
                        <div class="landing-reply-bubble">Perfecto, puedes comenzar por el chatbot o entrar al portal para continuar tu expediente.</div>
                    </div>
                    <div class="landing-hero-visual-mini landing-visual-passport">
                        <i class="fas fa-passport"></i>
                        <span>Documento seguro</span>
                    </div>
                    <div class="landing-hero-visual-mini landing-visual-flag">
                        <span class="flag-large">🇺🇸</span>
                        <span>Visa americana</span>
                    </div>
                </div>
            </div>
        </header>
    </div>

    <main>
        <section class="landing-country-strip" aria-label="Accesos rápidos de trámites">
            <div class="landing-container">
                <div class="landing-section-head center reveal landing-strip-head">
                    <div class="landing-kicker">Accesos rápidos</div>
                    <h2 class="landing-strip-title">Trámites y herramientas principales</h2>
                    <p>Las banderas son referencias visuales de los servicios principales, no una lista de países disponibles.</p>
                </div>
                <div class="landing-country-track reveal">
                    <?php foreach ($quickAccessCards as $card): ?>
                        <span class="landing-country-chip">
                            <img src="<?= BASE_URL ?>/img/landing/flags/<?= htmlspecialchars($card['flag']) ?>.svg" alt="<?= htmlspecialchars($card['title']) ?>">
                            <small><?= htmlspecialchars($card['code']) ?></small>
                            <span>
                                <strong><?= htmlspecialchars($card['title']) ?></strong>
                                <em><?= htmlspecialchars($card['description']) ?></em>
                            </span>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section-soft" id="tramites">
            <div class="landing-container">
                <div class="landing-section-head center reveal">
                    <div class="landing-kicker">Tipos de trámites</div>
                    <h2 class="landing-h2">Servicios pensados para un proceso más claro.</h2>
                    <p class="landing-section-copy">Esta sección hace más visual la landing y deja claro qué tipo de apoyo pueden recibir los clientes antes de entrar al CRM o al portal.</p>
                </div>

                <div class="landing-service-grid">
                    <?php foreach ($services as $service): ?>
                        <article class="landing-service-card reveal">
                            <span class="landing-service-icon"><i class="fas <?= htmlspecialchars($service['icon']) ?>"></i></span>
                            <h3><?= htmlspecialchars($service['title']) ?></h3>
                            <p><?= htmlspecialchars($service['description']) ?></p>
                            <div class="landing-service-link">Asesoría guiada <i class="fas fa-arrow-right"></i></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="landing-section landing-portal-clean" id="portal">
            <div class="landing-container">
                <div class="landing-section-head center reveal">
                    <div class="landing-kicker">Portal digital para clientes</div>
                    <h2 class="landing-h2">Todo lo importante del trámite en un solo lugar.</h2>
                    <p class="landing-section-copy">El portal ayuda a que el cliente sepa qué falta, qué ya fue enviado y qué está revisando el equipo, sin depender de conversaciones sueltas.</p>
                </div>

                <div class="landing-portal-layout">
                    <div class="landing-portal-steps reveal">
                        <article class="landing-portal-step">
                            <span>01</span>
                            <div>
                                <h3>Consulta el avance</h3>
                                <p>Estatus, porcentaje, asesor asignado y últimas actualizaciones visibles desde su perfil.</p>
                            </div>
                        </article>
                        <article class="landing-portal-step">
                            <span>02</span>
                            <div>
                                <h3>Llena el formulario por secciones</h3>
                                <p>El cliente puede guardar avance y continuar después sin perder información.</p>
                            </div>
                        </article>
                        <article class="landing-portal-step">
                            <span>03</span>
                            <div>
                                <h3>Sube documentos</h3>
                                <p>Pasaporte, comprobantes y archivos adicionales quedan ligados directamente al trámite.</p>
                            </div>
                        </article>
                        <article class="landing-portal-step">
                            <span>04</span>
                            <div>
                                <h3>Mensajes internos</h3>
                                <p>Las dudas y respuestas se guardan dentro del CRM para que el asesor pueda dar seguimiento.</p>
                            </div>
                        </article>
                    </div>

                    <div class="landing-portal-demo reveal" aria-hidden="true">
                        <div class="landing-portal-window">
                            <div class="landing-portal-window-top">
                                <span class="landing-dot"></span><span class="landing-dot"></span><span class="landing-dot"></span>
                                <strong>Mi trámite</strong>
                            </div>
                            <div class="landing-portal-window-card primary">
                                <div>
                                    <small>VISA-2026-000001</small>
                                    <h3>Seguimiento inteligente</h3>
                                </div>
                                <span>72%</span>
                            </div>
                            <div class="landing-portal-progress"><span></span></div>
                            <div class="landing-portal-task-grid">
                                <div><i class="fas fa-file-lines"></i><strong>Formulario</strong><small>Completado</small></div>
                                <div><i class="fas fa-cloud-arrow-up"></i><strong>Documento adicional</strong><small>En revisión</small></div>
                                <div><i class="fas fa-comment-dots"></i><strong>Observaciones</strong><small>1 pendiente</small></div>
                            </div>
                        </div>
                        <div class="landing-portal-chat">
                            <span>ChatBot</span>
                            <p>Inicia el trámite desde el bot y continúa en el portal.</p>
                            <div><strong>Bot:</strong> ¿Deseas iniciar solicitud nueva o continuar tu trámite?</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section-soft" id="proceso">
            <div class="landing-container landing-process-grid">
                <div class="reveal">
                    <div class="landing-kicker">Flujo del proceso</div>
                    <h2 class="landing-h2">Un recorrido simple desde el primer mensaje hasta la cita.</h2>
                    <p class="landing-section-copy">El cliente entiende fácilmente cada etapa: contacto inicial, captura de datos, revisión documental y seguimiento posterior. Eso hace la landing más útil y también ayuda a reducir dudas repetidas.</p>
                    <div class="landing-hero-cta">
                        <a href="<?= BASE_URL ?>/login" class="landing-btn landing-btn-primary"><i class="fas fa-lock"></i> Ir al login</a>
                        <a href="#ubicacion" class="landing-btn landing-btn-ghost">Ver ubicación</a>
                    </div>
                </div>

                <div class="landing-step-list">
                    <article class="landing-step reveal"><span class="landing-step-number">1</span><div><h3>Inicia el contacto</h3><p>Desde el chatbot o la página, el cliente recibe orientación inicial para comenzar su trámite.</p></div></article>
                    <article class="landing-step reveal"><span class="landing-step-number">2</span><div><h3>Captura información</h3><p>Completa formularios por secciones y guarda avances cuando lo necesites.</p></div></article>
                    <article class="landing-step reveal"><span class="landing-step-number">3</span><div><h3>Sube documentos</h3><p>El equipo revisa pasaporte, comprobantes y archivos adicionales dentro del CRM.</p></div></article>
                    <article class="landing-step reveal"><span class="landing-step-number">4</span><div><h3>Consulta el avance</h3><p>Ve observaciones, mensajes, actualizaciones y preparación para la siguiente etapa.</p></div></article>
                </div>
            </div>
        </section>

        <section class="landing-section">
            <div class="landing-container landing-process-grid align-center">
                <div class="landing-phone-card reveal">
                    <div class="landing-phone-screen">
                        <div class="landing-phone-top"><span class="landing-screen-title">Mi trámite</span><span class="landing-small-pill">Guardado parcial</span></div>
                        <div class="landing-form-preview">
                            <div class="landing-input-preview"></div>
                            <div class="landing-input-preview short"></div>
                            <div class="landing-upload-preview"><span class="landing-doc-icon"><i class="fas fa-upload"></i></span><div><strong>Subir pasaporte</strong><small class="block text-slate-500">PDF, JPG o PNG</small></div></div>
                            <div class="landing-message-bubble">El cliente puede responder observaciones desde aquí.</div>
                            <div class="landing-reply-bubble">El asesor ve la respuesta en seguimiento.</div>
                        </div>
                    </div>
                </div>
                <div class="reveal">
                    <div class="landing-kicker">Experiencia más llamativa</div>
                    <h2 class="landing-h2">Más visuales, mejor jerarquía y contenidos útiles.</h2>
                    <p class="landing-section-copy">Para hacer la página más atractiva agregamos bloques tipo showcase, chips de países, secciones de trámites y un footer más completo con redes sociales y datos de contacto.</p>
                    <div class="landing-bullet-list">
                        <div><i class="fas fa-check-circle"></i> Secciones mejor separadas y sin elementos fuera del margen.</div>
                        <div><i class="fas fa-check-circle"></i> Más tarjetas visuales e información práctica para el cliente.</div>
                        <div><i class="fas fa-check-circle"></i> CTA visibles para login, portal y chatbot.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section-soft">
            <div class="landing-container">
                <div class="landing-section-head center reveal">
                    <div class="landing-kicker">Roadmap del trámite</div>
                    <h2 class="landing-h2">Etapas visibles para mantener transparencia.</h2>
                    <p class="landing-section-copy">Una vista tipo tablero ayuda a que el cliente entienda qué está pendiente, qué está en revisión y qué sigue.</p>
                </div>
                <div class="landing-roadmap">
                    <article class="landing-stage reveal"><div class="landing-stage-title"><span></span>Nuevo</div><div class="landing-stage-card">Registro del cliente y creación de solicitud.</div><div class="landing-stage-card">Asignación de asesor responsable.</div></article>
                    <article class="landing-stage reveal"><div class="landing-stage-title"><span></span>En revisión</div><div class="landing-stage-card">Validación de formulario y documentos.</div><div class="landing-stage-card">Observaciones visibles para el cliente.</div></article>
                    <article class="landing-stage reveal"><div class="landing-stage-title"><span></span>Cita programada</div><div class="landing-stage-card">Confirmación de fecha, indicaciones y recordatorios.</div><div class="landing-stage-card">Comunicación dentro del sistema.</div></article>
                    <article class="landing-stage reveal"><div class="landing-stage-title"><span></span>Resultado</div><div class="landing-stage-card">Seguimiento final y cierre del trámite.</div><div class="landing-stage-card">Historial completo disponible para el equipo.</div></article>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section-dark" id="seguridad">
            <div class="landing-container">
                <div class="landing-section-head center reveal">
                    <div class="landing-kicker">Seguridad y control</div>
                    <h2 class="landing-h2">Preparado para operación profesional.</h2>
                    <p class="landing-section-copy">La landing también comunica los puntos de seguridad solicitados para el CRM y el portal del cliente.</p>
                </div>
                <div class="landing-support-grid">
                    <article class="landing-support-card reveal"><span class="landing-support-icon"><i class="fas fa-location-dot"></i></span><h3>Login con ubicación</h3><p>Validación por rango establecido por administración para accesos internos.</p></article>
                    <article class="landing-support-card reveal"><span class="landing-support-icon"><i class="fas fa-key"></i></span><h3>Doble validación</h3><p>Base para verificación adicional y bloqueo por intentos incorrectos.</p></article>
                    <article class="landing-support-card reveal"><span class="landing-support-icon"><i class="fas fa-clock"></i></span><h3>Inactividad</h3><p>Cierre automático de sesión para proteger datos sensibles.</p></article>
                    <article class="landing-support-card reveal"><span class="landing-support-icon"><i class="fas fa-comments"></i></span><h3>Comunicación interna</h3><p>Mensajes ligados al trámite para reducir atención manual por WhatsApp.</p></article>
                </div>
            </div>
        </section>

        <section class="landing-section" id="ubicacion">
            <div class="landing-container">
                <div class="landing-section-head center reveal">
                    <div class="landing-kicker">Ubicación y contacto</div>
                    <h2 class="landing-h2">Atención cercana y múltiples formas de inicio.</h2>
                    <p class="landing-section-copy">Aquí puedes anunciar dónde se encuentra la oficina, los canales de atención y las redes sociales para reforzar confianza.</p>
                </div>
                <div class="landing-location-grid">
                    <article class="landing-location-card reveal">
                        <span class="landing-location-icon"><i class="fas fa-map-marker-alt"></i></span>
                        <h3>¿Dónde estamos?</h3>
                        <p><?= htmlspecialchars($officeAddress) ?></p>
                        <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener noreferrer" class="landing-inline-link">Abrir ubicación <i class="fas fa-arrow-up-right-from-square"></i></a>
                    </article>
                    <article class="landing-location-card reveal">
                        <span class="landing-location-icon"><i class="fas fa-clock"></i></span>
                        <h3>Horario</h3>
                        <p><?= htmlspecialchars($officeHours) ?></p>
                        <p class="landing-location-muted">Seguimiento por portal y mensajes internos disponible para tus clientes.</p>
                    </article>
                    <article class="landing-location-card reveal">
                        <span class="landing-location-icon"><i class="fas fa-phone"></i></span>
                        <h3>Contacto</h3>
                        <p><?= htmlspecialchars($officePhone) ?><br><?= htmlspecialchars($officeEmail) ?></p>
                        <a href="<?= htmlspecialchars($chatbotHref) ?>" class="landing-inline-link">Hablar con el bot <i class="fas fa-robot"></i></a>
                    </article>
                </div>

                <div class="landing-map-card reveal">
                    <iframe
                        src="<?= htmlspecialchars($mapEmbedUrl) ?>"
                        width="100%"
                        height="390"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Mapa de ubicación"></iframe>
                    <div class="landing-map-caption">
                        <div>
                            <strong>Ubicación de referencia</strong>
                            <span><?= htmlspecialchars($officeAddress) ?></span>
                        </div>
                        <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" rel="noopener noreferrer">Cómo llegar <i class="fas fa-route"></i></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section" id="contacto">
            <div class="landing-container">
                <div class="landing-cta-band reveal">
                    <div class="landing-cta-content">
                        <h2>Empieza tu trámite sin perder información importante.</h2>
                        <p>Accede al portal, habla con el chatbot o inicia sesión si ya tienes un usuario asignado. El equipo podrá continuar tu seguimiento desde el CRM.</p>
                        <div class="landing-hero-cta">
                            <a href="<?= htmlspecialchars($chatbotHref) ?>" class="landing-btn landing-btn-primary"><i class="fas fa-paper-plane"></i> Empezar trámite</a>
                            <a href="<?= BASE_URL ?>/login" class="landing-btn landing-btn-ghost"><i class="fas fa-right-to-bracket"></i> Ya tengo cuenta</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="landing-section landing-section-soft" id="preguntas">
            <div class="landing-container">
                <div class="landing-section-head center reveal">
                    <div class="landing-kicker">Preguntas frecuentes</div>
                    <h2 class="landing-h2">Lo básico antes de iniciar.</h2>
                </div>
                <div class="landing-faq-grid">
                    <article class="landing-faq-card reveal"><h3>¿Necesito usuario para ver mi trámite?</h3><p>Sí. El equipo te asigna un usuario cliente para consultar tu avance, documentos, mensajes y observaciones.</p></article>
                    <article class="landing-faq-card reveal"><h3>¿Puedo guardar mi formulario?</h3><p>Sí. El formulario está pensado para llenarse por etapas y continuar después.</p></article>
                    <article class="landing-faq-card reveal"><h3>¿El chatbot reemplaza al asesor?</h3><p>No. Ayuda a iniciar y automatizar pasos, pero el seguimiento queda conectado con el equipo dentro del CRM.</p></article>
                </div>
            </div>
        </section>
    </main>

    <footer class="landing-footer">
        <div class="landing-container landing-footer-top">
            <div class="landing-footer-brand-block">
                <a href="<?= htmlspecialchars($landingHomeAnchor) ?>" class="landing-brand">
                    <?php if ($siteLogo): ?>
                        <img src="<?= BASE_URL . htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="landing-brand-logo">
                    <?php else: ?>
                        <span class="landing-brand-icon"><i class="fas fa-passport"></i></span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($siteName) ?></span>
                </a>
                <p class="landing-footer-copy">Plataforma para organizar trámites de visa, documentos, comunicación con clientes y seguimiento interno desde un solo lugar.</p>
                <div class="landing-socials">
                    <?php foreach ($socialLinks as $social): ?>
                        <?php $socialHref = !empty($social['url']) ? $social['url'] : '#'; ?>
                        <a href="<?= htmlspecialchars($socialHref) ?>" <?= $socialHref !== '#' ? 'target="_blank" rel="noopener noreferrer"' : '' ?> aria-label="<?= htmlspecialchars($social['label']) ?>"><i class="<?= htmlspecialchars($social['icon']) ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="landing-footer-col">
                <h4>Navegación</h4>
                <a href="#tramites">Trámites</a>
                <a href="#portal">Portal cliente</a>
                <a href="#proceso">Proceso</a>
                <a href="#ubicacion">Ubicación</a>
            </div>
            <div class="landing-footer-col">
                <h4>Servicios</h4>
                <a href="#tramites">Visa primera vez</a>
                <a href="#tramites">Renovación</a>
                <a href="#tramites">Formulario DS-160</a>
                <a href="#tramites">Preparación para cita</a>
            </div>
            <div class="landing-footer-col">
                <h4>Contacto</h4>
                <span><?= htmlspecialchars($officeAddress) ?></span>
                <span><?= htmlspecialchars($officeHours) ?></span>
                <span><?= htmlspecialchars($officePhone) ?></span>
                <span><?= htmlspecialchars($officeEmail) ?></span>
            </div>
        </div>
        <div class="landing-container landing-footer-bottom">
            <p>© <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. Todos los derechos reservados.</p>
            <div class="landing-footer-links">
                <a href="<?= BASE_URL ?>/login">Login</a>
                <a href="<?= htmlspecialchars($chatbotHref) ?>">ChatBot</a>
                <a href="#preguntas">Preguntas</a>
            </div>
        </div>
    </footer>

    <script src="<?= BASE_URL ?>/js/landing.js?v=3"></script>
</body>
</html>
