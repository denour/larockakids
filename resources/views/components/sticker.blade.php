@props(['kid', 'contact' => null, 'attendance' => null])

@php
    // Un nombre y un apellido: las columnas pueden traer varios de cada uno
    // ("Mateo Andrés", "Hernández López"), y en la etiqueta solo cabe uno.
    $primerNombre = trim(strtok(trim($kid->first_name ?? ''), ' ') ?: '');
    $primerApellido = trim(strtok(trim($kid->last_name ?? ''), ' ') ?: '');

    $corto = trim($primerNombre . ' ' . $primerApellido);
    $n = mb_strlen($corto);
    $tamNombre = $n <= 11 ? 30 : ($n <= 14 ? 24 : ($n <= 17 ? 20 : 17));

    // El contacto que hizo el check-in es el "responsable" del sticker. Si por
    // alguna razón no viene (defensivo), se cae al contacto principal del niño.
    $contactoPrincipal = $contact
        ?? optional($kid)->contacts?->firstWhere('pivot.relationship_type', 'parent')
        ?? optional($kid)->contacts?->first();

    $telefono = optional($contactoPrincipal)->full_phone;
    $responsable = optional($contactoPrincipal)->full_name;

    $alergias = optional($kid)->allergies?->pluck('name')->join(', ');
@endphp

<div class="sticker">
    <div class="nombre" style="font-size: {{ $tamNombre }}px">{{ $corto }}</div>
    <div class="datos">
        {{ optional($kid)->age }} años
        @if(!empty($responsable))
            &middot; Resp. {{ $responsable }}
        @endif
    </div>
    @if($telefono)
        <div class="telbox">
            <div class="telcap">TELÉFONO</div>
            <div class="tel">{{ $telefono }}</div>
        </div>
    @endif
    @if($attendance?->service)
        <div class="reunion">{{ $attendance->service->getShortLabel() }}</div>
    @endif
    @if(!empty($alergias))
        <div class="alerta">ALERGIA: {{ mb_strtoupper($alergias) }}</div>
    @endif
    <div class="pie">{{ optional($attendance?->created_at ?? now())->format('d/m/Y · H:i') }}</div>
</div>

<style>
    /* 234px = 62mm exactos a 96 dpi. NO CAMBIAR este ancho. */
    .sticker {
        width: 234px; height: 234px; padding: 9px; box-sizing: border-box;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center; text-align: center;
        overflow: hidden;
        font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff;
    }
    .sticker > * { flex: none; }
    .nombre  { font-weight: 800; line-height: 1.0; text-transform: uppercase;
               letter-spacing: -.5px; width: 100%; }
    .datos   { font-size: 15px; font-weight: 600; line-height: 1.25; margin-top: 3px; }
    .telbox  { margin-top: 4px; background: #000; color: #fff;
               border-radius: 4px; padding: 2px 8px; }
    .telcap  { font-size: 9px;  font-weight: 600; letter-spacing: .5px; }
    .tel     { font-size: 18px; font-weight: 800; letter-spacing: .3px; line-height: 1.15; }
    .reunion { margin-top: 4px; font-size: 21px; font-weight: 800;
               border: 2px solid #000; border-radius: 5px; padding: 1px 8px; line-height: 1.15; }
    .alerta  { margin-top: 4px; font-size: 13px; font-weight: 800;
               background: #000; color: #fff; border-radius: 3px; padding: 1px 7px; }
    .pie     { margin-top: 4px; font-size: 11px; font-weight: 500; color: #333; }
</style>
