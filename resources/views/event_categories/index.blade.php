<x-app-layout>
    <x-slot name="header">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('spaces.index') }}"
                        class="text-sm font-medium text-gray-500 hover:underline inline-flex items-center">
                        <i class="fa-solid fa-house mr-2"></i> マイスペース
                    </a>
                </li>

                <li class="inline-flex items-center">
                    <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                        </path>
                    </svg>

                    <a href="{{ route('spaces.show', $space) }}"
                        class="text-sm font-medium text-gray-500 hover:underline inline-flex items-center">
                        <span class="text-sm font-medium text-gray-700">{{ $space->name }}</span>
                    </a>
                </li>

                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">イベントカテゴリ管理</span>
                    </div>
                </li>
            </ol>
        </nav>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                {{-- カテゴリ作成フォーム --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">新規カテゴリ作成</h3>
                        <form method="POST" action="{{ route('event-categories.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <x-input-label for="name" :value="__('カテゴリ名')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                    :value="old('name')" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="color" :value="__('カラー')" />
                                <input id="color" name="color" type="color" value="{{ old('color', '#3b82f6') }}"
                                    class="mt-1 block rounded-md border-gray-300 shadow-sm">
                                <x-input-error :messages="$errors->get('color')" class="mt-2" />
                            </div>
                            <x-primary-button>作成</x-primary-button>
                        </form>
                    </div>
                </div>

                {{-- カテゴリ一覧 --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900"
                        x-data="sortableComponent('{{ csrf_token() }}', '{{ route('event-categories.reorder') }}')"
                        x-init="initSortable()">
                        <h3 class="text-lg font-semibold mb-4">カテゴリ一覧</h3>
                        <p class="text-sm text-gray-500 mb-4">左のアイコンをドラッグして並び替えられます。</p>
                        <ul x-ref="sortablelist" class="space-y-2">
                            @foreach ($categories as $category)
                                <li data-id="{{ $category->id }}" class="border p-3 rounded-md bg-white">
                                    {{-- 表示モード --}}
                                    <div x-show="editingCategoryId !== {{ $category->id }}"
                                        class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            {{-- ドラッグハンドル --}}
                                            <div class="handle cursor-move text-gray-400 hover:text-gray-600 pr-3"
                                                title="並び替え">
                                                <i class="fa-solid fa-grip-vertical"></i>
                                            </div>
                                            <span class="w-4 h-4 rounded-full mr-3"
                                                style="background-color: {{ $category->color }};"></span>
                                            <span>{{ $category->name }}</span>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <button type="button"
                                                @click="editingCategoryId = {{ $category->id }}; $nextTick(() => $refs.editInput{{ $category->id }}.focus())"
                                                class="text-blue-500 hover:text-blue-700 text-sm">編集</button>
                                            <form method="POST" action="{{ route('event-categories.destroy', $category) }}"
                                                onsubmit="return confirm('このカテゴリを使用しているイベントは「カテゴリなし」になります。本当に削除しますか？');">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-500 hover:text-red-700 text-sm">削除</button>
                                            </form>
                                        </div>
                                    </div>
                                    {{-- 編集モード --}}
                                    <div x-show="editingCategoryId === {{ $category->id }}" x-cloak>
                                        <form method="POST" action="{{ route('event-categories.update', $category) }}"
                                            class="space-y-3">
                                            @csrf
                                            @method('PUT')
                                            <div class="flex items-center space-x-2">
                                                <input type="color" name="color" value="{{ $category->color }}"
                                                    class="w-10 h-10 p-1 block rounded-md border-gray-300 shadow-sm">
                                                <x-text-input x-ref="editInput{{ $category->id }}" name="name" type="text"
                                                    class="flex-grow" value="{{ $category->name }}" required />
                                            </div>
                                            <div class="flex justify-end space-x-2">
                                                <x-secondary-button type="button"
                                                    @click="editingCategoryId = null">キャンセル</x-secondary-button>
                                                <x-primary-button>保存</x-primary-button>
                                            </div>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Alpine.jsのコンポーネントとして編集機能とドラッグ＆ドロップ機能をまとめる
        function sortableComponent(csrfToken, reorderUrl) {
            return {
                editingCategoryId: null, // 編集中のカテゴリIDを管理
                initSortable() {
                    const el = this.$refs.sortablelist;
                    new Sortable(el, {
                        animation: 150,
                        handle: '.handle', // ドラッグの対象となるハンドル
                        onEnd: (evt) => {
                            // 並び順が変わった後のIDの配列を取得
                            const ids = Array.from(el.children).map(item => item.dataset.id);
                            this.saveOrder(ids);
                        },
                    });
                },
                saveOrder(ids) {
                    fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ ids: ids }),
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                console.log('Order saved successfully');
                            }
                        })
                        .catch(error => {
                            console.error('Error saving order:', error);
                            alert('並び順の保存に失敗しました。');
                        });
                }
            }
        }
    </script>
</x-app-layout>