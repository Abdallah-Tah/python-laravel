<x-app-layout>
    <x-slot name="header">
        {{ __('Dashboard') }}
    </x-slot>

    <div class="p-4 bg-white rounded-lg shadow-xs">
        <div class="card-body">
            {{-- Your code to display $data goes here. --}}
            {{-- This is just an example and depends on the structure of your data. --}}
            <div>Answer: {{ $data['answer'] }}</div>
            <div>Images:
                @foreach ($data['images'] as $image)
                    <img src="{{ $image }}">
                @endforeach
            </div>
            <div>Code: {{ $data['code'] }}</div>
        </div>
    </div>
    </div>
</x-app-layout>
