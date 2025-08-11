# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Essential Commands

### Development
- `composer dev` - Start full development environment (server, queue, logs, vite)
- `php artisan serve` - Start Laravel development server
- `npm run dev` - Start Vite development server for assets
- `php artisan queue:listen --tries=1` - Process background jobs
- `php artisan pail --timeout=0` - View real-time logs

### Testing & Quality
- `composer test` - Run all tests (clears config first)
- `php artisan test` - Run PHPUnit tests directly
- `composer format` - Format code using Laravel Pint
- `composer format-test` - Check code formatting without applying changes
- `./vendor/bin/pint` - Run Pint directly

### Database
- `php artisan migrate` - Run database migrations
- `php artisan migrate:fresh --seed` - Fresh migration with seeders

### Daily Digest
- `php artisan digest:send` - Send daily digest to all users with books
- `php artisan digest:send --user-id=123` - Send digest to specific user

## Architecture Overview

### Core Application Structure
This is a Laravel-based book digest application that sends personalized daily email digests featuring quotes and insights from users' reading lists.

**Key Models:**
- `User` - Laravel Breeze authentication with book relationships
- `Book` - Contains title, author, description, publication_year, genre with rich query scopes
- Many-to-many relationship via `user_books` pivot table with `read_at` timestamp

**Services Architecture:**
- `GeminiService` - Handles AI content generation via Google's Gemini API
  - Quote generation with fallback system
  - Book information lookup and autocomplete
  - Four digest section types: snippets, cross-book connections, quotes to ponder, reflections
- `QuoteService` - Orchestrates daily digest generation for users

### Email System
- `DailyBookDigest` mailable class sends rich HTML emails
- Console command `SendDailyDigest` processes all users or specific user
- Template located at `resources/views/emails/daily-digest.blade.php`

### Frontend Stack
- Laravel Breeze with Alpine.js and Tailwind CSS
- Vite for asset compilation
- Responsive design with dark mode support

### AI Integration Details
The `GeminiService` includes:
- Comprehensive error handling and logging
- Fallback quotes for offline/API failure scenarios  
- Safety settings and content filtering
- Random seed injection for content variety
- Structured prompts for consistent quality

### Database Schema
- SQLite for development (database.sqlite)
- Key migrations: users, books, user_books pivot, cache, jobs, personal_access_tokens
- Book model includes computed attributes: display_name, is_classic, short_description

### Testing
- PHPUnit with separate Unit and Feature test suites
- Comprehensive test coverage for models, services, and controllers
- In-memory SQLite for fast test execution

### Code Quality
- Laravel Pint for code formatting (PSR-12 based)
- Ordered imports by length
- No unused imports enforcement

## Important Notes

- The application uses Gemini API with graceful fallbacks when API is unavailable
- All AI content generation includes extensive logging for debugging
- Book model has automatic trimming of title/author fields on create/update
- Daily digest command can target specific users or all users with books
- Email templates support both fallback and AI-generated content sections