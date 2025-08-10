<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Daily Book Digest</title>
    <style>
        body {
            font-family: Georgia, serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4a5568;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2d3748;
            margin: 0;
            font-size: 28px;
        }
        .date {
            color: #718096;
            font-size: 14px;
            margin-top: 5px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 30px;
            color: #4a5568;
        }
        .quote-section {
            margin-bottom: 35px;
            padding: 25px;
            background-color: #f7fafc;
            border-left: 4px solid #4299e1;
            border-radius: 0 8px 8px 0;
        }
        .book-info {
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .book-title {
            font-style: italic;
            color: #4a5568;
        }
        .quote-content {
            font-size: 15px;
            line-height: 1.7;
            color: #2d3748;
            margin-bottom: 10px;
            white-space: pre-line;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
            font-size: 13px;
        }
        .footer a {
            color: #4299e1;
            text-decoration: none;
        }
        .inspirational-message {
            text-align: center;
            font-style: italic;
            color: #4a5568;
            margin: 30px 0;
            padding: 20px;
            background-color: #edf2f7;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Your Daily Book Digest</h1>
            <div class="date">{{ $date }}</div>
        </div>

        <div class="greeting">
            Good morning, {{ $user->name }}! ☀️
        </div>

        <div class="inspirational-message">
            "A reader lives a thousand lives before he dies. The man who never reads lives only one." - George R.R. Martin
        </div>

        @if(empty($quotes))
            <p>We couldn't generate any quotes today. Please make sure you have books added to your reading list!</p>
        @else
            @foreach($quotes as $quoteData)
                <div class="quote-section">
                    <div class="book-info">
                        From <span class="book-title">"{{ $quoteData['book']->title }}"</span> by {{ $quoteData['book']->author }}
                    </div>
                    <div class="quote-content">{{ $quoteData['quote_content'] }}</div>
                </div>
            @endforeach
        @endif

        <div class="inspirational-message">
            Let these words inspire your day and fuel your passion for reading! 📖✨
        </div>

        <div class="footer">
            <p>This digest was generated with love by your personal book quote system.</p>
            <p>Happy reading! 📚</p>
        </div>
    </div>
</body>
</html>
