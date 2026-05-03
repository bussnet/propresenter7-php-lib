<?php

declare(strict_types=1);

namespace ProPresenter\Parser;

class CommunicationDevice
{
    /** @var array<string, mixed> */
    private array $fields;

    /**
     * @param array<string, mixed>|null $fields
     */
    public function __construct(?array $fields = null)
    {
        $this->fields = $fields ?? [];
    }

    public function getId(): string
    {
        return (string) ($this->fields['id'] ?? '');
    }

    public function setId(string $id): self
    {
        $this->fields['id'] = $id;

        return $this;
    }

    public function getName(): string
    {
        return (string) ($this->fields['name'] ?? '');
    }

    public function setName(string $name): self
    {
        $this->fields['name'] = $name;

        return $this;
    }

    public function getType(): string
    {
        return (string) ($this->fields['type'] ?? '');
    }

    public function setType(string $type): self
    {
        $this->fields['type'] = $type;

        return $this;
    }

    public function getAddress(): string
    {
        return (string) ($this->fields['address'] ?? '');
    }

    public function setAddress(string $address): self
    {
        $this->fields['address'] = $address;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
