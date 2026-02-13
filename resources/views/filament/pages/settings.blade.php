<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save Settings
            </x-filament::button>
        </div>
    </form>

    @if($currentLogo = \App\Models\Setting::get('site_logo'))
        <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <h3 class="text-lg font-medium mb-4">Current Logo Preview</h3>
            <img src="{{ \App\Models\Setting::getLogoUrl() }}" 
                 alt="Current Logo" 
                 class="max-h-32 rounded shadow">
        </div>
    @endif
</x-filament-panels::page>
