# 운영/보안 워크플로우

Docker, Nginx/PHP 컨테이너, MariaDB/Redis, env key, Laravel config, storage 권한, queue/cache, R2/S3, Socialite provider, 배포 영향에 이 워크플로우를 사용한다.

## 단계

1. 변경된 표면을 Docker, env, config, storage, queue/cache, 외부 provider, 배포 절차 중 하나로 분류한다.
2. secret 값 노출을 피하고 key와 파일 이름만 언급한다.
3. 새 필수 key와 `src/.env.example`을 일치시킨다.
4. 업로드와 공개 파일을 MIME 검증, 확장자 처리, 경로 안전성, 공개 URL 노출, 정리 동작 관점에서 검토한다.
5. migration, cache clear, queue restart, storage permission 변경, 외부 서비스 설정 같은 배포 작업을 나열한다.

## 로컬 포인트

- Docker: `docker-compose.yml`, `docker/php`, `docker/nginx`
- Config: `src/config`
- Media services: `src/app/Services/Media`
- Social auth: `src/config/social-auth.php`, `src/config/services.php`

## 배포 전 체크리스트

- 새 env key가 `src/.env.example`에 반영되었는가?
- migration이 운영 데이터에 안전한가?
- queue worker 재시작이 필요한가?
- config/cache clear가 필요한가?
- storage 권한 또는 공개 URL 정책이 바뀌었는가?
- 외부 provider 설정 변경이 필요한가?
- secret 값이 코드, 로그, 응답에 노출되지 않는가?
- Docker port, volume, network, container user 변경이 기존 운영 환경과 충돌하지 않는가?

## 검증

- config key와 fallback 동작을 검증한다.
- 가능하면 storage fake나 mock 처리한 외부 호출을 사용한다.
- Docker 변경은 port, volume, network, container user 영향을 확인한다.
