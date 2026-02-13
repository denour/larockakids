<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Credenciales QR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 5mm;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
        }

        .print-container {
            display: grid;
            grid-template-columns: repeat(2, 90mm);
            gap: 5mm;
            padding: 5mm;
            max-width: 210mm;
            margin: 0 auto;
            justify-content: center;
        }

        .badge {
            width: 90mm;
            height: 54mm;
            background: #161d6a;
            border-radius: 8px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 3mm;
            break-inside: avoid;
            overflow: hidden;
            gap: 2mm;
        }

        .badge-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .badge-logo {
            width: 30mm;
            height: auto;
            margin-bottom: 2mm;
        }

        .badge-logo img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }

        .badge-qr-container {
            background: white;
            border-radius: 6px;
            padding: 2mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1mm;
        }

        .badge-qr {
            width: 28mm;
            height: 28mm;
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
            font-size: 9px;
            font-weight: bold;
            color: #161d6a;
            letter-spacing: 1px;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .badge-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 2mm;
        }

        .badge-name-container {
            background: white;
            border-radius: 6px;
            padding: 2mm 3mm;
            width: 100%;
            text-align: center;
        }

        .badge-kid-name {
            font-size: 12px;
            font-weight: 600;
            color: #161d6a;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .badge-kid-age {
            font-size: 10px;
            font-weight: 500;
            color: #4a5568;
            margin-top: 1mm;
        }

        .badge-footer {
            font-size: 7px;
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
        }

        .no-print {
            padding: 20px;
            text-align: center;
            background: white;
            margin-bottom: 20px;
        }

        .no-print button {
            background: #161d6a;
            color: white;
            border: none;
            padding: 10px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }

        .no-print button:hover {
            background: #1e40af;
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
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none;
            }

            .print-container {
                padding: 0;
                gap: 2mm;
            }

            .badge {
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
                <div class="badge-left">
                    <div class="badge-qr-container">
                        <div class="badge-qr">
                            @if($qrCode->qr_image_path)
                                <img src="{{ Storage::url($qrCode->qr_image_path) }}" alt="QR {{ $qrCode->code }}">
                            @else
                                <div style="color: #a0aec0; font-size: 10px;">Sin QR</div>
                            @endif
                        </div>
                        <div class="badge-code">{{ $qrCode->code }}</div>
                    </div>
                </div>

                <div class="badge-right">
                    <div class="badge-logo">
                        <img src="{{ \App\Models\Setting::getLogoUrl() }}" alt="{{ \App\Models\Setting::getSiteName() }}">
                    </div>

                    <div class="badge-name-container">
                        <div class="badge-kid-name">
                            @if($qrCode->kid)
                                {{ $qrCode->kid->full_name }}
                            @else
                                &nbsp;
                            @endif
                        </div>
                        @if($qrCode->kid && $qrCode->kid->birth_date)
                            <div class="badge-kid-age">
                                {{ $qrCode->kid->age }} Años
                            </div>
                        @endif
                    </div>

                    <div class="badge-footer">
                        Escanea para check-in
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
