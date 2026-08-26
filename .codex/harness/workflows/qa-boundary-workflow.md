# QA 경계 워크플로우

Pest 테스트, 회귀 확인, 테스트 실패, 경계 간 검증에 이 워크플로우를 사용한다.

## 단계

1. 변경된 동작과 사용자 흐름을 식별한다.
2. 라우트, 미들웨어, 요청 검증, 정책, 서비스, 모델, view, JS의 기대값을 비교한다.
3. 변경된 동작에 대해 집중된 Pest 테스트를 추가한다.
4. 먼저 가장 좁고 유용한 테스트 명령을 실행한다.
5. 공유 서비스, auth, policy, migration, 공개 workflow가 변경되었으면 더 넓은 테스트로 확장한다.

## 우선 영역

- 인증 사용자와 guest 동작
- owner/admin/other-user 권한
- Tip create/update/delete와 visibility/status
- Comments, likes, bookmarks, follows, notifications
- Image upload와 storage fake
- Social login provider 흐름
- Admin middleware와 routes

## 테스트 명령 선택 기준

- 단일 서비스 변경: 관련 Unit 테스트 또는 `php artisan test --filter=ServiceName`
- 라우트/컨트롤러 변경: 관련 Feature 테스트
- 인증/권한 변경: Auth, Policy, 대상 Feature 테스트
- Blade/JS/CSS 변경: `npm run build`
- migration/model 관계 변경: 관련 Feature 테스트와 factory/seeder 영향 확인
- 이미지 업로드/storage 변경: storage fake 기반 테스트와 실패 케이스 확인
- 외부 provider 변경: config override 또는 mock 기반 테스트
- 공유 서비스 변경: `composer test`

## 코드 리뷰 체크리스트

- 컨트롤러에 비즈니스 로직이 과하게 들어가지 않았는가?
- Form Request, Policy, Service, Model의 책임이 분리되어 있는가?
- authorization과 validation 실패 케이스가 테스트되었는가?
- 목록 화면에 N+1 query 가능성이 없는가?
- Blade form field name과 Request validation key가 일치하는가?
- JS가 기대하는 응답 shape과 controller 응답이 일치하는가?
- migration, env, queue, storage 영향이 최종 보고에 포함되었는가?
- 실패 시 사용자에게 안전하고 이해 가능한 응답이 돌아가는가?

## 보고

버그나 위험을 먼저 제시한다. 가능하면 file/line 참조를 포함하고, 그다음 테스트 커버리지와 잔여 위험을 설명한다.
