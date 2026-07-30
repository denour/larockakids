{{-- Da a las pantallas de autenticación el mismo aire que el kiosco de
     onboarding: fondo pastel, wordmark de Piedritas Kids y tipografía
     redondeada. Se incluye desde las vistas de auth publicadas, así que no
     toca ninguna otra pantalla del panel.

     El fondo va en position:fixed a propósito: la vista se incrusta dentro
     de la tarjeta, y con absolute se anclaría a ella en vez de a la página. --}}
<style>
    @font-face {
        font-family: 'Baloo 2';
        src: url('{{ asset('fonts/baloo2-latin.woff2') }}') format('woff2');
        font-weight: 400 800;
        font-display: swap;
    }

    body { background: #f5f6fb; }

    .pk-fondo {
        position: fixed;
        inset: 0;
        z-index: 0;
        pointer-events: none;
        overflow: hidden;
    }
    .pk-fondo span {
        position: absolute;
        border-radius: 9999px;
        display: block;
        opacity: .55;
    }
    .pk-b1 { width: 22rem; height: 22rem; top: -10rem;   left: -8rem;   background: #e4e2f8; filter: blur(8px); }
    .pk-b2 { width: 14rem; height: 14rem; top: 22%;      left: -6rem;   background: #fbe3e7; filter: blur(6px); }
    .pk-b3 { width: 8rem;  height: 8rem;  bottom: 14%;   left: 6%;      background: #e4f1e7; filter: blur(6px); }
    .pk-b4 { width: 5rem;  height: 5rem;  top: 20%;      right: 8%;     background: #fdf1d2; filter: blur(5px); }
    .pk-b5 { width: 26rem; height: 26rem; top: 48%;      right: -12rem; background: #dedcf4; filter: blur(10px); }
    .pk-b6 { width: 14rem; height: 14rem; bottom: -6rem; right: 16%;    background: #fbe3ee; filter: blur(8px); }

    .pk-puntos {
        position: absolute;
        top: 14%;
        right: 5%;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .5rem;
    }
    .pk-puntos i {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        background: #ccd4ee;
        display: block;
    }

    /* La tarjeta queda por encima del fondo y se redondea como las del kiosco. */
    .fi-simple-main {
        position: relative;
        z-index: 1;
        border-radius: 1.5rem !important;
        border: 0 !important;
        box-shadow: 0 20px 60px -20px rgba(80, 80, 140, .25) !important;
        --tw-ring-color: transparent !important;
    }

    /* El logo de piedras se sustituye por el wordmark de Kids, que es el que
       ve el papá en el kiosco. Se hace por CSS porque el <img> lo pinta
       Filament dentro de su propio header. */
    .fi-simple-header .fi-logo { display: none !important; }
    .fi-simple-header::before {
        content: '';
        display: block;
        width: 15rem;
        max-width: 100%;
        height: 5.2rem;
        margin: 0 auto .5rem;
        background: url('{{ asset('images/brand/piedritas-kids.png') }}') center / contain no-repeat;
    }

    .fi-simple-header-heading {
        font-family: 'Baloo 2', ui-rounded, system-ui, sans-serif;
        color: #20336b !important;
        font-weight: 700;
        letter-spacing: -.01em;
    }

    /* Azul del kiosco en lugar del ámbar del panel, solo en estas pantallas.
       Se redefine la rampa --primary-* de Filament en vez de pisar cada regla:
       así el botón, el enlace de contraseña y el foco del campo cambian solos. */
    .fi-simple-layout,
    .fi-simple-page {
        /* Filament escribe la rampa separada por COMAS y la usa dentro de
           rgba(var(--primary-600), opacidad). Con espacios el color queda
           inválido y el botón pierde el fondo. */
        --primary-50:  239, 243, 253;
        --primary-100: 223, 231, 250;
        --primary-200: 191, 207, 245;
        --primary-300: 150, 175, 238;
        --primary-400: 106, 140, 231;
        --primary-500: 71, 110, 222;
        --primary-600: 47, 91, 214;
        --primary-700: 40, 79, 192;
        --primary-800: 35, 66, 156;
        --primary-900: 31, 57, 124;
    }

    .fi-simple-page .fi-btn {
        border-radius: .9rem;
    }

    @media (max-width: 640px) {
        .fi-simple-header::before { width: 11rem; height: 3.8rem; }
        .pk-puntos { display: none; }
    }
</style>

<div class="pk-fondo" aria-hidden="true">
    <span class="pk-b1"></span>
    <span class="pk-b2"></span>
    <span class="pk-b3"></span>
    <span class="pk-b4"></span>
    <span class="pk-b5"></span>
    <span class="pk-b6"></span>
    <div class="pk-puntos">
        @for ($i = 0; $i < 16; $i++)
            <i></i>
        @endfor
    </div>
</div>
