<?php namespace App\Helpers\LAM\Maker;
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
	 * 设置由解析器解析的数组
	 * @param $parsed
	 * @return self
	 */
	public function setParsed($parsed);

    /**
     * 获取解析器解析过的数组
     * @return array
     */
    public function getParsed();

    /**
     * 开始生成文件
     * @return bool
     */
	public function makeFile();
}
