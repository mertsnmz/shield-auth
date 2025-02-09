<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shield Auth - Secure Authentication System</title>
    <link rel="icon" type="image/png" href="{{ asset('shield-icon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #0f172a;
            font-family: 'Poppins', sans-serif;
            color: #ffffff;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 1200px;
            width: 100%;
        }
        .title {
            font-size: 50px;
            font-weight: 600;
            opacity: 0;
            animation: fadeIn 1s ease-in-out forwards;
            background: linear-gradient(45deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            font-size: 24px;
            opacity: 0;
            animation: fadeIn 1s ease-in-out 0.5s forwards;
            color: #94a3b8;
            margin-top: 1rem;
        }
        .features {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 2rem;
            opacity: 0;
            animation: fadeIn 1s ease-in-out 1s forwards;
            flex-wrap: wrap;
        }
        .feature {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 8px;
            width: 180px;
            transition: transform 0.3s ease;
        }
        .feature:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 24px;
            margin-bottom: 0.5rem;
        }
        .feature-text {
            font-size: 14px;
            color: #94a3b8;
        }
        .docs-section {
            margin-top: 3rem;
            opacity: 0;
            animation: fadeIn 1s ease-in-out 1.5s forwards;
        }
        .docs-title {
            font-size: 24px;
            color: #60a5fa;
            margin-bottom: 1rem;
        }
        .docs-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .docs-link {
            background: rgba(59, 130, 246, 0.1);
            padding: 1rem 2rem;
            border-radius: 8px;
            color: #60a5fa;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        .docs-link:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }
        .docs-link-icon {
            margin-right: 0.5rem;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .spinner {
            margin: 20px auto;
            border: 4px solid rgba(59, 130, 246, 0.2);
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            position: relative;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .success-check {
            display: none;
            font-size: 60px;
            color: #3b82f6;
            animation: fadeIn 0.5s ease-in-out forwards;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="title">Shield Auth</div>
    <div class="subtitle">Enterprise-Grade Authentication & Authorization System</div>

    <div class="features">
        <div class="feature">
            <div class="feature-icon">🔐</div>
            <div class="feature-text">Secure Session Management</div>
        </div>
        <div class="feature">
            <div class="feature-icon">📱</div>
            <div class="feature-text">Two-Factor Authentication</div>
        </div>
        <div class="feature">
            <div class="feature-icon">🔑</div>
            <div class="feature-text">OAuth2 Integration</div>
        </div>
        <div class="feature">
            <div class="feature-icon">🛡️</div>
            <div class="feature-text">Advanced Security Features</div>
        </div>
    </div>

    <div class="docs-section">
        <div class="docs-title">API Documentation</div>
        <div class="docs-links">
            <a href="/docs/" class="docs-link">
                <span class="docs-link-icon">📚</span>
                Interactive Docs
            </a>
            <a href="/docs/collection.json" class="docs-link">
                <span class="docs-link-icon">🔄</span>
                Postman Collection
            </a>
            <a href="/docs/openapi.yaml" class="docs-link">
                <span class="docs-link-icon">📋</span>
                OpenAPI Spec
            </a>
        </div>
    </div>

    <!-- Spinner -->
    <div id="loader" class="spinner"></div>

    <!-- Success Check Icon -->
    <div id="success" class="success-check">&#10004;</div>
</div>

<script>
    setTimeout(() => {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('success').style.display = 'block';
    }, 2000);
</script>
</body>
</html>
