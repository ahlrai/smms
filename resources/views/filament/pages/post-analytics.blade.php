<x-filament-panels::page>

    <div class="space-y-6">

        {{-- HEADER --}}
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm"
        >
            <div class="flex items-center justify-between">

                <div>
                    <h1 class="text-2xl font-bold">
                        Post Analytics
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Statistik performa setiap postingan berdasarkan data analytics.
                    </p>
                </div>

                <div class="flex gap-3">

                    <select
                        wire:model.live="platform"
                        class="rounded-xl border-gray-300 dark:border-gray-700"
                    >
                        <option value="">Semua Platform</option>
                        <option value="instagram">Instagram</option>
                        <option value="facebook">Facebook</option>
                    </select>

                    <select
                        wire:model.live="sortBy"
                        class="rounded-xl border-gray-300 dark:border-gray-700"
                    >
                        <option value="reach">Reach</option>
                        <option value="impressions">Impressions</option>
                        <option value="likes">Likes</option>
                        <option value="comments">Comments</option>
                    </select>

                </div>

            </div>
        </div>

        {{-- TABLE --}}
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
        >

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead
                        class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
                    >
                        <tr>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">
                                Post
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Platform
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Reach
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Impressions
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Likes
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Comments
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Shares
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Engagement
                            </th>

                            <th class="px-4 py-4 text-center text-xs font-semibold uppercase">
                                Link
                            </th>

                        </tr>
                    </thead>

                    <tbody>

                        @forelse($this->getAnalytics() as $item)

                            @php
                                $engagement =
                                    $item->likes +
                                    $item->comments +
                                    $item->shares;
                            @endphp

                            <tr
                                class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition"
                            >

                                <td class="px-6 py-4">

                                    <div class="font-medium">
                                        {{ $item->post?->title ?? 'Untitled Post' }}
                                    </div>

                                    <div
                                        class="text-xs text-gray-500 mt-1"
                                    >
                                        {{ Str::limit($item->post?->caption, 60) }}
                                    </div>

                                </td>

                                <td class="px-4 py-4 text-center">

                                    @if($item->platform === 'facebook')
                                        <span
                                            class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold"
                                        >
                                            Facebook
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 rounded-full bg-pink-100 text-pink-700 text-xs font-semibold"
                                        >
                                            Instagram
                                        </span>
                                    @endif

                                </td>

                                <td class="px-4 py-4 text-center font-semibold">
                                    {{ number_format($item->reach) }}
                                </td>

                                <td class="px-4 py-4 text-center font-semibold">
                                    {{ number_format($item->impressions) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    {{ number_format($item->likes) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    {{ number_format($item->comments) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    {{ number_format($item->shares) }}
                                </td>

                                <td class="px-4 py-4 text-center">

                                    <span
                                        class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold"
                                    >
                                        {{ number_format($engagement) }}
                                    </span>

                                </td>

                                <td class="px-4 py-4 text-center">

                                    @if($item->post?->post_url)
                                        <a
                                            href="{{ $item->post->post_url }}"
                                            target="_blank"
                                            class="text-primary-600 hover:text-primary-700 font-medium text-sm"
                                        >
                                            View
                                        </a>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center py-10 text-gray-500"
                                >
                                    Belum ada data analytics.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-filament-panels::page>