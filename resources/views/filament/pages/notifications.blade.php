<x-filament-panels::page>

    <div class="space-y-4">

        {{-- HEADER --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">
                Notifications
            </h1>

            <div class="text-sm text-gray-400">
                {{ $this->notifications->count() }} Notifications
            </div>
        </div>

        {{-- LIST NOTIFICATION --}}
        @forelse ($this->notifications as $notification)

            @php
                $type = $notification->data['type'] ?? 'info';

                $color = match($type) {
                    'success' => 'success',
                    'error' => 'danger',
                    'warning' => 'warning',
                    default => 'info',
                };

                $icon = match($type) {
                    'success' => 'heroicon-o-check-circle',
                    'error' => 'heroicon-o-x-circle',
                    'warning' => 'heroicon-o-clock',
                    default => 'heroicon-o-bell',
                };
            @endphp

            <x-filament::section>

                <div class="flex items-start gap-4">

                    {{-- ICON --}}
                    <div>
                        <x-filament::icon
                            :icon="$icon"
                            class="w-8 h-8 text-{{ $color }}-500"
                        />
                    </div>

                    {{-- CONTENT --}}
                    <div class="flex-1">

                        {{-- TITLE --}}
                        <div class="flex items-center justify-between">

                            <h2 class="font-bold text-lg">
                                {{ $notification->data['title'] ?? 'Notification' }}
                            </h2>

                            <span class="text-sm text-gray-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>

                        </div>

                        {{-- MESSAGE --}}
                        <div class="mt-2 text-sm text-gray-300">
                            {{ $notification->data['message'] ?? '-' }}
                        </div>

                        {{-- META --}}
                        <div class="mt-3 flex gap-2 flex-wrap">

                            <span class="fi-badge fi-color-primary">
                                {{ ucfirst($notification->data['platform'] ?? '-') }}
                            </span>

                            <span class="fi-badge fi-color-gray">
                                {{ $notification->created_at->format('d M Y H:i') }}
                            </span>

                        </div>

                    </div>

                </div>

            </x-filament::section>

        @empty

            <x-filament::section>

                <div class="text-center py-10">

                    <div class="text-5xl mb-3">
                        🔔
                    </div>

                    <h2 class="text-lg font-bold">
                        Belum Ada Notifikasi
                    </h2>

                    <p class="text-gray-500 mt-1">
                        Notifikasi posting akan muncul di sini.
                    </p>

                </div>

            </x-filament::section>

        @endforelse

    </div>

</x-filament-panels::page>