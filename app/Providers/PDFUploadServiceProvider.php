<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\ChatBotPDFController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;



class PDFUploadServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->checkAndUploadPDF();
    }

    protected function checkAndUploadPDF(){

        $pdfFilename = 'mediaintelligence_book.pdf';
        $pdfPath = storage_path('app/' . $pdfFilename);

        if(file_exists($pdfPath)){
             $controller = new ChatBotPDFController();
             $request = new \Illuminate\Http\Request(['pdf_filename' => $pdfFilename]);
             $response = $controller->uploadPDFToChatAPI($request);
 
             if ($response->getStatusCode() == 200) 
             {
                 $responseBody = $response->getData();
                 if (isset($responseBody->sourceId)) {
                    $sourceId = $responseBody->sourceId;
    
                    Cache::put('pdf_upload_response', $sourceId, now()->addYear());
                }
             }
        }
    }
}
