<?php

namespace Tests\Unit\Models;

use App\Models\StudentMedicalDocument;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;


class StudentMedicalDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_can_create_student_medical_document()
    {
        $student = Student::factory()->create();

        $document = StudentMedicalDocument::create([
            'student_id' => $student->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/path/to/file.pdf',
            'file_name' => 'medical_certificate.pdf',
            'expiry_date' => now()->addYear(),
            'renewal_frequency' => 'annual',
            'notes' => 'Test document notes',
            'is_active' => true
        ]);

        $this->assertInstanceOf(StudentMedicalDocument::class, $document);
        $this->assertEquals($student->id, $document->student_id);
        $this->assertEquals('medical_certificate', $document->document_type);
        $this->assertEquals('/path/to/file.pdf', $document->file_path);
        $this->assertTrue($document->is_active);
    }

    #[Test]
    public function test_belongs_to_student()
    {
        $student = Student::factory()->create();
        $document = StudentMedicalDocument::factory()->create([
            'student_id' => $student->id
        ]);

        $this->assertInstanceOf(Student::class, $document->student);
        $this->assertEquals($student->id, $document->student->id);
    }

    #[Test]
    public function test_is_expired_returns_true_for_expired_document()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => now()->subDays(10),
            'is_active' => true
        ]);

        $this->assertTrue($document->isExpired());
    }

    #[Test]
    public function test_is_expired_returns_false_for_valid_document()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => now()->addDays(30),
            'is_active' => true
        ]);

        $this->assertFalse($document->isExpired());
    }

    #[Test]
    public function test_is_expired_returns_false_for_document_without_expiry()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => null,
            'is_active' => true
        ]);

        $this->assertFalse($document->isExpired());
    }

    #[Test]
    public function test_expires_soon_returns_true_for_document_expiring_within_30_days()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => now()->addDays(15),
            'is_active' => true
        ]);

        $this->assertTrue($document->expiresSoon());
    }

    #[Test]
    public function test_expires_soon_returns_false_for_document_expiring_after_30_days()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => now()->addDays(45),
            'is_active' => true
        ]);

        $this->assertFalse($document->expiresSoon());
    }

    #[Test]
    public function test_expires_soon_returns_false_for_expired_document()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => now()->subDays(10),
            'is_active' => true
        ]);

        $this->assertFalse($document->expiresSoon());
    }

    #[Test]
    public function test_expires_soon_returns_false_for_document_without_expiry()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => null,
            'is_active' => true
        ]);

        $this->assertFalse($document->expiresSoon());
    }

    #[Test]
    public function test_expiry_date_casting()
    {
        $expiryDate = now()->addYear();
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'expiry_date' => $expiryDate,
            'is_active' => true
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $document->expiry_date);
        $this->assertEquals($expiryDate->format('Y-m-d'), $document->expiry_date->format('Y-m-d'));
    }

    #[Test]
    public function test_is_active_casting()
    {
        $document = StudentMedicalDocument::create([
            'student_id' => Student::factory()->create()->id,
            'document_type' => 'medical_certificate',
            'file_path' => '/documents/medical_cert_123.pdf',
            'file_name' => 'medical_cert_123.pdf',
            'is_active' => true
        ]);

        $this->assertIsBool($document->is_active);
        $this->assertTrue($document->is_active);
    }

    #[Test]
    public function test_fillable_attributes()
    {
        $document = new StudentMedicalDocument();
        $fillable = $document->getFillable();

        $expectedFillable = [
            'student_id',
            'document_type',
            'file_path',
            'file_name',
            'expiry_date',
            'renewal_frequency',
            'notes',
            'is_active'
        ];

        $this->assertEquals($expectedFillable, $fillable);
    }

    #[Test]
    public function test_casts()
    {
        $document = new StudentMedicalDocument();
        $casts = $document->getCasts();

        $this->assertArrayHasKey('expiry_date', $casts);
        $this->assertArrayHasKey('is_active', $casts);
    }
}
