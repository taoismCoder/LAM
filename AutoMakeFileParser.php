<?php namespace App\Helpers\LAM;

use Artisan;
use App\Helpers\LAM\Parser\CommonParser;
use Illuminate\Support\Str;
/**
 * Class AutoMakeFileParser
 * 生成器入口类
 * @package  App\Helpers\LAM
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class AutoMakeFileParser extends CommonParser
{
    /**
     * 解析后的格式化数据
     * @var array
     */
    protected $parsed = [];

    /**
     * 要生成的文件类型
     * @var string
     */
    protected $makeType = '';

    /**
     * 表与关联的模型的数据
     * @var array
     */
    protected $modelTableRelation = [];

    /**
     * 匹配类型为 explode
     * @var string
     */
    const TYPE_EXPLODE = 'explode';

    /**
     * 匹配类型为正则
     * @var string
     */
    const TYPE_PREG = 'preg';

    /**
     * AutoMakeFileParser constructor.
     */
	public function __construct()
	{
		parent::__construct();
	}

    /**
     * 表信息解析器
     * @return \Illuminate\Foundation\Application|\App\Helpers\LAM\Parser\TableParser
     */
    protected function getTableParser()
    {
        return app('App\Helpers\LAM\Parser\TableParser');
    }

    /**
     * Migration 生成器
     * @return \Illuminate\Foundation\Application|\App\Helpers\LAM\Maker\MigrationMaker
     */
    protected function getMigrationMaker()
    {
        return app('App\Helpers\LAM\Maker\MigrationMaker');
    }

    /**
     * 设置解析后的格式化值
     * @param $parsed
     */
    protected function setParsed($parsed)
    {
        $this->parsed = $parsed;
    }

    /**
     * 获取解析后的格式化值
     * @return array
     */
    protected function getParsed()
    {
        return $this->parsed;
    }

    /**
     * 设置要生成的文件类型
     * @param $type
     */
    protected function setMakeType($type)
    {
        $this->makeType = $type;
    }

    /**
     * 要生成的文件类型
     * @param string $type
     * @return string
     */
    protected function getMakeType($type = '')
    {
        $rst = '';
        $type = $type ?: $this->makeType;
        switch ($type){
            case 'route':
                $rst = 'Route';
                break;
            case 'ctrl':
                $rst = 'Controller';
                break;
            case 'serv':
                $rst = 'Service';
                break;
            case 'res':
                $rst = 'Repository';
                break;
            case 'table':
                $rst = 'Table';
                break;
            case 'model':
                $rst = 'Model';
                break;
        };
        return $rst;
    }

	/**
	 * 解析源文件内容
	 * @return AutoMakeFileParser|array
	 */
	public function parse($raw)
	{
		// 重置生成类型
		$this->setMakeType('');

		$parsed = [];
		list($headings, $data) = $this->parseRawData($raw);

		if ( ! is_array($headings)) {
			return $parsed;
		}
		foreach ($headings as $key => $heading) {

			$parsed[] = [
				'type'  => $heading,
				'intro'  => $this->parseIntro($heading, $data[$key])
			];
		};

		unset($headings, $data);

		$this->setParsed($parsed);

		return $this;
	}

	/**
	 * 根据解析结果生成对应的文件
	 */
	public function makeFiles()
	{
		$fileParseRst = $this->getParsed();
		foreach ($fileParseRst as $item){
			$this->makeSingleFile($item['type'], $item['intro']);
		}
	}

    /**
     * 生成单个文件
     * @param $type
     * @param $intro
     * @return bool
     * @todo 将此方法重构为分配到不同的生成器中,尽量一句话
     */
	public function makeSingleFile($type, $intro)
	{
		$this->setMakeType($type);

		if($type == 'route'){
			return $this->makeRoute($intro);
		}

		if($type == 'serv'){
			return $this->makeService($intro);
		}

		// 会同时生成 res 中包含的 model
		if($type == 'res'){
			return $this->makeRepository($intro);
		}

		// table 中的 model 不在此列，此处只生成与 table 无关的model
		if($type == 'model'){
			return $this->makeModel($intro);
		}

		// 生成 table 对应的 migration
		if($type == 'table'){
			return $this->makeMigration($intro);
		}
	}

	/**
	 * 生成路由及控制器
	 * @todo 生成路由暂时忽略, 此方法改为自定义模板生成，不依赖 Artisan
	 * @param $intro
	 * @return bool
	 */
	private function makeRoute($intro)
	{
		$type = 'ctrl';

		foreach ($intro as $filePathName => $item){
			$needMakeFilePath = $this->getNeedMakeFilePath($filePathName, $type);
			if(!file_exists($needMakeFilePath)){
				// 生成基础 Controller
				Artisan::call('make:controller', ['name' => $filePathName]);
				echo $filePathName.' '.Artisan::output().'<br/>';
				// 修改基础 Controller, 追加 route 信息中存在的方法, 一个文件只会执行一次
				if(file_exists($needMakeFilePath)){
					echo '<li>开始装入需要的函数</li>';
					// 要添加的函数列表
					$funcArr = array_column($item, 'action');
					// 当前文件的文本内容
					$fileContents = $this->getFileContents($needMakeFilePath);
					// 函数模版内容
					$funcStubContent = $this->getFileContents($this->getStub('ctrl_func'));
					// 替换模版中的值，并追加到控制器内容中
					$this->appendFuncToFileContent($needMakeFilePath, $funcArr, $fileContents, $funcStubContent, false);
				}
			}else{
				echo $filePathName.' 文件已存在<br/>';
				return false;
			}

		}
		return true;
	}

	/**
	 * 生成服务类
	 * @param $intro
	 * @return bool
     * @todo 改为调用服务生成器 ServiceMaker
	 */
	private function makeService($intro)
	{
		$type = 'serv';
		$needMakeFiles = $intro;
		foreach ($needMakeFiles as $filePathName){
			// 判断文件是否存在，其中包含根据服务名称获取要生成的文件路径
			$fileIsExists = $this->alreadyExists($filePathName, $type);

			if(!$fileIsExists){
				// 获取 Service 生成模版
				$stubPath = $this->getStub($type);
				if (file_exists($stubPath)){
					// 根据服务名称获取需要生成的文件路径
					$needMakeFilePath = $this->getNeedMakeFilePath($filePathName, $type);
					// 需要创建的文件夹路径
					$needMakeDir = str_replace(class_basename($filePathName).'.php', '', $needMakeFilePath);
					// 需要替换的 tag 及对应的值
					$replaceArr = [
						'DummyNamespace' => $this->getFileNamespace($filePathName),
						'DummyClass' => class_basename($filePathName),
						'DummyUsePath' => $filePathName,
						'DummyMore' => ''
					];
					// 替换模版中的名称及相关信息
					$finalContents = $this->replaceStubTags($stubPath, $replaceArr);
					// 判断文件夹是否存在，不存在则创建文件夹
					if(!is_dir($needMakeDir)){
						mkdir($needMakeDir, 0755, true);
					}
					// 输出文件
					$this->put($needMakeFilePath, $finalContents);
					echo $needMakeFilePath.' 执行完毕<br/>';
				}else{
					echo $filePathName.' 模板不存在<br/>';
					return false;
				}
			}else{
				echo $filePathName.' 文件已存在<br/>';
				return false;
			}

		}
		return true;
	}

	/**
	 * 生成仓库类
	 * @param $intro
	 * @return bool
     * @todo 改为仓库生成器 RepositoryMaker
	 */
	protected function makeRepository($intro)
	{
		$type = $this->makeType;
		$needMakeFiles = $intro;
		foreach ($needMakeFiles as $filePathName){
			// 判断文件是否存在，其中包含根据服务名称获取要生成的文件路径
			$fileIsExists = $this->alreadyExists($filePathName, $type);
			if(!$fileIsExists){
				// 获取 Repository 生成模版
				$stubPath = $this->getStub($type);
				if (file_exists($stubPath)){
					// 根据服务名称获取需要生成的文件路径
					$needMakeFilePath = $this->getNeedMakeFilePath($filePathName, $type);
					// 需要创建的文件夹路径
					$needMakeDir = $this->getPathDir($needMakeFilePath);
					// 基础类名 或 对应的 Model 名称
					$baseClassName = class_basename($filePathName);
					// 当前文件完整的类名
					$dummyClassName = $baseClassName.$this->getMakeType();

					// 需要替换 model 模版的 tag 及对应的值
					$tableModelNamespace = $this->getFileNamespace($filePathName, 'model');
					$replaceModelArr = [
						'DummyNamespace' => $tableModelNamespace,
						'DummyClassName' => $baseClassName,
						'DummyUsePath' => $tableModelNamespace.'\\'.$baseClassName,
						'DummyFilePathName' => $filePathName
					];
					// 生成相关 Model, 传入生成的 Model 路径
					$this->makeTableModel($this->getNeedMakeFilePath($filePathName, 'model'), $replaceModelArr);

					// 需要替换 res 模版的 tag 及对应的值
					$replaceArr = [
						'DummyNamespace' => $this->getFileNamespace($filePathName),
						'DummyClassName' => $dummyClassName,
						'DummyUsePath' => $this->getFileNamespace($filePathName).'\\'.$dummyClassName,
						'DummyModelName' => $baseClassName,
						'DummyModelUsePath' => $this->getFileNamespace($filePathName, 'model').'\\'.$baseClassName,
						'DummyFilePathName' => $filePathName,
						'DummyMore' => ''
					];
					// 替换模版中的名称及相关信息
					$finalContents = $this->replaceStubTags($stubPath, $replaceArr);

					// 判断文件夹是否存在，不存在则创建文件夹
					if(!is_dir($needMakeDir)){
						mkdir($needMakeDir, 0755, true);
					}
					// 输出文件
					$this->put($needMakeFilePath, $finalContents);
					echo $needMakeFilePath.' 执行完毕<br/>';
				}else{
					echo $filePathName.' 模板不存在<br/>';
					return false;
				}
			}else{
				echo $filePathName.' 文件已存在<br/>';
				return false;
			}

		}
		return true;
	}

	/**
	 * 生成与数据表无关的模型类
	 * @param $intro
	 * @return bool
     * @todo 改为模型生成器
	 */
	protected function makeModel($intro)
	{
		$type = $this->makeType;
		$needMakeFiles = $intro;
		//todo 等待
		return true;
	}

	/**
	 * 生成 Migration
	 * @param $intro
	 * @return bool
     * @todo 改为Migration生成器
	 */
	protected function makeMigration($intro)
	{
		//$tableParsedArray = $this->getModelTableRelation();
        //TODO 建议：$migrationParsed = $this->getMigrationParser($this->parsed);
		//$this->getMigrationMaker()->setRawTxtArray($tableParsedArray)->makeMigration();
        //TODO 建议：$this->getMigrationMaker()->setParsed($migrationParsed)->make();
		return true;
	}

	/**
	 * 生成数据表模型类
	 * @param $realFilePath
	 * @param $replaceModelArr
	 * @return bool
     * @todo 改为表模型生成器
	 */
	protected function makeTableModel($realFilePath, $replaceModelArr)
	{
		// 判断当前文件是否存在
		if($this->alreadyExists($realFilePath, 'model')){
			echo $realFilePath.' 文件已存在<br/>';
			return false;
		}
		// 需要创建的文件夹路径
		$needMakeDir = $this->getPathDir($realFilePath);
		// 检查模版路径是否存在
		$stubPath = $this->getStub('model');
		if (!file_exists($stubPath)){
			echo $stubPath.' 模板不存在<br/>';
			return false;
		}
		// 判断文件夹是否存在，不存在则创建文件夹
		if(!is_dir($needMakeDir)){
			// 创建文件夹
			mkdir($needMakeDir, 0755, true);
		}

		// 替换模版中的名称及相关信息
		$finalContents = $this->replaceStubTags($stubPath, $replaceModelArr, 'model');
		// 输出文件
		if($this->put($realFilePath, $finalContents)){
			echo $realFilePath.' <b>执行完毕</b><br/>';
			return true;
		}else{
			echo $realFilePath.' <label style="color:red">执行失败</label><br/>';
			return false;
		}

	}

	/**
	 * 替换模版中的标签
	 * @param $filePath
	 * @param $replaceArr
	 * @param $type
	 * @return bool
     * @todo 替换字符等操作应放置在各自的解析器中，路径应该再生成器中，从解析器中的 getReplaceOption() 中获得需要替换的内容
	 */
	protected function replaceStubTags($filePath, $replaceArr, $type = '')
	{
		if(empty($replaceArr)){
			return false;
		}
		$type = !empty($type) ? $type : $this->makeType;
		$filePathName = $replaceArr['DummyFilePathName'] ?? '';
		$fileContents = file_get_contents($filePath);
		$baseReplace = str_replace(
			array_keys($replaceArr), array_values($replaceArr), $fileContents
		);
		if($this->makeType == 'res'){
			$modelTableRelation = $this->getModelTableRelation()[$filePathName] ?? [];
			$baseReplace = $this->getTableParser()->replaceTableEach($modelTableRelation, $baseReplace, $type);
		}

		return $baseReplace;
	}

	/**
	 * 获取文件的命名空间
	 * @param $rawFileName
	 * @param string $type
	 * @return string
     * @todo 各自的解析器负责组成各自文件的命名空间
	 */
	protected function getFileNamespace($rawFileName, $type = '')
	{
		if(empty($type)) $type = $this->makeType;
		$rootNamespace = trim(app()->getNamespace(), '\\');
		$targetNamespace = 'get'.$this->getMakeType($type).'Namespace';
		$serviceRootNamespace = $this->$targetNamespace($rootNamespace);
		return $serviceRootNamespace.'\\'.str_replace('/'.class_basename($rawFileName), '', $rawFileName);
	}

	/**
	 * 获取真实路径
	 * @param  string  $name
	 * @return string
     * @todo 各自的生成器负责组成各自文件的真实路径
	 */
	protected function getPath($name)
	{
		// 如果存在 App\ 则去除 App\
		$name = str_replace_first(app()->getNamespace(), '', $name);
		return app('path').'/'.str_replace('\\', '/', $name).'.php';
	}

	/**
	 * 检查文件是否存在
	 * @param  string $rawName
	 * @param $type
	 * @return bool
     * @todo 此处理方法应防止在生成器通用方法类(CommonMaker)中
	 */
	protected function alreadyExists($rawName, $type)
	{
		return file_exists($this->getNeedMakeFilePath($rawName, $type));
	}

	/**
	 * 根据原始名称获取对应类型的生成路径
	 * @param $rawName
	 * @param $type
	 * @return string
     * @todo 此方法放置在各自的生成器中
	 */
	protected function getNeedMakeFilePath($rawName, $type)
	{
		return $this->getPath($this->parseName($rawName, $type));
	}

	/**
	 * 根据源文件中的相对命名空间获取完整准确的命名空间
	 * @param  string $name
	 * @param string $type
	 * @return string
     * @todo 此方法放置在搁置的解析器中
	 */
	protected function parseName($name, $type)
	{
		$rootNamespace = app()->getNamespace();
		if (Str::startsWith($name, $rootNamespace)) {
			return $name;
		}

		if (Str::contains($name, '/')) {
			$name = str_replace('/', '\\', $name);
		}

		$rootNamespace = trim($rootNamespace, '\\');
		$suffix = '';
		switch ($type){
			case 'ctrl':
				$rootNamespace = $this->getControllersNamespace($rootNamespace);
				break;
			case 'res':
				// 如果书写生成文档时不包含后缀，则追加
				if(mb_strpos($name, 'Repository') === false){
					// 补充的后缀，方便简写
					$suffix = 'Repository';
				}
				$rootNamespace = $this->getRepositoryNamespace($rootNamespace);
				break;
			case 'serv':
				$rootNamespace = $this->getServiceNamespace($rootNamespace);
				break;
			case 'model':
				$rootNamespace = $this->getModelNamespace($rootNamespace);
				break;
		}

		return $this->parseName($rootNamespace.'\\'.$name.$suffix, $type);
	}

	/**
	 * 获取控制器的根命名空间
	 * @param  string  $rootNamespace
	 * @return string
     * @todo 放置在 Controller 解析器的 getRootNamespace() 中
	 */
	protected function getControllersNamespace($rootNamespace)
	{
		return $rootNamespace.'\Http\Controllers';
	}

	/**
	 * 获取仓库类的根命名空间
	 * @param  string  $rootNamespace
	 * @return string
     * @todo 放置在 Repository 解析器的 getRootNamespace() 中
	 */
	protected function getRepositoryNamespace($rootNamespace)
	{
		return $rootNamespace.'\Repository';
	}

	/**
	 * 获取服务类的根命名空间
	 * @param  string  $rootNamespace
	 * @return string
     * @todo 放置在 Service 解析器的 getRootNamespace() 中
	 */
	protected function getServiceNamespace($rootNamespace)
	{
		return $rootNamespace.'\Service';
	}

	/**
	 * 获取模型类的根命名空间
	 * @param  string  $rootNamespace
	 * @return string
     * @todo 放置在 Model 解析器的 getRootNamespace() 中
	 */
	protected function getModelNamespace($rootNamespace)
	{
		return $rootNamespace.'\Models';
	}

	/**
	 * 初步解析源文件，将解析结果分组
	 * @return array
	 */
	private function parseRawData($raw)
	{
		// 匹配 - - 中的内容
		$pattern = "/-(.*?)-/";
		preg_match_all($pattern, $raw, $headings);
		$data    = preg_split($pattern, $raw);

		if ($data[0] < 1) {
			$trash = array_shift($data);
			unset($trash);
		}
		// 清除第一个数组，-xxx-,只需要第二个就可以了
		array_shift($headings);

		return [end($headings), $data];
	}

	/**
	 * 解析对应类型的内容到数组
	 * 路由，控制器，仓库，服务
	 * @param $type
	 * @param $introRaw
	 * @return mixed
     * @todo 此方法在入口方法用新的方式实现后就可以删除了
	 */
	private function parseIntro($type, $introRaw)
	{
		switch ($type){
			case 'route':
				return $this->parseRouteIntro($introRaw);
				break;
			case 'ctrl':
				return $this->parseCtrlIntro($introRaw);
				break;
			case 'res':
				return $this->parseResIntro($introRaw);
				break;
			case 'serv':
				return $this->parseServIntro($introRaw);
				break;
			case 'table':
				return $this->parseTableIntro($introRaw);
				break;
			default:
				return [];
				break;
		}
	}

    /**
     * 通过初步分组的解析值再解析出生成路由用的格式化值
     * @param $introRaw
     * @return array
     * @todo 此方法交由 RouteParser 路由解析器类负责
     */
	private function parseRouteIntro($introRaw)
	{
		$pattern = "/(.*?),/";
		preg_match_all($pattern, $introRaw, $routes);
		array_shift($routes);

		$routeUrlPattern = "/(.*?)=>/";

		$routes = end($routes);
		$parseRoutes = [];

		foreach ($routes as $row){
			// 清除制表符
			$row = preg_replace('/[\t\r\n\s]/', '', $row);
			preg_match_all($routeUrlPattern, $row, $routeUrl);
			$routeActionAndName = preg_split($routeUrlPattern, $row);

			array_shift($routeUrl);
			$routeUrl = current(end($routeUrl));

			if ($routeActionAndName[0] < 1) {
				$trash = array_shift($routeActionAndName);
				unset($trash);
			}

			list($ctrlAndAction, $routeName) = explode('->', current($routeActionAndName));

			list($ctrl, $action) = explode('@', $ctrlAndAction);

			$parseRoutes[$ctrl][] = [
				'url' => $routeUrl,
				'action' => $action.'@'.$routeName,
				'name' => $routeName
			];

		}

		return $parseRoutes;
	}

	/**
	 * 解析出 仓库 相关列表
	 * @param $introRaw
	 * @return array
     * @todo 由仓库类解析器负责
	 */
	private function parseResIntro($introRaw)
	{
		$rst = $this->pregRaw(self::TYPE_EXPLODE, ',', $introRaw);
		return array_filter($rst);
	}

	/**
	 * 解析 控制器 相关列表
	 * @param $introRaw
	 * @return array
     * @todo 由控制器类解析器负责
	 */
	private function parseCtrlIntro($introRaw)
	{
		return [];
	}

	/**
	 * 解析出 服务 相关列表
	 * @param $introRaw
	 * @return array
     * @todo 由服务类解析器负责
	 */
	private function parseServIntro($introRaw)
	{
		$rst = array_filter($this->pregRaw(self::TYPE_EXPLODE, ',', $introRaw));
		return $rst;
	}

	/**
	 * 解析出 数据表 相关列表
	 * @param $introRaw
	 * @return array
     * @todo 由表信息解析器负责
	 */
	private function parseTableIntro($introRaw)
	{
		$rst = [];
		$baseParse = array_filter($this->pregRaw(self::TYPE_EXPLODE, '=>', $introRaw));

		foreach ($baseParse as $row){

			// 获取第一个 : 出现的位置并且提取该子串
			$titleStr = mb_substr($row, 0, mb_stripos($row, ':'));

			// 获得剩余的字符串, 需要把坐标+1, 排除掉第一个 :
			$row = mb_substr($row, mb_stripos($row, ':') + 1);

			// 将数据表标题分解到指定的变量
			list($tableModel, $tableName, $comment) = explode('|', $titleStr);

			// 去除表注释中包含的引号
			$comment = str_replace("'", '', $comment);

			// 获取表字段基础数组, 并去除空元素
			$baseFieldsStr = array_filter(explode('->', $row));

			// 循环表字段, 拆分为相应的数组
			$finalFields = [];
			foreach ($baseFieldsStr as $line){
				// 分割字段名和字段属性
				list($field, $detail) = explode('|', $line);
				// 分割字段属性
				$parseDetail = explode(',', $detail);
				$finalDetail = [];
				foreach ($parseDetail as $detailLine){
					list($lineName, $lineValue) = explode(':', $detailLine);
					// 如果属性值存在如 string@128 这种结构，那么留给后续生成的时候处理即可
					$finalDetail[$lineName] = $lineValue;
				}
				$finalFields[$field] = $finalDetail;
			}
			$rst[$tableModel] = [
				'name' => $tableName,
				'fields' => $finalFields,
				'model' => $tableModel,
				'comment' => $comment
			];
		}
		$this->setModelTableRelation($rst);
		return $rst;
	}

	/**
	 * 数据表解析后，放入到关联关系属性中，以便 res , model 生成循环时调用
	 * @param $parsedData
     * @todo 由表信息解析器负责
	 */
	protected function setModelTableRelation($parsedData)
	{
		$this->modelTableRelation = $parsedData;
	}

    /**
     * 获取解析后的表信息格式化值
     * @return array
     * @todo 由表信息解析器负责
     */
	protected function getModelTableRelation()
	{
		return $this->modelTableRelation;
	}

}
