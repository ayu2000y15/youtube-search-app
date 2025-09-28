{{-- 編集など、セカンダリアクションのリンク用ボタン --}}
<a {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-bold text-sm text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</a>