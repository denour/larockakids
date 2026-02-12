<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Kid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class QueryController extends Controller
{
    public function kids(Request $request)
    {
        $query = Kid::with(['contacts', 'allergies']);

        if ($request->has('age_min')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= ?', [$request->age_min]);
        }

        if ($request->has('age_max')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= ?', [$request->age_max]);
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        $kids = $query->get()->map(fn($kid) => [
            'id' => $kid->id,
            'full_name' => $kid->full_name,
            'age' => $kid->age,
            'gender' => $kid->gender,
            'birth_date' => $kid->birth_date->format('Y-m-d'),
            'allergies' => $kid->allergies->pluck('name'),
            'contacts' => $kid->contacts->map(fn($c) => [
                'name' => $c->name,
                'phone' => $c->phone,
                'relationship' => $c->pivot->relationship_type,
            ]),
        ]);

        return response()->json(['kids' => $kids, 'total' => $kids->count()]);
    }

    public function attendance(Request $request)
    {
        $query = Attendance::with(['kid', 'contact']);

        if ($request->has('date')) {
            $query->whereDate('check_in', $request->date);
        }

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('check_in', [$request->from, $request->to]);
        }

        if ($request->has('kid_id')) {
            $query->where('kid_id', $request->kid_id);
        }

        $attendance = $query->orderBy('check_in', 'desc')->get()->map(fn($a) => [
            'id' => $a->id,
            'kid' => $a->kid->full_name ?? 'N/A',
            'check_in' => $a->check_in?->format('Y-m-d H:i'),
            'check_out' => $a->check_out?->format('Y-m-d H:i'),
            'status' => $a->status?->value ?? $a->status,
            'contact' => $a->contact->name ?? 'N/A',
        ]);

        return response()->json(['attendance' => $attendance, 'total' => $attendance->count()]);
    }

    public function exportKids(Request $request)
    {
        $query = Kid::with(['contacts', 'allergies']);

        if ($request->has('age_min')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) >= ?', [$request->age_min]);
        }

        if ($request->has('age_max')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= ?', [$request->age_max]);
        }

        $kids = $query->get();

        $csv = "ID,Nombre,Edad,Género,Fecha Nacimiento,Alergias,Contactos\n";
        foreach ($kids as $kid) {
            $allergies = $kid->allergies->pluck('name')->join('; ');
            $contacts = $kid->contacts->map(fn($c) => "{$c->name} ({$c->phone})")->join('; ');
            $csv .= "{$kid->id},{$kid->full_name},{$kid->age},{$kid->gender},{$kid->birth_date->format('Y-m-d')},\"{$allergies}\",\"{$contacts}\"\n";
        }

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="kids_export.csv"',
        ]);
    }
}
