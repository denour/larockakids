<?php

namespace App\Http\Controllers;

use App\Services\AttendanceScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrScannerController extends Controller
{
    public function __construct(protected AttendanceScannerService $scannerService) {}

    /**
     * Display the check-in scanner page.
     */
    public function checkInPage(): View
    {
        return view('scanner.check-in');
    }

    /**
     * Process check-in scan.
     */
    public function processCheckIn(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $result = $this->scannerService->processCheckIn($request->input('code'), $request->ip());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'action' => $result['action'] ?? null,
            'kid_name' => isset($result['kid']) ? $result['kid']->full_name : null,
            'has_active_attendance' => $result['has_active_attendance'] ?? false,
            'warning' => $result['warning'] ?? null,
            'requires_graduation' => $result['requires_graduation'] ?? false,
        ]);
    }

    /**
     * Display the check-out scanner page.
     */
    public function checkOutPage(): View
    {
        return view('scanner.check-out');
    }

    /**
     * Process check-out scan.
     */
    public function processCheckOut(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $result = $this->scannerService->processCheckOut($request->input('code'), $request->ip());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'action' => $result['action'] ?? null,
            'kid_name' => isset($result['kid']) ? $result['kid']->full_name : null,
            'no_active_attendance' => $result['no_active_attendance'] ?? false,
        ]);
    }

    /**
     * Display the assistance scanner page.
     */
    public function assistancePage(): View
    {
        return view('scanner.assistance');
    }

    /**
     * Process assistance scan.
     */
    public function processAssistance(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $result = $this->scannerService->processAssistance($request->input('code'));

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'kid_name' => isset($result['kid']) ? $result['kid']->full_name : null,
        ]);
    }
}
