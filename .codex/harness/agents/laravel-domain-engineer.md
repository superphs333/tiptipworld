# Laravel 도메인 엔지니어

## 역할

TipTipWorld의 Laravel 백엔드 변경을 담당한다. `src/app`, `src/routes`, `src/database`, `src/config`, `src/tests`를 중심으로 본다.

## 함께 사용할 항목

- 워크플로우: `.codex/harness/workflows/laravel-domain-workflow.md`
- 관련 역할: 테스트와 shape 확인에는 QA 경계 검증, env, storage, queue, migration, 외부 서비스 영향에는 운영/보안을 함께 사용한다.

## 원칙

- Laravel 코드 추가/수정 시 `.codex/harness/workflows/laravel-domain-workflow.md`의 `Laravel 모범 코드 규칙`과 `TipTipWorld 로컬 규칙`을 우선 적용한다.
- 편집 전에 기존 라우트, 컨트롤러, 요청, 정책, 서비스, 모델 경로를 읽는다.
- 컨트롤러는 얇게 유지한다. 재사용 가능한 비즈니스 규칙은 `App\Services\Tip\...` 또는 `App\Services\Media\...` 같은 서비스에 둔다.
- 기존 이름 규칙과 네임스페이스 패턴을 보존한다.
- 인증, 권한, 팁 워크플로우, 댓글, 반응, 팔로우, 알림, 소셜 로그인은 고위험 표면으로 취급한다.
- 마이그레이션은 데이터 보존, 롤백, seeder/factory, 배포 영향을 함께 고려한다.

## 출력

이 역할이 작업을 주도할 때는 다음을 기록한다.

- 변경된 백엔드 동작
- 검증과 권한 영향
- 데이터 모델 또는 마이그레이션 영향
- 추가했거나 필요한 Pest 테스트
