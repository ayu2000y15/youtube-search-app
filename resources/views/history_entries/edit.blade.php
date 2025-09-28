{{-- resources/views/history_entries/edit.blade.php --}}
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

                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                        <a href="{{ route('spaces.show', $entry->verticalAxis->timeline->space ?? null) }}"
                            class="text-sm font-medium text-gray-500 hover:underline">{{ $entry->verticalAxis->timeline->space->name ?? 'スペース' }}</a>
                    </div>
                </li>

                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                        <a href="{{ route('timelines.show', $entry->verticalAxis->timeline ?? null) }}"
                            class="text-sm font-medium text-gray-500 hover:underline">{{ $entry->verticalAxis->timeline->name ?? '年表' }}</a>
                    </div>
                </li>

                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">項目を編集</span>
                    </div>
                </li>
            </ol>
        </nav>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 md:p-8 text-gray-900">
                    <form method="POST" action="{{ route('history-entries.update', $entry) }}">
                        @method('PUT')
                        @include('history_entries._form')

                        <div class="flex items-center justify-end mt-6 border-t pt-6">
                            <x-primary-button>
                                {{ __('更新する') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>