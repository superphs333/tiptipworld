<!DOCTYPE html>
<html>
<head>
    <title>팁 게시판</title>
</head>
<body>
    <h1>💡 팁 공유 목록</h1>
    <hr>

    @foreach ($tips as $tip)
        <div style="margin-bottom: 20px; border-bottom: 1px solid #ccc;">
            <h3>{{ $tip->title }}</h3>
            <p>{{ $tip->content }}</p>
            <small>작성자: {{ $tip->user->name }} | 작성일: {{ $tip->created_at->format('Y-m-d') }}</small>
        </div>
    @endforeach

    @if($tips->isEmpty())
        <p>등록된 팁이 없음.</p>
    @endif
</body>
</html>