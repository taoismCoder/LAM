<?php
/**
 * 引擎处理基类
 */

namespace App\Helpers\LAM;


class CommonParser {

	public function __construct(){}

	/**
	 * @return \Illuminate\Foundation\Application|\App\Helpers\LAM\TableEachFieldParser
	 */
	protected function getTableEachFieldParse()
	{
		return app('App\Helpers\LAM\TableEachFieldParser');
	}

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