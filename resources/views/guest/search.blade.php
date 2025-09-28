@extends('layouts.guest-app')

@section('content')

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Title -->
            <div class="mb-6">
                <nav class="text-sm breadcrumbs mb-2">
                    <a href="@if($space->visibility === 2)
                            {{ route('guest.space.public', $space->slug) }}
                        @else
                            {{ route('guest.space.invite', [$space->slug, $space->invite_token]) }}
                        @endif" 
                       class="text-indigo-600 hover:text-indigo-800">
                        {{ $space->name }}
                    </a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-600">検索結果</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900">
                    <i class="fa-solid fa-search mr-2"></i>検索結果
                    @if($query)
                        : "{{ $query }}"
                    @endif
                </h1>
            </div>
            @if($query)
                @if($results->count() > 0)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="mb-4 flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800">
                                    検索結果: {{ $results->total() }}件
                                </h3>
                                <div class="text-sm text-gray-500">
                                    "{{ $query }}" の検索結果
                                </div>
                            </div>

                            <div class="space-y-4">
                                @foreach($results as $dialogue)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-2">
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 mb-1">
                                                    <a href="@if($space->visibility === 2)
                                                            {{ route('guest.video.public', [$space->slug, $dialogue->video]) }}#dialogue-{{ $dialogue->id }}
                                                        @else
                                                            {{ route('guest.video.invite', [$space->slug, $space->invite_token, $dialogue->video]) }}#dialogue-{{ $dialogue->id }}
                                                        @endif" 
                                                       class="text-indigo-600 hover:text-indigo-800">
                                                        {{ $dialogue->video->title }}
                                                    </a>
                                                </h4>
                                                <p class="text-sm text-gray-600 mb-2">
                                                    <i class="fa-solid fa-tv mr-1"></i>{{ $dialogue->video->channel->name }}
                                                </p>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ floor($dialogue->timestamp / 60) }}:{{ sprintf('%02d', $dialogue->timestamp % 60) }}
                                            </div>
                                        </div>

                                        <div class="bg-gray-50 rounded p-3">
                                            @if($dialogue->speaker)
                                                <div class="font-medium text-sm text-gray-700 mb-1">
                                                    {{ $dialogue->speaker }}:
                                                </div>
                                            @endif
                                            <div class="text-gray-800">
                                                {!! str_replace($query, '<mark class="bg-yellow-200 px-1 rounded">' . $query . '</mark>', e($dialogue->dialogue)) !!}
                                            </div>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between">
                                            <a href="@if($space->visibility === 2)
                                                    {{ route('guest.video.public', [$space->slug, $dialogue->video]) }}#dialogue-{{ $dialogue->id }}
                                                @else
                                                    {{ route('guest.video.invite', [$space->slug, $space->invite_token, $dialogue->video]) }}#dialogue-{{ $dialogue->id }}
                                                @endif" 
                                               class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                                                <i class="fa-solid fa-play mr-1"></i>
                                                この場面を見る
                                            </a>
                                            <span class="text-xs text-gray-400">
                                                {{ $dialogue->video->published_at->format('Y年m月d日') }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- ページネーション -->
                            <div class="mt-6">
                                {{ $results->appends(['q' => $query])->links() }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
                                <i class="fa-solid fa-search text-gray-400 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">検索結果が見つかりませんでした</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                "{{ $query }}" に一致する文字起こしが見つかりませんでした。
                            </p>
                            <div class="text-sm text-gray-400">
                                <p>検索のコツ:</p>
                                <ul class="list-disc list-inside mt-2 space-y-1">
                                    <li>異なるキーワードで検索してみてください</li>
                                    <li>キーワードを短くしてみてください</li>
                                    <li>ひらがな・カタカナ・漢字を変えてみてください</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 mb-4">
                            <i class="fa-solid fa-search text-gray-400 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">文字起こしを検索</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            上の検索ボックスにキーワードを入力して、動画の文字起こしを検索してください。
                        </p>
                        <a href="@if($space->visibility === 2)
                                {{ route('guest.space.public', $space->slug) }}
                            @else
                                {{ route('guest.space.invite', [$space->slug, $space->invite_token]) }}
                            @endif" 
                           class="inline-flex items-center text-indigo-600 hover:text-indigo-800">
                            <i class="fa-solid fa-arrow-left mr-1"></i>
                            スペースに戻る
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection