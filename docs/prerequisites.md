# 설치 전 요구사항과 개발 환경 준비

[README로 돌아가기](../README.md)

## 필수 도구

- Docker와 Docker Compose
- Git
- 로컬에서 직접 앱 명령을 실행할 경우 PHP, Composer, Node.js, npm
- Docker 컨테이너 안에서 앱 명령을 실행할 경우 로컬 PHP/Composer/Node.js 설치는 필수는 아닙니다.

## 저장소 구조

- 루트 디렉터리: Docker Compose, Nginx/PHP Docker 설정, 루트 `.env`
- `src/`: Laravel 12 애플리케이션
- `src/app`: 컨트롤러, 모델, 서비스, 정책 등 애플리케이션 코드
- `src/resources/views`: Blade 뷰
- `src/resources/css`, `src/resources/js`: Vite 프론트엔드 자산
- `src/database`: 마이그레이션, 팩토리, 시더, 로컬 SQLite 파일
- `docker/`: Nginx와 PHP 컨테이너 설정

## 루트 환경변수 준비

루트의 `.env`는 Docker Compose가 MariaDB 컨테이너를 초기화할 때 사용합니다. 실제 값은 커밋하지 말고 로컬에서만 관리합니다.

필요한 키:

```dotenv
MYSQL_ROOT_PASSWORD=
MYSQL_DATABASE=
MYSQL_USER=
MYSQL_PASSWORD=
```

## Laravel 환경변수 준비

Laravel 앱 환경변수는 `src/.env`에서 관리합니다. 최초 설치 전 `src/.env.example`을 기준으로 `src/.env`를 준비합니다.

Docker Compose의 MariaDB를 사용할 때 확인할 주요 키:

```dotenv
APP_NAME=TipTipWorld
APP_ENV=local
APP_DEBUG=true
APP_URL=http://tiptipworld.com

DB_CONNECTION=mariadb
DB_HOST=db
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

REDIS_HOST=redis
REDIS_PORT=6379
```

파일 업로드 또는 외부 스토리지를 사용할 경우 `FILESYSTEM_DISK`, `AWS_*`, `R2_*` 키를 설정합니다. Google/Kakao 로그인 사용 시 `GOOGLE_*`, `KAKAO_*` 키를 설정합니다.

## 로컬 도메인

Nginx 설정의 `server_name`은 `tiptipworld.com`입니다. 로컬 브라우저에서 이 도메인으로 접속하려면 hosts 파일에 개발 서버 주소를 등록합니다.

예시:

```text
127.0.0.1 tiptipworld.com
```

프록시 컨테이너 또는 외부 reverse proxy를 사용하는 환경에서는 `proxy-nw` Docker 네트워크가 먼저 존재해야 합니다.

```bash
docker network create proxy-nw
```

이미 존재하는 네트워크라면 위 명령은 생략합니다.

## 권한 준비

Laravel은 `src/storage`와 `src/bootstrap/cache`에 쓸 수 있어야 합니다. Docker Compose의 `app` 서비스는 `1000:1000` 사용자로 실행되므로 호스트 파일 소유자와 쓰기 권한을 맞춰야 합니다.

```bash
chmod -R 775 src/storage src/bootstrap/cache
```

권한 문제가 계속되면 로컬 사용자에 맞게 소유자를 조정합니다.
