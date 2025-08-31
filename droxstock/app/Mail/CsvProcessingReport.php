<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * CSV Processing Report Email
 * 
 * Sends detailed processing results to users after CSV upload completion
 */
class CsvProcessingReport extends Mailable
{
    use Queueable, SerializesModels;

    public array $processingResults;
    public string $userName;
    public string $fileName;

    /**
     * Create a new message instance.
     */
    public function __construct(array $processingResults, string $userName, string $fileName)
    {
        $this->processingResults = $processingResults;
        $this->userName = $userName;
        $this->fileName = $fileName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->processingResults['success'] ? 'Success' : 'Failed';
        
        return new Envelope(
            subject: "CSV Processing Report: {$this->fileName} - {$status}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.csv-processing-report',
            with: [
                'processingResults' => $this->processingResults,
                'userName' => $this->userName,
                'fileName' => $this->fileName,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
