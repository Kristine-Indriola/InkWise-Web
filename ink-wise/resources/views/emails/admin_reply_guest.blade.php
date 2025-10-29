<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reply from InkWise</title>
</head>
<body>
  <p>Hi{{ $original->name ? ' ' . $original->name : '' }},</p>

  <p>We received your message:</p>
  <blockquote>{{ $original->message }}</blockquote>

  <p>Our reply:</p>
  <blockquote>{{ $replyText }}</blockquote>

  <p>Regards,<br>InkWise Team</p>
</body>
</html>
