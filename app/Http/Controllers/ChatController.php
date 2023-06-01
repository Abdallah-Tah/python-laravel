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
    protected $fastApiUrl;

    public function __construct()
    {
        $this->fastApiUrl = env('FAST_API_URL');
    }

    public function index()
    {
        $chats = Chat::all();
        return view('chat', ['chats' => $chats]);
    }

    public function processPrompt(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'file' => 'required|mimes:pdf,csv',
            'question' => 'required|string',
        ]);

        $file = $request->file('file');
        $question = $request->input('question');

        if ($file->getClientOriginalExtension() != 'pdf') {
            $this->processPromptCsv($file, $question);
        } else {
            $this->processPromptPdf($file, $question);
        }

        return redirect()->route('chat.index')->with('success', 'Chat saved successfully');
    }


    /**
     * Process a prompt Pdf using the FastAPI endpoint
     *
     * @param Request $request
     * @return void
     */
    public function processPromptPdf($pdf = null, $question = null)
    {
        $file = $pdf;
        $question = $question;

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

        $response = Http::attach(
            'file',
            $fileContent,
            $file_name
        )->timeout(60)->post($this->fastApiUrl . '/pdf', [
            'prompt' => $question,
        ]);

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

    /**
     * Process a prompt CSV using the FastAPI endpoint
     *
     * @param Request $request
     * @return void
     */
    public function processPromptCsv($CSV = null, $question = null)
    {
        $file = $CSV;
        $question = $question;

        if (strpos($file->getClientOriginalName(), ' ') !== false) {
            $file_name = str_replace(' ', '_', $file->getClientOriginalName());
        } else {
            $file_name = $file->getClientOriginalName();
        }

        if ($file->getClientOriginalExtension() != 'csv') {
            return redirect()->route('chat.index')->with('error', 'Only CSV files are allowed');
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

        $response = Http::attach(
            'file',
            $fileContent,
            $file_name
        )->timeout(60)->post($this->fastApiUrl . '/csv', [
            'prompt' => $question,
        ]);

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
            $chat->response = json_encode($responseData['table']);
            $chat->search_results = json_encode($responseData['search_results']);
            $chat->save();
        }

        return redirect()->route('chat.index')->with('success', 'Chat saved successfully');
    }
}
