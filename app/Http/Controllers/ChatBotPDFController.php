<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class ChatBotPDFController extends Controller
{
    /**
     * Récupère un fichier PDF stocké localement et l'envoie à l'endpoint de l'API.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function uploadPDFToChatAPI(Request $request)
    {
        // Valider l'entrée (facultatif)
        $validated = $request->validate([
            'pdf_filename' => 'required|string',
        ]);

        // Récupérer le nom du fichier PDF depuis la requête
        $pdfFilename = $validated['pdf_filename'];

        // Définir le chemin du fichier PDF stocké localement
        $pdfPath = storage_path('app/' .$pdfFilename);

        // Vérifier si le fichier existe
        if (!file_exists($pdfPath)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Créer un client HTTP
        $client = new Client();

        try {
            // Faire une requête POST à l'API avec le fichier PDF
            $response = $client->request('POST', 'https://api.chatpdf.com/v1/sources/add-file', [
                'headers' => [
                    'x-api-key' => env('CHATPDF_API_KEY'),
                ],
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($pdfPath, 'r'),
                        'filename' => basename($pdfPath),
                    ],
                ],
                'verify' => false,
            ]);

            // Récupérer la réponse de l'API
            $responseBody = json_decode($response->getBody(), true);

            return response()->json($responseBody, $response->getStatusCode());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


        /**
     * Envoyer un message et obtenir une réponse de l'API ChatPDF.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function fetchChatPDFResponse(Request $request)
    {
        $cachedData = Cache::get('pdf_upload_response');
        
        // Pas besoin d'accéder à stdClass, car $cachedData est une chaîne
        $srcId = $cachedData ?? null;
        
        if (!$srcId) {
            return response()->json(['error' => 'Aucune donnée trouvée dans le cache.'], 404);
        }

        // Valider l'entrée
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        // Récupérer le message depuis la requête
        $message = $validated['message'];

        // Définir l'API key et l'URL de l'API
        $apiKey = env('CHATPDF_API_KEY');
        $apiUrl = 'https://api.chatpdf.com/v1/chats/message';

        // Créer le paramètre de la requête
        $loginParam = [
            'sourceId' => $srcId,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $message,
                ],
            ],
        ];

        // Créer un client HTTP
        $client = new Client();

        try {
            // Faire une requête POST à l'API avec les paramètres
            $response = $client->post($apiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $apiKey,
                ],
                'json' => $loginParam,
                'verify' => false,
            ]);

            // Récupérer la réponse de l'API
            $responseBody = json_decode($response->getBody(), true);

            return response()->json(['content' => $responseBody['content']], $response->getStatusCode());
        } catch (\Exception $e) {
            Log::error('An error occurred in uploadPDFToChatAPI.', [
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
