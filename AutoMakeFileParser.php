<?php namespace App\Helpers;

use Artisan;
use EasyWeChat\Core\Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
/**
 * Class     AutoMakeFileParser
 *
 * @package  App\Helpers
 * @author   Vicleos <510331882@qq.com>
 */
class AutoMakeFileParser
{
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
	 * 匹配类型为 explode
	 */
	const TYPE_EXPLODE = 'explode';

	/**
	 * 匹配类型为正则
	 */
	const TYPE_PREG = 'preg';

	/* ------------------------------------------------------------------------------------------------
	 |  Main Functions
	 | ------------------------------------------------------------------------------------------------
	 */
	/**
	 * Parse file content.
	 *
	 * @param  string  $raw
	 *
	 * @return mixed
	 */
	public function parse($raw)
	{
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
		if($type == 'route'){
			return $this->makeRoute($intro);
		}

		if($type == 'srv'){
			return $this->makeService($intro);
		}

		if($type == 'srv'){
			return $this->makeRepository($intro);
		}
	}

	/* ------------------------------------------------------------------------------------------------
	 |  Other Functions
	 | ------------------------------------------------------------------------------------------------
	 */

	/**
	 * 生成路由及控制器
	 * @todo 生成路由暂时忽略
	 * @param $intro
	 * @return bool
	 */
	private function makeRoute($intro)
	{
		$type = 'ctrl';
		$needMakeControllers = array_unique(array_column($intro, 'controller'));
		foreach ($needMakeControllers as $filePathName){
			$fileIsExists = $this->alreadyExists($filePathName, $type);
			if(!$fileIsExists){
				Artisan::call('make:controller', ['name' => $filePathName]);
				echo $filePathName.' '.Artisan::output().'<br/>';
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
		$needMakeControllers = $intro;
		foreach ($needMakeControllers as $filePathName){
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
	 * 替换模版中的标签
	 * @param $filePath
	 * @param $replaceArr
	 * @return bool
	 */
	protected function replaceStubTags($filePath, $replaceArr)
	{
		if(empty($replaceArr)){
			return false;
		}
		$fileContents = file_get_contents($filePath);
		return str_replace(
			array_keys($replaceArr), array_values($replaceArr), $fileContents
		);
	}

	/**
	 * 获取文件的命名空间
	 * @param $rawFileName
	 * @return string
	 */
	protected function getFileNamespace($rawFileName)
	{
		$rootNamespace = trim(app()->getNamespace(), '\\');
		$serviceRootNamespace = $this->getServiceNamespace($rootNamespace);
		return $serviceRootNamespace.'\\'.str_replace('/'.class_basename($rawFileName), '', $rawFileName);
	}

	/**
	 * 根据类型和需要生成的模版类型获取模版路径
	 * @param $type
	 * @param string $stubType
	 * @return string
	 */
	protected function getStub($type, $stubType = 'plain')
	{
		return __DIR__.'/stubs/'.$type.'.'.$stubType.'.stub';
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

		switch ($type){
			case 'ctrl':
				$rootNamespace = $this->getControllersNamespace($rootNamespace);
				break;
			case 'res':
				$rootNamespace = $this->getRepositoryNamespace($rootNamespace);
				break;
			case 'serv':
				$rootNamespace = $this->getServiceNamespace($rootNamespace);
				break;
		}

		return $this->parseName($rootNamespace.'\\'.$name, $type);
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
				return self::parseRouteIntro($introRaw);
				break;
			case 'ctrl':
				return self::parseCtrlIntro($introRaw);
				break;
			case 'res':
				return self::parseResIntro($introRaw);
				break;
			case 'srv':
				return self::parseSevIntro($introRaw);
				break;
			case 'table':
				return self::parseTableIntro($introRaw);
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

			$parseRoutes[] = [
				'url' => $routeUrl,
				'controller' => $ctrl,
				'action' => $action,
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
	private function parseSevIntro($introRaw)
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
			$parseRow = $this->pregRaw(self::TYPE_EXPLODE, ':', $row);
			$tableName = $parseRow[0];
			$fields = array_filter($this->pregRaw(self::TYPE_EXPLODE, '->', $parseRow[1]));
			//下划线命名法转驼峰命名法
			$model = $this->UnderlineToCamelCase($tableName);
			$rst[] = [
				'name' => $tableName,
				'fields' => $fields,
				'model' => $model
			];
		}
		return $rst;
	}

	/**
	 * 根据正则匹配对应的结果
	 * @param string $parseType
	 * @param $pattern
	 * @param $raw
	 * @return mixed
	 */
	private function pregRaw($parseType = self::TYPE_PREG, $pattern, $raw)
	{
		$parseRst = [];

		if ($parseType == self::TYPE_PREG){
			preg_match_all($pattern, $raw, $parseRst);
			array_shift($parseRst);
		}elseif($parseType == self::TYPE_EXPLODE){
			$raw = $this->clearTabs($raw);
			$parseRst = explode($pattern, $raw);
		}

		return $parseRst;
	}

	/**
	 * 清除制表符
	 * @param $raw
	 * @return mixed
	 */
	private function clearTabs($raw)
	{
		$raw = preg_replace('/[\t\r\n\s]/', '', $raw);
		return $raw;
	}

	/**
	 * 下划线命名法转驼峰命名法
	 * @param $str
	 * @return mixed
	 */
	private function UnderlineToCamelCase($str)
	{
		// 去除空格(单词首字母大写(将下划线替换为空格))
		return preg_replace('# #', '', ucwords(str_replace('_', ' ', $str)));
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string  $contents
	 * @param  bool  $lock
	 * @return int
	 */
	public function put($path, $contents, $lock = false)
	{
		// 如果直接使用put方法创建文件，为了防止意外覆盖旧文件，则先备份旧文件
		if(file_exists($path)){
			copy($path, $path.'_'.date('Y_m_d_H_i_s', time()));
		}
		return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
	}

}
