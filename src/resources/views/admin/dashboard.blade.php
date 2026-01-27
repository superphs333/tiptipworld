<x-app-layout>
    @php
        $headerTitle = match (true) {
            request()->is('admin/users*') => 'User',
            request()->is('admin/categories*') => 'Categories',
            request()->is('admin/tags*') => 'Tags',
            request()->is('admin/tips*') => 'Tips',
            default => 'User',
        };
    @endphp

    <x-slot name="header">
        {{ $headerTitle }}
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    관리자 대시보드에 오신 것을 환영합니다.
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
