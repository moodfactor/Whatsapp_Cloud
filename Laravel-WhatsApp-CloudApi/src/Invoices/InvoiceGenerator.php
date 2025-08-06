<?php

namespace BiztechEG\WhatsAppCloudApi\Invoices;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class InvoiceGenerator
{
    /**
     * Generate a PDF invoice from HTML content.
     *
     * @param string $html The HTML content for the invoice.
     * @param string|null $filename Optional filename. If not provided, one will be generated.
     * @return string URL to the generated PDF file.
     */
    public static function generateInvoice(string $html, ?string $filename = null): string
    {
        // Set up Dompdf options
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Allow remote content like images
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Generate a unique filename if not provided
        if (!$filename) {
            $filename = 'invoice_' . time() . '.pdf';
        }

        // Get the generated PDF output
        $output = $dompdf->output();

        // Store the PDF file on the public disk (ensure you have run "php artisan storage:link")
        Storage::disk('public')->put($filename, $output);

        // Return the public URL to the generated file
        return Storage::disk('public')->url($filename);
    }
}
