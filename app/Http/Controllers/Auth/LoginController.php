<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    /**
     * Googleの認証ページにリダイレクト
     */
    public function redirectToGoogle()
    {
        try {
            Log::info('Starting Google redirect');

            $redirectUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => config('services.google.client_id'),
                'redirect_uri' => config('services.google.redirect'),
                'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/youtube.force-ssl',
                'response_type' => 'code',
                'access_type' => 'offline',
                'prompt' => 'consent',
                'state' => csrf_token() // CSRFトークンを追加してセキュリティを向上
            ]);

            Log::info('Redirect URL: ' . $redirectUrl);

            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('Google redirect failed: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Google認証の初期化に失敗しました。');
        }
    }

    /**
     * Googleからのコールバックを処理
     */
    public function handleGoogleCallback()
    {
        try {
            Log::info('Google callback started');
            Log::info('Request parameters:', request()->all());

            // エラーパラメータをチェック
            if (request()->has('error')) {
                Log::error('Google OAuth error: ' . request()->get('error'));
                return redirect('/login')->with('error', 'Google認証がキャンセルされました。');
            }

            // 認証コードが存在するかチェック
            if (!request()->has('code')) {
                Log::error('No authorization code received');
                return redirect('/login')->with('error', '認証コードが受信されませんでした。');
            }

            // 手動でOAuth処理を行う
            $code = request()->get('code');
            $tokenResponse = $this->exchangeCodeForToken($code);

            if (!$tokenResponse) {
                throw new \Exception('トークンの取得に失敗しました');
            }

            Log::info('Token exchange successful', [
                'access_token' => isset($tokenResponse['access_token']) ? 'present' : 'missing',
                'refresh_token' => isset($tokenResponse['refresh_token']) ? 'present' : 'missing'
            ]);

            // アクセストークンを使用してユーザー情報を取得
            $userInfo = $this->getUserInfo($tokenResponse['access_token']);

            if (!$userInfo) {
                throw new \Exception('ユーザー情報の取得に失敗しました');
            }

            Log::info('Google user data:', [
                'id' => $userInfo['id'] ?? 'missing',
                'name' => $userInfo['name'] ?? 'missing',
                'email' => $userInfo['email'] ?? 'missing'
            ]);

            // Google IDでユーザーを検索
            $user = User::where('google_id', $userInfo['id'])->first();

            if ($user) {
                Log::info('Existing user found, updating tokens');
                // 既存ユーザーの場合、トークンを更新
                $user->update([
                    'google_token' => $tokenResponse['access_token'] ?? null,
                    'google_refresh_token' => $tokenResponse['refresh_token'] ?? null,
                ]);
            } else {
                Log::info('Creating new user');
                // 新規ユーザーの場合、作成
                $user = User::create([
                    'name' => $userInfo['name'],
                    'email' => $userInfo['email'],
                    'google_id' => $userInfo['id'],
                    'google_token' => $tokenResponse['access_token'] ?? null,
                    'google_refresh_token' => $tokenResponse['refresh_token'] ?? null,
                    'email_verified_at' => now(), // Googleアカウントは既に検証済み
                ]);
                Log::info('New user created with ID: ' . $user->id);
            }

            Auth::login($user);
            Log::info('User logged in successfully');

            return redirect()->intended('/dashboard')->with('success', 'Googleアカウントでログイン/登録しました。');
        } catch (\Exception $e) {
            Log::error('Google callback failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Request data: ', request()->all());

            $errorMessage = 'Google認証に失敗しました。';
            if (str_contains($e->getMessage(), 'invalid_client')) {
                $errorMessage .= ' クライアント設定を確認してください。';
            } elseif (str_contains($e->getMessage(), 'redirect_uri_mismatch')) {
                $errorMessage .= ' リダイレクトURIが一致しません。';
            } else {
                $errorMessage .= ' エラー: ' . $e->getMessage();
            }

            return redirect('/login')->with('error', $errorMessage);
        }
    }

    /**
     * 認証コードをアクセストークンに交換
     */
    private function exchangeCodeForToken($code)
    {
        try {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Token exchange exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * アクセストークンを使用してユーザー情報を取得
     */
    private function getUserInfo($accessToken)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('User info fetch failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('User info fetch exception: ' . $e->getMessage());
            return null;
        }
    }
}
