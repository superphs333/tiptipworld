# TipTipWorld

TipTipWorld는 생활 속 노하우와 실전 팁을 쉽고 빠르게 공유하는 커뮤니티 서비스임.

## 프로젝트 목적과 사용 방식

이 프로젝트는 일상에서 검증한 작은 노하우를 체계적으로 모으고, 필요한 사람이 빠르게 찾아 다시 활용할 수 있게 만드는 것을 목적으로 함. 서비스를 체험하려는 사용자는 기능 목록과 설치 문서를 먼저 확인하고, 개발자는 애플리케이션 구조와 서버 구성 문서를 함께 확인할 수 있음.

사용자는 자신이 직접 경험한 유용한 팁을 글과 이미지로 정리해 게시하고, 다른 사용자의 팁을 탐색하며 댓글, 좋아요, 북마크로 관심 있는 정보를 모아둘 수 있음. 요리, 생활, 업무, 학습, 취미처럼 일상에서 바로 써먹을 수 있는 작은 지식을 사람들과 나누는 데 초점을 둠.

TipTipWorld는 Laravel 12 기반으로 개발된 팁 공유 커뮤니티 애플리케이션임.

## 기술 스택

| 영역 | 사용 기술 |
| --- | --- |
| Backend | PHP 8.4, Laravel 12, PHP-FPM |
| Frontend | Blade, Vite, Tailwind CSS, Alpine.js |
| Editor | Tiptap, Summernote |
| Database / Cache | MariaDB 10.11, Redis 7.2 |
| Auth | Laravel Breeze, Laravel Socialite, Google/Kakao OAuth |
| Storage / Media | Laravel Filesystem, S3 호환 스토리지, GD |
| Infra | Docker Compose, Nginx, Node.js 22, Composer, npm |
| Test / Quality | Pest, Laravel Pint |

## 아키텍처

![TipTipWorld Architecture](docs/assets/readme-architecture.svg)

브라우저 요청은 Nginx를 거쳐 Laravel PHP-FPM 애플리케이션으로 전달됨. Laravel은 MariaDB에 서비스 데이터를 저장하고, Redis를 캐시와 큐 기반 기능에 활용하며, 이미지 파일은 S3 호환 스토리지에 저장할 수 있도록 구성되어 있음. 상세 서버 구성은 [서버 구성 문서](docs/architecture.md)에서 확인할 수 있음.

## 주요 기능

| 기능 | 설명 |
| --- | --- |
| 팁 작성과 관리 | 제목, 본문, 이미지, 카테고리, 태그를 사용해 경험 기반 노하우를 작성하고 수정/삭제 |
| 팁 탐색과 검색 | 최신 팁, 인기 콘텐츠, 키워드 검색, 카테고리/태그별 목록으로 필요한 정보 탐색 |
| 커뮤니티 반응 | 댓글, 답글, 좋아요, 북마크로 유용한 팁에 반응하고 저장 |
| 사용자 활동 공간 | 프로필, 마이페이지, 내 팁, 보관함, 알림으로 개인 활동 관리 |
| 소셜 로그인 | 일반 로그인과 Google/Kakao OAuth 계정 연동 지원 |
| 관리자 기능 | 사용자, 카테고리, 태그, 팁 관리를 통해 커뮤니티 운영 지원 |

## 문서 구성

| 문서 | 내용 |
| --- | --- |
| [설치 문서](docs/installation.md) | 로컬 실행 준비, 환경 파일, Docker 실행, 의존성 설치, 마이그레이션 |
| [서버 구성](docs/architecture.md) | Docker Compose 구성, 요청 흐름, 네트워크/볼륨, 서버 런타임 |
| [애플리케이션 구조](docs/application.md) | Laravel 디렉터리, 라우트, 뷰/자산, 테스트 위치 |
| [운영 문서](docs/operations.md) | 상태 확인, 로그, 캐시, 큐, 마이그레이션 운영 명령 |
| [기능 목록](docs/features/README.md) | 기능별 상세 문서 진입점 |

## 라이선스

TipTipWorld 애플리케이션 코드는 `src/composer.json`에 선언된 MIT License를 따름. 외부 라이브러리는 각 패키지가 제공하는 라이선스 조건을 함께 따름.

| 구분 | 주요 라이브러리 | 라이선스 |
| --- | --- | --- |
| PHP | Laravel Framework, Laravel Socialite, Laravel Tinker | MIT |
| PHP | League Flysystem AWS S3, SocialiteProviders Kakao | MIT |
| JavaScript | Tiptap, jQuery, Summernote | MIT |

전체 의존성의 라이선스는 `src/composer.lock`과 `src/package-lock.json`에 기록된 패키지별 라이선스 정보를 기준으로 확인할 수 있음.
