<?php

namespace App\Http\Controllers;

use App\Exports\KidsWithContactsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class KidExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(new KidsWithContactsExport, 'kids_export.xlsx');
    }
} 