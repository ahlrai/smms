<x-filament-panels::page>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Current Configuration --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">

            <div class="mb-5">
                <h2 class="text-lg font-bold">
                    Current Configuration
                </h2>

                <p class="text-sm text-gray-500">
                    Active Meta API configuration used by the system.
                </p>
            </div>

            <div class="space-y-4">

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Facebook App ID
                    </span>

                    <span class="font-medium">
                        {{ \App\Models\Setting::get('facebook_app_id', '-') }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Graph Version
                    </span>

                    <span
                        class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-600">
                        {{ \App\Models\Setting::get('facebook_graph_version', 'v22.0') }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Facebook Callback
                    </span>

                    <span class="max-w-[250px] truncate text-right font-medium">
                        {{ \App\Models\Setting::get('facebook_callback_url', '-') }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Instagram Callback
                    </span>

                    <span class="max-w-[250px] truncate text-right font-medium">
                        {{ \App\Models\Setting::get('instagram_callback_url', '-') }}
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">
                        Verify Token
                    </span>

                    <span class="font-medium">
                        {{ \App\Models\Setting::get('meta_verify_token', '-') }}
                    </span>
                </div>

            </div>
        </div>

        {{-- Edit Configuration --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">

            <div class="mb-5">
                <h2 class="text-lg font-bold">
                    Edit Configuration
                </h2>

                <p class="text-sm text-gray-500">
                    Update Meta API credentials and callback URLs.
                </p>
            </div>

            {{ $this->form }}

        </div>

    </div>

</x-filament-panels::page>