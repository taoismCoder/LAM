<?php
namespace Taoism\LAM\Maker;
/**
 * 生成器基础接口
 * Interface BaseMaker
 * @package Taoism\LAM\Maker
 */
interface BaseMaker {
    	/**
	 * 获取模板文件路径
	 * @return mixed
	 */
	public function getStub();
	
	/**
	 * 设置模板文件路径
	 * @param $path
	 * @return mixed
	 */
	public function setStub($path);
	
	/**
	 * 获取原文
	 * @return mixed
	 */
	public function getRawContents();
	
	/**
	 * 设置原文
	 * @param $raw
	 * @return mixed
	 */
	public function setRawContents($raw);
}
