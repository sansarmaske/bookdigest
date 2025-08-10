# Book Digest

Book Digest is an application that sends you daily email digests featuring memorable passages from books you've read. Stay connected with literature through curated excerpts delivered to your inbox, helping you reflect on and revisit the meaningful moments from your reading journey.

## Features

- **Daily Email Digests**: Receive curated book passages directly in your inbox
- **Personal Library Management**: Track and organize books you've read
- **AI-Powered Content**: Intelligent passage selection and literary analysis
- **Customizable Preferences**: Tailor your digest frequency and content types
- **Quote Collections**: Build your personal collection of meaningful passages
- **Reading Insights**: Discover patterns and themes across your reading history

## Technology Stack

- **Backend**: Laravel PHP framework
- **Frontend**: Modern web technologies for responsive user experience
- **Database**: Efficient storage for books, quotes, and user preferences
- **Email Service**: Reliable delivery system for daily digests
- **AI Integration**: Smart content curation and analysis

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js and npm
- Database (MySQL/PostgreSQL)

### Installation

1. Clone the repository
```bash
git clone <repository-url>
cd book-digest
```

2. Install PHP dependencies
```bash
composer install
```

3. Install JavaScript dependencies
```bash
npm install
```

4. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

5. Set up database
```bash
php artisan migrate
```

6. Build assets
```bash
npm run build
```

### Development

Start the development server:
```bash
php artisan serve
```

For frontend development with hot reloading:
```bash
npm run dev
```

## Usage

1. **Add Books**: Import or manually add books to your personal library
2. **Configure Preferences**: Set your digest frequency and content preferences
3. **Receive Digests**: Get daily emails with carefully selected passages
4. **Explore Insights**: Discover connections and themes in your reading

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# Testing pre-commit hook
