<?php

namespace App\Contracts;

interface AIServiceInterface
{
    /**
     * Generate a quote from a book
     */
    public function generateQuote(string $bookTitle, string $author, ?string $description = ''): array;

    /**
     * Get book information based on partial title
     */
    public function getBookInfo(string $partialTitle): array;

    /**
     * Generate a snippet for today's digest
     */
    public function generateTodaysSnippet(string $bookTitle, string $author, ?string $description = ''): array;

    /**
     * Generate cross-book connections between multiple books
     */
    public function generateCrossBookConnection(array $books): array;

    /**
     * Generate a thought-provoking quote for pondering
     */
    public function generateQuoteToPonder(string $bookTitle, string $author, ?string $description = ''): array;

    /**
     * Generate today's reflection based on the user's books
     */
    public function generateTodaysReflection(array $books): array;

    /**
     * Check if the service is available and configured
     */
    public function isAvailable(): bool;

    /**
     * Get the service name/provider
     */
    public function getProviderName(): string;
}
