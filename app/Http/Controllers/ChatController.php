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

        $isRunning = $this->checkPythonScript();

        if($isRunning == false) {
            $this->runPythonScript();
        }else {
            return redirect()->route('chat.index')->with('error', 'A process is already running');
        }

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

        // dd($file_name, $this->fastApiUrl);
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
        )->timeout(60)->post($this->fastApiUrl . '/process/pdf/', [
            'prompt' => $question,
        ]);
        // dd($response);
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
        )->timeout(60)->post($this->fastApiUrl . '/process/csv/', [
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
            Log::info('Response Data:', ['data' => $responseData]);

            $chat = new Chat;
            $chat->question = $question;
            $chat->file_name = $file->getClientOriginalName();
            //[2023-06-02 13:29:14] local.INFO: Response Data: {"data":{"response":"The dataframe contains 1034 rows and 6 columns. The columns are Name (object), Team (object), Position (object), Height (int64), Weight (int64), and Age (float64). The summary statistics of each column are count, mean, standard deviation, minimum, 25th percentile, 50th percentile, 75th percentile, and maximum."}} 

            $chat->response = $responseData['response'];
            //Undefined array key "search_results"
            $chat->search_results = json_encode($responseData ?? []);
            $chat->save();
        }

        return redirect()->route('chat.index')->with('success', 'Chat saved successfully');
    }

    /**
     * Run a python script before processing a prompt
     *
     * @param Request $request
     * @return void
     */
    public function runPythonScript()
    {
        try {
            $processName = 'python_script.py';
            $command = "pgrep -f $processName";
            exec($command, $output, $result);

            if ($result == 0) {
                // The script is running.
            } else {
                // The script is not running, so run it
                $command = 'python ' . base_path() . '/python/app.py';
                exec($command);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * check if a python script is running or not
     *
     * @param Request $request
     * @return void
     */
    public function checkPythonScript()
    {
        try {
            $processName = 'python_script.py';
            $command = "pgrep -f $processName";
            exec($command, $output, $result);

            if ($result == 0) {
                // The script is running.
                return true;
            } else {
                // The script is not running, so run it
                return false;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
