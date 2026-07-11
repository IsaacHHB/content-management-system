<!doctype html><html lang="en"><body>
<p><strong>From:</strong> {{ $submission->name }} ({{ $submission->email }})</p>
@if($submission->phone)<p><strong>Phone:</strong> {{ $submission->phone }}</p>@endif
<p><strong>Subject:</strong> {{ $submission->subject }}</p>
<p>{!! nl2br(e($submission->message)) !!}</p>
</body></html>
