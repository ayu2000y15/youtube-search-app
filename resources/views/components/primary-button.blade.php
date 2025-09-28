{{-- フォームの実行など、プライマリアクションのボタン --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-bold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>