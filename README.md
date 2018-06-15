# LaravelAutoMake 简称 LAM
一款 `Laravel` 代码生成脚手架

### 什么是 LAM ?

`LaravelAutoMake` (Laravel 脚手架，自动生成器) 通过规则文本与模版逐步生成我们需要的控制器、模型、仓库、函数、属性、数据库表等需求，而我们可以避免更多重复劳动。

### 我们的目标？
* 消除码农一切不必要的重复性劳动 XD

### 使用方法：(1.0入口类名会变更，暂时如下)
```php
$rst = (new AutoMakeFileParser())->parse(Storage::get('exampleRaw.txt'))->makeFiles();
```

### 推荐的结构分层
-. Controller
-. Service
-. Repository
-. Models

### 分支说明
* master: 最新代码会在master，所以master是最新的，但是不保证稳定。且有一些公司自用的东西，所以提交记录可以参考，但不能直接使用master分支。
* release：是相对稳定的最新代码分支，也是LAM对外打包的分支
* 其它分支：根据开发需要，大的版本会以版本号为分支名，打一些临时分支。

### 最新Release (目前正在着手重构代码，未来发布1.0基础版本)
* [Release](https://github.com/taoismCoder/LAM/releases)

### 其它LINKS
* 我想查找详细的文档资料 => [文档/手册](https://github.com/taoismCoder/LAM/wiki)
* [如何使用LAM](https://github.com/taoismCoder/LAM/wiki/LAM%E4%BD%BF%E7%94%A8%E6%89%8B%E5%86%8C)
* [LAM开发实例](https://github.com/taoismCoder/LAM/wiki/LAM_Example)
* 我要反馈问题 => [Issues](https://github.com/taoismCoder/LAM/issues)
 
## About

    @version     v0.0.1
    @author      TaoismCoder
    @license     MIT

## Contact

    @问题反馈   https://github.com/taoismCoder/LAM/issues (推荐)
    @QQ群      283932057
    
## Contributors List 贡献者

[Contributors Details](https://github.com/taoismCoder/LAM/graphs/contributors)

## 新版待定 idea
- YAML ?
- Vue2 ?
- Python 客户端界面 ?
- composer laravel 模块 ?
