<div class="space-y-5">

    <div class="rounded-2xl border p-4 bg-white shadow">

        <div class="font-bold text-lg mb-2">
            Facebook Preview
        </div>

        <div class="border rounded-xl p-4">

            <div class="font-semibold">
                {{ $getRecord()?->socialAccount?->username ?? 'facebook.user' }}
            </div>

            <div class="text-sm text-gray-500 mb-3">
                Facebook
            </div>

            <div class="font-bold mb-2">
                {{ $getState()['title'] ?? '' }}
            </div>

            <div class="text-sm whitespace-pre-line">
                {{ $getState()['facebook_caption'] ?? '' }}
            </div>

        </div>

    </div>

    <div class="rounded-2xl border p-4 bg-white shadow">

        <div class="font-bold text-lg mb-2">
            Instagram Preview
        </div>

        <div class="border rounded-xl p-4">

            <div class="font-semibold">
                {{ $getRecord()?->socialAccount?->username ?? 'instagram.user' }}
            </div>

            <div class="text-sm text-pink-500 mb-3">
                Instagram
            </div>

            <div class="font-bold mb-2">
                {{ $getState()['title'] ?? '' }}
            </div>

            <div class="text-sm whitespace-pre-line">
                {{ $getState()['instagram_caption'] ?? '' }}
            </div>

        </div>

    </div>

</div>