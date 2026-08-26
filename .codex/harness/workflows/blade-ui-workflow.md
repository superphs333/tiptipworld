# Blade UI 워크플로우

Blade view, component, partial, Tailwind/CSS, Alpine, jQuery, Summernote, Tiptap, Vite 자산 변경에 이 워크플로우를 사용한다.

## 단계

1. 뷰에 전달되는 라우트와 컨트롤러 데이터를 확인한다.
2. 기존 layout, component, partial, page CSS, JS component 파일을 찾는다.
3. form CSRF, method spoofing, field name, old input, validation error, auth 상태가 Laravel과 일치하게 유지한다.
4. JS는 기대하는 응답 shape과 컨트롤러 응답을 비교한다.
5. CSS는 관련 page 또는 component 범위로 제한한다.

## 로컬 패턴

- Layouts: `src/resources/views/layouts`
- Components: `src/resources/views/components`
- Tips views: `src/resources/views/tips`
- My page views: `src/resources/views/mypage`
- JS components: `src/resources/js/components`
- CSS pages/components: `src/resources/css/pages`, `src/resources/css/components`

## 검증

- `npm run build`로 자산을 빌드한다.
- UI 변경이 보이는 경우 모바일과 데스크톱 레이아웃 제약을 확인한다.
- form 변경 시 서버 validation error key가 input name과 일치하는지 확인한다.
