1. 下载本项目
2. 将所有文件复制到 Helpers/LAM 文件夹中，文件夹请自行创建
3. 在控制器中或其他地方调用 `use App\Helpers\LAM\AutoMakeFileParser;`
4. 实例化并使用 LAM :
``` php
...
// Sotrage 获取文件的默认路径为 `storage\app\`
$content = Storage::get('exampleRaw.txt');
$autoMaker = new AutoMakeFileParser();
$autoMaker->parse($content)->makeFiles();
...
```
```php
// 一句话写法
$rst = (new AutoMakeFileParser())->parse(Storage::get('exampleRaw.txt'))->makeFiles();
```
