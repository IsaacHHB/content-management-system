<!doctype html>
<html lang="en">
<body>
    <p>You have been invited to the Native Dads Network CMS.</p>
    <p><a href="{{ $url }}">Accept your invitation</a></p>
    <p>This invitation expires {{ $invite->expires_at->toDayDateTimeString() }}.</p>
</body>
</html>
