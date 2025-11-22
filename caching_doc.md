# Manual cache management
## If you need to manually clear the cache:

```php 
app(CategoryService::class)->clearMenuCache();
```
# ** Or force refresh on next request **:
```php
$categoryService->getMenu(forceRefresh: true);
```
The menu API is optimized and ready for production. The first request will be slower (cache miss), but all subsequent requests will be fast (cached).