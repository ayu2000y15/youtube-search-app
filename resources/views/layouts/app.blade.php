<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Scripts -->

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        {{-- ナビゲーションバーを読み込む（固定） --}}
        <div class="fixed top-0 left-0 right-0 z-40">
            @include('layouts.navigation')
        </div>

        {{-- ナビゲーションバーの高さ分だけ上部マージンを追加 --}}
        <div class="pt-16">
            <!-- Page Heading -->
            @isset($header)
                <header id="page-header"
                    class="bg-white shadow fixed top-16 left-0 right-0 z-30 transition-transform duration-300">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
                {{-- ヘッダーの高さ分だけスペースを確保 --}}
                <div id="header-spacer" class="h-24"></div>
            @endisset

            <!-- Page Content -->
            <main>
                {{-- 成功/エラーメッセージ表示 --}}
                @if (session('success'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                            role="alert">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
                            role="alert">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>

        {{-- トップに戻るボタン --}}
        <button id="scroll-to-top"
            class="fixed bottom-6 right-6 bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-full shadow-lg transition-all duration-300 opacity-0 invisible hover:scale-110 z-50"
            title="トップに戻る">
            <i class="fa-solid fa-chevron-up text-lg"></i>
        </button>

        {{-- 各ページ固有のJavaScriptを読み込むための記述を追加 --}}
        @stack('scripts')

        {{-- ヘッダーとトップに戻るボタンのJavaScript --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // URLパラメータからメッセージを取得して表示
                const urlParams = new URLSearchParams(window.location.search);
                const successMessage = urlParams.get('success');
                const errorMessage = urlParams.get('error');

                // 成功メッセージの表示
                if (successMessage) {
                    showMessage(successMessage, 'success');
                    // URLからパラメータを削除
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }

                // エラーメッセージの表示
                if (errorMessage) {
                    showMessage(errorMessage, 'error');
                    // URLからパラメータを削除
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }

                const scrollToTopButton = document.getElementById('scroll-to-top');
                const pageHeader = document.getElementById('page-header');
                const headerSpacer = document.getElementById('header-spacer');

                let lastScrollY = window.scrollY;
                let headerHeight = 0;

                // ヘッダーの高さを取得
                if (pageHeader) {
                    headerHeight = pageHeader.offsetHeight;
                    if (headerSpacer) {
                        headerSpacer.style.height = headerHeight + 'px';
                    }
                }

                // スクロール位置によってボタンの表示/非表示を制御
                function toggleScrollToTopButton() {
                    if (window.scrollY > 300) {
                        scrollToTopButton.classList.remove('opacity-0', 'invisible');
                    } else {
                        scrollToTopButton.classList.add('opacity-0', 'invisible');
                    }
                }

                // ヘッダーの表示/非表示を制御
                function togglePageHeader() {
                    if (!pageHeader) return;

                    const currentScrollY = window.scrollY;

                    // 上にスクロールしている場合、またはページトップ近くの場合は表示
                    if (currentScrollY < lastScrollY || currentScrollY < headerHeight) {
                        pageHeader.style.transform = 'translateY(0)';
                    }
                    // 下にスクロールしている場合は隠す
                    else if (currentScrollY > lastScrollY && currentScrollY > headerHeight) {
                        pageHeader.style.transform = 'translateY(-100%)';
                    }

                    lastScrollY = currentScrollY;
                }

                // スクロールイベントリスナー
                window.addEventListener('scroll', function () {
                    toggleScrollToTopButton();
                    togglePageHeader();
                });

                // ボタンクリックイベント
                if (scrollToTopButton) {
                    scrollToTopButton.addEventListener('click', function () {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    });
                }

                // 初期状態をチェック
                toggleScrollToTopButton();

                // ウィンドウリサイズ時にヘッダーの高さを再計算
                window.addEventListener('resize', function () {
                    if (pageHeader && headerSpacer) {
                        headerHeight = pageHeader.offsetHeight;
                        headerSpacer.style.height = headerHeight + 'px';
                    }
                });

                // メッセージ表示関数
                function showMessage(message, type) {
                    // 既存のメッセージを削除
                    const existingMessages = document.querySelectorAll('.flash-message');
                    existingMessages.forEach(msg => msg.remove());

                    // メインコンテンツエリアを取得
                    const main = document.querySelector('main');
                    if (!main) return;

                    // メッセージ要素を作成
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'flash-message max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6';

                    const alertDiv = document.createElement('div');
                    alertDiv.className = `px-4 py-3 rounded relative mb-4 ${type === 'success'
                        ? 'bg-green-100 border border-green-400 text-green-700'
                        : 'bg-red-100 border border-red-400 text-red-700'
                        }`;
                    alertDiv.setAttribute('role', 'alert');
                    alertDiv.textContent = message;

                    // 閉じるボタンを追加
                    const closeButton = document.createElement('span');
                    closeButton.className = 'absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer';
                    closeButton.innerHTML = '<i class="fa-solid fa-times"></i>';
                    closeButton.onclick = function () {
                        messageDiv.remove();
                    };
                    alertDiv.appendChild(closeButton);

                    messageDiv.appendChild(alertDiv);
                    main.insertBefore(messageDiv, main.firstChild);

                    // 5秒後に自動で削除
                    setTimeout(() => {
                        if (messageDiv.parentNode) {
                            messageDiv.remove();
                        }
                    }, 5000);
                }
            });
        </script>
    </div>
</body>

</html>