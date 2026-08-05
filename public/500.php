<?php
/**
 * 500 Internal Server Error Page
 * User-friendly error page for server errors
 */

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Set content type
header('Content-Type: text/html; charset=utf-8');

// Get site URL
$site_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'ummadirectory.com');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Umma Directory</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-900: #111827;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--gray-700);
        }
        
        .error-container {
            text-align: center;
            max-width: 500px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: #dc2626;
            font-size: 40px;
            font-weight: bold;
        }
        
        h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 16px;
        }
        
        p {
            font-size: 16px;
            line-height: 1.6;
            color: var(--gray-600);
            margin-bottom: 32px;
        }
        
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 15px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }
        
        .btn-secondary:hover {
            background: var(--gray-200);
        }
        
        .tech-details {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
            font-size: 13px;
            color: var(--gray-600);
        }
        
        .tech-details code {
            background: var(--gray-100);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        @media (max-width: 480px) {
            .error-container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">!</div>
        <h1>Something Went Wrong</h1>
        <p>
            We're experiencing technical difficulties on our end. 
            Our team has been notified and is working to fix this immediately.
        </p>
        
        <div class="actions">
            <a href="<?php echo $site_url; ?>" class="btn btn-primary">Go Home</a>
            <a href="javascript:location.reload()" class="btn btn-secondary">Try Again</a>
        </div>
        
        <div class="tech-details">
            <p>Error Code: <code>500 INTERNAL_SERVER_ERROR</code></p>
            <p>If this persists, please contact support with this error code.</p>
        </div>
    </div>
    
    <script>
        // Auto-retry after 30 seconds (optional)
        setTimeout(() => {
            const retryBtn = document.querySelector('.btn-secondary');
            if (retryBtn) {
                retryBtn.textContent = 'Try Again (Auto-refresh in 5s...)';
                setTimeout(() => location.reload(), 5000);
            }
        }, 25000);
    </script>
</body>
</html>
