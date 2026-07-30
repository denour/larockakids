@props(['kid', 'contact' => null, 'attendance' => null])

@php
    // Un nombre y un apellido: las columnas pueden traer varios de cada uno
    // ("Mateo Andrés", "Hernández López"), y en la etiqueta solo cabe uno.
    $primerNombre = trim(strtok(trim($kid->first_name ?? ''), ' ') ?: '');
    $primerApellido = trim(strtok(trim($kid->last_name ?? ''), ' ') ?: '');

    $corto = trim($primerNombre . ' ' . $primerApellido);
    $n = max(mb_strlen($corto), 1);

    // El nombre debe caber en UN renglón dentro de los 216px útiles (234 menos
    // el padding). Medido en Arial 800 mayúsculas, cada carácter ocupa como
    // mucho 0.61 x el tamaño de fuente; usamos 0.75 para absorber la variación
    // entre la fuente del servidor y la que tenga el navegador que imprime.
    // Una escalera de escalones fijos ya falló: "MAVI CASTRO" a 30px medía
    // 202px aquí y se partía en dos en la máquina del salón.
    $anchoUtil = 216;
    $tamNombre = (int) max(11, min(30, floor($anchoUtil / ($n * 0.75))));

    // El contacto que hizo el check-in es el "responsable" del sticker. Si por
    // alguna razón no viene (defensivo), se cae al contacto principal del niño.
    $contactoPrincipal = $contact
        ?? optional($kid)->contacts?->firstWhere('pivot.relationship_type', 'parent')
        ?? optional($kid)->contacts?->first();

    $telefono = optional($contactoPrincipal)->full_phone;
    // Solo el nombre de pila: el apellido completo parte la línea en dos y
    // se come el alto de la etiqueta. Quien recoge se identifica de viva voz.
    $responsable = trim(strtok(trim(optional($contactoPrincipal)->first_name ?? ''), ' ') ?: '');

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
    /* El contenido va centrado, así que el relleno inferior lo empuja hacia
       arriba LA MITAD de su valor: 32px abajo contra 9px arriba sube el
       bloque unos 3mm, que es lo que se pidió para el teléfono y la reunión. */
    .sticker {
        width: 234px; height: 234px; padding: 9px 9px 32px; box-sizing: border-box;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center; text-align: center;
        overflow: hidden;
        font-family: Arial, Helvetica, sans-serif; color: #000; background: #fff;
    }
    .sticker > * { flex: none; }
    /* nowrap es la garantía dura de un solo renglón: si alguna fuente rinde
       más ancha de lo previsto, prefiero que el nombre se acerque al borde
       antes que partirse en dos y empujar el resto de la etiqueta. */
    .nombre  { font-weight: 800; line-height: 1.0; text-transform: uppercase;
               letter-spacing: -.5px; width: 100%; white-space: nowrap; }
    /* El responsable respira antes del bloque del teléfono. */
    .datos   { font-size: 15px; font-weight: 600; line-height: 1.25; margin-top: 3px; }
    .telbox  { margin-top: 9px; background: #000; color: #fff;
               border-radius: 5px; padding: 3px 10px; }
    .tel     { font-size: 19px; font-weight: 800; letter-spacing: .3px; line-height: 1.2; }
    /* Pegado al teléfono: la reunión se lee como parte del mismo bloque. */
    .reunion { margin-top: 5px; font-size: 21px; font-weight: 800;
               border: 2px solid #000; border-radius: 5px; padding: 1px 8px; line-height: 1.15; }
    .alerta  { margin-top: 5px; font-size: 13px; font-weight: 800;
               background: #000; color: #fff; border-radius: 3px; padding: 1px 7px; }
    /* La fecha no queda pegada al borde inferior de la etiqueta. */
    .pie     { margin-top: 9px; margin-bottom: 6px; font-size: 11px;
               font-weight: 500; color: #333; }
</style>
