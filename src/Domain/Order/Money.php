<?php
declare(strict_types=1);

namespace App\Domain\Order;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        private int $minor,
        private string $currency = 'RUB',
    ) {
        if ($minor < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
        if ($currency === '') {
            throw new InvalidArgumentException('Currency is required.');
        }
    }

    public static function rubles(float $rubles): self
    {
        return new self((int) round($rubles * 100), 'RUB');
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->minor + $other->minor, $this->currency);
    }

    public function multiply(int $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Factor cannot be negative.');
        }
        return new self($this->minor * $factor, $this->currency);
    }

    public function minor(): int
    {
        return $this->minor;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function formatted(): string
    {
        return number_format($this->minor / 100, 2, '.', ' ') . ' ' . $this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currencies must match.');
        }
    }
}
