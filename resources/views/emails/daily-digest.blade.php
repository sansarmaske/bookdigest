<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Daily Book Digest</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #f0f4ff 0%, #ffffff 50%, #f0fdfa 100%);
            min-height: 100vh;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .header {
            text-align: center;
            position: relative;
            padding-bottom: 30px;
            margin-bottom: 40px;
        }
        .header::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 100px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            opacity: 0.1;
            border-radius: 50px;
            filter: blur(20px);
        }
        .header h1 {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            position: relative;
        }
        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 2px;
        }
        .date {
            color: #6b7280;
            font-size: 16px;
            margin-top: 8px;
            font-weight: 500;
        }
        .greeting {
            font-size: 20px;
            margin-bottom: 30px;
            color: #374151;
            text-align: center;
        }
        .inspirational-quote {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            position: relative;
        }
        .inspirational-quote::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 60px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            opacity: 0.05;
            border-radius: 30px;
            filter: blur(15px);
        }
        .inspirational-quote p {
            font-style: italic;
            color: #4b5563;
            font-size: 18px;
            margin: 0 0 10px 0;
            line-height: 1.6;
        }
        .inspirational-quote .author {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }
        .quote-section {
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-left: 6px solid #6366f1;
            border-radius: 0 20px 20px 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            position: relative;
        }
        .quote-section::before {
            content: '';
            position: absolute;
            top: -10px;
            right: -10px;
            width: 100px;
            height: 50px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            opacity: 0.05;
            border-radius: 25px;
            filter: blur(15px);
        }
        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        .section-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .section-title {
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        .snippet-item {
            margin-bottom: 25px;
            padding-bottom: 25px;
        }
        .snippet-item:not(:last-child) {
            border-bottom: 2px solid #f3f4f6;
        }
        .book-info {
            display: inline-block;
            background: linear-gradient(135deg, #eff6ff, #f0f9ff);
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid #dbeafe;
        }
        .book-info .book-title {
            font-style: italic;
            font-weight: 600;
            color: #1d4ed8;
        }
        .book-info .book-author {
            color: #3730a3;
            font-weight: 500;
        }
        .book-info-text {
            font-size: 14px;
            font-weight: 500;
            color: #1e40af;
        }
        .quote-content {
            font-size: 18px;
            line-height: 1.7;
            color: #374151;
            margin-bottom: 15px;
            white-space: pre-line;
            font-weight: 400;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #f3f4f6;
            color: #6b7280;
            font-size: 14px;
        }
        .footer p {
            margin: 10px 0;
        }
        .footer .main-text {
            font-size: 16px;
            color: #4b5563;
        }
        .footer .emoji {
            font-size: 24px;
        }
        .inspirational-message {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f0fdfa, #ecfdf5);
            border-radius: 20px;
            border: 1px solid #a7f3d0;
            position: relative;
        }
        .inspirational-message::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 60px;
            background: linear-gradient(135deg, #10b981, #059669);
            opacity: 0.05;
            border-radius: 30px;
            filter: blur(15px);
        }
        .inspirational-message p {
            font-style: italic;
            color: #047857;
            font-size: 18px;
            margin: 0;
            font-weight: 500;
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

        <div class="inspirational-quote">
            <p>"A reader lives a thousand lives before he dies. The man who never reads lives only one."</p>
            <div class="author">— George R.R. Martin</div>
        </div>

        @if(empty($quotes) && empty($todaysSnippet))
            <div style="text-align: center; padding: 40px; background: linear-gradient(135deg, #fef3cd, #fde68a); border-radius: 20px; border: 1px solid #f59e0b;">
                <p style="color: #92400e; font-size: 18px; margin: 0;">We couldn't generate any quotes today. Please make sure you have books added to your reading list!</p>
            </div>
        @else
            <!-- Today's Snippet Section -->
            <div class="quote-section">
                <div class="section-header">
                    <div class="section-icon">📖</div>
                    <h2 class="section-title">Today's Snippets</h2>
                </div>

                @if(isset($todaysSnippet) && is_array($todaysSnippet))
                    @foreach($todaysSnippet as $snippet)
                        <div class="snippet-item">
                            <div class="book-info">
                                <span class="book-info-text">From </span>
                                <span class="book-title">"{{ $snippet['book']->title }}"</span>
                                <span class="book-info-text"> by </span>
                                <span class="book-author">{{ $snippet['book']->author }}</span>
                            </div>
                            <div class="quote-content">{{ $snippet['quote_content'] }}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

        <div class="inspirational-message">
            <p>Let these words inspire your day and fuel your passion for reading! 📖✨</p>
        </div>

        <div class="footer">
            <p class="main-text">This digest was generated with love by your personal book quote system.</p>
            <p><span class="emoji">Happy reading! 📚</span></p>
        </div>
    </div>
</body>
</html>
