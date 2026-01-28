<x-app-layout>
    <x-slot name="header">
        {{ $headerTitle }}
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @include($tabView)
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
