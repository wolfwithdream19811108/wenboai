# 闻博 AI 本地测试环境

## 启动

本机需要 Docker Desktop。容器运行 PHP 8.3。项目目录下运行：

```powershell
docker compose up -d --build
```

首次安装或 `composer.lock` 更新后，执行：

```powershell
docker compose exec -T php composer install
```

Composer 安装结束时会自动执行 `tools/patch_php83_vendor.php`，修复
`topthink/think-image` 1.x 尚未处理的 PHP 8 字符串下标语法。不要使用
`--no-scripts` 跳过这个兼容补丁。

浏览器访问：<http://localhost:8088>

- MySQL 仅在 Docker 内部开放，不映射到宿主机。
- 本地管理员种子账号为 `admin` / `admin123`，仅限测试环境。
- 本地使用文件缓存，不依赖生产服务器上的 Memcached。
- 生产站的 URI 白名单没有在本地启用，方便调试全部页面和接口。

## 数据库模式

`.env` 中的 `DB_MODE` 控制数据库连接：

- `DB_MODE=local`：使用 Docker 中的 MySQL（示例配置的默认值，最安全）。
- `DB_MODE=remote`：使用已有的 `data/conf/database.php` 远程数据库配置；页面和接口可能直接读写远程数据。

修改后执行 `docker compose up -d --force-recreate php`，让 PHP 容器读取新值。本地运行配置、文件缓存和非 HTTPS Cookie 不受数据库模式影响。

当前工作目录的 `.env` 已按调试需要设为 `DB_MODE=remote`。若要进行可能写数据的功能测试，请先切回 `local` 或使用脱敏测试库。

## 常用命令

```powershell
# 查看状态
docker compose ps

# 查看日志
docker compose logs -f web php db

# 停止（保留数据库）
docker compose down

# 完全重建本地数据库
docker compose down -v
docker compose up -d --build
```

## 导入业务数据库

当前代码包没有生产业务库备份，首次启动只会导入 ThinkCMF 基础结构和少量本地首页种子数据。完整验证文章、会话、订单等功能前，需要另行取得脱敏后的数据库导出。

推荐在容器启动后手动导入，避免每次重建时误导入敏感数据：

```powershell
Get-Content -Raw .\path\to\sanitized.sql | docker compose exec -T db mysql -uwenboai -pwenboai_local wenboai_local
```

不要把数据库导出提交进 Git；`docker/mysql/import/*.sql` 已被忽略。

## 配置隔离

容器会设置 `CMF_ENV=local`，从而加载：

- `data/conf/database.local.php`
- `data/conf/config.local.php`

原来的生产数据库配置仍保留在本机，但已加入 `.gitignore`。只有
`DB_MODE=local` 时容器才不会连接远程库；`DB_MODE=remote` 会明确启用原配置。

## PHP 8.3 验证

```powershell
# 确认 PHP 版本与扩展
docker compose exec -T php php -v
docker compose exec -T php php -m

# 验证 Composer 平台要求与安全公告
docker compose exec -T php composer check-platform-reqs
docker compose exec -T php composer audit --locked
```

项目已将运行时约束设为 PHP `^8.3`，并升级了 PHPMailer、dompdf、
PhpSpreadsheet、Guzzle、phpseclib 和 TCPDF 等依赖。旧的 PHPExcel 已移除。
ThinkPHP 5 核心保留在 `simplewind/thinkphp`，按项目源码维护，不再由 Composer
安装或删除。
