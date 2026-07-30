{{-- Decorative pastel scenery, purely visual. The mockups use crisp low-saturation
     circles over an almost-white page rather than a blurred wash, so the blur here is
     deliberately small: enough to soften the edge, not enough to smear the shapes. --}}
<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <div class="absolute -left-24 top-[24%] h-56 w-56 rounded-full bg-[#fbe3e7] blur-[6px]"></div>
    <div class="absolute left-[9%] top-[52%] h-14 w-14 rounded-full bg-[#dfe4f7] blur-[4px]"></div>
    <div class="absolute bottom-[14%] left-[3%] h-32 w-32 rounded-full bg-[#e4f1e7] blur-[6px]"></div>
    <div class="absolute -left-32 -top-40 h-[22rem] w-[22rem] rounded-full bg-[#e4e2f8] blur-[8px]"></div>

    <div class="absolute right-[7%] top-[22%] h-20 w-20 rounded-full bg-[#fdf1d2] blur-[5px]"></div>
    <div class="absolute -right-40 top-[54%] h-[26rem] w-[26rem] rounded-full bg-[#dedcf4] blur-[10px]"></div>
    <div class="absolute -bottom-32 right-[18%] h-56 w-56 rounded-full bg-[#fbe3ee] blur-[8px]"></div>

    {{-- The dotted 4x4 grid that sits in the right margin of every mockup. --}}
    <div class="absolute right-[4%] top-[16%] grid grid-cols-4 gap-2">
        @for ($i = 0; $i < 16; $i++)
            <span class="block h-1.5 w-1.5 rounded-full bg-[#ccd4ee]"></span>
        @endfor
    </div>

    <span class="absolute left-[11%] top-[19%] text-2xl text-[#f2c94c]">✦</span>
    <span class="absolute right-[11%] top-[50%] text-xl text-[#f5a8c5]">✦</span>
    <span class="absolute bottom-[18%] left-[7%] text-lg text-[#b9a6ef]">★</span>
</div>
