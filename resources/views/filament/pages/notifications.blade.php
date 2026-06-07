<x-filament-panels::page>

    <div class="space-y-6">

        {{-- HEADER --}}
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm"
        >
            <div class="flex items-center justify-between">

                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Notifications
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Reminder jadwal posting, publikasi berhasil, dan publikasi gagal.
                    </p>
                </div>

                <div class="flex items-center gap-3">

                    <div
                        class="px-4 py-2 rounded-xl bg-primary-50 text-primary-600 text-sm font-semibold"
                    >
                        {{ $this->notifications->count() }} Notifikasi
                    </div>

                    @if($unreadCount > 0)

                        <button
                            wire:click="markAllAsRead"
                            class="px-4 py-2 text-sm rounded-xl bg-primary-600 text-white hover:bg-primary-700 transition"
                        >
                            Tandai Semua Dibaca
                        </button>

                    @endif

                </div>

            </div>
        </div>

        {{-- LIST NOTIFICATION --}}
        @forelse($this->notifications as $notification)

            @php

                $type = $notification->type ?? 'info';

                $badgeColor = match ($type) {
                    'success' => 'bg-green-100 text-green-700',
                    'danger' => 'bg-red-100 text-red-700',
                    'warning' => 'bg-yellow-100 text-yellow-700',
                    default => 'bg-blue-100 text-blue-700',
                };

                $iconBg = match ($type) {
                    'success' => 'bg-green-100',
                    'danger' => 'bg-red-100',
                    'warning' => 'bg-yellow-100',
                    default => 'bg-blue-100',
                };

                $iconColor = match ($type) {
                    'success' => 'text-green-600',
                    'danger' => 'text-red-600',
                    'warning' => 'text-yellow-600',
                    default => 'text-blue-600',
                };

                $icon = match ($type) {
                    'success' => 'heroicon-o-check-circle',
                    'danger' => 'heroicon-o-x-circle',
                    'warning' => 'heroicon-o-clock',
                    default => 'heroicon-o-bell',
                };

                $statusText = match ($type) {
                    'success' => 'Berhasil',
                    'danger' => 'Gagal',
                    'warning' => 'Reminder',
                    default => 'Info',
                };

            @endphp

            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
            >

                <div class="p-6">

                    <div class="flex gap-4">

                        {{-- ICON --}}
                        <div
                            class="w-12 h-12 rounded-full flex items-center justify-center {{ $iconBg }}"
                        >
                            <x-filament::icon
                                :icon="$icon"
                                class="w-6 h-6 {{ $iconColor }}"
                            />
                        </div>

                        {{-- CONTENT --}}
                        <div class="flex-1">

                            <div class="flex justify-between items-start">

                                <div>

                                    <div class="flex items-center gap-2">

                                        <h2
                                            class="font-semibold text-lg text-gray-900 dark:text-white"
                                        >
                                            {{ $notification->title }}
                                        </h2>

                                        @if(is_null($notification->read_at))
                                            <span
                                                class="w-2.5 h-2.5 rounded-full bg-primary-500"
                                            ></span>
                                        @endif

                                    </div>

                                    <p
                                        class="text-sm text-gray-500 mt-1"
                                    >
                                        {{ $notification->created_at->format('d M Y • H:i') }}
                                    </p>

                                </div>

                                <span
                                    class="px-3 py-1 rounded-full text-xs font-semibold {{ $badgeColor }}"
                                >
                                    {{ $statusText }}
                                </span>

                            </div>

                            {{-- MESSAGE --}}
                            <div
                                class="mt-4 text-sm text-gray-700 dark:text-gray-300 leading-relaxed"
                            >
                                {{ $notification->message }}
                            </div>

                            {{-- INFO KONTEN --}}
                            @if($notification->post_title)

                                <div
                                    class="mt-4 grid md:grid-cols-2 gap-4"
                                >

                                    <div
                                        class="rounded-xl border border-gray-200 dark:border-gray-700 p-4"
                                    >
                                        <div
                                            class="text-xs uppercase text-gray-500"
                                        >
                                            Konten
                                        </div>

                                        <div
                                            class="mt-1 font-medium text-gray-900 dark:text-white"
                                        >
                                            {{ $notification->post_title }}
                                        </div>
                                    </div>

                                    @if($notification->platform)

                                        <div
                                            class="rounded-xl border border-gray-200 dark:border-gray-700 p-4"
                                        >
                                            <div
                                                class="text-xs uppercase text-gray-500"
                                            >
                                                Platform
                                            </div>

                                            <div
                                                class="mt-1 font-medium text-gray-900 dark:text-white"
                                            >
                                                {{ ucfirst($notification->platform) }}
                                            </div>
                                        </div>

                                    @endif

                                </div>

                            @endif

                            {{-- URL POST --}}
                            @if($notification->post_url)

                                <div class="mt-4">

                                    <a
                                        href="{{ $notification->post_url }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-medium text-sm"
                                    >
                                        <span>🔗</span>
                                        <span>Lihat Postingan</span>
                                    </a>

                                </div>

                            @endif

                            {{-- FOOTER --}}
                            <div
                                class="mt-5 flex flex-wrap items-center gap-2"
                            >

                                @if($notification->status)

                                    <span
                                        class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs"
                                    >
                                        Status:
                                        {{ ucfirst($notification->status) }}
                                    </span>

                                @endif

                                <span
                                    class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs"
                                >
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>

                                @if(is_null($notification->read_at))

                                    <button
                                        wire:click="markAsRead({{ $notification->id }})"
                                        class="ml-auto text-primary-600 hover:text-primary-700 text-sm font-medium"
                                    >
                                        Tandai Dibaca
                                    </button>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        @empty

            <div
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm"
            >

                <div class="py-16 text-center">

                    <div class="text-6xl mb-4">
                        🔔
                    </div>

                    <h2
                        class="text-xl font-semibold text-gray-900 dark:text-white"
                    >
                        Belum Ada Notifikasi
                    </h2>

                    <p
                        class="text-gray-500 mt-2"
                    >
                        Notifikasi reminder dan publikasi akan muncul di sini.
                    </p>

                </div>

            </div>

        @endforelse

    </div>

</x-filament-panels::page>