<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Kalender Konten</x-slot>

        {{-- Navigation --}}
        <div class="flex items-center justify-between mb-4">
            <button wire:click="previousMonth"
                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-800 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                ← Sebelumnya
            </button>
            <span class="font-semibold text-gray-700 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($currentMonth . '-01')->translatedFormat('F Y') }}
            </span>
            <button wire:click="nextMonth"
                class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-800 rounded hover:bg-gray-200 dark:hover:bg-gray-700">
                Berikutnya →
            </button>
        </div>

        {{-- Day headers --}}
        <div class="grid grid-cols-7 gap-1 mb-2">
            @foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $day)
                <div class="text-center text-xs font-semibold text-gray-500 py-1">{{ $day }}</div>
            @endforeach
        </div>

        {{-- Calendar grid --}}
        @php
            $startOfMonth = \Carbon\Carbon::parse($currentMonth . '-01');
            $daysInMonth  = $startOfMonth->daysInMonth;
            $startDay     = $startOfMonth->dayOfWeek;
            $posts        = collect($this->getPostsForMonth())->groupBy('date');
            $today        = \Carbon\Carbon::today()->format('Y-m-d');
        @endphp

        <div class="grid grid-cols-7 gap-1">
            {{-- Empty cells before start --}}
            @for ($i = 0; $i < $startDay; $i++)
                <div class="h-20 rounded bg-gray-50 dark:bg-gray-800/30"></div>
            @endfor

            {{-- Day cells --}}
            @for ($day = 1; $day <= $daysInMonth; $day++)
                @php
                    $dateStr  = $startOfMonth->copy()->day($day)->format('Y-m-d');
                    $dayPosts = $posts->get($dateStr, collect());
                    $isToday  = $dateStr === $today;
                @endphp
                <div class="h-20 rounded p-1 border text-xs overflow-hidden
                    {{ $isToday
                        ? 'border-teal-500 bg-teal-50 dark:bg-teal-900/20'
                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }}">

                    <div class="font-bold mb-1 {{ $isToday ? 'text-teal-600' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ $day }}
                    </div>

                    @foreach ($dayPosts->take(2) as $post)
                        <div class="truncate rounded px-1 py-0.5 mb-0.5 text-white text-[10px]
                            {{ $post['status'] === 'published'
                                ? 'bg-green-500'
                                : ($post['status'] === 'scheduled' ? 'bg-amber-500' : 'bg-gray-400') }}">
                            {{ $post['caption'] }}
                        </div>
                    @endforeach

                    @if ($dayPosts->count() > 2)
                        <div class="text-gray-400 text-[10px]">+{{ $dayPosts->count() - 2 }} lagi</div>
                    @endif
                </div>
            @endfor
        </div>

        {{-- Legend --}}
        <div class="flex gap-4 mt-4 text-xs text-gray-500">
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded bg-green-500 inline-block"></span> Published
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded bg-amber-500 inline-block"></span> Scheduled
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded bg-gray-400 inline-block"></span> Draft
            </span>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>