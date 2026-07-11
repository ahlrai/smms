@php
    $platform = null;

    if ($this->socialAccountId) {
        $platform = \App\Models\SocialAccount::find($this->socialAccountId)?->platform;
    }
@endphp

<x-filament-widgets::widget>
    <x-filament::section>

        <x-slot name="heading">
            Platform Performance Analytics
        </x-slot>
        
        <div class="mb-6">
            {{ $this->form }}
        </div>
        <div class="grid grid-cols-1 gap-6">
            @if(!$platform || $platform === 'facebook')
            {{-- FACEBOOK --}}
            <div class="rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-bold mb-4">
                    Facebook Analytics
                </h2>
                <div class="grid grid-cols-2 gap-4">

                    <div class="rounded-lg bg-gray-800 p-4">
                        <div class="text-sm text-gray-400">Likes</div>
                        <div class="text-2xl font-bold">
                            {{ number_format($facebook['likes']) }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-800 p-4">
                        <div class="text-sm text-gray-400">Comments</div>
                        <div class="text-2xl font-bold">
                            {{ number_format($facebook['comments']) }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-800 p-4">
                        <div class="text-sm text-gray-400">Shares</div>
                        <div class="text-2xl font-bold">
                            {{ number_format($facebook['shares']) }}
                        </div>
                    </div>

                </div>
            </div>
            @endif
            
            {{-- INSTAGRAM --}}
            <div class="grid grid-cols-1 gap-6">
            @if(!$platform || $platform === 'instagram')   
            <div class="rounded-xl border border-gray-700 p-6">
            <h2 class="text-lg font-bold mb-4">
                    Instagram Analytics
                </h2>
                <div class="grid grid-cols-2 gap-4">

                    <div class="rounded-lg bg-gray-800 p-4">
                        <div class="text-sm text-gray-400">Likes</div>
                        <div class="text-2xl font-bold">
                            {{ number_format($instagram['likes']) }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-800 p-4">
                        <div class="text-sm text-gray-400">Comments</div>
                        <div class="text-2xl font-bold">
                            {{ number_format($instagram['comments']) }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-gray-800 p-4">
                        <div class="text-sm text-gray-400">Shares</div>
                        <div class="text-2xl font-bold">
                            {{ number_format($instagram['shares']) }}
                        </div>
                    </div>
                    </div>
                    </div>
                </div>
                    
            </div>

        </div>
    @endif

    </x-filament::section>
</x-filament-widgets::widget>