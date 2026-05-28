<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SocialAuthController extends Controller
{
    protected FacebookService $facebookService;

    protected InstagramService $ig;

    public function __construct(
        FacebookService $facebookService,
        InstagramService $ig
    ) {
        $this->facebookService = $facebookService;
        $this->ig = $ig;
    }

    /*
    |--------------------------------------------------------------------------
    | FACEBOOK
    |--------------------------------------------------------------------------
    */

    public function redirectToFacebook(): RedirectResponse
    {
        return redirect(
            $this->facebookService->getOAuthUrl()
        );
    }

    public function handleFacebookCallback(
        Request $request
    ): RedirectResponse {

        Log::info('=== FACEBOOK CALLBACK ===');
        Log::info($request->all());

        if ($request->has('error')) {

            Log::error(
                'Facebook Error',
                $request->all()
            );

            return redirect('/admin/social-accounts')->with(
                'error',
                'Login Facebook dibatalkan'
            );
        }

        try {

            $shortToken =
                $this->facebookService
                ->getShortLivedToken(
                    $request->code
                );

            Log::info(
                'FB SHORT TOKEN',
                $shortToken
            );

            if (!isset($shortToken['access_token'])) {

                throw new \Exception(
                    'Access token Facebook tidak ditemukan'
                );
            }

            $longToken =
                $this->facebookService
                ->getLongLivedToken(
                    $shortToken['access_token']
                );

            Log::info(
                'FB LONG TOKEN',
                $longToken
            );

            if (!isset($longToken['access_token'])) {

                throw new \Exception(
                    'Long lived token Facebook tidak ditemukan'
                );
            }

            $pages =
                $this->facebookService
                ->getPages(
                    $longToken['access_token']
                );

            Log::info(
                'FB PAGES',
                $pages
            );

            foreach ($pages as $page) {

                SocialAccount::updateOrCreate(

                    [

                        'platform' =>
                            'facebook',

                        'account_id' =>
                            $page['id']

                    ],

                    [

                        'username' =>
                            $page['name'],

                        'access_token' =>
                            $page['access_token'],

                        'refresh_token' =>
                            $page['instagram_business_account']['id']
                            ?? null,

                        'token_expired_at' =>
                            now()->addDays(60),

                        'created_by' =>
                            auth()->id()

                    ]

                );
            }

            return redirect('/admin/social-accounts')->with(
                'success',
                'Facebook berhasil connect'
            );

        } catch (\Exception $e) {

            Log::error(
                'FACEBOOK SAVE TOKEN ERROR',
                [
                    'message' => $e->getMessage()
                ]
            );

            return redirect('/admin/social-accounts')->with(
                'error',
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSTAGRAM
    |--------------------------------------------------------------------------
    */

    public function redirectToInstagram(): RedirectResponse
    {
        return redirect(
            $this->ig->getOAuthUrl()
        );
    }

    public function handleInstagramCallback(
        Request $request
    ): RedirectResponse {

        try {

            Log::info(
                '=== CALLBACK INSTAGRAM MASUK ===',
                $request->all()
            );

            if ($request->has('error')) {

                Log::error(
                    'Instagram login gagal',
                    $request->all()
                );

                return redirect('/admin/social-accounts')->with(
                    'error',
                    'Login Instagram dibatalkan'
                );
            }

            $shortToken =
                $this->facebookService
                ->getShortLivedToken(
                    $request->code,
                    true
                );

            Log::info(
                'SHORT TOKEN',
                $shortToken
            );

            if (!isset($shortToken['access_token'])) {

                throw new \Exception(
                    'Access token Instagram tidak ditemukan'
                );
            }

            $longToken =
                $this->facebookService
                ->getLongLivedToken(
                    $shortToken['access_token']
                );

            Log::info(
                'LONG TOKEN',
                $longToken
            );

            if (!isset($longToken['access_token'])) {

                throw new \Exception(
                    'Long lived token Instagram tidak ditemukan'
                );
            }

            $pages =
                $this->facebookService
                ->getPages(
                    $longToken['access_token']
                );

            Log::info(
                'IG PAGES',
                $pages
            );

            foreach ($pages as $page) {

                SocialAccount::updateOrCreate(

                    [

                        'platform' =>
                            'facebook',

                        'account_id' =>
                            $page['id']

                    ],

                    [

                        'username' =>
                            $page['name'],

                        'access_token' =>
                            $page['access_token'],

                        'refresh_token' =>
                            $page['instagram_business_account']['id']
                            ?? null,

                        'token_expired_at' =>
                            now()->addDays(60),

                        'created_by' =>
                            auth()->id()

                    ]

                );
            }

            return redirect(
                '/admin/social-accounts'
            )->with(

                'success',

                'Instagram berhasil terhubung'

            );

        } catch (\Exception $e) {

            Log::error(
                $e->getMessage()
            );

            return back()
                ->withErrors(
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DISCONNECT
    |--------------------------------------------------------------------------
    */

    public function disconnect(
        int $id
    ): RedirectResponse {

        $account = SocialAccount::findOrFail($id);

        if (
            $account->created_by !== auth()->id()
        ) {

            return back()->with(
                'error',
                'Tidak punya akses'
            );
        }

        $account->delete();

        return back()->with(
            'success',
            'Akun diputus'
        );
    }
}