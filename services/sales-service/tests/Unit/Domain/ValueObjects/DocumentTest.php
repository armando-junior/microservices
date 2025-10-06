<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObjects;

use PHPUnit\Framework\TestCase;
use Src\Domain\ValueObjects\Document;
use Src\Domain\Exceptions\InvalidDocumentException;

class DocumentTest extends TestCase
{
    /** @test */
    public function it_creates_valid_cpf(): void
    {
        $cpf = Document::fromString('11144477735');
        
        $this->assertInstanceOf(Document::class, $cpf);
        $this->assertEquals('11144477735', $cpf->value());
        $this->assertEquals('111.444.777-35', $cpf->formatted());
        $this->assertEquals('CPF', $cpf->type());
    }

    /** @test */
    public function it_creates_valid_cnpj(): void
    {
        $cnpj = Document::fromString('11222333000181');
        
        $this->assertInstanceOf(Document::class, $cnpj);
        $this->assertEquals('11222333000181', $cnpj->value());
        $this->assertEquals('11.222.333/0001-81', $cnpj->formatted());
        $this->assertEquals('CNPJ', $cnpj->type());
    }

    /** @test */
    public function it_accepts_cpf_with_formatting(): void
    {
        $cpf = Document::fromString('111.444.777-35');
        
        $this->assertEquals('11144477735', $cpf->value());
        $this->assertEquals('111.444.777-35', $cpf->formatted());
    }

    /** @test */
    public function it_accepts_cnpj_with_formatting(): void
    {
        $cnpj = Document::fromString('11.222.333/0001-81');
        
        $this->assertEquals('11222333000181', $cnpj->value());
        $this->assertEquals('11.222.333/0001-81', $cnpj->formatted());
    }

    /** @test */
    public function it_throws_exception_for_invalid_cpf(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('Invalid CPF');
        
        Document::fromString('12345678901');
    }

    /** @test */
    public function it_throws_exception_for_sequential_cpf(): void
    {
        $this->expectException(InvalidDocumentException::class);
        
        Document::fromString('11111111111');
    }

    /** @test */
    public function it_throws_exception_for_invalid_cnpj(): void
    {
        $this->expectException(InvalidDocumentException::class);
        $this->expectExceptionMessage('Invalid CNPJ');
        
        Document::fromString('11111111000111');
    }

    /** @test */
    public function it_throws_exception_for_empty_document(): void
    {
        $this->expectException(InvalidDocumentException::class);
        
        Document::fromString('');
    }

    /** @test */
    public function it_throws_exception_for_invalid_length(): void
    {
        $this->expectException(InvalidDocumentException::class);
        
        Document::fromString('123456');
    }

    /** @test */
    public function it_compares_equality_correctly(): void
    {
        $doc1 = Document::fromString('11144477735');
        $doc2 = Document::fromString('111.444.777-35');
        $doc3 = Document::fromString('11222333000181');
        
        $this->assertTrue($doc1->equals($doc2));
        $this->assertFalse($doc1->equals($doc3));
    }

    /** @test */
    public function it_converts_to_string(): void
    {
        $doc = Document::fromString('11144477735');
        
        $this->assertEquals('11144477735', (string) $doc);
    }
}
