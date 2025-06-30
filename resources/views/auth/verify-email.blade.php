<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Required</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }

        .container {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            background-color: #f9f9f9;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Email Verification Required</h1>

        @if (session('message'))
            <div style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                {{ session('message') }}
            </div>
        @endif

        <p>Please check your email and click the verification link to continue.</p>
        <p>If you didn't receive the email, you can request a new one.</p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn">Resend Verification Email</button>
        </form>

        <a href="/" class="btn">Go to App</a>
    </div>
</body>

</html>
