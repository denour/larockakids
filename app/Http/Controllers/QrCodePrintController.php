<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class QrCodePrintController extends Controller
{
    /**
     * Print a single QR code badge.
     */
    public function print(QrCode $qrCode): View
    {
        return view('qr-codes.print', [
            'qrCodes' => collect([$qrCode]),
        ]);
    }

    /**
     * Print multiple QR code badges.
     */
    public function printBatch(Request $request): View
    {
        $idsParam = $request->query('ids', '');
        $ids = array_filter(explode(',', $idsParam), fn ($id) => $id !== '');

        $qrCodes = empty($ids)
            ? collect()
            : QrCode::whereIn('id', $ids)->with('kid')->get();

        return view('qr-codes.print', [
            'qrCodes' => $qrCodes,
        ]);
    }
}
