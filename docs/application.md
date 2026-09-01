# 애플리케이션 구조

[README로 돌아가기](../README.md)

Laravel 애플리케이션 코드는 `src/` 아래에 둠.

## 요청 처리 구조

Laravel 라우터는 `src/routes/web.php`와 `src/routes/auth.php`의 정의에 따라 컨트롤러를 호출함. 컨트롤러는 모델, 서비스, Form Request, Policy, Blade 뷰와 협력해 응답을 생성함.

데이터는 MariaDB, Laravel storage, 필요한 경우 Redis 또는 외부 S3/R2 호환 스토리지에 저장됨.

## 구조 다이어그램

문서, CI, AI 작업 보조 파일을 제외하고 실제 애플리케이션 실행과 기능에 직접 관여하는 구조만 표시함.

### 실행 구조

```mermaid
flowchart LR
    root["tiptipworld<br/>서비스 실행 루트"]

    root --> compose["docker-compose.yml<br/>app, web, db, redis 실행"]
    root --> docker["docker/<br/>컨테이너 설정"]
    root --> src["src/<br/>Laravel 애플리케이션"]

    docker --> php["php/<br/>PHP-FPM 설정"]
    php --> dockerfile["Dockerfile<br/>PHP 앱 이미지 빌드"]
    php --> phpini["conf.d/*.ini<br/>업로드, Xdebug 설정"]

    docker --> nginx["nginx/<br/>웹 서버 설정"]
    nginx --> nginxconf["default.conf<br/>Nginx에서 Laravel로 요청 전달"]

    src --> app["app/<br/>백엔드 핵심 코드"]
    src --> routes["routes/<br/>URL 라우트"]
    src --> config["config/<br/>Laravel/도메인 설정"]
    src --> database["database/<br/>DB 스키마와 초기 데이터"]
    src --> resources["resources/<br/>Blade, CSS, JS"]
    src --> public["public/<br/>웹 공개 진입점과 이미지"]
    src --> tests["tests/<br/>Pest 테스트"]
    src --> storage["storage/<br/>런타임 저장소"]
```

### 백엔드 핵심 구조

```mermaid
flowchart LR
    app["src/app<br/>백엔드 핵심 코드"]

    app --> http["Http<br/>요청 처리"]
    http --> controllers["Controllers<br/>요청을 받고 서비스 호출"]
    http --> requests["Requests<br/>입력 검증과 권한 확인"]
    http --> middleware["Middleware<br/>인증/관리자 접근 제어"]

    app --> models["Models<br/>DB 테이블과 관계"]
    models --> user["User.php<br/>회원, 역할, 소셜 계정"]
    models --> tip["Tip.php<br/>팁 글, 태그, 댓글, 반응"]
    models --> comment["Comment.php<br/>댓글과 대댓글"]
    models --> taxonomy["Category.php / Tag.php<br/>카테고리와 태그"]
    models --> authModels["Role.php / SocialAccount.php<br/>권한과 소셜 로그인 연결"]

    app --> services["Services<br/>비즈니스 로직"]
    services --> tipDomain["Tip 도메인<br/>작성, 조회, 태그"]
    services --> mediaDomain["Media 도메인<br/>R2, 썸네일, 본문 이미지"]
    services --> socialDomain["Social 도메인<br/>Google/Kakao 로그인"]
    services --> activityDomain["User Activity<br/>알림, 팔로우, 조회수"]

    app --> policies["Policies<br/>도메인 권한"]
    policies --> tipPolicy["TipPolicy.php<br/>팁 생성, 수정, 삭제 권한"]

    app --> support["Enums / Data / Support<br/>상태값, 필터, 표시 보조"]
    support --> enums["TipStatus / TipVisibility / TipSort<br/>상태, 공개범위, 정렬"]
    support --> filters["TipListFilters.php<br/>목록 필터"]
    support --> presenter["TipPresenter.php<br/>화면 표시 데이터 변환"]

    app --> notifications["Notifications<br/>알림"]
    notifications --> activity["ActivityNotification.php<br/>DB 알림 저장"]
```

### 서비스 계층 구조

```mermaid
flowchart LR
    services["src/app/Services<br/>비즈니스 로직 계층"]

    services --> tipDomain["Tip 도메인"]
    tipDomain --> tipWrite["TipWriteService.php<br/>팁 생성, 수정, 삭제"]
    tipDomain --> tipRead["TipReadService.php<br/>목록, 상세, 피드 조회"]
    tipDomain --> tipTag["TipTagService.php<br/>태그 정리와 동기화"]
    tipDomain --> tipResult["TipWriteResult.php<br/>저장 결과 전달"]

    services --> mediaDomain["Media 도메인"]
    mediaDomain --> r2["R2ImageStorageService.php<br/>R2 저장, 이동, 삭제, URL 생성"]
    mediaDomain --> thumbnail["TipThumbnailService.php<br/>팁 썸네일 처리"]
    mediaDomain --> editorImage["EditorImageService.php<br/>본문 이미지 이동/정리"]
    mediaDomain --> profileImage["ProfileImageService.php<br/>프로필 이미지 처리"]
    mediaDomain --> mediaPath["MediaPath.php<br/>미디어 경로 규칙"]

    services --> socialDomain["Social Auth 도메인"]
    socialDomain --> socialAuth["SocialAuthService.php<br/>소셜 로그인과 계정 연결"]
    socialDomain --> providerRegistry["SocialProviderRegistry.php<br/>provider별 OAuth 설정"]
    socialDomain --> accountRevoker["SocialAccountRevoker.php<br/>소셜 연결 해제"]

    services --> activityDomain["User Activity 도메인"]
    activityDomain --> follow["FollowService.php<br/>팔로우/언팔로우"]
    activityDomain --> notification["UserNotificationService.php<br/>댓글, 답글, 좋아요, 북마크, 팔로우 알림"]
    activityDomain --> viewCounter["TipViewCounterService.php<br/>쿠키 기반 조회수 제한"]

    services --> pageDomain["Page/Search 도메인"]
    pageDomain --> home["HomeViewService.php<br/>홈 화면 데이터 구성"]
    pageDomain --> search["SearchKeywordService.php<br/>검색어 정리"]
```

### 요청 처리 흐름

```mermaid
flowchart LR
    browser["사용자 요청<br/>브라우저/AJAX"]
    route["routes/web.php<br/>URL 매칭"]
    controller["Controller<br/>요청 진입"]
    request["Form Request<br/>입력 검증"]
    auth["Policy / Middleware<br/>권한 확인"]
    service["Service<br/>비즈니스 로직"]
    model["Model<br/>DB 관계와 쿼리"]
    db["Database<br/>데이터 저장"]
    response["Blade View / JSON<br/>응답 반환"]

    browser --> route --> controller --> request --> auth --> service --> model --> db
    service --> response
```

## 디렉터리 구조

| 경로 | 역할 |
| --- | --- |
| `src/app/Http/Controllers` | 화면/액션 컨트롤러 |
| `src/app/Http/Requests` | 요청 검증 |
| `src/app/Models` | Eloquent 모델 |
| `src/app/Services` | 팁, 홈 화면, 팔로우, 알림, 파일 저장, 소셜 계정 해제 등 재사용 로직 |
| `src/app/Policies` | 사용자 권한 정책 |
| `src/resources/views` | Blade 화면과 컴포넌트 |
| `src/resources/css`, `src/resources/js` | Vite로 빌드되는 프론트엔드 자산 |
| `src/routes/web.php` | 공개 화면, 인증 사용자 기능, 관리자 기능 라우트 |
| `src/routes/auth.php` | Breeze 인증, Google/Kakao 소셜 로그인 라우트 |
| `src/config` | Laravel 설정 |
| `src/database/migrations` | 데이터베이스 스키마 |
| `src/tests` | Pest 테스트 |

Vite는 `src/resources/css`와 `src/resources/js` 자산을 `src/public/build`로 빌드함. 주요 프론트엔드 의존성은 Tailwind CSS, Alpine.js, Axios, Tiptap, jQuery, Summernote임.
