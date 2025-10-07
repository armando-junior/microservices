<?php

declare(strict_types=1);

namespace Tests\Integration\Application\UseCases;

use Tests\IntegrationTestCase;
use Src\Application\UseCases\Customer\ListCustomers\ListCustomersUseCase;
use Src\Infrastructure\Persistence\EloquentCustomerRepository;
use Src\Domain\Entities\Customer;
use Src\Domain\ValueObjects\CustomerId;
use Src\Domain\ValueObjects\CustomerName;
use Src\Domain\ValueObjects\Email;
use Src\Domain\ValueObjects\Phone;
use Src\Domain\ValueObjects\Document;

class ListCustomersUseCaseTest extends IntegrationTestCase
{
    private ListCustomersUseCase $useCase;
    private EloquentCustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new EloquentCustomerRepository();
        $this->useCase = new ListCustomersUseCase($this->repository);
    }

    /** @test */
    public function it_lists_all_customers(): void
    {
        // Create multiple customers
        $this->createCustomer('João Silva', 'joao@example.com', '11144477735');
        $this->createCustomer('Maria Santos', 'maria@example.com', '52998224725');
        $this->createCustomer('Pedro Oliveira', 'pedro@example.com', '11222333000181');

        // List all customers
        $results = $this->useCase->execute();

        $this->assertCount(3, $results);
        
        // Extract names for comparison (order may vary)
        $names = array_map(fn($customer) => $customer->name, $results);
        $this->assertContains('João Silva', $names);
        $this->assertContains('Maria Santos', $names);
        $this->assertContains('Pedro Oliveira', $names);
    }

    /** @test */
    public function it_returns_empty_array_when_no_customers(): void
    {
        $results = $this->useCase->execute();

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /** @test */
    public function it_paginates_customers(): void
    {
        // Create 5 customers with unique and VALID CPFs
        $cpfs = [
            '11144477735',  // Valid CPF
            '52998224725',  // Valid CPF
            '82178537464',  // Valid CPF
            '72186361581',  // Valid CPF
            '29676067713'   // Valid CPF
        ];
        $names = ['Customer One', 'Customer Two', 'Customer Three', 'Customer Four', 'Customer Five'];
        
        for ($i = 0; $i < 5; $i++) {
            $this->createCustomer(
                $names[$i],
                "customer" . ($i + 1) . "@example.com",
                $cpfs[$i]
            );
        }

        // Get first page (2 per page)
        $page1 = $this->useCase->execute([], 1, 2);
        $this->assertCount(2, $page1);

        // Get second page
        $page2 = $this->useCase->execute([], 2, 2);
        $this->assertCount(2, $page2);

        // Verify different customers
        $this->assertNotEquals($page1[0]->id, $page2[0]->id);
    }

    /** @test */
    public function it_counts_total_customers(): void
    {
        // Create customers
        $this->createCustomer('João Silva', 'joao@example.com', '11144477735');
        $this->createCustomer('Maria Santos', 'maria@example.com', '52998224725');

        $count = $this->useCase->count();

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_filters_customers_by_status(): void
    {
        // Create active customer
        $customer1 = $this->createCustomer('João Silva', 'joao@example.com', '11144477735');
        
        // Create and deactivate another customer
        $customer2 = $this->createCustomer('Maria Santos', 'maria@example.com', '52998224725');
        $customer2->deactivate();
        $this->repository->save($customer2);

        // Filter by active status
        $activeCustomers = $this->useCase->execute(['status' => 'active']);
        $this->assertCount(1, $activeCustomers);
        $this->assertEquals('João Silva', $activeCustomers[0]->name);
    }

    private function createCustomer(
        string $name,
        string $email,
        string $document
    ): Customer {
        $customer = Customer::create(
            id: CustomerId::generate(),
            name: CustomerName::fromString($name),
            email: Email::fromString($email),
            phone: Phone::fromString('11987654321'),
            document: Document::fromString($document)
        );

        $this->repository->save($customer);

        return $customer;
    }
}

