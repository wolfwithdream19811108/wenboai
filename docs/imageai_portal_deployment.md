# ImageAI 开放平台部署

## 1. 更新代码

```bash
cd /www/wwwroot/wenboai.cn
git fetch origin main
git merge --ff-only origin/main
git lfs pull
```

## 2. 备份并执行数据库迁移

先使用宝塔备份数据库，再把下面的数据库名替换为网站实际数据库名：

```bash
mysql -u数据库用户 -p 数据库名 < database/imageai_portal.sql
```

迁移使用 `CREATE TABLE IF NOT EXISTS`，不会覆盖现有用户、积分或作品数据。生产环境已有 `cmf_user_points` 和 `cmf_ai_user_works` 时会直接保留原表。

需要回滚本功能时，先停用入口，再执行 `database/imageai_portal_rollback.sql`。回滚脚本只删除本功能新增的四张表，不会删除现有用户、积分和作品数据；已经写入 `cmf_ai_user_works` 的 API 作品仍会保留。

## 3. 宝塔 Nginx 白名单

现有站点采用 URI 白名单时，在“拒绝未授权访问”规则之前增加：

```nginx
if ($uri ~* "^/imageai(?:/|$)") {
    set $allow_access 1;
}
if ($uri ~* "^/api/ai/imagegenerate/create_for_test$") {
    set $allow_access 1;
}
if ($uri ~* "^/themes/Acme/public/assets/(css|js|images)/imageai/") {
    set $allow_access 1;
}
```

然后检查并重载：

```bash
/www/server/nginx/sbin/nginx -t && /www/server/nginx/sbin/nginx -s reload
```

## 4. 清缓存并重载 PHP 8.3

```bash
rm -rf data/runtime/cache/* data/runtime/temp/*
/etc/init.d/php-fpm-83 reload || /etc/init.d/php-fpm-83 restart
```

## 5. 同步后台菜单

进入宝塔网站后台的“菜单管理”，执行一次“同步菜单”。同步后会出现“ImageAI 开放平台”，包含开发者用户、API Keys、API 请求和生成作品四个页面。

## 6. 核验

- 打开 `/imageai/`、`/imageai/register`、`/imageai/login`、`/imageai/docs`。
- 新注册一个测试手机号，确认账号类型为 `api_only`。
- 在控制台创建 API Key，确认明文只展示一次。
- 用 `dry_run=true` 调用 `/api/ai/imagegenerate/create_for_test`，确认响应包含 `request_id` 且没有触发图片生成。
- 确认后台 API 请求页面出现对应记录。
- 最后使用一条合规提示词做一次真实生成，确认积分、作品表、文字水印、metadata 和又拍云地址均正常。
