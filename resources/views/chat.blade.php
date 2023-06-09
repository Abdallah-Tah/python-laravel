<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chat') }}
        </h2>
    </x-slot>

    <div class="p-6 bg-white rounded-lg shadow overflow-hidden mt-6">
        <div class="mt-4 space-y-6">
            @foreach ($chats as $chat)
                <div class="flex flex-col space-y-2">
                    <div class="flex items-start text-sm justify-end mb-4">
                        <div class="ml-3 bg-blue-500 rounded-lg p-3 text-white">
                            <p class="font-semibold">
                                User
                            </p>
                            <p>{{ $chat->question }}</p>
                        </div>
                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-blue-500 text-white ml-3">
                            <span>U</span>
                        </div>
                    </div>
                    <div class="flex items-start text-sm mt-4">
                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-gray-500 text-white">
                            <span>A</span>
                        </div>
                        <div class="ml-3 bg-gray-200 rounded-lg p-3 text-gray-700">
                            <p class="font-semibold">
                                Assistant
                            </p>
                            <p>{{ $chat->response }}</p>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 ml-14">
                        {{ $chat->created_at->diffForHumans() }}
                    </div>
                </div>
            @endforeach
        </div>
        <form action="{{ route('process.chat') }}" method="POST" class="mt-6">
            @csrf
            <div class="flex space-x-4 items-center">
                <div class="flex-1">
                    <x-input-label for="question" class="sr-only" />
                    <x-text-input type="text" name="question" id="question" value="{{ old('question') }}"
                        class="border-gray-300 rounded-lg w-full p-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        placeholder="Type your question here" />
                    @error('question')
                        <div class="text-red-500 mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-primary-button type="submit" class="px-4 py-2">Send</x-primary-button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
