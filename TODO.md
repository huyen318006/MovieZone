# TODO - Fix Article Management

## Steps

- [x] 1. Analyze code and identify issues
- [x] 2. Fix thumbnail storage: changed from `storage('articles', 'public')` to `public/uploads/articles/` (no need for `storage:link`)
- [x] 3. Fix `ArticleManageController::index()`: changed sort from `created_at` to `published_at` (newest first)
- [x] 4. Fix all view paths: changed `asset('storage/' . ...)` to `asset(...)`
- [x] 5. Created `public/uploads/articles/` directory for thumbnail uploads
