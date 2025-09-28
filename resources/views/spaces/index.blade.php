<x-app-layout>
    <x-slot name="header">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li aria-current="page">
                    <div class="flex items-center">
                        <i class="fa-solid fa-home mr-2 text-gray-500"></i>
                        <span class="text-sm font-medium text-gray-500">
                            マイスペース
                        </span>
                    </div>
                </li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-end mb-4">
                        {{-- 新規作成ボタンをコンポーネントに変更 --}}
                        <x-button-link-primary href="{{ route('spaces.create') }}">
                            <i class="fa-solid fa-plus mr-2"></i>
                            <span>新規作成</span>
                        </x-button-link-primary>
                    </div>

                    <ul class="space-y-4">
                        @forelse ($spaces as $space)
                            <li class="border p-4 rounded-lg flex justify-between items-center">
                                <div>
                                    <h3 class="text-lg font-bold">{{ $space->name }}</h3>
                                    <p class="text-sm text-gray-600">
                                        公開範囲:
                                        @if ($space->visibility === 0) 自分のみ
                                        @elseif ($space->visibility === 1) 限定公開
                                        @else 全体公開
                                        @endif
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    {{-- 編集ボタンをコンポーネントに変更 --}}
                                    <x-button-link-secondary href="{{ route('spaces.edit', $space) }}">
                                        <i class="fa-solid fa-pencil mr-2"></i>
                                        <span>編集</span>
                                    </x-button-link-secondary>

                                    {{-- 削除ボタンをコンポーネントに変更 --}}
                                    <form action="{{ route('spaces.destroy', $space) }}" method="POST"
                                        onsubmit="return confirm('本当に削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button-danger>
                                            <i class="fa-solid fa-trash mr-2"></i>
                                            <span>削除</span>
                                        </x-button-danger>
                                    </form>
                                </div>
                                <div class="mt-4 pt-4 border-t">
                                    <x-button-link-primary href="{{ route('spaces.channels.index', $space) }}"
                                        class="bg-gray-600 hover:bg-gray-800">
                                        <i class="fa-brands fa-youtube mr-2"></i>
                                        <span>チャンネル管理</span>
                                    </x-button-link-primary>
                                </div>
                            </li>
                        @empty
                            <p>まだスペースが作成されていません。</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>