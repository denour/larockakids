<div class="flex flex-wrap gap-1">
    @foreach($allergies as $allergy)
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: {{ $allergy->color }}; color: white;">
            {{ $allergy->name }}
        </span>
    @endforeach
</div> 