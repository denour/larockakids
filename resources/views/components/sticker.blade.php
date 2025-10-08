@props(['kid', 'contact'])

<div class="sticker" style="
    width: 62mm;
    padding: 12px;
    font-family: Arial, sans-serif;
    font-size: 12px;
    box-sizing: border-box;
    color: black;
    background: white;
">
    <div class="nombre" style="
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 6px;
        text-align: center;
        color: black;
    ">
        {{ $kid->first_name }} {{ $kid->last_name }}
    </div>

    <div class="detalle" style="margin-bottom: 4px; color: black;">
        <strong>Edad:</strong> {{ $kid->age }} años
    </div>

    <div class="detalle" style="margin-bottom: 4px; color: black;">
        <strong>Responsable:</strong> {{ $contact->first_name }} {{ $contact->last_name }}
    </div>

    <div class="detalle" style="margin-bottom: 4px; color: black;">
        <strong>Fecha:</strong> {{ now()->format('d/m/Y') }}
    </div>

    <div class="detalle" style="margin-bottom: 4px; color: black;">
        <strong>Hora:</strong> {{ now()->format('H:i') }}
    </div>

    @if($kid->allergies->isNotEmpty())
        <div class="observaciones" style="
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
            color: black;
            text-align: center;
        ">
            ⚠️ Alergias: {{ $kid->allergies->pluck('name')->join(', ') }}
        </div>
    @endif
</div>
