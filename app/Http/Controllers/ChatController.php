<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Chat::all();
        return view('chat', ['chats' => $chats]);
    }


    public function processPrompt(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'file' => 'required|mimes:pdf',
            'question' => 'required|string',
        ]);

        $file = $request->file('file');
        $question = $request->input('question');

         if (strpos($file->getClientOriginalName(), ' ') !== false) {
            $file_name = str_replace(' ', '_', $file->getClientOriginalName());
        } else {
            $file_name = $file->getClientOriginalName();
        }

        if ($file->getClientOriginalExtension() != 'pdf') {
            return redirect()->route('chat.index')->with('error', 'Only PDF files are allowed');
        }
        

        // Move the uploaded file to a temporary directory
        $filePath = $file->storeAs('uploads', $file_name, 'public');
        // $fileContent = file_get_contents(storage_path('app/public/' . $filePath));

        Log::info('Sending request', [
            'file' => $file_name,
            'prompt' => $question,
        ]);


        // Send the file and question to the FastAPI endpoint
        $fileContent = file_get_contents($file->path());
        // dd($fileContent);
        $response = Http::attach(
            'file',
            $fileContent,
            $file_name
        )->timeout(60)->post('http://localhost:8002/process_prompt', [
            'prompt' => $question,
        ]);

        // dd($response->json());


        // Delete the temporary file
        Storage::delete($filePath);



        // Handle the response from the FastAPI endpoint
        if ($response->failed()) {
            // Handle the error response
            throw new Exception('Failed to process prompt');
        }

        $responseData = $response->json();

        if ($response->successful()) {
            $responseData = $response->json();

            $chat = new Chat;
            $chat->question = $question;
            $chat->file_name = $file->getClientOriginalName();
            $chat->response = $responseData['response'];
            $chat->search_results = json_encode($responseData['search_results']);
            $chat->save();
        }

        return redirect()->route('chat.index')->with('success', 'Chat saved successfully');
    }




    // public function heavy(Request $request)
    // {
    //     $file = $request->file('file');

    //     $messages = [
    //         [
    //             "role" => "system",
    //             "content" => "You are a helpful assistant."
    //         ],
    //         [
    //             "role" => "user",
    //             "content" => "How many players are there in the dataset?"
    //         ]
    //     ];

    //     $response = Http::attach(
    //         'file',
    //         file_get_contents($file),
    //         'mlb_players.csv'
    //     )->post('http://127.0.0.1:8098/heavy' . $request->path(), [
    //         'columnData' => json_encode([
    //             'Name' => 'string',
    //             'Team' => 'string',
    //             'Position' => 'string',
    //             'Height(inches)' => 'integer',
    //             'Weight(lbs)' => 'integer',
    //             'Age' => 'integer',
    //         ]),
    //         'messages' => json_encode($messages),
    //         'model' => 'GPT-4',
    //         'lang' => 'en',
    //         'allowLogging' => true,
    //     ]);

    //     $responseData = json_decode($response->body(), true);

    //     dd($responseData);

    //     return response()->json($responseData);
    // }

    // public function heavy(Request $request)
    // {
    //     // dd($request->all());

    //     $file = $request->file('file');
    //     $fileName = $file->getClientOriginalName();

    //     // Assuming the file should be stored in a local public disk
    //     $filePath = $file->storeAs('uploads', $fileName, 'public');

    //     // Get the file content
    //     $fileContent = file_get_contents($file->getRealPath());

    //     // Fix the formatting of the messages field
    //     $messages = ['text' => $request->input('message')];

    //     $response = Http::asMultipart()->post('http://127.0.0.1:8098/heavy', [
    //         'columnData' => json_encode([
    //             'Name' => 'string',
    //             'Team' => 'string',
    //             'Position' => 'string',
    //             'Height(inches)' => 'integer',
    //             'Weight(lbs)' => 'integer',
    //             'Age' => 'integer',
    //         ]),
    //         // Ensure the messages field is properly formatted
    //         'messages' => json_encode($messages),
    //         'model' => $request->input('model'),
    //         'lang' => 'python',
    //         'allowLogging' => true,
    //         'file' => [
    //             'name'     => $fileName,
    //             'contents' => $fileContent,
    //             'filename' => $fileName,
    //         ],
    //     ]);

    //     $responseData = json_decode($response->body(), true);

    //     dd($responseData);

    //     return response()->json($responseData);
    // }

}
