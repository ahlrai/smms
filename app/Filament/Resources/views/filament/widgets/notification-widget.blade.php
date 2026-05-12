<x-filament::widget>

    <x-filament::card>

        <h2 class="text-lg font-bold mb-4">
            Notifications
        </h2>

        @php
            $notifications = auth()
                ->user()
                ->notifications()
                ->latest()
                ->take(10)
                ->get();
        @endphp

        @forelse($notifications as $notif)

            <div class="border rounded p-3 mb-3">

                <div class="font-semibold">
                    {{ $notif->data['title'] }}
                </div>

                <div class="text-sm text-gray-600">
                    {{ $notif->data['message'] }}
                </div>

                <div class="text-xs text-gray-400 mt-1">
                    {{ $notif->created_at->diffForHumans() }}
                </div>

            </div>

        @empty

            <p>
                Tidak ada notifikasi
            </p>

        @endforelse

    </x-filament::card>

</x-filament::widget>