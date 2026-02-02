<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Gafetes QR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }

        .print-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10mm;
            justify-content: flex-start;
            padding: 10mm;
        }

        .badge {
            width: 54mm;
            height: 85mm;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 5mm;
            page-break-inside: avoid;
        }

        .badge-header {
            text-align: center;
            width: 100%;
        }

        .badge-logo {
            font-size: 14px;
            font-weight: bold;
            color: #4a5568;
            margin-bottom: 2mm;
        }

        .badge-title {
            font-size: 10px;
            color: #718096;
        }

        .badge-qr {
            width: 40mm;
            height: 40mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .badge-code {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
            letter-spacing: 1px;
        }

        .badge-kid-name {
            font-size: 11px;
            color: #4a5568;
            text-align: center;
            min-height: 14px;
        }

        .badge-footer {
            font-size: 8px;
            color: #a0aec0;
            text-align: center;
        }

        .no-print {
            padding: 20px;
            text-align: center;
            background: white;
            margin-bottom: 20px;
        }

        .no-print button {
            background: #4299e1;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }

        .no-print button:hover {
            background: #3182ce;
        }

        .no-print .secondary {
            background: #718096;
        }

        .no-print .secondary:hover {
            background: #4a5568;
        }

        @media print {
            body {
                background: white;
            }

            .no-print {
                display: none;
            }

            .print-container {
                padding: 0;
            }

            .badge {
                border: 1px solid #ccc;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
        <button class="secondary" onclick="window.close()">Cerrar</button>
    </div>

    <div class="print-container">
        @foreach($qrCodes as $qrCode)
            <div class="badge">
                <div class="badge-header">
                    <div class="badge-logo">La Roca Kids</div>
                    <div class="badge-title">Gafete de Identificación</div>
                </div>

                <div class="badge-qr">
                    @if($qrCode->qr_image_path)
                        <img src="{{ Storage::url($qrCode->qr_image_path) }}" alt="QR {{ $qrCode->code }}">
                    @else
                        <div style="color: #a0aec0; font-size: 12px;">Sin imagen QR</div>
                    @endif
                </div>

                <div class="badge-code">{{ $qrCode->code }}</div>

                <div class="badge-kid-name">
                    @if($qrCode->kid)
                        {{ $qrCode->kid->full_name }}
                    @else
                        &nbsp;
                    @endif
                </div>

                <div class="badge-footer">
                    Escanea para check-in
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
