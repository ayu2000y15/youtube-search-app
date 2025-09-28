<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Date; // Carbonのラッパーをインポート

class UpdateLastLoginAt
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // ログインしたユーザーを取得
        $user = $event->user;
        // last_login_atを現在の日時で更新して保存
        $user->last_login_at = Date::now();
        $user->save();
    }
}
