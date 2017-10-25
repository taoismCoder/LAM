<?php
/**
 * 引擎处理基类
 */

namespace App\Helpers\LAM;


class CommonParser {

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

}