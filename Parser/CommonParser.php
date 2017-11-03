<?php
namespace App\Helpers\LAM\Parser;

use Taoism\LAM\Parser\BaseParser;
/**
 * Class CommonParser
 * 解析器通用方法
 * @package  App\Helpers\LAM\Parser
 * @author   Vicleos <510331882@qq.com> https://github.com/taoismCoder/LAM
 */
class CommonParser implements BaseParser {

	public function __construct(){}

	/**
	 * 清除制表符
	 * @param $raw
	 * @param string $custom
	 * @return mixed
	 */
	protected function clearTabs($raw, $custom = '\t\r\n\s')
	{
		$raw = preg_replace('/['.$custom.']/', '', $raw);
		return $raw;
	}

	/**
	 * 下划线命名法转驼峰命名法
	 * @param $str
	 * @return mixed
	 */
	protected function UnderlineToCamelCase($str)
	{
		// 去除空格(单词首字母大写(将下划线替换为空格))
		return preg_replace('# #', '', ucwords(str_replace('_', ' ', $str)));
	}

	/**
	 * 获取文件内容
	 * @param $path
	 * @return string
	 */
	protected function getFileContents($path)
	{
		return file_get_contents($path);
	}

	/**
	 * Write the contents of a file.
	 *
	 * @param  string  $path
	 * @param  string  $contents
	 * @param  bool  $lock
	 * @return int
	 */
	protected function put($path, $contents, $lock = false)
	{
		// 如果直接使用put方法创建文件，为了防止意外覆盖旧文件，则先备份旧文件
		if(file_exists($path)){
			copy($path, $path.'_'.date('Y_m_d_H_i_s', time()));
		}
		return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
	}

	/**
	 * 追加方法函数到文件内容中
	 * @param $path
	 * @param array $funcArr
	 * @param $contents
	 * @param $funcStubContent
	 * @param bool $isBackup
	 * @return string
	 */
	protected function appendFuncToFileContent($path, $funcArr = [], $contents, $funcStubContent, $isBackup = true)
	{
		$insertContents = '';
		foreach ($funcArr as $_action){
			list($_funcName, $_viewName) = explode('@', $_action);
			// 如果为首页方法名，则 view 名称增加默认 .index
			if($_funcName == 'index'){
				$_viewName .= '.index';
			}
			$insertContents .= str_replace(['FunctionName', 'ViewName'], [$_funcName, $_viewName], $funcStubContent);
		}
		$finalContents = str_replace('//', $insertContents, $contents);
		if ($isBackup){
			return $this->put($path, $finalContents);
		}else{
			return file_put_contents($path, $finalContents);
		}
	}

	/**
	 * 指定位置插入字符串
	 * @param string $str 原字符串
	 * @param int $i    插入位置
	 * @param string $insertStr 插入字符串
	 * @return string 处理后的字符串
	 */
	function insertToStr($str, $i, $insertStr){
		//TODO
		return $str;
	}

	/**
	 * 根据类型和需要生成的模版类型获取模版路径
	 * @param $type
	 * @param string $stubType
	 * @return string
	 */
	protected function getStub($type, $stubType = 'plain')
	{
		return __DIR__ . '/stubs/' .$type.'.'.$stubType.'.stub';
	}

	/**
	 * 根据正则匹配对应的结果
	 * @param string $parseType
	 * @param $pattern
	 * @param $raw
	 * @return mixed
	 */
	protected function pregRaw($parseType = 'preg', $pattern, $raw)
	{
		$parseRst = [];

		if ($parseType == 'preg'){
			preg_match_all($pattern, $raw, $parseRst);
			array_shift($parseRst);
		}elseif($parseType == 'explode'){
			$raw = $this->clearTabs($raw);
			$parseRst = explode($pattern, $raw);
		}

		return $parseRst;
	}

	/**
	 * 获取文件的文件夹路径
	 * @param $realPath
	 * @return string
	 */
	protected function getPathDir($realPath)
	{
		return mb_substr($realPath, 0, strripos($realPath, '/'));
	}

}