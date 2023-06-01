<x-app-layout>
    <x-slot name="header">
        {{ __('Chat') }}
    </x-slot>

    <div class="p-4 bg-white rounded-lg shadow-xs">

        <form action="{{ route('process.prompt') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mt-4">
                <x-input-label for="file name" label="File name" />
                <x-text-input id="file" class="block mt-1 w-full" type="file" name="file" :value="old('file')"
                    required autofocus />
            </div>

            <div class="mt-4">
                <x-input-label for="question" label="Question" />
                <x-text-input type="text" name="question" id="question" :value="old('question')" required autofocus />
                @error('question')
                    <div>{{ $message }}</div>
                @enderror
            </div>

            <div class="mt-4">
                <x-primary-button type="submit" class="btn btn-primary">Send</x-primary-button>
            </div>
        </form>



        <div class="mt-4 flex flex-col space-y-4 items-start justify-start w-full">
            @foreach($chats as $chat)
            <h2>Question: {{ $chat->question }}</h2>
            <table class="table">
                <thead>
                    <tr>
                        @foreach($chat->responseArray['columns'] as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($chat->responseArray['data'] as $row)
                        <tr>
                            @foreach($row as $value)
                                <td>{{ $value }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
        
        </div>




    </div>
</x-app-layout>
