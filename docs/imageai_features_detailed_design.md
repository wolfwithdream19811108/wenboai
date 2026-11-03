# 鉴渊 ImageAI 平台功能与详细设计说明

## 1. 文档目的与范围

本文档说明当前项目中已经实现的 ImageAI 开放平台、文生图接口、内容审核、AIGC 标识、实名认证、账号与后台管理等业务能力。

文档针对每项功能分别说明：

- 功能用途和用户行为；
- 系统设计与模块职责；
- 主要处理流程和关键判断；
- 涉及的控制器、服务、模板、脚本及数据库文件；
- 当前实现边界和需要注意的业务规则。

本文档以当前代码实际实现为准，不把规划中但尚未落入代码的能力计为已完成功能。

---

## 2. 系统整体设计

### 2.1 模块划分

系统在原 ThinkCMF 项目中增加了独立的 ImageAI 业务模块，同时复用原有用户、积分和作品数据。

| 层次 | 主要职责 | 主要目录 |
| --- | --- | --- |
| 路由层 | 将 /imageai 路径映射到独立控制器 | app/route.php |
| 前台控制器 | 首页、注册登录、控制台、网页生图、协议展示 | app/imageai/controller |
| 业务服务层 | 访问控制、API Key、计费、实名、测试账号、审计、失败日志 | app/imageai/service |
| 图片生成接口 | 鉴权、内容判断、生图、标识、存储、响应和审计 | api/ai/controller/ImagegenerateController.php |
| 配置层 | 图片生成参数、密钥、实名参数的集中读取与校验 | app/ai/service/ImageGenerationConfig.php、app/imageai/service/RealNameConfig.php |
| 后台管理 | ImageAI 用户、Key、请求、作品、协议、实名和失败日志 | app/imageai/controller/AdminPortalController.php |
| 页面模板 | ImageAI 前台和 ThinkCMF 后台页面 | public/themes/Acme/imageai、public/themes/admin_simpleboot3 |
| 数据层 | 用户扩展、API Key、请求日志、作品详情、实名、失败日志、测试账号 | database/imageai_*.sql |

### 2.2 核心调用链

网页生图与用户 API 调用共用同一条核心业务链：

1. 接收请求并生成 request_id；
2. 判断请求来自网页、用户 API Key 或后台测试 Bearer Key；
3. 校验账号状态、账号模式、API Key、实名和年龄；
4. 校验请求参数和模型；
5. 调用类目判断服务；
6. 根据敏感、文博领域和受限领域三个维度决定放行、拒绝或记录服务异常；
7. 对用户账号预扣积分；
8. 提交即梦图片生成任务并轮询结果；
9. 下载生成图片；
10. 添加显式文字水印；
11. 写入 PNG AIGC Metadata；
12. 保存服务器本地文件；
13. 尝试同步又拍云，失败时回退为本地图片；
14. 记录 API 请求、生成作品和扩展详情；
15. 生成失败时退还预扣积分；
16. 返回统一格式的结果。

核心实现文件：

- api/ai/controller/ImagegenerateController.php
- app/imageai/service/AccessService.php
- app/imageai/service/ApiKeyService.php
- app/imageai/service/BillingService.php
- app/imageai/service/RequestService.php
- app/imageai/service/FailureLogService.php

---

## 3. 图片生成集中配置

### 3.1 功能描述

后台“网站设置”增加以下独立配置页签：

- 图片生成设置forAPI；
- 实名认证设置；
- 测试账号设置。

图片生成参数和服务密钥不再分散在控制器中读取。业务行为保存在 image_generation_settings 配置项，敏感凭据保存在 function_keys 配置项。

### 3.2 可配置内容

图片生成设置包括：

- 前台示例提示词列表；
- 类目判断开关、接口地址、模型、超时和系统提示词；
- 对外统一拒答文案；
- 显式文字水印开关、文字、大小、不透明度和位置；
- AIGC Metadata 开关及字段；
- 又拍云同步开关、访问域名和保存目录；
- 本地图片保存目录和访问域名；
- 默认模型；
- 单张图片积分；
- API Key 每分钟调用上限；
- 即梦任务轮询间隔和最长等待时间。

密钥配置包括：

- 火山引擎访问密钥；
- 类目判断 API Key；
- 后台测试 Bearer Key 列表；
- 又拍云服务名、操作员和密码；
- 腾讯云 FaceID SecretId 和 SecretKey；
- 仍由旧业务使用的其他服务密钥。

### 3.3 保存逻辑

1. SettingController 读取当前已保存的配置；
2. 页面只提交本次填写或变更的数据；
3. mergeImageFunctionKeys 合并原有密钥，空白输入不会无意清除已经配置的值；
4. 如需删除密钥，使用明确的清除标记；
5. 已配置的密钥会回填到密码输入框，并可通过页面按钮临时切换显示；页面同时提供明确的“清除”复选框；
6. normalizeImageGenerationSettings 对布尔值、数字范围、URL、目录、Metadata 字段和示例提示词进行规范化；
7. normalizeRealNameSettings 校验实名参数和凭据状态；
8. 最终通过 cmf_set_option 保存；
9. ImageGenerationConfig 和 RealNameConfig 在运行时合并默认值与后台配置。

### 3.4 配置校验规则

- 水印文字必须同时包含“AI/人工智能”和“生成/合成”语义；
- 水印大小限制为图片短边的 5%～20%；
- 不透明度限制为 80%～100%；
- Metadata 开启时，Label、ContentProducer 和 ContentPropagator 必须完整；
- XMP 前缀和 ID 前缀必须符合字符格式；
- XMP Namespace 必须是有效 URL；
- 又拍云开启时，凭据、域名和目录必须完整；
- 类目判断开启时，API 地址、模型、系统提示词和 API Key 必须完整；
- 示例提示词的标题和正文必须成对填写，并限制长度。
- 后台保存图片生成设置时要求火山引擎密钥完整，并且至少配置一个系统测试 Bearer Key；
- 显式水印和 Metadata 虽然在页面上提供开关，但当前真实生成运行检查要求二者必须开启；关闭任一项会把图片生成服务判定为配置不完整。

### 3.5 涉及文件

- app/ai/service/ImageGenerationConfig.php
- app/imageai/service/RealNameConfig.php
- app/admin/controller/SettingController.php
- public/themes/admin_simpleboot3/admin/setting/site.html

### 3.6 旧业务服务凭据集中读取

除后台可维护的 ImageAI `function_keys` 外，项目还把原来散落在旧控制器中的第三方服务凭据集中到私有文件 `data/conf/services.php`，`data/conf/services.example.php` 只提供不含真实凭据的空值结构。

`cmf_service_config` 的读取优先级为：

1. `data/conf/services.php` 中对应的非空配置；
2. 同名环境变量；
3. `PHP_` 前缀环境变量；
4. 调用方提供的默认值。

当前使用该入口的旧业务包括阿里云滑块、聊天模型、图片搜索、AI 图片检测、腾讯云 COS、门户聊天和 Neo4j 查询。`tools/migrate_legacy_secrets.php` 可从旧网站备份中提取已知硬编码值并生成私有配置文件；脚本不输出凭据值，支持的平台会把文件权限设为 `0600`。

这套私有文件配置与后台 `function_keys` 是两条不同的配置链路：前者没有网页编辑入口，后者保存于 ThinkCMF option 数据中并由后台管理。当前代码没有对 `function_keys` 再做字段级加密，因此数据库备份和后台权限本身也必须受到保护。

涉及文件：

- simplewind/cmf/common.php
- data/conf/services.example.php
- tools/migrate_legacy_secrets.php
- api/ai/controller/ChatController.php
- api/portal/controller/ImagesearchController.php
- app/ai/controller/AidetectionController.php
- app/cert/controller/CertController.php
- app/portal/controller/ChatController.php
- app/portal/controller/VisualController.php

---

## 4. ImageAI 独立门户与路由

### 4.1 功能描述

系统新增独立的 /imageai 业务前缀，与原 /ai 页面相互独立。后续调整 /imageai/create 的页面不会影响原有生图页面。

### 4.2 公开路由

| 路径 | 功能 |
| --- | --- |
| /imageai/ | ImageAI 开放平台首页 |
| /imageai/register | 手机号注册 |
| /imageai/login | 手机号或测试账号登录 |
| /imageai/logout | 退出登录 |
| /imageai/forgot-password | 手机号找回密码 |
| /imageai/account | 开发者控制台 |
| /imageai/account/api-keys | API Key 管理 |
| /imageai/account/requests | 用户请求记录 |
| /imageai/account/works | 用户作品记录 |
| /imageai/account/real-name | 实名认证 |
| /imageai/account/security | 账号安全 |
| /imageai/docs | API 使用说明 |
| /imageai/service | 服务协议 |
| /imageai/privacy | 隐私政策 |
| /imageai/create | 独立网页生图页面 |

### 4.3 页面设计

门户首页包含：

- 平台定位和主要行动入口；
- API 接入流程；
- 内容审核、AIGC 标识、作品留档等能力说明；
- API 调用示例；
- 登录、注册、控制台和协议入口。

公共导航和开发者控制台导航拆分为独立模板，避免每个页面重复维护。

当前 /imageai/create 的浏览器标题直接定义为“鉴渊文博-文生图平台”。导航栏优先显示 theme_vars.logo；没有配置 Logo 时，回退显示 create.html 中的“鉴渊文博-文生图平台”文字。门户公共导航品牌“鉴渊 ImageAI”定义在 _nav.html 中。

### 4.4 涉及文件

- app/route.php
- app/imageai/controller/IndexController.php
- public/themes/Acme/imageai/index.html
- public/themes/Acme/imageai/docs.html
- public/themes/Acme/imageai/_head.html
- public/themes/Acme/imageai/_nav.html
- public/themes/Acme/imageai/_account_nav.html
- public/themes/Acme/public/assets/css/imageai/portal.css
- public/themes/Acme/public/assets/images/imageai/home-hero-art.png

---

## 5. 注册、登录、短信与滑块验证

### 5.1 手机号注册

注册用户需要提供：

- 本人手机号码；
- 短信验证码；
- 8～32位登录密码；
- 服务协议同意；
- 隐私政策同意。

注册成功后：

- 在 user 表创建用户；
- 密码通过 cmf_password 保存摘要；
- 在 imageai_user_profile 创建扩展资料；
- account_mode 设置为 api_only；
- register_source 设置为 imageai；
- 记录同意的服务协议版本、隐私协议版本、同意时间和 IP；
- 初始化 user_points 积分记录；
- 引导用户进入实名认证。

api_only 表示通过 ImageAI 注册入口创建的账号只能使用 API，不允许使用网页生图入口。

### 5.2 短信验证码

获取短信验证码前必须先通过阿里云滑块验证：

1. 前端加载阿里云验证码组件；
2. 滑块成功后把 captchaVerifyParam 发送到 /imageai/captcha/verify；
3. 服务端调用 cmf_verify_aliyun_captcha 完成二次校验；
4. 校验结果写入短期 Session；
5. /imageai/send-code 消费该 Session；
6. 通过 send_mobile_verification_code 插件钩子发送短信；
7. 对 IP 与手机号组合进行发送频率限制；
8. 注册时再校验短信验证码及有效期。

### 5.3 登录

登录入口同时支持：

- 手机号账号；
- 后台创建的测试账号。

处理逻辑：

1. 登录按钮先完成滑块验证；
2. 手机号格式账号走 UserModel 手机登录；
3. 非手机号账号走 TestAccountService；
4. 测试账号额外检查账号状态和有效期；
5. 登录成功后建立 ThinkCMF 用户 Session；
6. 账号被后台停用或测试期限过期时拒绝进入控制台。

### 5.4 找回密码

- 手机号账号可通过滑块和短信验证码设置新密码；
- 密码限制为8～32位；
- 测试账号密码由管理员在后台重置，不走手机号找回流程。

### 5.5 登录状态接口的字符编码兼容

旧用户数据中的昵称或登录名可能不是有效 UTF-8。原 isLogin 接口如果直接把完整用户数组交给 JSON 编码，可能出现“Malformed UTF-8 characters”并导致页面无法取得登录状态。

当前两个历史登录状态接口只返回必要字段：

- id；
- user_login；
- user_nickname。

normalizeUtf8Text 会先检查字符串是否已经是 UTF-8；否则尝试从 GB18030、GBK 或 BIG-5 转为 UTF-8，仍无法转换时返回空字符串，避免单个历史字段让整个 JSON 响应失败。

### 5.6 涉及文件

- app/imageai/controller/AuthController.php
- app/imageai/service/TestAccountService.php
- simplewind/cmf/common.php
- api/ai/controller/AiimageController.php
- api/portal/controller/AiimageController.php
- public/themes/Acme/imageai/register.html
- public/themes/Acme/imageai/login.html
- public/themes/Acme/imageai/forgot_password.html
- public/themes/Acme/public/assets/js/imageai/auth.js

---

## 6. 账号模式与统一访问控制

### 6.1 账号模式

imageai_user_profile.account_mode 当前使用三类值：

| 模式 | 含义 |
| --- | --- |
| api_only | ImageAI 注册入口创建的 API 专用账号 |
| normal | 原系统已有或普通账号，可按权限使用网页与 API |
| test | 后台创建的测试账号 |

### 6.2 状态判断

AccessService 提供账号模式、账号状态和生成资格的集中判断方法，组合检查：

- user.user_status 是否启用；
- imageai_user_profile.status 是否启用；
- 测试账号是否启用；
- 测试账号是否过期；
- 是否为 API 专用账号；
- 实名认证和年龄是否满足生成条件。

### 6.3 使用位置

完整的账号状态、实名和年龄判断应用于：

- 开发者控制台初始化；
- /imageai/create 页面入口；
- create_for_web 网页请求；
- 用户 API Key 创建；
- 用户 API Key 每次鉴权；
- create_for_test 的用户 API Key 调用。

原 /ai 桌面页面、移动页面及部分历史生图接口目前只调用 AccessService::isApiOnly，用于阻止 api_only 账号使用旧网页服务；这些旧入口没有统一调用 generationEligibility。因此“实名认证且年满18周岁”的完整门禁目前只对新的 ImageAI 网页生图和用户 API Key 链路有代码保证，不能描述为已经覆盖项目内所有历史生图接口。

### 6.4 涉及文件

- app/imageai/service/AccessService.php
- app/imageai/controller/AccountController.php
- app/imageai/controller/CreateController.php
- app/ai/controller/IndexController.php
- app/ai/controller/MobileController.php
- api/ai/controller/AiimageController.php

---

## 7. 腾讯云实名认证与成年判断

### 7.1 功能目标

实名认证门禁默认开启。开启时，用户自行创建和使用 API Key，以及使用新的 ImageAI 网页生图前，必须完成实名认证且年满18周岁。管理员如果关闭门禁，RealNameService::eligibility 会直接放行，因此这是可管理的业务门禁，不是无法关闭的硬编码限制。

后台系统测试 Bearer Key不绑定用户，不执行用户实名认证和积分判断。测试账号是否要求实名由后台开关控制。

### 7.2 状态设计

实名认证状态包括：

| 状态 | 含义 |
| --- | --- |
| unverified | 未提交或未认证 |
| verified_adult | 实名一致且已满18周岁 |
| verified_minor | 实名一致但未满18周岁 |
| failed | 腾讯云校验未通过或调用失败 |

### 7.3 认证流程

1. 用户阅读实名认证信息处理说明并勾选同意；
2. 提交真实姓名和身份证号；
3. 服务端校验姓名长度和身份证格式；
4. 从身份证号解析出生日期；
5. 检查用户当日认证次数；
6. 对身份证号生成带服务端密钥的 HMAC；
7. 如开启“一证一账号”，检查 HMAC 是否已绑定其他用户；
8. 通过 TencentRealNameClient 调用腾讯云 FaceID 身份证二要素接口；
9. 将腾讯云结果映射为用户可读提示；
10. 一致时根据出生日期写入成年或未成年状态；
11. 保存成年日期、证件尾号、腾讯云请求编号和认证时间；
12. 不在数据库长期保存完整姓名和完整身份证号。

### 7.4 年龄自动转正

未成年记录保存 adult_eligible_at。每次执行 eligibility 时，如果当前时间已经达到成年日期，系统会自动把状态从 verified_minor 更新为 verified_adult，无需用户重新认证。

### 7.5 门禁逻辑

AccessService::generationEligibility 的判断顺序：

1. 账号是否启用；
2. 实名门禁是否在后台开启；
3. 实名数据表是否可用；
4. 测试账号是否配置为免实名；
5. 是否存在实名记录；
6. 是否未成年；
7. 是否已经认证为成年人。

不符合条件时返回明确 code 和 message，例如：

- REAL_NAME_REQUIRED：请先完成实名认证；
- ADULT_REQUIRED：未满18周岁不能使用图片生成服务；
- REAL_NAME_TABLE_MISSING：实名模块尚未完成初始化。

### 7.6 后台配置

可配置：

- 是否启用实名认证门禁；
- 测试账号是否也要求实名；
- 是否限制一证一账号；
- 每日认证次数；
- 腾讯云调用超时；
- 腾讯云 FaceID SecretId 和 SecretKey。

成年门槛在当前保存逻辑中固定为18岁，不提供后台修改入口。测试账号默认也要求实名，管理员可通过“测试账号也要求实名”开关放宽。

### 7.7 涉及文件

- app/imageai/service/RealNameConfig.php
- app/imageai/service/RealNameService.php
- app/imageai/service/TencentRealNameClient.php
- app/imageai/service/AccessService.php
- app/imageai/controller/AccountController.php
- public/themes/Acme/imageai/account/real_name.html
- public/themes/admin_simpleboot3/imageai/admin_portal/real_names.html
- composer.json
- composer.lock
- database/imageai_real_name.sql
- database/imageai_real_name_rollback.sql

composer.json 和 composer.lock 在此功能中用于引入腾讯云 FaceID SDK，属于实名认证业务依赖。

---

## 8. API Key 生命周期管理

### 8.1 创建规则

创建 API Key 前执行 generationEligibility，因此用户必须：

- 账号有效；
- 已完成实名认证；
- 已年满18周岁；
- 或者属于后台明确允许免实名的测试账号。

每个用户最多保留5条 status=1 的 Key 记录。当前计数不会主动排除已经过期但尚未撤销的记录，过期也不会自动把 status 改为0；因此此处更准确的含义是“最多5条未撤销记录”，而不是“最多5条当前仍在有效期内的记录”。

Key 格式为 wb_sk_ 加随机字节的十六进制字符串。数据库只保存：

- SHA-256 哈希；
- 前缀；
- 后6位；
- 用户ID；
- 名称；
- 状态；
- 有效期；
- 最近使用时间和 IP。

明文只在创建成功后通过 Session 展示一次，刷新或离开页面后不再显示。

### 8.2 有效期

页面支持：

- 长期有效；
- 30天；
- 90天；
- 365天。

authenticate 同时检查 Key 是否存在、是否启用和是否过期。

页面状态列当前只根据 status 显示“有效/已撤销”，有效期单独显示；已经过期但尚未撤销的 Key 仍可能显示“有效”，但服务端鉴权一定会拒绝使用。

### 8.3 使用时二次检查

用户即使已经持有 Key，每次调用 API 时仍重新检查：

- 用户账号状态；
- ImageAI 用户状态；
- 测试账号有效期；
- 实名认证状态；
- 是否年满18周岁。

因此，用户后来被停用、Key 到期、实名状态被重置或未成年时，旧 Key 也不能继续调用。

### 8.4 限流

每个 Key 按分钟使用缓存计数，后台可配置每分钟上限。达到上限时返回“请求过于频繁，请稍后再试”。

### 8.5 创建失败提示

未实名或未成年时，创建按钮不再使用无法触发事件的原生 disabled 状态，而是：

- 页面顶部显示具体资格原因；
- 点击创建按钮弹出具体原因；
- 即使绕过前端提交，后端仍拒绝创建；
- 后端失败通过 Session 返回 API Key 页面显示，不进入无上下文错误页。

### 8.6 撤销

用户只能撤销属于自己的 Key；管理员可在后台撤销任意用户的 Key。撤销后 status 变为0，无法再次鉴权。

### 8.7 涉及文件

- app/imageai/service/ApiKeyService.php
- app/imageai/controller/AccountController.php
- public/themes/Acme/imageai/account/api_keys.html
- public/themes/admin_simpleboot3/imageai/admin_portal/keys.html
- database/imageai_portal.sql

---

## 9. 网页生图页面

### 9.1 页面定位

/imageai/create 是独立的文生图页面，页面名称为“鉴渊文博-文生图平台”。该页面与原 /ai 页面模板、样式和脚本相互独立，方便继续单独调整。

### 9.2 页面简化

当前页面保留：

- 文本提示词；
- 示例提示词下拉框；
- 生成按钮；
- 清空按钮；
- 右侧生成结果区；
- Loading 状态；
- 打开原图入口；
- Metadata 查看按钮；
- 用户头像下拉菜单。

页面已经移除：

- Moonshot 翻译；
- 诗文成艺；
- 古籍创艺；
- 万物成艺；
- 国潮产品；
- 工美图谱；
- 造型、纹样、工艺 Tab；
- 参考图；
- 艺术云库；
- 材质更换；
- 图片数量选择；
- 充值入口；
- 页面积分显示；
- 多余的生成完成提示。

### 9.3 生成逻辑

1. 页面加载后台配置的示例提示词；
2. 默认选择第一条并填入输入框；
3. 用户可切换示例或自行编辑；
4. 点击生成后调用 create_for_web；
5. 右侧结果区显示 Loading；
6. 服务端固定调用 jianyuan 模型并强制生成1张；
7. 完成后直接将图片加载到结果区；
8. 返回失败时显示接口给出的业务提示；
9. 登录用户可以查看该作品 Metadata。

### 9.4 入口控制

- 未登录用户可以打开展示页面，但真正生成时会被接口要求登录；
- api_only 账号进入页面时被引导到 API Key 页面；
- 停用或过期账号被拒绝；
- 未实名或未成年账号被引导到实名认证页面。

### 9.5 涉及文件

- app/imageai/controller/CreateController.php
- public/themes/Acme/imageai/create.html
- public/themes/Acme/public/assets/js/imageai/create.js
- public/themes/Acme/public/assets/css/imageai/create.css
- public/themes/Acme/public/assets/css/imageai/create-main.css
- public/themes/Acme/public/assets/css/imageai/create-v2.css

---

## 10. 图片生成接口

### 10.1 接口入口

主要接口：

- POST /api/ai/imagegenerate/create_for_test：用户 API Key或后台测试 Bearer Key；
- POST /api/ai/imagegenerate/create_for_web：网页登录态；
- GET /api/ai/imagegenerate/metadata：读取当前用户作品 Metadata。

### 10.2 请求鉴权

create_for_web：

- 使用 ThinkCMF 登录 Session；
- 拒绝未登录用户；
- 拒绝停用账号；
- 拒绝 api_only 账号；
- 检查实名与成年资格。

create_for_test：

- 从 Authorization: Bearer 请求头提取 Token；
- 优先匹配后台测试 Bearer Key；
- 未匹配时按 SHA-256 哈希查找用户 API Key；
- 用户 Key再次检查账号、实名、年龄和有效期；
- 对用户 Key执行每分钟限流；
- 后台测试 Key标记 source=api_test，不绑定用户，也不扣用户积分。

### 10.3 请求参数

核心参数：

- model：不填写时使用后台默认值；
- dialogue：从最后一条 role=user 的有效消息提取提示词；
- stream：决定结果放在 content 还是 choices；
- max_tokens：兼容接收但不参与图片生成。

当前只允许 jianyuan 模型。网页调用固定为 jianyuan。

ImagegenerateController 中仍保留无界、旧即梦查询等历史方法和服务对象，但 /imageai/create 及新的用户 API Key链路只调用 runJianyuanGeneration。页面上移除旧能力不等于已经删除所有历史后端方法。

### 10.4 即梦任务流程

1. 调用 JimengService::submitTask；
2. force_single=true，固定生成1张；
3. 保存上游 task_id；
4. 按后台 poll_interval 轮询；
5. done 状态提取第一张图片；
6. failed、expired、not_found 直接结束；
7. 超过 max_wait_time 返回生成超时；
8. 提交、查询、空结果和超时都会记录失败日志。

### 10.5 响应格式

接口统一返回 HTTP 200，并返回：

- content；
- choices；
- status；
- reason；
- request_id；
- 必要时附加 data。

非流式成功时图片 URL 放在 content；流式兼容模式放在 choices[0].delta。

### 10.6 涉及文件

- api/ai/controller/ImagegenerateController.php
- api/ai/service/external/JimengService.php
- api/ai/service/external/VolcEngineSignService.php
- api/ai/service/external/WujieService.php
- app/ai/service/ImageGenerationConfig.php

---

## 11. 内容类目判断与统一拒答

### 11.1 判断维度

生图前调用配置的对话模型判断三个维度：

- is_sensitive：是否包含敏感或不允许的内容；
- is_artwork：是否属于艺术品、工艺美术品、文创作品或文博领域；
- is_restricted_domain：是否属于不允许服务的受限领域。

### 11.2 决策规则

| 条件 | 内部决策 | 内部真实原因 |
| --- | --- | --- |
| 服务正常且三个条件均通过 | allow | success |
| is_sensitive=true | reject | 输入内容包含敏感信息，拒绝服务 |
| 非艺术文博领域或属于受限领域 | reject | 提示词不属于艺术品/工艺美术品/文创作品范畴，拒绝生成 |
| 分类服务不可用 | error | 内容类目判断服务暂不可用 |

分类服务异常采用 fail-close：不继续调用真实生图，避免审核服务故障时绕过判断。

### 11.3 返回格式兼容

parseClassifierContent 支持解析：

- 标准 JSON；
- Markdown 代码块或附加说明中可被首个大括号片段提取的 JSON；
- HTML 实体编码后的 JSON；

HTML 实体最多执行两轮解码。当前实现不会递归解包被 JSON 字符串再次包裹的对象；这类结果会按解析失败处理并执行 fail-close。

对于临时网络错误、限流和部分5xx响应，checkPromptWithAi 会按规则重试；不可重试错误直接返回服务异常。

ImagegenerateController 目前仍会构造 `YidunTextCheckService`，但新的 `runJianyuanGeneration` 流程没有调用该对象。当前三维度判断只由后台配置的类目判断模型完成，不能把易盾描述为此链路已经启用的第二层审核。

### 11.4 统一对外文案

无论是敏感内容、非文博领域还是类目服务异常，网页和 API 对外统一显示后台配置的拒答文案。默认文案为：

“我是一个文博领域的图片生成垂域大模型，你的问题不在我的能力范围，请更换问题或者提示词再试一次。”

内部真实原因不会丢失：

- imageai_api_request_log.reason 保存真实审计原因；
- imageai_failure_log 保存分类服务技术错误；
- 后台可按真实原因筛选；
- 用户个人请求记录会把内部原因转换为统一对外文案。

### 11.5 涉及文件

- api/ai/controller/ImagegenerateController.php
- app/ai/service/ImageGenerationConfig.php
- app/imageai/controller/AccountController.php
- app/imageai/controller/AdminPortalController.php
- public/themes/admin_simpleboot3/admin/setting/site.html
- public/themes/admin_simpleboot3/imageai/admin_portal/requests.html

---

## 12. 积分计费

### 12.1 计费设计

后台配置 API 单张积分。真实生图执行前对绑定用户进行原子预扣：

- user_points.points 必须大于等于所需积分；
- 使用带 points >= 扣费额条件的 setDec；
- 并发请求不能把余额扣成负数；
- 后台测试 Bearer Key因 user_id=0 不扣用户积分。

### 12.2 退款逻辑

预扣成功后，只要最终 response 不是成功：

- specResponse 检查 imageAiPointsReserved；
- 调用 BillingService::refund 加回积分；
- 将预扣标记清零，避免重复退款。

成功时，请求日志记录实际消耗积分。

### 12.3 涉及文件

- app/imageai/service/BillingService.php
- api/ai/controller/ImagegenerateController.php
- app/imageai/service/TestAccountService.php
- app/admin/controller/SettingController.php

---

## 13. 显式文字水印

### 13.1 功能描述

生成图片保存前自动加入显式 AI 生成标识。默认文字为“鉴渊AI生成”。

### 13.2 处理顺序

显式水印必须先绘制，AIGC Metadata 后写入。原因是 GD 重新编码 PNG 时会丢失原有文本和 XMP 数据。

正确顺序为：

1. 下载原图；
2. GD 解码；
3. 绘制黑色底和白色文字；
4. PNG 重新编码；
5. 写入 AIGC Metadata；
6. 保存文件。

### 13.3 水印约束

- 仅允许处理有效 PNG；
- 服务器必须具备 GD 和 FreeType；
- 使用 api/ai/controller/AigcWatermarkFont.ttf；
- 文字必须包含 AI/人工智能和生成/合成；
- 实际文字高度不得小于图片短边配置百分比；
- 支持左上、右上、左下、右下；
- 水印过长、字体缺失、尺寸不合规时不发布未标识图片。

### 13.4 涉及文件

- api/ai/controller/ImagegenerateController.php
- api/ai/controller/AigcWatermarkFont.ttf
- app/ai/service/ImageGenerationConfig.php
- public/themes/admin_simpleboot3/admin/setting/site.html

---

## 14. PNG AIGC Metadata

### 14.1 写入内容

系统在 PNG iTXt XMP 数据块中写入 AIGC JSON，主要字段：

- Label；
- ContentProducer；
- ProduceID；
- ReservedCode1；
- ContentPropagator；
- PropagateID；
- ReservedCode2。

默认命名空间为 http://www.tc260.org.cn/ns/AIGC/1.0/，前缀默认为 TC260。

### 14.2 ProduceID 规则

ProduceID 规则：

ID前缀 + UTC时间 YYYYMMDDHHMMSS + 128位安全随机数。

示意：

wb-20261025120131-32位十六进制随机值

当前算法提供极低碰撞概率的全局唯一标识，但没有使用中央数据库唯一约束，因此属于概率唯一设计，而不是数学意义上绝对不重复。

首次生成和传播由同一服务完成时：

- PropagateID 直接等于 ProduceID；
- 写入后的 ProduceID 同时保存到 imageai_work_detail.produce_id；
- 后续查看 Metadata 时核对文件 ProduceID、PropagateID及数据库记录。

### 14.3 替换逻辑

AigcPngMetadata::replace 会：

1. 校验 PNG 签名；
2. 校验所有数据块长度和 CRC；
3. 生成新的 AIGC XMP；
4. 清理原有文本块中的 AIGC 标识；
5. 在第一个 IDAT 前插入新的 iTXt；
6. 如果 EXIF 中存在无法安全重写的旧 AIGC 标识，则终止处理；
7. 校验 IEND 及尾随数据；
8. 返回重新构造的 PNG。

### 14.4 Metadata 查看

网页作品结果提供“查看 Metadata”按钮：

1. 前端携带 request_id 请求 metadata 接口；
2. 服务端要求用户已经登录；
3. 只允许查看属于当前用户的作品；
4. 优先读取服务器本地文件，必要时下载作品 URL；
5. 通过远程 URL 下载时限制最大图片大小为30MB；直接读取本地作品文件时当前没有同等的显式大小上限；
6. 解析格式、尺寸、文件大小、XMP 命名空间和 AIGC 字段；
7. 返回 ProduceID 与 PropagateID 是否一致；
8. 返回 ProduceID 与数据库作品记录是否一致。

### 14.5 涉及文件

- api/ai/controller/AigcPngMetadata.php
- api/ai/controller/ImagegenerateController.php
- public/themes/Acme/public/assets/js/imageai/create.js
- app/imageai/service/RequestService.php

---

## 15. 本地存储、又拍云同步与降级

### 15.1 本地保存

生成结果首先保存到服务器：

- 根据图片二进制检测真实格式；
- 当前标识流程只接受 PNG；
- 按日期创建 upload/aigc/YYYYMMDD 目录；
- 文件名使用时间和随机摘要；
- 保存 URL、物理路径、宽度、高度和 ProduceID。

### 15.2 又拍云同步

如果后台开启同步：

1. 按日期构造又拍云远程路径；
2. 使用 UpyunService 上传本地文件；
3. 上传成功后，对外返回又拍云 URL；
4. 上传失败时记录结构化失败日志；
5. 失败不影响本次生成交付，直接返回本地 URL。

### 15.3 后台重新同步

管理员可对又拍云失败记录执行重试：

1. 检查本地文件仍然存在；
2. 重新读取当前又拍云配置；
3. 上传到原远程路径；
4. 成功后将失败日志标记 resolved；
5. 同步更新请求日志、作品主表和作品详情表中的图片 URL；
6. 失败时增加 retry_count 并保留最后一次错误。

### 15.4 涉及文件

- api/ai/service/storage/UpyunService.php
- api/ai/controller/ImagegenerateController.php
- app/imageai/service/FailureLogService.php
- public/themes/admin_simpleboot3/imageai/admin_portal/failure_logs.html

---

## 16. 失败日志与排障

### 16.1 记录范围

失败日志可记录：

- configuration：配置不完整；
- classifier：类目判断服务异常；
- jimeng：任务提交、查询、状态或超时异常；
- local_storage：本地图片保存失败；
- upyun：又拍云配置或上传异常。

### 16.2 数据内容

记录内容包括：

- request_id；
- user_id；
- api_key_id；
- 阶段；
- 错误码和错误信息；
- HTTP 状态；
- 提示词 SHA-256 摘要；
- 本地图片 URL和物理路径；
- 远程路径和远程 URL；
- 脱敏后的上下文；
- 重试次数；
- pending、resolved、ignored 状态；
- 客户端 IP和时间。

### 16.3 脱敏

- 不保存完整 API Key；
- Bearer Token 自动替换为 REDACTED；
- password、secret、token、access_key、api_key 等上下文字段自动隐藏；
- 完整提示词不会进入失败上下文；
- 提示词只保存 SHA-256 摘要；
- 错误信息中如包含原提示词，会替换为 [PROMPT_REDACTED]。

### 16.4 降级日志

如果失败日志数据表本身不可用：

- 写入 runtime/log/imageai/YYYYMMDD.log；
- 如果文件也无法写入，则使用 error_log；
- 失败日志故障不会中断主生图请求。

### 16.5 保留周期

日志默认保留90天。每天第一次写日志时触发一次清理，清理失败不会影响业务。

### 16.6 涉及文件

- app/imageai/service/FailureLogService.php
- api/ai/controller/ImagegenerateController.php
- app/imageai/controller/AdminPortalController.php
- public/themes/admin_simpleboot3/imageai/admin_portal/failure_logs.html
- database/imageai_failure_logs.sql
- database/imageai_failure_logs_rollback.sql

---

## 17. 请求审计、来源和作品留档

### 17.1 request_id

每次请求使用 random_bytes 生成32字符十六进制 request_id，贯穿：

- API 响应；
- 请求日志；
- 作品主表 key；
- 作品详情；
- 失败日志；
- Metadata 查询。

### 17.2 请求来源

source 字段区分：

| 值 | 含义 |
| --- | --- |
| web | /imageai/create 网页调用 |
| api | 用户自行创建的 API Key |
| api_test | 后台配置的系统测试 Bearer Key |

旧数据在增加 source 字段时根据 api_key_id 和 user_id 回填。

### 17.3 请求日志

imageai_api_request_log 保存：

- request_id；
- 用户和 API Key；
- 模型；
- 来源；
- success、reject、error 状态；
- 真实内部原因；
- 提示词摘要；
- 图片 URL；
- 积分；
- 耗时；
- IP和时间。

### 17.4 作品留档

生成成功后同时写入：

1. ai_user_works：供现有作品系统和前台作品列表使用；
2. imageai_work_detail：保存 ImageAI 扩展信息。

扩展信息包括：

- request_id；
- user_id；
- api_key_id；
- source；
- model；
- prompt_digest；
- 上游 task_id；
- image_url；
- 宽高；
- ProduceID。

网页、用户 API 和系统测试 Key生成的图片都会进入作品数据；系统测试 Key使用 user_id=0，并显示为“系统测试 Key”。

请求日志和失败日志只保存提示词摘要；生成成功时，完整提示词仍会写入原有 ai_user_works.prompt，供作品功能使用。

### 17.5 涉及文件

- app/imageai/service/RequestService.php
- api/ai/controller/ImagegenerateController.php
- app/imageai/controller/AccountController.php
- app/imageai/controller/AdminPortalController.php
- database/imageai_portal.sql
- database/imageai_request_source.sql
- database/imageai_request_source_rollback.sql

---

## 18. 开发者控制台

### 18.1 控制台首页

展示：

- 可用积分；
- 有效 API Key数量；
- API 请求数量；
- 生成作品数量；
- 当前账号能力。

作品数量直接统计 ai_user_works 中该用户所有带图片地址的记录，因此可能同时包含 ImageAI 新作品和该用户在旧生图功能中留下的历史作品。

账号能力区域会提示：

- 尚未实名或未成年；
- API 专用账号只能使用 API；
- 普通账号可使用网页生图和 API。

### 18.2 API 请求记录

用户可以查看：

- request_id；
- 模型；
- 状态；
- 对外说明；
- 消耗积分；
- 请求时间。

请求记录每页30条，并使用与作品页相同的自定义分页生成逻辑。

为防止暴露内部审核策略，敏感、越界和分类服务异常的真实原因在用户页面被替换为统一拒答，后台仍保留原始原因。

### 18.3 作品记录

- 只显示当前用户有图片 URL的作品；
- 数据来源是复用的 ai_user_works 主表，因此也可能显示同一用户的旧生图作品；
- 每页24条；
- 采用卡片网格；
- 点击图片可打开原图；
- 显示请求编号和生成时间；
- 使用自定义分页，避免旧分页器样式和参数问题。

### 18.4 账号安全

- 显示手机号或登录账号；
- 手机号账号引导通过短信重置密码；
- 测试账号提示由管理员重置密码。

### 18.5 涉及文件

- app/imageai/controller/AccountController.php
- public/themes/Acme/imageai/account/index.html
- public/themes/Acme/imageai/account/api_keys.html
- public/themes/Acme/imageai/account/requests.html
- public/themes/Acme/imageai/account/works.html
- public/themes/Acme/imageai/account/security.html
- public/themes/Acme/imageai/account/real_name.html

---

## 19. 协议管理

### 19.1 前台协议

系统提供独立的：

- 服务协议；
- 隐私政策。

页面显示标题、版本、发布日期和正文。

### 19.2 后台发布

管理员可以分别编辑：

- 标题；
- 版本号；
- HTML 正文。

发布时自动记录 published_at。新注册用户记录注册当时同意的协议版本、时间和 IP。

### 19.3 默认协议内容覆盖范围

服务协议包含：

- 服务内容；
- 账号和密钥保管；
- 内容使用规范；
- 计费和记录；
- 服务变更。

隐私政策包含：

- 手机号、密码摘要和验证码结果；
- 实名认证数据处理；
- API Key和调用记录；
- 提示词摘要和作品；
- 数据保护与用户权利。

### 19.4 涉及文件

- app/imageai/service/AgreementService.php
- app/imageai/controller/IndexController.php
- app/imageai/controller/AdminPortalController.php
- public/themes/Acme/imageai/service.html
- public/themes/Acme/imageai/privacy.html
- public/themes/admin_simpleboot3/imageai/admin_portal/agreements.html

---

## 20. 后台 ImageAI 管理中心

### 20.1 菜单

后台新增“ImageAI 开放平台”，包含：

- 开发者用户；
- API Keys；
- API 请求；
- 生成作品；
- 失败日志；
- 实名认证记录；
- 协议管理。

启停用户、撤销 Key、重试又拍云、重置实名和发布协议作为隐藏操作权限挂在对应菜单下。

### 20.2 开发者用户

页面显示：

- 用户ID；
- 登录账号；
- 手机号；
- 账号模式；
- 实名状态及认证时间；
- 服务协议和隐私协议版本；
- 同意时间和 IP；
- 账号状态。

支持：

- 按登录账号、手机号、昵称搜索；
- 启用或停用 ImageAI 用户；
- 重设用户密码。

重设密码逻辑：

1. 复用 AdminPortal/userStatus 的后台权限；
2. operation=reset_password 时进入密码分支；
3. 检查目标属于 ImageAI 用户；
4. 校验密码8～32位；
5. 校验两次输入一致；
6. 通过 cmf_password 更新 user.user_pass；
7. 不保存明文密码。

### 20.3 API Key管理

- 查看用户、Key名称、前后缀、状态和最近使用信息；
- 管理员可撤销有效 Key。

API Key的 expires_at 已保存在数据库，并在用户自己的 Key页面显示；当前后台 keys.html 没有单独展示有效期列。

### 20.4 API 请求管理

支持按以下条件筛选：

- success、reject、error；
- web、api、api_test；
- 内容拒绝；
- 领域拒绝；
- 分类服务异常。

后台显示真实内部 reason，而不是统一对外拒答。

### 20.5 生成作品

作品列表关联：

- 用户手机号或账号；
- 请求来源；
- 模型；
- API Key ID；
- 上游任务；
- ProduceID；
- 图片宽高；
- 图片地址。

后台列表以完整 ai_user_works 表为主表，只要求 picture_url 非空，因此可能包含旧业务生成的历史作品；没有对应 imageai_work_detail 的旧记录会显示回退字段或空的 ImageAI 扩展信息。

### 20.6 实名记录

- 按成年、未成年、失败状态筛选；
- 可按登录账号、手机号、昵称或腾讯云 RequestId 搜索；
- 查看用户、证件尾号、腾讯云请求编号、认证和尝试时间；
- 管理员重置时会删除该用户的实名记录，之后用户会重新进入未认证状态并需要重新认证。

### 20.7 失败日志

- 按阶段、状态、请求编号、用户ID和起止日期筛选；
- 查看脱敏上下文、HTTP状态和重试次数；
- 对又拍云失败执行重新同步。

### 20.8 涉及文件

- app/imageai/controller/AdminPortalController.php
- public/themes/admin_simpleboot3/imageai/admin_portal/_tabs.html
- public/themes/admin_simpleboot3/imageai/admin_portal/users.html
- public/themes/admin_simpleboot3/imageai/admin_portal/keys.html
- public/themes/admin_simpleboot3/imageai/admin_portal/requests.html
- public/themes/admin_simpleboot3/imageai/admin_portal/works.html
- public/themes/admin_simpleboot3/imageai/admin_portal/failure_logs.html
- public/themes/admin_simpleboot3/imageai/admin_portal/real_names.html
- public/themes/admin_simpleboot3/imageai/admin_portal/agreements.html
- database/imageai_admin_menu.sql

---

## 21. 测试账号管理

### 21.1 功能目标

管理员可以在后台快速生成用于评审、演示和测试的登录账号，不要求测试人员使用真实手机号注册。

### 21.2 批量创建

可设置：

- 账号前缀；
- 起始编号；
- 数量，单批最多500个；
- 统一密码；
- 初始积分；
- 有效期；
- 初始启用状态。

系统为每一批生成 batch_code，并保存批次信息。每个账号同时创建：

- user 用户记录；
- user_points 积分记录；
- imageai_user_profile，account_mode=test；
- imageai_test_account 测试账号扩展记录。

批量创建成功后会把本次账号和统一密码返回到后台结果区，供管理员当次复制；数据库只保存密码摘要，不保存明文。

### 21.3 单独创建

可指定：

- 完整测试账号名；
- 单独密码；
- 初始积分；
- 有效期；
- 状态。

### 21.4 批量管理

管理员勾选一个或多个账号后可以：

- 启用；
- 停用；
- 重置统一密码；
- 设置积分；
- 增加或扣减积分；
- 设置有效期。

操作逻辑对选中的所有 user_id 循环执行。批量设置积分已经改为逐个更新或补建 user_points 记录，不再只处理第一个账号。

### 21.5 登录与过期

TestAccountService::login：

- 按 user_login 查找；
- 校验密码摘要；
- 检查 user_status；
- 检查测试账号 status；
- 检查 expires_at；
- 登录成功后更新最近登录信息。

测试账号仍需完成滑块验证。是否需要实名认证由后台 require_test_accounts 控制。

### 21.6 涉及文件

- app/imageai/service/TestAccountService.php
- app/admin/controller/SettingController.php
- public/themes/admin_simpleboot3/admin/setting/site.html
- app/imageai/controller/AuthController.php
- database/imageai_test_accounts.sql
- database/imageai_test_accounts_rollback.sql

---

## 22. 数据表详细设计

### 22.1 imageai_user_profile

用途：保存 ImageAI 账号模式、来源、状态和协议同意信息。

关键字段：

- user_id：关联用户；
- account_mode：normal、api_only、test；
- register_source：注册来源；
- status：ImageAI 独立启停状态；
- service_agreement_version；
- privacy_agreement_version；
- agreement_accepted_at；
- agreement_ip。

### 22.2 imageai_api_key

用途：保存用户 API Key生命周期。

关键字段：

- key_hash：不可逆 SHA-256；
- key_prefix、key_suffix：后台识别；
- status；
- expires_at；
- last_used_at；
- last_used_ip。

### 22.3 imageai_api_request_log

用途：请求级审计和后台筛选。

关键字段：

- request_id；
- user_id；
- api_key_id；
- source；
- model；
- status；
- reason；
- prompt_digest；
- image_url；
- points；
- duration_ms；
- client_ip。

### 22.4 imageai_work_detail

用途：为原 ai_user_works 增加 ImageAI 专用字段。

关键字段：

- work_id；
- request_id；
- source；
- model；
- upstream_task_id；
- width、height；
- produce_id。

### 22.5 imageai_failure_log

用途：保存可排查、可重试、经过脱敏的技术错误。

关键字段：

- stage；
- error_code；
- error_message；
- http_status；
- local_file_path；
- remote_path；
- context_json；
- retry_count；
- status；
- resolved_at。

### 22.6 imageai_real_name_verification

用途：保存实名结果和年龄资格，不保存完整证件。

关键字段：

- status；
- provider；
- id_card_hmac；
- id_card_last4；
- adult_eligible_at；
- provider_request_id；
- verified_at；
- last_result_code；
- last_error_message。

### 22.7 imageai_test_account_batch

用途：记录测试账号批次。

关键字段：

- batch_code；
- account_prefix；
- start_number；
- account_count；
- initial_points；
- expires_at；
- created_by。

### 22.8 imageai_test_account

用途：记录单个测试账号状态和期限。

关键字段：

- user_id；
- batch_id；
- expires_at；
- status；
- created_by。

### 22.9 数据库文件

- database/imageai_portal.sql
- database/imageai_portal_rollback.sql
- database/imageai_failure_logs.sql
- database/imageai_failure_logs_rollback.sql
- database/imageai_real_name.sql
- database/imageai_real_name_rollback.sql
- database/imageai_test_accounts.sql
- database/imageai_test_accounts_rollback.sql
- database/imageai_request_source.sql
- database/imageai_request_source_rollback.sql
- database/imageai_admin_menu.sql

---

## 23. 主要代码文件总表

| 文件 | 主要改动或职责 |
| --- | --- |
| app/route.php | 增加 /imageai 全套路由 |
| app/ai/service/ImageGenerationConfig.php | 图片生成设置、密钥、默认值和运行配置检查 |
| app/admin/controller/SettingController.php | 保存图片生成、实名、测试账号配置和操作 |
| app/imageai/controller/IndexController.php | 门户、文档、协议、隐私页面 |
| app/imageai/controller/AuthController.php | 注册、登录、短信、滑块、退出、找回密码 |
| app/imageai/controller/AccountController.php | 控制台、Key、请求、作品、实名和安全 |
| app/imageai/controller/CreateController.php | 独立网页生图入口和资格判断 |
| app/imageai/controller/AdminPortalController.php | ImageAI 后台管理 |
| app/imageai/service/AccessService.php | 账号模式、状态和生成资格统一判断 |
| app/imageai/service/AgreementService.php | 协议默认内容、读取和发布 |
| app/imageai/service/ApiKeyService.php | Key创建、哈希鉴权、撤销和限流 |
| app/imageai/service/BillingService.php | 积分原子预扣和退款 |
| app/imageai/service/RequestService.php | 请求编号、请求日志和作品记录 |
| app/imageai/service/RealNameConfig.php | 实名参数和腾讯云凭据读取 |
| app/imageai/service/RealNameService.php | 实名状态、年龄、一证一账号和次数限制 |
| app/imageai/service/TencentRealNameClient.php | 腾讯云 FaceID 请求签名和调用 |
| app/imageai/service/TestAccountService.php | 测试账号创建、登录、批量操作和积分 |
| app/imageai/service/FailureLogService.php | 失败记录、脱敏、保留周期和又拍云重试 |
| simplewind/cmf/common.php | 私有服务配置读取和阿里云滑块验证公共逻辑 |
| data/conf/services.example.php | 旧业务第三方服务私有配置的空值模板 |
| tools/migrate_legacy_secrets.php | 从旧网站备份提取已知服务凭据到私有配置，过程不输出凭据值 |
| api/ai/controller/ImagegenerateController.php | 生图主流程、内容判断、计费、标识、存储和审计 |
| api/ApiHelper.php | 历史 API 方法校验、JSON 参数解析和标准错误返回 |
| api/ai/controller/AigcPngMetadata.php | PNG AIGC XMP写入、替换和读取 |
| api/ai/controller/AigcWatermarkFont.ttf | 显式水印字体 |
| api/ai/service/storage/UpyunService.php | 又拍云文件上传和错误结果 |
| api/ai/service/external/WujieService.php | 仍由历史无界接口使用的服务封装 |
| api/ai/service/external/YidunTextCheckService.php | 保留的易盾文本审核封装；当前未接入新的三维度分类链路 |
| api/ai/controller/AiimageController.php | 旧生图入口的 api_only 限制、标准错误返回及登录状态 UTF-8 兼容 |
| api/portal/controller/AiimageController.php | 门户登录状态接口的 UTF-8 兼容 |
| public/themes/Acme/imageai | ImageAI 门户和开发者控制台模板 |
| public/themes/Acme/public/assets/js/imageai | 注册登录与网页生图脚本 |
| public/themes/Acme/public/assets/css/imageai | 门户和网页生图样式 |
| public/themes/admin_simpleboot3/admin/setting/site.html | 图片、实名和测试账号设置页 |
| public/themes/admin_simpleboot3/imageai/admin_portal | ImageAI 后台页面 |

---

## 24. 重新确认结果

以下内容已经按当前代码逐项重新确认：

- [x] /imageai 使用独立路由、模板、样式和脚本；
- [x] 手机号注册需要短信验证码、密码和协议同意；
- [x] 登录支持手机号和后台测试账号，并保留滑块验证；
- [x] ImageAI 注册用户默认是 api_only；
- [x] 实名门禁默认开启；开启时，用户 API Key创建和每次使用都会重新检查实名与成年资格，成年门槛固定为18岁；
- [x] 未实名或未成年点击创建 Key时有明确提示，后端仍有强制校验；
- [x] 后台测试 Bearer Key可以真实生成，不绑定用户、不扣用户积分；
- [x] 网页调用、用户 API 和测试 Key都记录 source；
- [x] 网页与 API 成功生成都会写入作品数据；
- [x] 内容审核按敏感、文博领域和受限领域三类判断；
- [x] 对外使用统一拒答，后台保留真实内部原因；
- [x] 后台 API 请求支持状态、来源和三类原因筛选；
- [x] 生成固定为1张并直接调用即梦任务；
- [x] 图片先写显式水印，再写 AIGC Metadata；
- [x] ProduceID 与 PropagateID一致；
- [x] Metadata 可以按当前登录用户和 request_id 查看并进行一致性核对；
- [x] 又拍云失败会返回服务器本地图片，不会让整次生成失败；
- [x] 又拍云失败有结构化日志并支持后台重试；
- [x] 失败日志会隐藏密钥、Token和完整提示词；
- [x] 远程 Metadata 图片下载限制30MB，本地文件读取当前没有相同的显式限制；
- [x] 用户控制台提供 Key、请求、作品、实名和安全页面；
- [x] 后台提供用户、手机号、实名状态、Key、请求、作品、失败日志和协议管理；
- [x] 后台用户列表支持启停和重设密码；
- [x] 后台可批量或单独创建测试账号；
- [x] 测试账号支持批量密码、积分、状态和有效期管理；
- [x] 多选测试账号的积分操作会作用于全部选中账号；
- [x] 服务协议和隐私政策可在后台编辑发布并记录用户同意版本；
- [x] 图片生成、类目判断、水印、Metadata、又拍云、积分和示例提示词均可集中配置。
- [x] 旧业务第三方服务凭据已改为通过私有配置或环境变量集中读取；ImageAI 后台密钥与该私有配置链路的边界已写明；
- [x] 易盾服务对象目前未接入新的三维度分类流程，未错误描述为已经启用的第二层审核；
- [x] 新 ImageAI 链路完整执行实名门禁；原 /ai 等历史入口目前仅拦截 api_only 账号，未错误描述为已经全部覆盖实名门禁。

经上述复核，本文档描述与当前业务代码、页面模板和数据库结构一致。
