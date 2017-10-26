<?php namespace App\Helpers\LAM;

use Artisan;
use Illuminate\Support\Str;
use App\Helpers\LAM\TableEachFieldParser;
/**
 * Class     AutoMakeFileParser
 *
 * @package  App\Helpers\LAM
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class AutoMakeFileParser extends CommonParser
{

	public function __construct()
	{
		parent::__construct();
	}

	/* ------------------------------------------------------------------------------------------------
	 |  Properties
	 | ------------------------------------------------------------------------------------------------
	 */
	/**
	 * Parsed data.
	 *
	 * @var array
	 */
	protected $parsed = [];

	/**
	 * 要生成的文件类型
	 * @var string
	 */
	protected $makeType = '';

	/**
	 * 匹配类型为 explode
	 */
	const TYPE_EXPLODE = 'explode';

	/**
	 * 匹配类型为正则
	 */
	const TYPE_PREG = 'preg';

	/**
	 * 表与关联的模型的数据
	 * @var array
	 */
	protected $modelTableRelation = [];

	/* ------------------------------------------------------------------------------------------------
	 |  Main Functions
	 | ------------------------------------------------------------------------------------------------
	 */
	/**
	 * Parse file content.
	 *
	 * @param  string  $raw
	 *
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

	protected function setParsed($parsed)
	{
		$this->parsed = $parsed;
	}

	protected function getParsed()
	{
		return $this->parsed;
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
	}

	/* ------------------------------------------------------------------------------------------------
	 |  Other Functions
	 | ------------------------------------------------------------------------------------------------
	 */

	/**
	 * 要生成的文件类型
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
	 * 生成路由及控制器
	 * @todo 生成路由暂时忽略
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
					$funcArr = array_column($item, 'action');
					$fileContents = $this->getFileContents($needMakeFilePath);
					$funcStubContent = $this->getFileContents($this->getStub('ctrl_func'));

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
	 */
	protected function makeModel($intro)
	{
		$type = $this->makeType;
		$needMakeFiles = $intro;
		//todo 等待
		return true;
	}

	/**
	 * 生成数据表模型类
	 * @param $realFilePath
	 * @param $replaceModelArr
	 * @return bool
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
			$baseReplace = $this->getTableEachFieldParse()->replaceTableEach($modelTableRelation, $baseReplace, $type);
		}

		return $baseReplace;
	}

	/**
	 * 获取文件的命名空间
	 * @param $rawFileName
	 * @param string $type
	 * @return string
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
	 * Get the destination class path.
	 *
	 * @param  string  $name
	 * @return string
	 */
	protected function getPath($name)
	{
		// 如果存在 App\ 则去除 App\
		$name = str_replace_first(app()->getNamespace(), '', $name);
		return app('path').'/'.str_replace('\\', '/', $name).'.php';
	}

	/**
	 * Determine if the class already exists.
	 *
	 * @param  string $rawName
	 * @param $type
	 * @return bool
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
	 */
	protected function getNeedMakeFilePath($rawName, $type)
	{
		return $this->getPath($this->parseName($rawName, $type));
	}

	/**
	 * Parse the name and format according to the root namespace.
	 *
	 * @param  string $name
	 * @param string $type
	 * @return string
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
	 * Get the controllers namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getControllersNamespace($rootNamespace)
	{
		return $rootNamespace.'\Http\Controllers';
	}

	/**
	 * Get the Repository namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getRepositoryNamespace($rootNamespace)
	{
		return $rootNamespace.'\Repository';
	}

	/**
	 * Get the Service namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getServiceNamespace($rootNamespace)
	{
		return $rootNamespace.'\Service';
	}

	/**
	 * Get the Model namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getModelNamespace($rootNamespace)
	{
		return $rootNamespace.'\Models';
	}

	/**
	 * Parse raw data.
	 *
	 * @param  string  $raw
	 *
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
	 * @return string
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
	 */
	private function parseCtrlIntro($introRaw)
	{
		return [];
	}

	/**
	 * 解析出 服务 相关列表
	 * @param $introRaw
	 * @return array
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
	 */
	protected function setModelTableRelation($parsedData)
	{
		$this->modelTableRelation = $parsedData;
	}

	protected function getModelTableRelation()
	{
		return $this->modelTableRelation;
	}

}
