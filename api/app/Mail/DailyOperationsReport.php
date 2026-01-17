<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyOperationsReport extends Mailable
{
    use Queueable, SerializesModels;

    public $date;
    public $summary;
    public $issues;
    public $inspectionLogs;
    public $maintenance;
    public $excelData;

    /**
     * Create a new message instance.
     */
    public function __construct($date, $summary, $issues, $inspectionLogs, $maintenance, $excelData = null)
    {
        $this->date = $date;
        $this->summary = $summary;
        $this->issues = $issues;
        $this->inspectionLogs = $inspectionLogs;
        $this->maintenance = $maintenance;
        $this->excelData = $excelData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DAILY REPORT] Isotank Operations - ' . $this->date,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.daily_report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->excelData) {
            return [
                \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $this->excelData, 'DailyReport_' . str_replace([' ', ','], '_', $this->date) . '.xlsx')
                    ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ];
        }
        return [];
    }
}
