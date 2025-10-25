<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Lesson;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Payment $payment
    ) {}

    public function handle(): void
    {
        // Vérifier si une facture n'existe pas déjà
        if ($this->payment->invoice()->exists()) {
            return;
        }

        $lesson = $this->payment->lesson;
        $student = $lesson->student;

        // Générer un numéro de facture unique
        $invoiceNumber = 'INV-' . Carbon::now()->format('Y') . '-' . str_pad($this->payment->id, 6, '0', STR_PAD_LEFT);

        // Créer la facture
        Invoice::create([
            'payment_id' => $this->payment->id,
            'student_id' => $student->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $this->payment->amount,
            'tax_amount' => $this->payment->amount * 0.21, // TVA 21%
            'total_amount' => $this->payment->amount * 1.21,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'paid',
            'items' => [
                [
                    'description' => "Cours de {$lesson->courseType->name}",
                    'date' => $lesson->start_time->format('d/m/Y'),
                    'duration' => $lesson->duration . ' minutes',
                    'location' => $lesson->location->name,
                    'teacher' => $lesson->teacher->user->profile->full_name,
                    'unit_price' => $this->payment->amount,
                    'quantity' => 1,
                    'total' => $this->payment->amount,
                ]
            ],
            'customer_info' => [
                'name' => $student->user->profile->full_name,
                'email' => $student->user->email,
                'address' => $student->user->profile->address,
                'phone' => $student->user->profile->phone,
            ],
        ]);
    }
}
