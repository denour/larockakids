<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\WhatsAppNotification;

class WhatsAppController extends Controller
{
    public function index()
    {
        return view('whatsapp');
    }

    public function testNotification(Request $request)
    {
        broadcast(new WhatsAppNotification(
            'Este es un mensaje de prueba de La Roca Kids', // Mensaje genérico
            '526861729522' // Número fijo
        ));

        return response()->json([
            'status' => 'success',
            'message' => 'Notification sent'
        ]);
    }
} 