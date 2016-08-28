# 金数据表单应用开发Laravel/socialite接口包
A PHP laravel framework for Jinshuju based on Socialite.（原作者：[Taylor Otwell](https://github.com/laravel/socialite))

Socialite：PHP Laravel框架下基于OAuth2.0的api接口调用包

## 介绍

基于[Taylor Otwell](https://github.com/taylorotwell)的[Laravel/Socialite](https://github.com/laravel/socialite)开发,Laravel框架下用于金数据表单应用开发的api包

## 开发文档

原始文档：[Laravel Socialite](https://github.com/laravel/socialite).

相关文档：[金数据api文档](https://github.com/jinshuju/jinshuju-api-docs)

### 安装

在 `composer.json` 中添加依赖 dependency:

`composer require Fermiz/SocialiteForJinshuju`

(也可以使用`composer require laravel/socialite`获取原始的包进行覆盖）

### 配置

在 `config/app.php` 的`providers`数组中添加`Laravel\Socialite\SocialiteServiceProvider`:

```php
'providers' => [
// Other service providers...

Laravel\Socialite\SocialiteServiceProvider::class,
],
```

在 `config/app.php` 的`aliases`数组中添加 `Socialite`:

```php
'Socialite' => Laravel\Socialite\Facades\Socialite::class,
```

在 `config/services.php` 配置文件中添加必要的api参数:

```php
'jinshuju' => [
'client_id' => '金数据应用ID',
'client_secret' => '金数据应用Secret',
'redirect' => '回调地址',
'auth_url' => 'https://account.jinshuju.net',//金数据认证地址
'api_url' =>  'https://api.jinshuju.net',//金数据api接口地址
],
```

此外，你还需要配置环境变量或直接在源码中修改：

```php
JINSHUJU_URL= 'https://jinshuju.net'//金数据网址
APP_DOMAIN= '应用的域名'
```

### 调用方法

使用方法前需要controller中引入`use Socialite`;

```php
使用`Socialite::driver('jinshuju')->rediect()`获取code并回调;
使用`Socialite::driver('jinshuju')->user()`取得accesstoken;
使用`Socialite::driver('jinshuju')->refresh()`使用refreshtoken刷新accesstoken;
使用`Socialite::driver('jinshuju')->autorefresh()`自动刷新accesstoken（定时任务crontab时使用）;
使用`Socialite::driver('jinshuju')->getFormByToken($token)`取得所有表单信息（$token为accesstoken）;
使用`Socialite::driver('jinshuju')->getFeildByToken($form,$token)`取得某个表单的所有字段信息（$form为表单的token（例如，https://jinshuju.net/forms/wBqXiH中的wBqXiH），$token为accesstoken）;
使用`Socialite::driver('jinshuju')->getDataByToken($form,$token)`取得获取表单某个字段的所有数据（同上）;
使用`Socialite::driver('jinshuju')->update($form,$redirect,$rectfields)`更新表单的跳转地址及附带参数;（$form为表单的token，$redirect为跳转地址，$rectfields为附带参数即选择的字段token如field_2）
使用`Socialite::driver('jinshuju')->submitValidate($fieldinfo,$jamr_h)`验证表单是否为有效提交;
```




