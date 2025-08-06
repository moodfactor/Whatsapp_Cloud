<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WhatsApp Developer Playground</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1>WhatsApp Developer Playground</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {!! session('success') !!}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {!! session('error') !!}
        </div>
    @endif

    <form action="{{ route('whatsapp.playground.send') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="recipient">Recipient Phone Number</label>
            <input type="text" name="recipient" id="recipient" class="form-control" placeholder="+1234567890" required>
        </div>
        <div class="form-group">
            <label for="message">Message Text</label>
            <textarea name="message" id="message" class="form-control" rows="3" placeholder="Enter your test message" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Test Message</button>
    </form>
</div>
</body>
</html>
