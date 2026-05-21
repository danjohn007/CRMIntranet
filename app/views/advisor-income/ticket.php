<?php
$ticketTitle = 'Ticket ' . ($income['folio'] ?? '');
$servicePrice = (float) ($ticketData['service_price'] ?? 0);
$paidAmount = (float) ($ticketData['paid_amount'] ?? 0);
$changeAmount = (float) ($ticketData['change_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($ticketTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            .ticket-sheet {
                box-shadow: none !important;
                border: 1px solid #d1d5db !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
    <div class="max-w-2xl mx-auto px-4 py-6">
        <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <a href="<?= BASE_URL ?>/ingresos" class="inline-flex items-center text-primary hover:opacity-80">
                <span class="mr-2">←</span>Volver a ingresos
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Imprimir / Guardar PDF
            </button>
        </div>

        <div class="ticket-sheet bg-white border border-gray-200 rounded-xl shadow-lg p-6">
            <div class="border-b border-dashed border-gray-300 pb-4 mb-4">
                <p class="text-sm uppercase tracking-wide text-gray-500">Ticket de ingreso</p>
                <h1 class="text-2xl font-bold"><?= htmlspecialchars(SITE_NAME) ?></h1>
                <p class="text-sm text-gray-500 mt-1">Folio: <span class="font-semibold text-gray-800"><?= htmlspecialchars($income['folio'] ?? '') ?></span></p>
                <p class="text-sm text-gray-500">Fecha del ingreso: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($income['income_datetime'] ?? 'now'))) ?></p>
                <p class="text-sm text-gray-500">Ticket generado: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($ticketData['generated_at'] ?? 'now'))) ?></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <p class="text-xs uppercase text-gray-500">Cliente</p>
                    <p class="font-medium"><?= htmlspecialchars($ticketData['customer_name'] ?: 'Público en general') ?></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Teléfono</p>
                    <p class="font-medium"><?= htmlspecialchars($ticketData['customer_phone'] ?: 'No proporcionado') ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase text-gray-500">Correo</p>
                    <p class="font-medium"><?= htmlspecialchars($ticketData['customer_email'] ?: 'No proporcionado') ?></p>
                </div>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden mb-5">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs uppercase text-gray-500">Servicio</th>
                            <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Precio</th>
                            <th class="px-4 py-3 text-right text-xs uppercase text-gray-500">Monto pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-gray-200">
                            <td class="px-4 py-3"><?= htmlspecialchars($ticketData['service_name'] ?? ($income['income_type'] ?? '')) ?></td>
                            <td class="px-4 py-3 text-right">$<?= number_format($servicePrice, 2) ?></td>
                            <td class="px-4 py-3 text-right">$<?= number_format($paidAmount, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Precio del servicio</span>
                    <span class="font-semibold">$<?= number_format($servicePrice, 2) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Monto pagado</span>
                    <span class="font-semibold">$<?= number_format($paidAmount, 2) ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-500">Cambio</span>
                    <span class="font-semibold">$<?= number_format($changeAmount, 2) ?></span>
                </div>
            </div>

            <?php if (!empty($income['note'])): ?>
            <div class="mt-5 pt-4 border-t border-dashed border-gray-300">
                <p class="text-xs uppercase text-gray-500 mb-1">Nota del ingreso</p>
                <p class="text-sm text-gray-700"><?= htmlspecialchars($income['note']) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
